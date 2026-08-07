<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerImage;
use App\Models\BoilerTube;
use App\Models\TubeBaseline;
use App\Models\TubeMeasurement;
use App\Models\TubePhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class TubeMappingController extends Controller
{
    // Resolve unit + section + year dari query string, dipakai bareng oleh
    // index() dan export (Excel/PDF) supaya filter yang aktif di layar
    // selalu sama dengan yang di-export.
    private function resolveFilters(Request $request): array
    {
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

        $sections = BoilerArea::where('unit', $unit)->orderBy('id')->pluck('name')->all();

        $section = $request->get('section', 'Primary Superheater');
        if (! in_array($section, $sections, true)) {
            $section = in_array('Primary Superheater', $sections, true)
                ? 'Primary Superheater'
                : ($sections[0] ?? 'Primary Superheater');
        }

        $selectedArea = BoilerArea::where('unit', $unit)->where('name', $section)->first();
        $tubeCount = $selectedArea?->tube_count ?: 200;

        return [$units, $years, $unit, $year, $sections, $section, $tubeCount];
    }

    public function index(Request $request)
    {
        // Unit & tahun mengikuti data model dashboard (BoilerTube),
        // supaya konsisten dengan Global View.
        [$units, $years, $unit, $year, $sections, $section, ] = $this->resolveFilters($request);

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

            return $m ? [(int) $m[1] => BoilerTube::statusFromCreep($t->creep_pct)] : [];
        });

        $creepByTubeNumber = $tubes->mapWithKeys(function ($t) {
            preg_match('/(\d+)$/', $t->tube_id, $m);

            return $m ? [(int) $m[1] => $t->creep_pct] : [];
        });

        // Grid boiler section: jumlah slot & susunan titik ukur mengikuti
        // section yang sedang dipilih (bukan hardcode Primary Superheater).
        // Ini bikin grid berubah isinya tiap ganti section di dropdown.
        $selectedArea = BoilerArea::where('unit', $unit)
            ->where('name', $section)
            ->first();
        $tubeCount = $selectedArea?->tube_count ?: 200;
        $tubePointNames = $selectedArea?->pointList() ?? TubeMeasurement::POINTS;
        $tubePoints = TubeMeasurement::query()
            ->where('unit', $unit)
            ->where('section', $section)
            ->get()
            ->groupBy('tube_number')
            ->map(fn ($rows) => $rows->keyBy('point'));

        // Statistik MIN/MAX/AVG ketebalan dinding tube selama 5 tahun
        // (2021-2025), dihitung langsung dari data dummy excel/CSV yang
        // sama dipakai seeder — supaya angka di popup tube-mapping selalu
        // sinkron dengan data dummy Unit 3A 2021-2025 aslinya.
        $tubeThicknessStats = $this->thicknessStatsForSection($unit, $section);

        $pointsTable = $this->pointsTableForSection($unit, $section, $year);
        $pointNames = TubeMeasurement::POINTS;

        // Ringkasan MIN/MAX/AVG ketebalan (mm) se-section, dihitung dari
        // seluruh titik A-D milik semua pipa yang sudah punya data
        // pengukuran (bukan dari CSV dummy) — dipakai di card ringkasan
        // atas tabel titik A-D pada Tube Mapping.
        $allMm = [];
        foreach ($pointsTable as $row) {
            foreach (($row['mm'] ?? []) as $v) {
                if ($v !== null) {
                    $allMm[] = $v;
                }
            }
        }
        $measurementSummary = [
            'min' => $allMm ? round(min($allMm), 2) : null,
            'max' => $allMm ? round(max($allMm), 2) : null,
            'avg' => $allMm ? round(array_sum($allMm) / count($allMm), 2) : null,
            'count' => count($allMm),
        ];

        // Override $statusByTubeNumber & $creepByTubeNumber dari
        // $pointsTable per-tahun (bukan dari $tubes = BoilerTube seeds)
        // supaya WARNA GRID + POPUP + TABEL A-D semuanya KONSISTEN.
        $statusByTubeNumber = collect();
        $creepByTubeNumber = collect();
        foreach ($pointsTable as $tubeNumber => $data) {
            $s = match ($data['status']) {
                'critical' => 'Critical',
                'warning' => 'Warning',
                'safe' => 'Safe',
                default => null,
            };
            if ($s !== null) {
                $statusByTubeNumber[$tubeNumber] = $s;
            }
            $creepByTubeNumber[$tubeNumber] = $data['creep_pct'] ?? null;
        }

        // Summary dihitung dari $statusByTubeNumber (data pengukuran
        // per-tahun), bukan dari $tubes (BoilerTube seeds).
        $total = $statusByTubeNumber->count();
        $countSafe = $statusByTubeNumber->filter(fn($s) => $s === 'Safe')->count();
        $countWatch = $statusByTubeNumber->filter(fn($s) => $s === 'Warning' || $s === 'Watch')->count();
        $countCritical = $statusByTubeNumber->filter(fn($s) => $s === 'Critical')->count();
        $summary = [
            'safe_pct' => $total ? round($countSafe / $total * 100) : 0,
            'watch_pct' => $total ? round($countWatch / $total * 100) : 0,
            'critical_pct' => $total ? round($countCritical / $total * 100) : 0,
        ];

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

        // Gambar boiler yang diupload admin lewat Input Data > Upload Gambar Boiler
        // Difilter per section supaya gambar yang tampil sesuai dengan BOILER SECTION yang dipilih
        $boilerImages = BoilerImage::where('unit', $unit)
            ->where('section', $section)
            ->orderBy('created_at', 'desc')
            ->get();

        // Foto per tube (upload dari popup card tube mapping) —
        // dikirim sebagai array [tube_number => [{id, url, nama_file}, ...]]
        $tubePhotos = TubePhoto::where('unit', $unit)
            ->where('section', $section)
            ->get()
            ->groupBy('tube_number')
            ->map(fn($photos) => $photos->map(fn($p) => [
                'id' => $p->id,
                'url' => route('tube-mapping.photo.file', $p),
                'nama_file' => $p->nama_file,
                'is_image' => (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $p->nama_file),
            ])->values()->all());

        return view('tube-mapping.index', compact(
            'tubePoints', 'tubeCount', 'tubePointNames', 'summary', 'topPriority',
            'historicalNdt', 'creepTrend', 'units', 'sections', 'years',
            'unit', 'section', 'year', 'statusByTubeNumber', 'tubeThicknessStats', 'creepByTubeNumber', 'sectionCode',
            'pointsTable', 'pointNames', 'boilerImages', 'measurementSummary', 'tubePhotos'
        ));
    }

    /**
     * Bangun tabel titik ukur A-D (persen ketebalan sisa vs baseline) per
     * nomor tube untuk section+unit+TAHUN aktif, dari tube_measurements +
     * tube_baselines. Dipakai buat tabel "Jenis Pipa per Titik A-D" di
     * bawah grafik creep, dan buat cari status per-titik (warna merah
     * kalau ada 1 titik < 70%).
     *
     * Sebelumnya TANPA filter tahun → ganti tahun dropdown tidak
     * berpengaruh (data semua tahun di-merge jadi satu).
     * Sekarang filter per YEAR(measured_at) supaya setiap tahun
     * menghasilkan grid & tabel berbeda sesuai data asli.
     *
     * @return array<int, array{pct: array<string,float|null>, status: string, creep_pct: float|null}>
     */
    private function pointsTableForSection(string $unit, string $section, int $year): array
    {
        $pointNames = TubeMeasurement::POINTS;

        $baselines = TubeBaseline::where('unit', $unit)
            ->where('section', $section)
            ->pluck('initial_thickness_mm', 'tube_number');

        // SQLite tidak support YEAR() — gunakan strftime / LIKE
        // untuk memfilter berdasarkan tahun dari kolom measured_at.
        $yearPrefix = "{$year}-";
        $measurements = TubeMeasurement::where('unit', $unit)
        ->where('section', $section)
        ->where('year', $year)
        ->get()
        ->groupBy('tube_number');

        // creep per tube = (baseline - min_thickness) / baseline * 100
        // supaya grid bisa tampil % creep yang sesuai dengan tahun itu
        $table = [];
        foreach ($measurements as $tubeNumber => $rows) {
            $baseline = $baselines[$tubeNumber] ?? null;
            $pctByPoint = [];
            $mmByPoint = [];
            foreach ($pointNames as $p) {
                $row = $rows->firstWhere('point', $p);
                $pctByPoint[$p] = ($row && $baseline) ? round($row->thickness_mm / $baseline * 100, 1) : null;
                $mmByPoint[$p] = $row ? round($row->thickness_mm, 2) : null;
            }

            $validPct = array_filter($pctByPoint, fn ($v) => $v !== null);
            // STATUS dari TITIK TERLEMAH (MIN), bukan rata-rata.
            // Satu titik di bawah 70% sudah cukup bikin tube CRITICAL,
            // walau titik lain masih tinggi. Threshold: ≥75% SAFE, <75%–70% WARNING, <70% CRITICAL.
            $minPct = $validPct ? min($validPct) : null;
            $status = match (true) {
                $minPct === null => 'unknown',
                $minPct < 70 => 'critical',
                $minPct < 75 => 'warning',
                default => 'safe',
            };

            // creep: berapa persen ketebalan udah hilang dari baseline
            $thicknessValues = $rows->pluck('thickness_mm')->filter(fn ($v) => $v !== null);
            $minThickness = $thicknessValues->isNotEmpty() ? $thicknessValues->min() : null;
            $creepPct = ($baseline && $minThickness !== null)
                ? round(($baseline - $minThickness) / $baseline * 100, 2)
                : null;

            // MIN/MAX/AVG ketebalan (mm) dari titik A-D pipa ini SENDIRI —
            // langsung dari data yang diinput lewat Input Data Pengukuran,
            // bukan dari CSV dummy 5 tahun (itu dipakai buat popup/creep chart terpisah).
            $mmValues = array_values(array_filter($mmByPoint, fn ($v) => $v !== null));
            $minMm = $mmValues ? min($mmValues) : null;
            $maxMm = $mmValues ? max($mmValues) : null;
            $avgMm = $mmValues ? round(array_sum($mmValues) / count($mmValues), 2) : null;

            $table[(int) $tubeNumber] = [
                'pct' => $pctByPoint,
                'mm' => $mmByPoint,
                'min_mm' => $minMm,
                'max_mm' => $maxMm,
                'avg_mm' => $avgMm,
                'status' => $status,
                'creep_pct' => $creepPct,
                // NILAI AWAL (baseline) pipa ini — dari TubeBaseline, sama
                // dengan yang diisi/tampil di Input Data Pengukuran.
                'baseline' => $baseline !== null ? round($baseline, 2) : null,
            ];
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
        $status = BoilerTube::statusFromCreep($tube->creep_pct);

        return response()->json([
            'tube_id' => $tube->tube_id,
            'creep_pct' => $tube->creep_pct,
            'remaining_life_months' => $tube->remaining_life_months,
            'status' => $status,
            'recommended_action' => BoilerTube::actionFromStatus($status),
            'scan_date' => $tube->scan_date?->format('Y-m-d'),
        ]);
    }

    /**
     * Upload foto untuk satu tube dari popup card tube mapping.
     */
    public function photoStore(Request $request)
    {
        try {
            // Tidak dibatasi tipe file (image/PDF/dokumen apapun boleh) dan
            // ukuran dibikin sangat longgar (2GB, di atas batas real PHP
            // upload_max_filesize/post_max_size — lihat public/.user.ini).
            // 'max' di sini cuma jaring pengaman terakhir, bukan batas utama.
            $request->validate([
                'unit' => 'required|string',
                'section' => 'required|string',
                'tube_number' => 'required|integer',
                'photo' => 'required|file|max:2097152', // 2048 MB
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->implode(' '),
            ], 422);
        }

        try {
            $file = $request->file('photo');
            $originalName = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $safeName = uniqid('tube_') . '.' . $ext;
            $path = $file->storeAs('tube_photos', $safeName, 'public');

            $photo = TubePhoto::create([
                'unit' => $request->unit,
                'section' => $request->section,
                'tube_number' => $request->tube_number,
                'nama_file' => $originalName,
                'path' => $path,
            ]);

            return response()->json([
                'ok' => true,
                'id' => $photo->id,
                'url' => route('tube-mapping.photo.file', $photo),
                'nama_file' => $originalName,
                'is_image' => (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $originalName),
            ]);
        } catch (\Throwable $e) {
            // Selalu balikin JSON walau ada error tak terduga (mis. disk
            // penuh, permission storage/app/public salah, dst) supaya
            // frontend tidak nerima HTML/500 kosong yang bikin "status 200"
            // palsu setelah redirect.
            return response()->json([
                'ok' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serve file foto tube (disimpan di storage/app/public/tube_photos).
     */
    public function photoFile(TubePhoto $tubePhoto)
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($tubePhoto->path)) {
            abort(404);
        }

        $fullPath = $disk->path($tubePhoto->path);
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * Hapus foto tube dari popup card.
     */
    public function photoDestroy(TubePhoto $tubePhoto)
    {
        $disk = Storage::disk('public');
        if ($disk->exists($tubePhoto->path)) {
            $disk->delete($tubePhoto->path);
        }
        $tubePhoto->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Export laporan Tube Mapping (tabel Titik A-D per pipa) ke CSV —
     * dibuka Excel/Google Sheets langsung. Filter unit/section/tahun
     * ikut apa yang lagi aktif di layar (dikirim lewat query string).
     */
    public function exportExcel(Request $request)
    {
        [, , $unit, $year, , $section, $tubeCount] = $this->resolveFilters($request);

        $pointsTable = $this->pointsTableForSection($unit, $section, $year);
        $pointNames = TubeMeasurement::POINTS;

        $filename = 'tube-mapping-' . str($unit)->slug() . '-' . str($section)->slug() . '-' . $year . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($pointsTable, $pointNames, $tubeCount, $unit, $section, $year) {
            $out = fopen('php://output', 'w');
            // BOM supaya Excel baca UTF-8 dengan benar
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ["Tube Mapping Report - {$unit} - {$section} - Tahun {$year}"]);
            fputcsv($out, []);

            $header = ['Tube #', 'Nilai Awal (mm)'];
            foreach ($pointNames as $p) {
                $header[] = "Titik {$p} (mm)";
            }
            $header = array_merge($header, ['Min (mm)', 'Max (mm)', 'Avg (mm)', 'Status']);
            fputcsv($out, $header);

            for ($i = 1; $i <= $tubeCount; $i++) {
                $row = $pointsTable[$i] ?? null;
                $line = [$i, $row['baseline'] ?? ''];
                foreach ($pointNames as $p) {
                    $line[] = $row['mm'][$p] ?? '';
                }
                $line[] = $row['min_mm'] ?? '';
                $line[] = $row['max_mm'] ?? '';
                $line[] = $row['avg_mm'] ?? '';
                $line[] = $row ? strtoupper($row['status']) : 'BELUM ADA DATA';
                fputcsv($out, $line);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export laporan Tube Mapping ke PDF lewat halaman print khusus
     * (tanpa dependency tambahan) — browser yang generate PDF-nya lewat
     * dialog Print > Save as PDF, otomatis kebuka begitu halaman dimuat.
     */
    public function exportPdf(Request $request)
    {
        [, , $unit, $year, , $section, $tubeCount] = $this->resolveFilters($request);

        $pointsTable = $this->pointsTableForSection($unit, $section, $year);
        $pointNames = TubeMeasurement::POINTS;

        return view('tube-mapping.print', compact(
            'unit', 'section', 'year', 'tubeCount', 'pointsTable', 'pointNames'
        ));
    }
}
