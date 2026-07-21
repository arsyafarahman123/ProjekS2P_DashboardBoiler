<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RlaAnalysisController extends Controller
{
    /**
     * Dropdown options available on the page.
     * NOTE: Unit "1&2" is intentionally ONE option (not "1" and "2" separately).
     */
    protected array $boilerSections = [
        'Economizer',
        'Waterwall',
        'Primary Superheater',
        'Secondary Superheater',
        'Reheater',
    ];

    protected array $units = ['1&2', '3', '3A'];

    protected array $years = [2022, 2023, 2024, 2025, 2026];

    public function index(Request $request)
    {
        $section = $request->query('section', 'Secondary Superheater');
        $unit    = $request->query('unit', '3A');
        $year    = (int) $request->query('year', 2026);

        // Guard against invalid/unknown query values falling through
        if (!in_array($section, $this->boilerSections, true)) {
            $section = $this->boilerSections[0];
        }
        if (!in_array($unit, $this->units, true)) {
            $unit = $this->units[0];
        }
        if (!in_array($year, $this->years, true)) {
            $year = end($this->years);
        }

        $data = $this->generateDummyData($section, $unit, $year);

        return view('rla-analysis.index', [
            'boilerSections' => $this->boilerSections,
            'units'          => $this->units,
            'years'          => $this->years,
            'selectedSection' => $section,
            'selectedUnit'    => $unit,
            'selectedYear'    => $year,
            'data'            => $data,
        ]);
    }

    /**
     * Build a deterministic (seeded) dummy dataset so the same
     * Section + Unit + Tahun combination always returns the same numbers.
     */
    protected function generateDummyData(string $section, string $unit, int $year): array
    {
        $seedKey = $section . '|' . $unit . '|' . $year;
        mt_srand(crc32($seedKey));

        // --- Risk profile: unit "1&2" is the oldest equipment -> higher risk baseline
        $riskBias = match ($unit) {
            '1&2' => 1.25,
            '3'   => 1.0,
            '3A'  => 0.8,
            default => 1.0,
        };
        // Older years -> slightly worse condition (equipment has degraded more by "today")
        $yearBias = 1 + ((2026 - $year) * 0.03);
        $risk = $riskBias * $yearBias;

        // --- Top stat cards ---
        $efficiency = round(max(70, min(97, 92 - ($risk * 6) + mt_rand(-200, 200) / 100)), 1);
        $boilerLoad = mt_rand(70, 98);
        $activeAlerts = min(6, max(0, (int) round(1 + $risk + mt_rand(0, 2))));
        $criticalAlerts = min($activeAlerts, (int) round(max(0, $risk - 0.7) + (mt_rand(0, 100) < 40 ? 1 : 0)));
        $status = $activeAlerts >= 5 ? 'DEGRADED' : 'ACTIVE';

        // --- Tube ID, generated from section + unit ---
        $sectionCode = match ($section) {
            'Economizer'             => 'ECO',
            'Waterwall'              => 'WW',
            'Primary Superheater'    => 'SH-1',
            'Secondary Superheater'  => 'SH-2',
            'Reheater'               => 'RH',
            default                  => 'SH-2',
        };
        $unitCode = str_replace('&', '', $unit); // "1&2" -> "12" for the tube code
        $tubeId = sprintf('%s-U%s-R%d-T%d', $sectionCode, $unitCode, mt_rand(1, 20), mt_rand(1, 30));
        $materials = ['T91', 'T92', 'SA213-T22', 'SA210-A1'];
        $material = $materials[array_rand($materials)];
        $remainingLifeMonths = max(1, (int) round(24 / $risk) - mt_rand(0, 6));

        // --- Remaining Life Prediction chart (quarterly points, 3 years back to 3 years ahead) ---
        $labels = [];
        $selectedTube = [];
        $watchAvg = [];
        $watchTubes = [];
        $thinningRate = [];
        $startYear = $year - 3;
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $points = 20;

        $selectedStart = 160 + mt_rand(-15, 15);
        $watchAvgStart = $selectedStart - mt_rand(5, 15);
        $watchTubesStart = $selectedStart - mt_rand(10, 25);

        // Selected Tube hits 0 sooner when risk is higher
        $selectedZeroAt = max(6, (int) round($points * (1 / $risk) * 0.55));

        for ($i = 0; $i < $points; $i++) {
            $qy = $startYear + intdiv($i, 4);
            $labels[] = $qy . '-' . $quarters[$i % 4];

            $decayNoise = mt_rand(-4, 4);

            $selVal = $i < $selectedZeroAt
                ? max(0, round($selectedStart * (1 - ($i / $selectedZeroAt)) + $decayNoise))
                : 0;
            $selectedTube[] = $selVal;

            $avgVal = max(10, round($watchAvgStart - ($i * ($watchAvgStart / ($points * 1.3))) + $decayNoise));
            $watchAvg[] = $avgVal;

            $tubesVal = max(15, round($watchTubesStart - ($i * ($watchTubesStart / ($points * 1.8))) + $decayNoise));
            $watchTubes[] = $tubesVal;

            $thinningRate[] = round(min(2, ($i / $points) * 2 * $risk) + (mt_rand(-5, 5) / 100), 2);
        }
        $designLimit = 60;

        // --- Risk Mitigation priorities ---
        $priorityPool = [
            'critical' => [
                "Replace Tube ID # {$tubeId} (Selected Tube) at Next Outage.",
                "Isolate and inspect {$section} header welds before restart.",
                "Schedule emergency NDT re-scan on {$tubeId} within 2 weeks.",
            ],
            'high' => [
                "Increase Sootblowing Frequency in {$section}.",
                "Reduce firing rate on Unit {$unit} until next inspection window.",
                "Re-calibrate flow instrumentation on {$section} bank.",
            ],
            'medium' => [
                "Adjust Burner 4 for Flame Distribution optimization.",
                "Review water chemistry logs for Unit {$unit}.",
                "Update thickness survey schedule for {$section}.",
            ],
            'info' => [
                "Conduct specific chemical cleaning on {$section} tubes during next annual shutdown.",
                "Archive current NDT baseline for trend comparison.",
                "Plan spare tube procurement for next major outage.",
            ],
        ];
        $priorities = [
            ['level' => 'PRIORITY 1 (CRITICAL)', 'color' => 'critical', 'text' => $priorityPool['critical'][array_rand($priorityPool['critical'])]],
            ['level' => 'PRIORITY 2', 'color' => 'high', 'text' => $priorityPool['high'][array_rand($priorityPool['high'])]],
            ['level' => 'PRIORITY 3', 'color' => 'medium', 'text' => $priorityPool['medium'][array_rand($priorityPool['medium'])]],
            ['level' => 'PRIORITY 4', 'color' => 'info', 'text' => $priorityPool['info'][array_rand($priorityPool['info'])]],
        ];

        // --- Creep percentage chart (weekly, 7 points, gently increasing) ---
        $creepLabels = [];
        $creepValues = [];
        $creepStart = 15 + ($risk * 8) + mt_rand(0, 5);
        $baseDate = \DateTime::createFromFormat('Y-m-d', "{$year}-01-07");
        for ($i = 0; $i < 7; $i++) {
            $d = clone $baseDate;
            $d->modify("+{$i} week" . ($i === 1 ? '' : 's'));
            $creepLabels[] = $d->format('M j');
            $creepValues[] = round(min(60, $creepStart + ($i * (3 + $risk * 1.5)) + mt_rand(-1, 1)), 1);
        }
        $currentCreep = end($creepValues);

        // --- Historical NDT table ---
        $historicalNdt = [
            ['date' => ($year - 7) . '-06', 'tube_id' => $tubeId, 'creep' => round($currentCreep - mt_rand(3, 8), 1)],
            ['date' => ($year + 2) . '-10', 'tube_id' => $tubeId, 'creep' => $currentCreep],
        ];

        // --- NDT report panel ---
        $findings = ['DETECTED CORROSION / PITTING', 'DETECTED WALL THINNING', 'DETECTED CREEP CAVITATION', 'NO SIGNIFICANT FINDINGS'];
        $finding = $risk > 1 ? $findings[array_rand(array_slice($findings, 0, 3))] : $findings[array_rand($findings)];

        return [
            'status'          => $status,
            'efficiency'      => $efficiency,
            'active_alerts'   => $activeAlerts,
            'critical_alerts' => $criticalAlerts,
            'boiler_load'     => $boilerLoad,
            'tube_id'         => $tubeId,
            'material'        => $material,
            'remaining_life_months' => $remainingLifeMonths,
            'chart' => [
                'labels'        => $labels,
                'selected_tube' => $selectedTube,
                'watch_avg'     => $watchAvg,
                'watch_tubes'   => $watchTubes,
                'thinning_rate' => $thinningRate,
                'design_limit'  => $designLimit,
            ],
            'priorities' => $priorities,
            'creep' => [
                'labels'  => $creepLabels,
                'values'  => $creepValues,
                'current' => $currentCreep,
            ],
            'historical_ndt' => $historicalNdt,
            'ndt_finding'    => $finding,
        ];
    }
}
