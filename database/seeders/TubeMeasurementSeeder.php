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
// 3. Isi tube_measurements untuk SEMUA tahun (2021–2025), bukan cuma
//    tahun terakhir — supaya grid Tube Mapping berubah tiap ganti tahun
//    di dropdown dan warnanya ngikut data dummy pertahun.
class TubeMeasurementSeeder extends Seeder
{
    // Semua tahun yang ada di CSV (2021–2025) akan di-seed sekaligus,
    // supaya tiap tahun menghasilkan warna grid & tabel titik A-D yang
    // berbeda-beda (bukan statis cuma data 2025 saja).
    private const MEASUREMENT_YEARS = [2021, 2022, 2023, 2024, 2025];

    public function run(): void
    {
        $csvPath = database_path('seeders/data/tube_dummy_2021_2025.csv');

        if (! file_exists($csvPath)) {
            $this->command?->error("File CSV tidak ditemukan: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle); // buang baris header

        // "unit|section|tube_number" => ['baseline'=>.., 'min_allowable'=>.., 'years'=>[year=>row]]
        $rowsByKey = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            // Normalisasi "UNIT 3A" (dari excel) -> "Unit 3A"
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
            $totalMeasurementRows = 0;

            foreach ($rowsByKey as $key => $info) {
                [$unit, $section, $tubeNumber] = explode('|', $key);
                $tubeNumber = (int) $tubeNumber;

                // Susunan titik ukur ikut konfigurasi area (default A-D)
                $area = BoilerArea::where('unit', $unit)->where('name', $section)->first();
                $points = $area?->pointList() ?? TubeMeasurement::POINTS;
                $pointCount = count($points);

                // 1) Baseline (satu per tube, tidak per tahun)
                $baselineRows[] = [
                    'unit' => $unit,
                    'section' => $section,
                    'tube_number' => $tubeNumber,
                    'initial_thickness_mm' => $info['baseline'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // 2) Titik ukur untuk SETIAP TAHUN yang ada di CSV
                //    (2021, 2022, 2023, 2024, 2025) — bukan cuma 2025.
                //    Seed unik per-tahun pakai (tubeNumber + year + point)
                //    supaya variasi per titik berbeda antar tahun, tidak
                //    terlihat copas dari tahun ke tahun.
                foreach (self::MEASUREMENT_YEARS as $year) {
                    $yearRow = $info['years'][$year] ?? null;
                    if (! $yearRow) {
                        continue;
                    }

                    $avgTarget = (float) $yearRow['measured_thickness'];
                    $seed = "{$unit}{$section}{$tubeNumber}_{$year}";
                    $values = $this->generatePointValues($avgTarget, $pointCount, $seed);

                    foreach ($points as $i => $point) {
                        $measurementRows[] = [
                            'unit' => $unit,
                            'section' => $section,
                            'tube_number' => $tubeNumber,
                            'point' => $point,
                            'thickness_mm' => round($values[$i], 2),
                            'measured_at' => Carbon::parse($yearRow['inspected_at'])->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $totalMeasurementRows++;
                    }
                }

                // Insert per-batch biar query nggak overflow
                if (count($baselineRows) >= 500) {
                    TubeBaseline::insert($baselineRows);
                    $baselineRows = [];
                }
                if (count($measurementRows) >= 1000) {
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

            $this->command?->info("Total tube_measurements rows: {$totalMeasurementRows}");
        });

        $this->command?->info('Selesai: tube_baselines & tube_measurements (2021–2025) sudah diisi dari data excel.');
    }

    // Bikin N nilai di sekitar $avgTarget dengan variasi kecil, tapi
    // rata-ratanya dipaksa balik persis ke $avgTarget (titik terakhir
    // dikoreksi supaya AVG match). $seed dipakai biar hasilnya konsisten
    // tiap kali seeder dijalankan ulang (bukan random murni).
    private function generatePointValues(float $avgTarget, int $count, string $seed): array
    {
        mt_srand(crc32($seed));

        $values = [];
        $spread = 0.15; // variasi maksimum tiap titik, dalam mm
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
}