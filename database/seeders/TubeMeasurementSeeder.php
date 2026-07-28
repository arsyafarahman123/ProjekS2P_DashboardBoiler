<?php

namespace Database\Seeders;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
use App\Models\TubeBaseline;
use App\Models\TubeMeasurement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Import data dummy dari file excel (yang sudah di-convert ke CSV) lalu:
// 1. Isi tube_baselines dari kolom "Wall Thickness Awal"
// 2. Pecah kolom "Wall Thickness Terukur" (1 angka) jadi N titik ukur
//    (A, B, C, ... sesuai konfigurasi area) dengan variasi kecil di
//    sekitar angka itu, sehingga AVG dari titik-titik itu balik ke
//    angka excel aslinya.
// 3. Hitung ulang status Safe/Watch/Critical dari titik PALING TIPIS
//    (MIN), bukan dari rata-rata — karena titik paling tipis yang
//    paling menentukan risiko sebenarnya.
// 4. Update boiler_tubes (creep_pct, status) supaya Global View &
//    grid warna Tube Mapping ikut konsisten.
class TubeMeasurementSeeder extends Seeder
{
    // Tahun yang dipakai buat isi titik ukur per-pipa (tube_measurements
    // cuma nyimpen snapshot TERBARU per titik, jadi kita pakai tahun
    // paling akhir di data biar konsisten sama grid Tube Mapping).
    private const MEASUREMENT_YEAR = 2025;

    public function run(): void
    {
        $csvPath = database_path('seeders/data/tube_dummy_2021_2025.csv');

        if (! file_exists($csvPath)) {
            $this->command?->error("File CSV tidak ditemukan: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle); // buang baris header

        $rowsByKey = []; // "unit|section|tube_number" => ['baseline'=>.., 'years'=>[year=>row]]

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            // Normalisasi "UNIT 3A" (dari excel) -> "Unit 3A" (dipakai konsisten
            // di seluruh project, termasuk BoilerTube::DEFAULT_UNIT). Tanpa ini,
            // data ke-simpan dengan casing beda dan dianggap unit yang lain.
            $data['unit'] = BoilerTube::DEFAULT_UNIT;
            $key = $data['unit'].'|'.$data['section'].'|'.$data['tube_number'];
            $rowsByKey[$key]['baseline'] = (float) $data['initial_thickness'];
            $rowsByKey[$key]['min_allowable'] = (float) $data['min_allowable'];
            $rowsByKey[$key]['years'][(int) $data['year']] = $data;
        }
        fclose($handle);

        $this->command?->info('Total tube unik ditemukan: '.count($rowsByKey));

        DB::transaction(function () use ($rowsByKey) {
            TubeBaseline::truncate();
            TubeMeasurement::truncate();

            $baselineRows = [];
            $measurementRows = [];
            $boilerTubeUpdates = [];

            foreach ($rowsByKey as $key => $info) {
                [$unit, $section, $tubeNumber] = explode('|', $key);
                $tubeNumber = (int) $tubeNumber;

                // Susunan titik ukur ikut konfigurasi area (default A-D,
                // bisa berubah kalau admin nambah/kurang titik lewat menu Add Area).
                $area = BoilerArea::where('unit', $unit)->where('name', $section)->first();
                $points = $area?->pointList() ?? TubeMeasurement::POINTS;

                // 1) Baseline
                $baselineRows[] = [
                    'unit' => $unit,
                    'section' => $section,
                    'tube_number' => $tubeNumber,
                    'initial_thickness_mm' => $info['baseline'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // 2) Titik ukur, digenerate dari nilai tahun MEASUREMENT_YEAR
                $yearRow = $info['years'][self::MEASUREMENT_YEAR] ?? null;
                if ($yearRow) {
                    $avgTarget = (float) $yearRow['measured_thickness'];
                    $values = $this->generatePointValues($avgTarget, count($points), $unit.$section.$tubeNumber);

                    foreach ($points as $i => $point) {
                        $measurementRows[] = [
                            'unit' => $unit,
                            'section' => $section,
                            'tube_number' => $tubeNumber,
                            'point' => $point,
                            'thickness_mm' => round($values[$i], 2),
                            'measured_at' => $yearRow['inspected_at'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $min = min($values);
                    $baseline = $info['baseline'];
                    $minAllowable = $info['min_allowable'];

                    $creepPct = $baseline > $minAllowable
                        ? max(0, min(100, ($baseline - $min) / ($baseline - $minAllowable) * 100))
                        : 0;
                    $status = $this->statusFromCreep($creepPct);

                    $boilerTubeUpdates[] = [
                        'unit' => $unit,
                        'section' => $section,
                        'tube_number' => $tubeNumber,
                        'year' => self::MEASUREMENT_YEAR,
                        'creep_pct' => round($creepPct, 2),
                        'status' => $status,
                    ];
                }

                // Insert per-batch
                if (count($baselineRows) >= 500) {
                    TubeBaseline::insert($baselineRows);
                    $baselineRows = [];
                }
                if (count($measurementRows) >= 500) {
                    TubeMeasurement::insert($measurementRows);
                    $measurementRows = [];
                }
            }

            if (! empty($baselineRows)) {
                TubeBaseline::insert($baselineRows);
            }
            if (! empty($measurementRows)) {
                TubeMeasurement::insert($measurementRows);
            }

            // Catatan: boiler_tubes (creep_pct/status per tahun) TIDAK lagi
            // ditimpa di sini. BoilerTubeSeeder sekarang mengisi boiler_tubes
            // langsung dari kolom Creep Percentage & Status di excel/CSV untuk
            // semua tahun (2021–2025) dan semua 200 tube/section, supaya grid
            // Tube Mapping selalu sinkron 1:1 dengan Global View. Titik ukur
            // (tube_measurements) di sini murni buat detail MIN/MAX/AVG per
            // titik saat sebuah tube di-klik di grid.
        });

        $this->command?->info('Selesai: tube_baselines, tube_measurements, dan boiler_tubes (tahun 2025) sudah di-update dari data excel.');
    }

    // Bikin N nilai di sekitar $avgTarget dengan variasi kecil, tapi
    // rata-ratanya dipaksa balik persis ke $avgTarget (titik terakhir
    // dikoreksi supaya AVG match). $seed dipakai biar hasilnya konsisten
    // tiap kali seeder dijalankan ulang (bukan random murni).
    private function generatePointValues(float $avgTarget, int $count, string $seed): array
    {
        mt_srand(crc32($seed));

        $values = [];
        $spread = 0.02; // variasi maksimum tiap titik, dalam mm (dikecilkan dari 0.15
                         // supaya status MIN-dari-4-titik tidak "kelempar" keluar dari
                         // band Safe/Warning/Critical yang seharusnya cuma karena noise acak)
        for ($i = 0; $i < $count - 1; $i++) {
            $offset = (mt_rand(-100, 100) / 100) * $spread;
            $values[] = $avgTarget + $offset;
        }

        // Titik terakhir dikoreksi supaya rata-rata semua titik = avgTarget persis
        $sumSoFar = array_sum($values);
        $lastValue = ($avgTarget * $count) - $sumSoFar;
        $values[] = $lastValue;

        mt_srand(); // reset seed global

        return $values;
    }

    private function statusFromCreep(float $creepPct): string
    {
        return match (true) {
            $creepPct > 80 => 'Critical',
            $creepPct >= 40 => 'Warning',
            default => 'Safe',
        };
    }
}