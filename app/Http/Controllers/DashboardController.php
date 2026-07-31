<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{

    public function index()
    {
        $years = BoilerTube::YEARS;
        $defaultUnit = BoilerTube::DEFAULT_UNIT;
        $defaultYear = (int) end($years);

        return view('dashboard', [
            'units' => BoilerTube::UNITS,
            'years' => $years,
            'drawingUnit' => BoilerTube::DEFAULT_UNIT,
            'statusColor' => BoilerTube::STATUS_COLOR,
            'defaultUnit' => $defaultUnit,
            'defaultYear' => $defaultYear,
            'initialPayload' => $this->buildPayload($defaultUnit, $defaultYear),
        ]);
    }

    // Setara gabungan callback update_main() + show_section_detail() di app.py,
    // dipanggil via fetch() setiap unit/tahun berganti.
    public function data(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit' => 'nullable|string|in:' . implode(',', BoilerTube::UNITS),
            'year' => 'nullable|integer|in:' . implode(',', BoilerTube::YEARS),
        ]);
        $validator->validate();

        $allYears = BoilerTube::YEARS;

        $unit = $request->query('unit', BoilerTube::DEFAULT_UNIT);
        $year = (int) $request->query('year', end($allYears));

        return response()->json($this->buildPayload($unit, $year));
    }

    // Bangun payload data dashboard (dipakai baik untuk render awal via Blade
    // maupun untuk response JSON /api/boiler-data saat filter unit/tahun berganti).
    private function buildPayload(string $unit, int $year): array
    {
        // Daftar area/section dinamis per unit (bisa ditambah admin lewat Add Area)
        $areas = BoilerArea::where('unit', $unit)->orderBy('id')->get();
        $sections = $areas->pluck('name')->all();
        $tubes = BoilerTube::where('unit', $unit)->where('year', $year)->get();

        // ---- Status & jumlah per section (section_status / section_counts) ----
        // Hitung status dari creep_pct (bukan dari kolom status DB)
        // Threshold PME: creep<=25→Safe, creep>25&<=30→Warning, creep>30→Critical
        $sectionSummary = [];
        foreach ($areas as $area) {
            $sub = $tubes->where('section', $area->name);
            $critical = $sub->filter(fn($t) => BoilerTube::statusFromCreep($t->creep_pct) === 'Critical')->count();
            $watch = $sub->filter(fn($t) => BoilerTube::statusFromCreep($t->creep_pct) === 'Warning')->count();
            $safe = $sub->filter(fn($t) => BoilerTube::statusFromCreep($t->creep_pct) === 'Safe')->count();
            $worst = $critical ? 'Critical' : ($watch ? 'Warning' : 'Safe');

            $sectionSummary[] = [
                'id' => $area->id,
                'section' => $area->name,
                'code' => $area->code,
                'critical' => $critical,
                'watch' => $watch,
                'safe' => $safe,
                'total' => $sub->count(),
                'worst' => $worst,
                'color' => BoilerTube::STATUS_COLOR[$worst],
            ];
        }

        // ---- Kartu status atas (cards) ----
        $criticalCount = $tubes->filter(fn($t) => BoilerTube::statusFromCreep($t->creep_pct) === 'Critical')->count();
        $watchCount = $tubes->filter(fn($t) => BoilerTube::statusFromCreep($t->creep_pct) === 'Warning')->count();
        $efficiency = round(100 - ($criticalCount * 0.9 + $watchCount * 0.25), 1);
        $minYear = min(BoilerTube::YEARS);
        $load = round(85 + ($year - $minYear) * 1.5, 1);

        // ---- Top 5 tube dengan sisa umur paling kritis (untuk chart perbandingan) ----
        $top5Ids = $tubes->sortBy('remaining_life_months')->take(5)->pluck('tube_id')->values();

        $comparison = BoilerTube::where('unit', $unit)
            ->whereIn('tube_id', $top5Ids)
            ->orderBy('year')
            ->get()
            ->groupBy('tube_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => [
                'year' => $r->year,
                'creep_pct' => $r->creep_pct,
            ])->values());

        // ---- Detail tube per section (untuk tabel collapsible) ----
        $detail = [];
        foreach ($sections as $section) {
            $sub = $tubes->where('section', $section)->sortByDesc('creep_pct')->values();
            $detail[$section] = $sub->map(fn ($r) => [
                'tube_id' => $r->tube_id,
                'creep_pct' => $r->creep_pct,
                'remaining_life_months' => $r->remaining_life_months,
                'status' => BoilerTube::statusFromCreep($r->creep_pct),
                'recommended_action' => BoilerTube::actionFromStatus(BoilerTube::statusFromCreep($r->creep_pct)),
                'scan_date' => $r->scan_date->format('Y-m-d'),
            ]);
        }

        return [
            'unit' => $unit,
            'year' => $year,
            'cards' => [
                'efficiency' => $efficiency,
                'critical_count' => $criticalCount,
                'watch_count' => $watchCount,
                'load' => $load,
            ],
            'section_summary' => $sectionSummary,
            'comparison' => $comparison,
            'detail' => $detail,
        ];
    }
}