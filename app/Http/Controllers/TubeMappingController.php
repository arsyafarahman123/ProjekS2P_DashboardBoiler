<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerImage;
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

        $creepByTubeNumber = $tubes->mapWithKeys(function ($t) {
            preg_match('/(\d+)$/', $t->tube_id, $m);

            return $m ? [(int) $m[1] => $t->creep_pct] : [];
        });

        // ===================================================================
        // DATA TITIK UKUR A,B,C,D + STATUS + WARNA — SEMUA dari perhitungan
        // formula: (nilai − min_allowable) / (awal − min_allowable) × 100%
        //
        // Status per tube ditentukan oleh TITIK TERENDAH (MIN) dari keempat
        // titik A,B,C,D. Threshold:
        //   ≥75% → SAFE (hijau)
        //   70–74.99% → WARNING (kuning)
        //   <70% → CRITICAL (merah)
        //
        // Ini menentukan: warna grid, status popup, dan STATUS kolom di
        // tabel Ketebalan per Titik. Semua pakai aturan yang sama.
        // ===================================================================
        $pointData = $this->pointDataForSection($unit, $section);

        // peta [tube_number => 'Safe'|'Warning'|'Critical'] — warna grid kotak
        $statusByTubeNumber = collect($pointData)->mapWithKeys(fn ($d, $no) => [
            (int) $no => $d['status'],
        ]);

        // peta [tube_number => [pct => ..., status => ..., points => ..., avg_mm => ...]]
        // dipakai popup (REMAINING %) dan tabel per-titik
        $pointMeasurements = $pointData;

        // Summary legenda: Safe% / Warning% / Critical%
        $total = max(count($pointData), 1);
        $safeCount = collect($pointData)->where('status', 'Safe')->count();
        $warningCount = collect($pointData)->where('status', 'Warning')->count();
        $criticalCount = collect($pointData)->where('status', 'Critical')->count();
        $summary = [
            'safe_pct' => round($safeCount / $total * 100),
            'watch_pct' => round($warningCount / $total * 100),
            'critical_pct' => round($criticalCount / $total * 100),
        ];

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
        // (2021-2025), dihitung langsung dari data dummy excel/CSV.
        $tubeThicknessStats = $this->thicknessStatsForSection($unit, $section);

        // Tabel per-titik A-D dipakai di view sebagai $pointsTable
        $pointsTable = $pointData;
        $pointNames = TubeMeasurement::POINTS;

        $sectionCode = BoilerTube::SECTION_CODES[$section] ?? strtoupper(substr($section, 0, 3));

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

        $boilerImages = BoilerImage::where('unit', $unit)->orderByDesc('created_at')->get();

        return view('tube-mapping.index', compact(
            'pshPoints', 'pshTotal', 'pshPointNames', 'summary', 'topPriority',
            'historicalNdt', 'creepTrend', 'units', 'sections', 'years',
            'unit', 'section', 'year', 'statusByTubeNumber', 'tubeThicknessStats', 'creepByTubeNumber', 'sectionCode',
            'pointsTable', 'pointNames', 'boilerImages', 'pointMeasurements'
        ));
    }

    /**
     * Data lengkap per tube: nilai A,B,C,D (mm & persen), rata-rata mm,
     * persentase sisa umur, dan STATUS.
     *
     * Formula per titik:
     *   % = (nilai_titik − min_allowable) / (awal − min_allowable) × 100%
     *
     * Formula REM./REMAINING (rata-rata):
     *   % = (avg(A,B,C,D) − min_allowable) / (awal − min_allowable) × 100%
     *
     * Status tube = MIN dari keempat titik:
     *   ≥75% → Safe (hijau)
     *   70–74.99% → Warning (kuning)
     *   <70% → Critical (merah)
     *
     * @return array<int, array{points:array<string,float|null>, pct:array<string,float|null>, avg_mm:float|null, remaining_pct:float|null, status:string}>
     */
    private function pointDataForSection(string $unit, string $section): array
    {
        $pointNames = TubeMeasurement::POINTS;

        $baselines = TubeBaseline::where('unit', $unit)
            ->where('section', $section)
            ->pluck('initial_thickness_mm', 'tube_number');

        $minAllowables = $this->minAllowableFromCsv($unit, $section);

        $measurements = TubeMeasurement::where('unit', $unit)
            ->where('section', $section)
            ->get()
            ->groupBy('tube_number');

        $result = [];
        foreach ($measurements as $tubeNumber => $rows) {
            $baseline = $baselines[$tubeNumber] ?? null;
            $minAllowable = $minAllowables[$tubeNumber] ?? null;

            $pointsMm = [];   // nilai mm asli
            $pointsPct = [];  // nilai persen per titik
            foreach ($pointNames as $p) {
                $row = $rows->firstWhere('point', $p);
                $mm = $row ? round($row->thickness_mm, 2) : null;
                $pointsMm[$p] = $mm;

                // % per titik: (nilai − min_allowable) / (awal − min_allowable)
                if ($mm !== null && $baseline && $minAllowable && $baseline > $minAllowable) {
                    $pctVal = round(($mm - $minAllowable) / ($baseline - $minAllowable) * 100, 2);
                    $pointsPct[$p] = max(0, min(100, $pctVal));
                } else {
                    $pointsPct[$p] = null;
                }
            }

            // Rata-rata mm
            $validMm = array_filter($pointsMm, fn ($v) => $v !== null);
            $avgMm = $validMm ? round(array_sum($validMm) / count($validMm), 2) : null;

            // REM./REMAINING % = (avg_mm − min_allowable) / (awal − min_allowable)
            $remainingPct = null;
            if ($avgMm && $baseline && $minAllowable && $baseline > $minAllowable) {
                $remainingPct = round(($avgMm - $minAllowable) / ($baseline - $minAllowable) * 100, 2);
                $remainingPct = max(0, min(100, $remainingPct));
            }

            // Status ditentukan dari TITIK TERENDAH (MIN dari keempat % titik)
            $validPct = array_filter($pointsPct, fn ($v) => $v !== null);
            $minPct = $validPct ? min($validPct) : null;
            $status = match (true) {
                $minPct === null => 'N/A',
                $minPct < 70 => 'Critical',
                $minPct < 75 => 'Warning',
                default => 'Safe',
            };

            $result[(int) $tubeNumber] = [
                'points' => $pointsMm,
                'pct' => $pointsPct,         // % per titik — dipakai view tabel
                'avg_mm' => $avgMm,
                'remaining_pct' => $remainingPct, // % REM./REMAINING — dipakai popup
                'status' => $status,                   // Safe/Warning/Critical — GRID + STATUS kolom
            ];
        }

        return $result;
    }

    /**
     * Baca database/seeders/data/tube_dummy_2021_2025.csv dan hitung
     * MIN / MAX / AVG "Wall Thickness Terukur" per nomor tube untuk
     * satu unit+section, dari seluruh tahun yang ada.
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
        $baselineByTube = [];

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
            $baselineByTube[$tubeNumber] = (float) $data['initial_thickness'];
        }
        fclose($handle);

        $stats = [];
        foreach ($rowsByTube as $tubeNumber => $byYear) {
            ksort($byYear);
            $values = array_values($byYear);
            $minAllowable = $minAllowableByTube[$tubeNumber];
            $baseline = $baselineByTube[$tubeNumber];
            $rangeMm = $baseline - $minAllowable;

            $minMm = min($values);
            $maxMm = max($values);

            // % pakai rumus & threshold YANG SAMA persis dengan status tube
            // (remaining%: (nilai - min_allowable) / (awal - min_allowable) * 100),
            // supaya warna MIN/MAX di popup selalu konsisten dengan STATUS.
            $minPct = $rangeMm > 0 ? max(0, min(100, round(($minMm - $minAllowable) / $rangeMm * 100, 2))) : null;
            $maxPct = $rangeMm > 0 ? max(0, min(100, round(($maxMm - $minAllowable) / $rangeMm * 100, 2))) : null;

            $stats[$tubeNumber] = [
                'min' => round($minMm, 2),
                'max' => round($maxMm, 2),
                'avg' => round(array_sum($values) / count($values), 2),
                'years' => count($values),
                'by_year' => $byYear,
                'min_allowable' => round($minAllowable, 2),
                'baseline' => round($baseline, 2),
                'min_pct' => $minPct,
                'max_pct' => $maxPct,
            ];
        }

        return $stats;
    }

    /**
     * Baca kolom Minimum Allowable Thickness dari CSV per nomor tube
     * untuk satu unit+section.
     */
    private function minAllowableFromCsv(string $unit, string $section): array
    {
        $csvPath = database_path('seeders/data/tube_dummy_2021_2025.csv');
        if (! file_exists($csvPath)) {
            return [];
        }

        $unitCsvLabel = strtoupper($unit);
        $result = [];

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (strtoupper($data['unit']) !== $unitCsvLabel || $data['section'] !== $section) {
                continue;
            }
            $tn = (int) $data['tube_number'];
            if (! isset($result[$tn])) {
                $result[$tn] = (float) $data['min_allowable'];
            }
        }
        fclose($handle);

        return $result;
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