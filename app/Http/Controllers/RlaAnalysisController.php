<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
use App\Models\RlaDocument;
use App\Models\TubeMeasurement;
use Illuminate\Http\Request;

class RlaAnalysisController extends Controller
{
    /**
     * Minimum Wall Thickness (MWT) acuan tiap section (mm) — ini batas
     * standar teknik yang dipakai sebagai garis putus-putus di chart,
     * BUKAN data hasil ukur (jadi tetap konstanta, bukan ditarik dari
     * TubeMeasurement/TubeBaseline).
     */
    protected array $mwtBySection = [
        'Furnace Bottom Slope'         => 4.20,
        'Furnace Waterwall Tube'       => 4.20,
        'Platen Superheater'           => 4.80,
        'Final Superheater'            => 4.80,
        'Low Temperature Superheater'  => 4.50,
        'Primary Superheater'          => 4.50,
        'Secondary Superheater'        => 5.00,
        'Economizer'                   => 2.80,
        'Sootblower Area'              => 4.20,
    ];

    public function index(Request $request)
    {
        // Unit & tahun disamakan dengan Tube Mapping (BoilerTube::UNITS /
        // ::YEARS) supaya kedua halaman narik dari sumber yang sama.
        $units = BoilerTube::UNITS;
        $years = BoilerTube::YEARS;
        rsort($years);

        $unit = $request->query('unit', BoilerTube::DEFAULT_UNIT);
        if (!in_array($unit, $units, true)) {
            $unit = BoilerTube::DEFAULT_UNIT;
        }

        $year = (int) $request->query('year', max(BoilerTube::YEARS));
        if (!in_array($year, $years, true)) {
            $year = max(BoilerTube::YEARS);
        }

        // Dropdown Boiler Section sekarang ikut BoilerArea per unit — SAMA
        // seperti Tube Mapping (termasuk area tambahan yang dibuat admin
        // lewat Add Area), bukan 5 opsi hardcode lagi.
        $boilerSections = BoilerArea::where('unit', $unit)->orderBy('id')->pluck('name')->all();

        $section = $request->query('section', 'Secondary Superheater');
        if (!in_array($section, $boilerSections, true)) {
            $section = $boilerSections[0] ?? 'Secondary Superheater';
        }

        $data = $this->buildRealData($unit, $section, $year);

        // Dokumen RLA yang relevan dengan unit & tahun terpilih
        $documents = $this->getRelatedDocuments($unit, $year);

        return view('rla-analysis.index', [
            'boilerSections' => $boilerSections,
            'units'          => $units,
            'years'          => $years,
            'selectedSection' => $section,
            'selectedUnit'    => $unit,
            'selectedYear'    => $year,
            'data'            => $data,
            'documents'       => $documents,
        ]);
    }

    /**
     * Data grafik "Thickness per Tube Number" — ditarik LANGSUNG dari
     * TubeMeasurement (titik A-D per nomor tube), sumber data yang sama
     * dipakai tabel titik A-D di Tube Mapping. Sumbu-X di-sampling tiap 6
     * nomor tube (meniru pola Gambar 18 di laporan assessment mekanik),
     * nggak perlu render semua tube. MWT tetap konstanta standar per
     * section (lihat $mwtBySection), bukan data hasil ukur.
     */
    protected function buildThicknessChart(string $unit, string $section, int $year): array
    {
        $points = TubeMeasurement::POINTS; // ['A','B','C','D']

        $rows = TubeMeasurement::where('unit', $unit)
            ->where('section', $section)
            ->where('year', $year)
            ->get()
            ->groupBy('tube_number')
            // Cuma pakai tube yang 4 titiknya (A-D) lengkap, biar garis di
            // chart nggak putus-putus karena data kosong.
            ->filter(fn ($rowsForTube) => $rowsForTube->pluck('point')->unique()->count() >= count($points));

        $tubeNumbers = $rows->keys()
            ->map(fn ($n) => (int) $n)
            ->sort()
            ->values()
            ->filter(fn ($n, $i) => $i % 6 === 0)
            ->values()
            ->all();

        $series = ['a' => [], 'b' => [], 'c' => [], 'd' => []];
        foreach ($tubeNumbers as $no) {
            $pointRows = $rows[$no]->keyBy('point');
            foreach ($points as $p) {
                $val = $pointRows[$p]->thickness_mm ?? null;
                $series[strtolower($p)][] = $val !== null ? round((float) $val, 2) : null;
            }
        }

        return [
            'tube_numbers' => $tubeNumbers,
            'a' => $series['a'],
            'b' => $series['b'],
            'c' => $series['c'],
            'd' => $series['d'],
            'mwt' => $this->mwtBySection[$section] ?? 4.50,
        ];
    }

    /**
     * Ambil semua dokumen RLA yang ada (tanpa filter unit/tahun).
     * Ditampilkan apa adanya supaya user tidak bingung kenapa dokumen
     * yang sudah diupload tidak muncul.
     */
    protected function getRelatedDocuments(string $unit, int $year)
    {
        return RlaDocument::orderBy('created_at', 'desc')->get();
    }

    /**
     * Bangun semua data panel RLA dari data Tube Mapping ASLI (BoilerTube +
     * TubeMeasurement), bukan random lagi. Kalau kombinasi unit/section/
     * tahun belum punya data pengukuran (mis. Unit 1/2/3 yang belum diisi
     * admin lewat Input Data), panel terkait akan kosong apa adanya —
     * bukan angka karangan.
     */
    protected function buildRealData(string $unit, string $section, int $year): array
    {
        $thicknessChart = $this->buildThicknessChart($unit, $section, $year);

        // Tube paling kritis di section+unit+tahun terpilih (creep_pct
        // tertinggi) → jadi "Selected Tube" buat judul chart & priority list.
        $selectedTube = BoilerTube::where('unit', $unit)
            ->where('section', $section)
            ->where('year', $year)
            ->orderByDesc('creep_pct')
            ->first();

// RUL table: top-5 tube paling berisiko DI SECTION+UNIT+TAHUN
        // terpilih, biar konsisten sama panel lain di halaman ini (Thickness
        // Chart, Priority List, Historical NDT) yang juga ikut section aktif.
        // Kalau butuh top-5 lintas section se-Unit, itu udah ada di panel
        // "Top Priority" pada halaman Tube Mapping.
        $rulTable = BoilerTube::query()
            ->where('unit', $unit)
            ->where('section', $section)
            ->where('year', $year)
            ->orderByDesc('creep_pct')
            ->orderBy('remaining_life_months')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'tube_id'    => $t->tube_id,
                'section'    => $t->section,
                'rul_months' => $t->remaining_life_months,
                'status'     => $t->status,
                'badge'      => $this->statusBadgeClass($t->status),
            ]);

        // Historical NDT: riwayat scan tube di section+unit terpilih —
        // sumber & urutan sama seperti panel Historical NDT di Tube Mapping.
        $historicalNdt = BoilerTube::query()
            ->where('unit', $unit)
            ->where('section', $section)
            ->orderByDesc('scan_date')
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'date'      => $t->scan_date?->format('Y-m'),
                'tube_id'   => $t->tube_id,
                'creep_pct' => $t->creep_pct,
            ]);

        $priorities = $this->buildPriorities($unit, $section, $selectedTube);

        return [
            'thickness_chart' => $thicknessChart,
            'selected_tube'   => $selectedTube,
            'rul_table'       => $rulTable,
            'historical_ndt'  => $historicalNdt,
            'priorities'      => $priorities,
        ];
    }

    /**
     * Rekomendasi mitigasi — teksnya tetap template (belum ada sumber data
     * buat kalimat rekomendasi bebas), TAPI tube_id/section/unit dan level
     * prioritas Priority 1 sekarang ikut status ASLI tube terpilih, bukan
     * random lagi.
     */
protected function buildPriorities(string $unit, string $section, ?BoilerTube $selectedTube): array
{
    if (!$selectedTube) {
        return [];
    }

    $tubeLabel = $selectedTube->tube_id;
    $status = $selectedTube->status;

    $p1Text = match ($status) {
        'Critical' => "Replace Tube ID # {$tubeLabel} (Selected Tube) at Next Outage.",
        'Warning'  => "Schedule NDT re-scan on Tube ID # {$tubeLabel} (Selected Tube) before next outage.",
        'Safe'     => "Continue routine monitoring on Tube ID # {$tubeLabel} (Selected Tube).",
        default    => "Belum ada data pengukuran untuk {$section} pada kombinasi Unit/Tahun ini.",
    };

    return [
        ['level' => 'PRIORITY 1 (CRITICAL)', 'color' => 'critical', 'text' => $p1Text],
        ['level' => 'PRIORITY 2', 'color' => 'high', 'text' => "Increase Sootblowing Frequency in {$section}."],
        ['level' => 'PRIORITY 3', 'color' => 'medium', 'text' => "Review water chemistry logs for Unit {$unit}."],
        ['level' => 'PRIORITY 4', 'color' => 'info', 'text' => "Update thickness survey schedule for {$section}."],
    ];
}

    /**
     * Map status asli BoilerTube ('Critical'/'Warning'/'Safe') ke class
     * badge yang sudah ada di CSS (.rul-badge.critical/.watch/.safe).
     */
    protected function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            'Critical' => 'critical',
            'Warning'  => 'watch',
            'Safe'     => 'safe',
            default    => 'safe',
        };
    }
}