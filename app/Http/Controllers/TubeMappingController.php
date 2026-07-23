<?php

namespace App\Http\Controllers;

use App\Models\TubeScan;
use Illuminate\Http\Request;

class TubeMappingController extends Controller
{
    public function index(Request $request)
    {
        $unit = $request->get("unit", "Unit 3A");
        $section = $request->get("section", "Superheater");
        $year = (int) $request->get("year", now()->year);

        $tubes = TubeScan::query()
            ->where("unit", $unit)
            ->where("section", $section)
            ->where("year", $year)
            ->orderBy("row_no")
            ->orderBy("col_no")
            ->get();

        $maxRow = (int) ($tubes->max("row_no") ?? 20);
        $maxCol = (int) ($tubes->max("col_no") ?? 30);

        $grid = [];
        foreach ($tubes as $t) {
            $grid[$t->row_no][$t->col_no] = $t;
        }

        $total = $tubes->count();
        $safeCount = $tubes->where("status", "Safe")->count();
        $watchCount = $tubes->where("status", "Watch")->count();
        $criticalCount = $tubes->where("status", "Critical")->count();

        // Dihitung dari data tube yang sedang difilter (unit/section/tahun),
        // bukan angka tetap, supaya berubah mengikuti pilihan dropdown.
        $avgCreep = $total ? (float) $tubes->avg("creep_pct") : 0;
        $criticalRatio = $total ? $criticalCount / $total : 0;
        $watchRatio = $total ? $watchCount / $total : 0;

        $globalEfficiency = 100 - ($avgCreep * 0.5) - ($criticalRatio * 20) - ($watchRatio * 5);
        $globalEfficiency = round(max(60, min(99.9, $globalEfficiency)), 1);

        $boilerLoad = 100 - ($criticalRatio * 25) - ($avgCreep * 0.15);
        $boilerLoad = round(max(55, min(99, $boilerLoad)));

        $operationalStatus = $criticalRatio > 0.15 ? "WARNING" : ($total ? "ACTIVE" : "NO DATA");

        $summary = [
            "safe_pct" => $total ? round($safeCount / $total * 100) : 0,
            "watch_pct" => $total ? round($watchCount / $total * 100) : 0,
            "critical_pct" => $total ? round($criticalCount / $total * 100) : 0,
            "critical_count" => $criticalCount,
            "active_alerts" => $watchCount ? $criticalCount + 2 : $criticalCount,
            "global_efficiency" => $globalEfficiency,
            "boiler_load" => $boilerLoad,
            "operational_status" => $operationalStatus,
        ];

        $topPriority = TubeScan::query()
            ->where("year", $year)
            ->orderByDesc("creep_pct")
            ->orderBy("remaining_life_months")
            ->limit(5)
            ->get()
            ->map(function ($t, $i) {
                $risk = round(($t->creep_pct * 0.7) + ((60 - min($t->remaining_life_months, 60)) * 0.5), 1);
                return [
                    "rank" => $i + 1,
                    "tube_id" => $t->tube_id,
                    "unit" => $t->unit,
                    "risk" => $risk,
                ];
            });

        $historicalNdt = TubeScan::query()
            ->where("unit", $unit)
            ->where("section", $section)
            ->orderByDesc("scan_date")
            ->limit(6)
            ->get();

        $selectedTubeId = $tubes->first()?->tube_id;
        $creepTrend = TubeScan::query()
            ->where("unit", $unit)
            ->where("section", $section)
            ->when($selectedTubeId, fn ($q) => $q->where("tube_id", $selectedTubeId))
            ->orderBy("year")
            ->get(["year", "creep_pct"]);

        $units = TubeScan::query()->distinct()->orderBy("unit")->pluck("unit");
        $sections = TubeScan::query()->distinct()->orderBy("section")->pluck("section");
        $years = TubeScan::query()->distinct()->orderByDesc("year")->pluck("year");

        return view("tube-mapping.index", compact(
            "grid", "maxRow", "maxCol", "summary", "topPriority",
            "historicalNdt", "creepTrend", "units", "sections", "years",
            "unit", "section", "year"
        ));
    }
    public function show(string $tubeId)
    {
        $tube = TubeScan::where("tube_id", $tubeId)->orderByDesc("year")->firstOrFail();

        return response()->json([
            "tube_id" => $tube->tube_id,
            "material" => $tube->material,
            "current_thickness_mm" => $tube->current_thickness_mm,
            "min_design_thickness_mm" => $tube->min_design_thickness_mm,
            "remaining_life_months" => $tube->remaining_life_months,
            "creep_pct" => $tube->creep_pct,
            "status" => $tube->status,
            "corrosion_detected" => $tube->corrosion_detected,
        ]);
    }
}
