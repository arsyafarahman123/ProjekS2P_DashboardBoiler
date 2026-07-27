<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
use App\Models\TubeBaseline;
use App\Models\TubeMeasurement;
use Illuminate\Http\Request;

class TubeMappingController extends Controller
{
    public function index(Request $request)
    {
        // Unit & tahun mengikuti data model dashboard (BoilerTube),
        // supaya konsisten dengan Global View.
        $units = BoilerTube::UNITS;
        $years = BoilerTube::YEARS;
        rsort($years);

        $unit = $request->get('unit', BoilerTube::DEFAULT_UNIT);
        if (! in_array($unit, $units, true)) {
            $unit = BoilerTube::DEFAULT_UNIT;
        }

        $year = (int) $request->get('year', max(BoilerTube::YEARS));
        if (! in_array($year, $years, true)) {
            $year = max(BoilerTube::YEARS);
        }

        // Daftar Boiler Section disamakan dengan Risk Summary by Section di
        // Global View: diambil dari BoilerArea per unit (termasuk area
        // tambahan yang dibuat admin lewat Add Area).
        $sections = BoilerArea::where('unit', $unit)->orderBy('id')->pluck('name')->all();

        $section = $request->get('section', 'Primary Superheater');
        if (! in_array($section, $sections, true)) {
            $section = in_array('Primary Superheater', $sections, true)
                ? 'Primary Superheater'
                : ($sections[0] ?? 'Primary Superheater');
        }

        $tubes = BoilerTube::query()
            ->where('unit', $unit)
            ->where('section', $section)
            ->where('year', $year)
            ->get();

        // Peta nomor tube -> status (buat warnain kotak grid). tube_id
        // formatnya "KODE-U3A-01", jadi angka di belakang diambil sebagai
        // nomor tube.
        $statusByTubeNumber = $tubes->mapWithKeys(function ($t) {
            preg_match('/(\d+)$/', $t->tube_id, $m);

            return $m ? [(int) $m[1] => $t->status] : [];
        });

        $creepByTubeNumber = $tubes->mapWithKeys(function ($t) {
            preg_match('/(\d+)$/', $t->tube_id, $m);

            return $m ? [(int) $m[1] => $t->creep_pct] : [];
        });

        // Grid Primary Superheater Unit 3A: jumlah slot & susunan titik ukur
        // mengikuti pengaturan area (menu admin Input Data).
        $pshArea = BoilerArea::where('unit', BoilerTube::DEFAULT_UNIT)
            ->where('name', 'Primary Superheater')
            ->first();
        $pshTotal = $pshArea?->tube_count ?: 200;
        $pshPointNames = $pshArea?->pointList() ?? TubeMeasurement::POINTS;
        $pshPoints = TubeMeasurement::query()
            ->where('unit', BoilerTube::DEFAULT_UNIT)
            ->where('section', 'Primary Superheater')
            ->get()
            ->groupBy('tube_number')
            ->map(fn ($rows) => $rows->keyBy('point'));

        // Statistik MIN/MAX/AVG ketebalan dinding tube selama 5 tahun
        // (2021-2025), dihitung langsung dari data dummy excel/CSV yang
        // sama dipakai seeder — supaya angka di popup tube-mapping selalu
        // sinkron dengan data dummy Unit 3A 2021-2025 aslinya.
        $tubeThicknessStats = $this->thicknessStatsForSection($unit, $section);

        // Tabel titik ukur A-D per tube (persen ketebalan sisa terhadap
        // baseline). 100-75% = SAFE, 75-70% = WARNING, <70% = CRITICAL.
        // Satu titik di bawah 70% sudah cukup bikin tube itu CRITICAL,
        // walau titik lain masih tinggi (MIN yang menentukan risiko).
        $pointsTable = $this->pointsTableForSection($unit, $section);
        $pointNames = TubeMeasurement::POINTS;

        $sectionCode = BoilerTube::SECTION_CODES[$section] ?? strtoupper(substr($section, 0, 3));

        $total = $tubes->count();
        $summary = [
            'safe_pct' => $total ? round($tubes->where('status', 'Safe')->count() / $total * 100) : 0,
            'watch_pct' => $total ? round($tubes->where('status', 'Watch')->count() / $total * 100) : 0,
            'critical_pct' => $total ? round($tubes->where('status', 'Critical')->count() / $total * 100) : 0,
        ];

        $topPriority = BoilerTube::query()
            ->where('year', $year)
            ->orderByDesc('creep_pct')
            ->orderBy('remaining_life_months')
            ->limit(5)
            ->get()
            ->map(function ($t, $i) {
                $risk = round(($t->creep_pct * 0.7) + ((60 - min($t->remaining_life_months, 60)) * 0.5), 1);

                return [
                    'rank' => $i + 1,
                    'tube_id' => $t->tube_id,
                    'unit' => $t->unit,
                    'risk' => $risk,
                ];
            });

        $historicalNdt = BoilerTube::query()
            ->where('unit', $unit)
            ->where('section', $section)
            ->orderByDesc('scan_date')
            ->limit(6)
            ->get();

        // Tren creep rata-rata section per tahun
        $creepTrend = BoilerTube::query()
            ->where('unit', $unit)
            ->where('section', $section)
            ->selectRaw('year, ROUND(AVG(creep_pct), 1) as creep_pct')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        return view('tube-mapping.index', compact(
            'pshPoints', 'pshTotal', 'pshPointNames', 'summary', 'topPriority',
            'historicalNdt', 'creepTrend', 'units', 'sections', 'years',
            'unit', 'section', 'year', 'statusByTubeNumber', 'tubeThicknessStats', 'creepByTubeNumber', 'sectionCode',
            'pointsTable', 'pointNames'
        ));
    }

    /**
     * Bangun tabel titik ukur A-D (persen ketebalan sisa vs baseline) per
     * nomor tube untuk section+unit aktif, dari tube_measurements +
     * tube_baselines. Dipakai buat tabel "Jenis Pipa per Titik A-D" di
     * bawah grafik creep, dan buat cari status per-titik (warna merah
     * kalau ada 1 titik < 70%).
     *
     * @return array<int, array{pct: array<string,float|null>, status: string}>
     */
    private function pointsTableForSection(string $unit, string $section): array
    {
        $pointNames = TubeMeasurement::POINTS;

        $baselines = TubeBaseline::where('unit', $unit)
            ->where('section', $section)
            ->pluck('initial_thickness_mm', 'tube_number');

        $measurements = TubeMeasurement::where('unit', $unit)
            ->where('section', $section)
            ->get()
            ->groupBy('tube_number');

        $table = [];
        foreach ($measurements as $tubeNumber => $rows) {
            $baseline = $baselines[$tubeNumber] ?? null;
            $pctByPoint = [];
            foreach ($pointNames as $p) {
                $row = $rows->firstWhere('point', $p);
                $pctByPoint[$p] = ($row && $baseline) ? round($row->thickness_mm / $baseline * 100, 1) : null;
            }

            $validPct = array_filter($pctByPoint, fn ($v) => $v !== null);
            $minPct = $validPct ? min($validPct) : null;
            $status = match (true) {
                $minPct === null => 'unknown',
                $minPct < 70 => 'critical',
                $minPct < 75 => 'warning',
                default => 'safe',
            };

            $table[(int) $tubeNumber] = ['pct' => $pctByPoint, 'status' => $status];
        }

        return $table;
    }

    /**
     * Baca database/seeders/data/tube_dummy_2021_2025.csv (data dummy asli
     * Unit 3A 2021-2025) dan hitung MIN / MAX / AVG "Wall Thickness Terukur"
     * per nomor tube untuk satu unit+section, dari seluruh tahun yang ada.
     *
     * @return array<int, array{min: float, max: float, avg: float, years: int}>
     */
    private function thicknessStatsForSection(string $unit, string $section): array
    {
        $csvPath = database_path('seeders/data/tube_dummy_2021_2025.csv');
        if (! file_exists($csvPath)) {
            return [];
        }

        $unitCsvLabel = strtoupper($unit); // csv nyimpen "UNIT 3A"
        $rowsByTube = [];
        $minAllowableByTube = [];

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (strtoupper($data['unit']) !== $unitCsvLabel || $data['section'] !== $section) {
                continue;
            }
            $tubeNumber = (int) $data['tube_number'];
            $rowsByTube[$tubeNumber][(int) $data['year']] = (float) $data['measured_thickness'];
            $minAllowableByTube[$tubeNumber] = (float) $data['min_allowable'];
        }
        fclose($handle);

        $stats = [];
        foreach ($rowsByTube as $tubeNumber => $byYear) {
            ksort($byYear);
            $values = array_values($byYear);
            $stats[$tubeNumber] = [
                'min' => round(min($values), 2),
                'max' => round(max($values), 2),
                'avg' => round(array_sum($values) / count($values), 2),
                'years' => count($values),
                // Angka asli per tahun (bukan titik A/B/C/D karangan) —
                // dipakai buat tampilin "titik pengukuran" yang jujur di popup.
                'by_year' => $byYear,
                // Batas ketebalan minimum yang masih boleh (dari kolom
                // "Minimum Allowable Thickness" di excel) — acuan aman/kritis.
                'min_allowable' => round($minAllowableByTube[$tubeNumber], 2),
            ];
        }

        return $stats;
    }

    public function show(string $tubeId)
    {
        $tube = BoilerTube::where('tube_id', $tubeId)->orderByDesc('year')->firstOrFail();

        return response()->json([
            'tube_id' => $tube->tube_id,
            'creep_pct' => $tube->creep_pct,
            'remaining_life_months' => $tube->remaining_life_months,
            'status' => $tube->status,
            'recommended_action' => $tube->recommended_action,
            'scan_date' => $tube->scan_date?->format('Y-m-d'),
        ]);
    }
}
