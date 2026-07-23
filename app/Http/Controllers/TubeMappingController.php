<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
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
            'unit', 'section', 'year'
        ));
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
