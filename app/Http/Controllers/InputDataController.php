<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerImage;
use App\Models\BoilerTube;
use App\Models\RlaDocument;
use App\Models\TubeBaseline;
use App\Models\TubeMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

// Menu Input Data (khusus admin), berisi tiga pilihan:
//   - add/delete pipa  : menambah / mengurangi jumlah pipa sebuah area
//   - add/delete titik : menambah / menghapus titik ukur pada satu pipa
//   - input pengukuran : mengisi nilai awal & hasil ukur ketebalan per titik
// Semua alur dimulai dengan memilih unit lalu area pada unit tersebut.
class InputDataController extends Controller
{
    // ---------- Menu utama ----------

    public function index()
    {
        // Pemilihan unit + area dilakukan di masing-masing menu di dalamnya
        return view('admin.input-data.index');
    }

    // ---------- Add/Delete Pipa ----------

    public function pipa(Request $request)
    {
        [$unit, $area, $areas] = $this->resolveArea($request);

        $withData = $area ? $this->tubesWithData($unit, $area->name)->count() : 0;

        return view('admin.input-data.pipa', [
            'units' => BoilerTube::UNITS,
            'unit' => $unit,
            'areas' => $areas,
            'area' => $area,
            'withData' => $withData,
        ]);
    }

    public function pipaAdd(Request $request)
    {
        $area = $this->targetArea($request);
        $data = $request->validate(['jumlah' => 'required|integer|min:1|max:2000']);

        $old = $area->tube_count;
        $new = min(2000, $old + $data['jumlah']);
        $area->update(['tube_count' => $new]);

        return redirect()
            ->route('input-data.pipa', ['unit' => $area->unit, 'section' => $area->name])
            ->with('status', "Pipa area {$area->name} ditambah: {$old} menjadi {$new} pipa.");
    }

    public function pipaReduce(Request $request)
    {
        $area = $this->targetArea($request);
        $data = $request->validate(['jumlah' => 'required|integer|min:1|max:2000']);

        $old = $area->tube_count;
        if ($old < 1) {
            return back()->withErrors(['jumlah' => 'Area ini belum punya pipa.']);
        }

        $new = max(0, $old - $data['jumlah']);

        // Pipa dikurangi dari nomor paling akhir; data pipa yang terhapus ikut dibersihkan
        $removed = TubeMeasurement::where('unit', $area->unit)
            ->where('section', $area->name)
            ->where('tube_number', '>', $new)
            ->delete();
        $removed += TubeBaseline::where('unit', $area->unit)
            ->where('section', $area->name)
            ->where('tube_number', '>', $new)
            ->delete();

        $area->update(['tube_count' => $new]);

        $extra = $removed ? " ({$removed} baris data pipa yang terhapus ikut dibersihkan)" : '';

        return redirect()
            ->route('input-data.pipa', ['unit' => $area->unit, 'section' => $area->name])
            ->with('status', "Pipa area {$area->name} dikurangi: {$old} menjadi {$new} pipa.{$extra}");
    }

    // ---------- Add/Delete Titik (berlaku per area, untuk semua pipanya) ----------

    public function titik(Request $request)
    {
        [$unit, $area, $areas] = $this->resolveArea($request);

        $points = null;
        if ($area) {
            // Jumlah pipa yang sudah terisi nilai per titik (informasi di tiap kartu titik)
            $filledPerPoint = TubeMeasurement::where('unit', $unit)
                ->where('section', $area->name)
                ->whereNotNull('thickness_mm')
                ->selectRaw('point, COUNT(*) as c')
                ->groupBy('point')
                ->pluck('c', 'point');

            $points = collect($area->pointList())->map(fn ($p) => [
                'point' => $p,
                'filled' => (int) ($filledPerPoint[$p] ?? 0),
            ]);
        }

        return view('admin.input-data.titik', [
            'units' => BoilerTube::UNITS,
            'unit' => $unit,
            'areas' => $areas,
            'area' => $area,
            'points' => $points,
        ]);
    }

    public function titikAdd(Request $request)
    {
        $area = $this->targetArea($request);
        $list = $area->pointList();

        // Titik baru = huruf pertama A-Z yang belum dipakai area ini
        $next = collect(range('A', 'Z'))->first(fn ($l) => ! in_array($l, $list, true));
        if (! $next) {
            return back()->withErrors(['point' => 'Maksimal 26 titik per area.']);
        }

        $list[] = $next;
        sort($list);
        $area->update(['points' => implode(',', $list)]);

        return redirect()
            ->route('input-data.titik', ['unit' => $area->unit, 'section' => $area->name])
            ->with('status', "Titik {$next} ditambahkan ke area {$area->name} (berlaku untuk semua pipa).");
    }

    public function titikDelete(Request $request)
    {
        $area = $this->targetArea($request);
        $data = $request->validate([
            'point' => 'required|string|size:1|regex:/^[A-Z]$/',
        ]);

        $list = $area->pointList();

        if (! in_array($data['point'], $list, true)) {
            return back()->withErrors(['point' => 'Titik tidak ditemukan pada area ini.']);
        }
        if (count($list) <= 1) {
            return back()->withErrors(['point' => 'Minimal satu titik per area.']);
        }

        $list = array_values(array_diff($list, [$data['point']]));
        $area->update(['points' => implode(',', $list)]);

        // Nilai ukur titik ini di semua pipa area ikut dihapus
        $removed = TubeMeasurement::where('unit', $area->unit)
            ->where('section', $area->name)
            ->where('point', $data['point'])
            ->delete();

        $extra = $removed ? " ({$removed} nilai ukur ikut terhapus)" : '';

        return redirect()
            ->route('input-data.titik', ['unit' => $area->unit, 'section' => $area->name])
            ->with('status', "Titik {$data['point']} dihapus dari area {$area->name}.{$extra}");
    }

    // ---------- Input data pengukuran ----------

    public function ukur(Request $request)
    {
        [$unit, $area, $areas] = $this->resolveArea($request);

        $points = [];
        $rows = collect();
        $filledCount = 0;
        $activeYear = (int) now()->format('Y');
        $tubeCount = 0;

        if ($area) {
            // Susunan titik mengikuti pengaturan area (menu Add/Delete Titik)
            $points = $area->pointList();

            // Jumlah pipa aktif: tube_count area, atau nomor pipa terbesar
            // yang sudah punya data. Konsisten dengan Tube Mapping yang
            // fallback ke 200 kalau tube_count 0.
            $tubeCount = $this->effectiveTubeCount($area);

            // Info tahun terakhir terukur (hanya untuk badge/header).
            $lastMeasuredForYear = TubeMeasurement::where('unit', $unit)
                ->where('section', $area->name)
                ->max('measured_at');
            $activeYear = $lastMeasuredForYear
                ? (int) Carbon::parse($lastMeasuredForYear)->format('Y')
                : (int) now()->format('Y');

            $baselines = TubeBaseline::where('unit', $unit)
                ->where('section', $area->name)
                ->get()
                ->keyBy('tube_number');

            // REKAP menampilkan SEMUA data tersimpan (semua tahun), bukan
            // hanya tahun aktif. Sebelumnya dibatasi $activeYear yang diambil
            // dari tanggal dummy terbaru (2025), sehingga data input user
            // manual (mis. tahun 2026) TIDAK PERNAH muncul di rekap.
            // Untuk tiap titik, ambil nilai dari record dengan YEAR TERBESAR
            // (pengukuran terakhir yang tersimpan).
            $measurements = TubeMeasurement::where('unit', $unit)
                ->where('section', $area->name)
                ->get()
                ->groupBy('tube_number')
                ->map(function ($rows) {
                    return $rows->sortByDesc('year')
                        ->values()
                        ->groupBy('point')
                        ->map(fn ($g) => $g->first());
                });

            // Record pengukuran TERAKHIR per pipa (berdasarkan tanggal ukur,
            // lalu id) — dipakai sebagai fallback NILAI UKUR untuk data lama
            // yang belum punya kolom measured_mm. Logika ini SAMA dengan
            // ukurTubeData() supaya angka di tabel = angka di auto-fill form.
            $lastMeasuredByTube = TubeMeasurement::where('unit', $unit)
                ->where('section', $area->name)
                ->whereNotNull('thickness_mm')
                ->get()
                ->sortByDesc(fn ($m) => [$m->measured_at?->timestamp ?? 0, $m->id])
                ->groupBy('tube_number')
                ->map(fn ($g) => $g->first());

            // tubesWithData() memakai pluck() yang mengembalikan nilai mentah
            // dari SQLite (string "1", "2", ...). Cast ke integer supaya
            // in_array(..., true) di bawah cocok dengan $no dari range() (int).
            // Tanpa filter tahun agar pipa dengan data TAHUN BERAPA PUN
            // (termasuk data manual user) tetap dianggap "terisi" di rekap.
            $filled = $this->tubesWithData($unit, $area->name)
                ->map(fn ($v) => (int) $v)
                ->all();
            $filledCount = count($filled);

            // Semua pipa area ditampilkan sekaligus; pipa tanpa data bernilai null
            $rows = collect(range(1, max(1, $tubeCount)))
                ->mapWithKeys(fn ($no) => [
                    $no => in_array($no, $filled, true)
                        ? $this->tubeData($no, $baselines, $measurements, $lastMeasuredByTube)
                        : null,
                ]);
        }

        // Satu tanggal ukur untuk semua pipa (pengukuran satu sesi):
        // pakai tanggal ukur terakhir yang tersimpan, atau hari ini
        $lastMeasured = $area
            ? TubeMeasurement::where('unit', $unit)->where('section', $area->name)->max('measured_at')
            : null;
        $measuredAtDefault = $lastMeasured ? substr($lastMeasured, 0, 10) : now()->toDateString();

        return view('admin.input-data.ukur', [
            'units' => BoilerTube::UNITS,
            'unit' => $unit,
            'areas' => $areas,
            'area' => $area,
            'tubeCount' => $tubeCount,
            'points' => $points,
            'rows' => $rows,
            'filledCount' => $filledCount,
            'measuredAtDefault' => $measuredAtDefault,
            'activeYear' => $activeYear,
        ]);
    }

    // Simpan nilai ukur SATU pipa: pipa # dipilih, lalu isi NILAI TITIK
    // untuk SEMUA titik (A/B/C/D) sekaligus + NILAI UKUR + NILAI AWAL.
    // Field yang dikosongkan tidak menimpa nilai lama.
    public function ukurStore(Request $request)
    {
        $area = $this->targetArea($request);

        $tubeCount = $this->effectiveTubeCount($area);

        if ($tubeCount < 1) {
            return back()->withErrors(['section' => 'Area ini belum punya pipa. Tambahkan lewat menu Add/Delete Pipa.']);
        }

        $pointNames = $area->pointList();

        // Validasi: tube_number + nilai_awal + measured_mm + measured_at,
        // plus satu field nilai per titik (nilai_A, nilai_B, ...)
        $rules = [
            'tube_number' => 'required|integer|min:1|max:' . $tubeCount,
            // NILAI AWAL — baseline pipa, opsional
            'nilai_awal' => 'nullable|numeric|min:0|max:1000',
            // NILAI UKUR — nilai INDEPENDEN per pipa, terpisah dari titik
            'measured_mm' => 'nullable|numeric|min:0|max:1000',
            'measured_at' => 'nullable|date',
        ];
        foreach ($pointNames as $p) {
            $rules['nilai_' . $p] = 'nullable|numeric|min:0|max:1000';
        }

        $data = $request->validate($rules);

        $hasAwal = $this->validThickness($data['nilai_awal'] ?? null);
        $hasUkur = $this->validThickness($data['measured_mm'] ?? null);

        // Kumpulkan nilai titik yang valid (tidak kosong)
        $pointValues = [];
        foreach ($pointNames as $p) {
            $v = $data['nilai_' . $p] ?? null;
            if ($this->validThickness($v)) {
                $pointValues[$p] = (float) $v;
            }
        }

        if (empty($pointValues) && ! $hasAwal && ! $hasUkur) {
            return back()->withErrors([
                'nilai_A' => 'Isi minimal satu nilai: NILAI TITIK, NILAI UKUR, atau NILAI AWAL.',
            ])->withInput();
        }

        $measuredAt = $data['measured_at'] ?? now()->toDateString();
        // Tahun diambil dari tanggal ukur, supaya data tampil konsisten
        // dengan filter tahun di Tube Mapping (tanpa ini data tersimpan
        // dengan year NULL dan TIDAK PERNAH muncul di Tube Mapping).
        $year = (int) Carbon::parse($measuredAt)->format('Y');

        $key = [
            'unit' => $area->unit,
            'section' => $area->name,
            'tube_number' => $data['tube_number'],
            'year' => $year,
        ];

        if ($hasAwal || $hasUkur) {
            // Baseline (NILAI AWAL + NILAI UKUR independen) disimpan sekaligus;
            // field yang dikosongkan tidak menimpa nilai lama.
            $baseline = TubeBaseline::firstOrNew([
                'unit' => $area->unit,
                'section' => $area->name,
                'tube_number' => $data['tube_number'],
            ]);
            if ($hasAwal) {
                $baseline->initial_thickness_mm = (float) $data['nilai_awal'];
            }
            if ($hasUkur) {
                $baseline->measured_mm = (float) $data['measured_mm'];
            }
            $baseline->save();
        }

        // Simpan nilai untuk SEMUA titik yang diisi sekaligus
        foreach ($pointValues as $p => $v) {
            try {
                TubeMeasurement::updateOrCreate($key + ['point' => $p], [
                    'thickness_mm' => $v,
                    'measured_at' => $measuredAt,
                    'year' => $year,
                ]);
            } catch (\Throwable $e) {
                return back()->withErrors([
                    'nilai_' . $p => 'Gagal menyimpan data: ' . $e->getMessage(),
                ])->withInput();
            }
        }

        $saved = [];
        foreach ($pointValues as $p => $v) {
            $saved[] = "titik {$p} = {$v} mm";
        }
        if ($hasUkur) {
            $saved[] = "nilai ukur = {$data['measured_mm']} mm";
        }
        if ($hasAwal) {
            $saved[] = "nilai awal = {$data['nilai_awal']} mm";
        }

        return redirect()
            ->to(route('input-data.ukur', ['unit' => $area->unit, 'section' => $area->name]) . '#pipa-' . $data['tube_number'])
            ->with('status', "Pipa #{$data['tube_number']} ({$area->name}) tersimpan: " . implode(', ', $saved) . ". Tanggal ukur: {$measuredAt}.");
    }

    // Nilai ketebalan valid: angka 0-1000 mm
    private function validThickness($v): bool
    {
        return $v !== null && $v !== '' && is_numeric($v) && $v >= 0 && $v <= 1000;
    }

    public function ukurDestroy(Request $request, int $tubeNumber)
    {
        $area = $this->targetArea($request);

        TubeMeasurement::where('unit', $area->unit)
            ->where('section', $area->name)
            ->where('tube_number', $tubeNumber)
            ->delete();

        TubeBaseline::where('unit', $area->unit)
            ->where('section', $area->name)
            ->where('tube_number', $tubeNumber)
            ->delete();

        return redirect()
            ->to(route('input-data.ukur', ['unit' => $area->unit, 'section' => $area->name]) . '#pipa-' . $tubeNumber)
            ->with('status', "Data pipa #{$tubeNumber} ({$area->name}) dihapus.");
    }

    // Hapus satu nilai titik saja (bukan seluruh pipa)
    public function ukurDestroyPoint(Request $request, int $tubeNumber, string $point)
    {
        $area = $this->targetArea($request);

        TubeMeasurement::where('unit', $area->unit)
            ->where('section', $area->name)
            ->where('tube_number', $tubeNumber)
            ->where('point', $point)
            ->delete();

        return redirect()
            ->to(route('input-data.ukur', ['unit' => $area->unit, 'section' => $area->name]) . '#pipa-' . $tubeNumber)
            ->with('status', "Nilai titik {$point} pipa #{$tubeNumber} ({$area->name}) dihapus.");
    }

    // ---------- Helper ----------

    // Tentukan unit + area aktif dari query string (default Unit 3A)
    private function resolveArea(Request $request): array
    {
        $unit = $request->get('unit', BoilerTube::DEFAULT_UNIT);
        if (! in_array($unit, BoilerTube::UNITS, true)) {
            $unit = BoilerTube::DEFAULT_UNIT;
        }

        $areas = BoilerArea::where('unit', $unit)->orderBy('id')->get();

        $section = $request->get('section');
        $area = $section ? $areas->firstWhere('name', $section) : null;
        if (! $area) {
            $area = $areas->firstWhere('name', 'Primary Superheater') ?? $areas->first();
        }

        return [$unit, $area, $areas];
    }

    // Area tujuan aksi POST/DELETE (dari hidden field unit + section)
    private function targetArea(Request $request): BoilerArea
    {
        $data = $request->validate([
            'unit' => 'required|string|in:' . implode(',', BoilerTube::UNITS),
            'section' => 'required|string',
        ]);

        return BoilerArea::where('unit', $data['unit'])
            ->where('name', $data['section'])
            ->firstOrFail();
    }

    // Nomor pipa yang sudah punya data (nilai awal atau hasil ukur terisi)
    private function tubesWithData(string $unit, string $section, ?int $year = null)
    {
        $a = TubeBaseline::where('unit', $unit)->where('section', $section)->pluck('tube_number');

        $query = TubeMeasurement::where('unit', $unit)
            ->where('section', $section)
            ->whereNotNull('thickness_mm');

        if ($year) {
            // Data tahun aktif + data lama (year NULL) supaya pipa yang
            // diinput sebelum kolom year ada tetap dihitung terisi.
            $query->where(function ($q) use ($year) {
                $q->where('year', $year)->orWhereNull('year');
            });
        }

        $b = $query->pluck('tube_number');

        return $a->merge($b)->unique()->sort()->values();
    }

    // Rangkum nilai awal + nilai titik satu pipa untuk tabel dan prefill form
    private function tubeData(int $no, $baselines, $points, $lastMeasuredByTube = null): array
    {
        $pts = $points->get($no);

        // NILAI UKUR = nilai INDEPENDEN per pipa (kolom measured_mm).
        // Terpisah dari nilai per titik: mengubah NILAI UKUR hanya
        // mengubah kolom NILAI UKUR di rekap, titik-titik tidak ikut berubah.
        //
        // Fallback untuk data LAMA (diinput sebelum kolom measured_mm ada):
        // kalau measured_mm kosong, NILAI UKUR diambil dari record pengukuran
        // TERAKHIR pipa ini (berdasarkan tanggal ukur, lalu id) — logika SAMA
        // dengan ukurTubeData() supaya angka di tabel = angka di auto-fill form.
        // Begitu user menyimpan NILAI UKUR, nilai permanen kolom measured_mm
        // yang dipakai.
        $measured = $baselines->get($no)?->measured_mm;
        if ($measured === null || $measured === '') {
            $last = $lastMeasuredByTube?->get($no);
            $measured = $last?->thickness_mm;
        }

        return [
            'tube_number' => $no,
            'initial' => $baselines->get($no)?->initial_thickness_mm,
            'measured_mm' => $measured,
            'points' => $pts
                ? $pts->map(fn ($m) => $m->thickness_mm)->all()
                : [],
            'measured_at' => $pts?->first(fn ($m) => $m->measured_at)?->measured_at?->format('Y-m-d'),
        ];
    }

    // Jumlah pipa efektif sebuah area: tube_count dari area, atau nomor pipa
    // terbesar yang sudah punya data (pengukuran/baseline). Dipakai supaya
    // area yang tube_count-nya 0 (mis. pernah dikurangi) tetap menampilkan
    // pipa yang datanya sudah tersimpan.
    private function effectiveTubeCount(BoilerArea $area): int
    {
        $maxMeasured = TubeMeasurement::where('unit', $area->unit)
            ->where('section', $area->name)
            ->max('tube_number');
        $maxBaseline = TubeBaseline::where('unit', $area->unit)
            ->where('section', $area->name)
            ->max('tube_number');

        return max((int) $area->tube_count, (int) $maxMeasured, (int) $maxBaseline);
    }

    // Data satu pipa untuk auto-fill form (AJAX): dipanggil saat user
    // memilih nomor pipa di dropdown "PILIH PIPA #".
    public function ukurTubeData(Request $request, int $tubeNumber)
    {
        $data = $request->validate([
            'unit' => 'required|string|in:' . implode(',', BoilerTube::UNITS),
            'section' => 'required|string',
        ]);

        $area = BoilerArea::where('unit', $data['unit'])
            ->where('name', $data['section'])
            ->firstOrFail();

        $baseline = TubeBaseline::where('unit', $data['unit'])
            ->where('section', $data['section'])
            ->where('tube_number', $tubeNumber)
            ->first();

        // Tahun aktif diambil dari tanggal ukur yang sedang berlaku (untuk
        // info saja; nilai yang diambil tetap yang terbaru dari semua tahun)
        $year = (int) $request->get('year', now()->format('Y'));

        // Auto-fill menampilkan nilai TERBARU per titik dari SEMUA tahun
        // (bukan hanya tahun aktif), supaya nilai yang baru disimpan manual
        // oleh user langsung muncul di form.
        $measurements = TubeMeasurement::where('unit', $data['unit'])
            ->where('section', $data['section'])
            ->where('tube_number', $tubeNumber)
            ->get()
            ->sortByDesc('year')
            ->groupBy('point')
            ->map(fn ($g) => $g->first())
            ->pluck('thickness_mm', 'point');

        // NILAI UKUR independen dari baseline; fallback ke nilai ukur titik
        // terakhir kalau baseline data lama belum punya kolom measured_mm.
        $measured = $baseline?->measured_mm;
        if ($measured === null || $measured === '') {
            $last = TubeMeasurement::where('unit', $data['unit'])
                ->where('section', $data['section'])
                ->where('tube_number', $tubeNumber)
                ->whereNotNull('thickness_mm')
                ->orderByDesc('measured_at')
                ->orderByDesc('id')
                ->first();
            $measured = $last?->thickness_mm;
        }

        return response()->json([
            'tube_number' => $tubeNumber,
            'initial' => $baseline?->initial_thickness_mm,
            'measured_mm' => $measured,
            'points' => $measurements->isEmpty() ? (object) [] : $measurements,
        ]);
    }

    // ---------- Upload Dokumen RLA ----------

    public function rla()
    {
        $documents = RlaDocument::orderBy('created_at', 'desc')->get();

        return view('admin.input-data.rla', [
            'units' => BoilerTube::UNITS,
            'documents' => $documents,
        ]);
    }

    public function rlaStore(Request $request)
    {
        $unit = $request->input('unit');
        if (! $unit || ! in_array($unit, BoilerTube::UNITS)) {
            return back()->withErrors(['unit' => 'Unit tidak valid.']);
        }

        $tanggal = $request->input('tanggal');
        if (! $tanggal || ! strtotime($tanggal)) {
            return back()->withErrors(['tanggal' => 'Tanggal tidak valid.']);
        }

        // Bypass $request->hasFile() karena kadang gagal deteksi file
        // di Windows/local server. Pakai $_FILES langsung lebih reliable.
        $uploaded = $_FILES['file_rla'] ?? null;
        if (! $uploaded || empty($uploaded['tmp_name']) || $uploaded['error'] !== UPLOAD_ERR_OK) {
            $errMsg = 'File harus diupload.';
            if ($uploaded && $uploaded['error'] === UPLOAD_ERR_INI_SIZE) {
                $errMsg = 'File terlalu besar (melebihi upload_max_filesize). Maksimal 20MB.';
            } elseif ($uploaded && $uploaded['error'] === UPLOAD_ERR_FORM_SIZE) {
                $errMsg = 'File terlalu besar (melebihi MAX_FILE_SIZE form). Maksimal 20MB.';
            }
            return back()->withErrors(['file_rla' => $errMsg]);
        }

        $tmpPath = $uploaded['tmp_name'];
        $originalName = basename($uploaded['name']);
        $fileSize = (int) ($uploaded['size'] ?? 0);

        if ($fileSize > 20480 * 1024) {
            return back()->withErrors(['file_rla' => 'File maksimal 20MB.']);
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'xlsx', 'xls', 'csv', 'png', 'jpg', 'jpeg'];

        if (! in_array($ext, $allowed)) {
            return back()->withErrors(['file_rla' => 'Format file tidak didukung. Gunakan: ' . implode(', ', $allowed)]);
        }

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $dir = storage_path('app/public/rla_documents');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $targetPath = $dir . DIRECTORY_SEPARATOR . $safeName;

        copy($tmpPath, $targetPath);

        RlaDocument::create([
            'unit' => $unit,
            'tanggal' => $tanggal,
            'nama_file' => $originalName,
            'path' => 'rla_documents/' . $safeName,
        ]);

        return redirect()
            ->route('input-data.rla')
            ->with('status', "Dokumen RLA {$unit} tanggal {$tanggal} berhasil diupload.");
    }

    public function rlaFile(RlaDocument $document)
    {
        $full = storage_path('app/public/' . $document->path);
        if (! file_exists($full)) {
            abort(404, 'File tidak ditemukan.');
        }

        $ext = strtolower(pathinfo($document->nama_file, PATHINFO_EXTENSION));

        $mime = match ($ext) {
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'  => 'application/vnd.ms-excel',
            'csv'  => 'text/csv',
            default => 'application/octet-stream',
        };

        return response()->file($full, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $document->nama_file . '"',
        ]);
    }

    public function rlaDownload(RlaDocument $document)
    {
        $full = storage_path('app/public/' . $document->path);
        if (! file_exists($full)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->download($full, $document->nama_file);
    }

    public function rlaDestroy(RlaDocument $document)
    {
        $full = storage_path('app/public/' . $document->path);
        if (file_exists($full)) {
            unlink($full);
        }
        $document->delete();

        return redirect()
            ->route('input-data.rla')
            ->with('status', "Dokumen {$document->nama_file} berhasil dihapus.");
    }

    // ---------- Upload Gambar Boiler ----------

    public function image(Request $request)
    {
        $unit = $request->get('unit', BoilerTube::DEFAULT_UNIT);
        if (! in_array($unit, BoilerTube::UNITS, true)) {
            $unit = BoilerTube::DEFAULT_UNIT;
        }

        // Daftar boiler section untuk unit yang dipilih
        $sections = BoilerArea::where('unit', $unit)->orderBy('id')->pluck('name')->all();

        $section = $request->get('section', '');
        if (! $section || ! in_array($section, $sections, true)) {
            $section = $sections[0] ?? '';
        }

        $images = BoilerImage::where('unit', $unit)
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter section hanya untuk tampilan, query di atas ambil semua
        $filteredImages = $section
            ? $images->where('section', $section)
            : $images;

        // Ringkasan gambar per section
        $allSectionImages = $section
            ? null
            : $images->groupBy('section');

        return view('admin.input-data.image', [
            'units' => BoilerTube::UNITS,
            'unit' => $unit,
            'sections' => $sections,
            'section' => $section,
            'images' => $filteredImages,
            'allUnitImages' => $images->groupBy('unit'),
            'allSectionImages' => $allSectionImages,
        ]);
    }

    public function imageStore(Request $request)
    {
        $unit = $request->input('unit');
        if (! $unit || ! in_array($unit, BoilerTube::UNITS)) {
            return back()->withErrors(['unit' => 'Unit tidak valid.']);
        }

        $section = $request->input('section');
        $sections = BoilerArea::where('unit', $unit)->pluck('name')->all();
        if (! $section || ! in_array($section, $sections, true)) {
            return back()->withErrors(['section' => 'Boiler section tidak valid.']);
        }

        if (! $request->hasFile('file_image')) {
            // Cek error upload level PHP (melebihi upload_max_filesize, dll)
            $uploaded = $request->file('file_image');
            $code = $uploaded ? $uploaded->getError() : 999;
            $msg = match ($code) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File terlalu besar. Maksimal 20MB.',
                UPLOAD_ERR_NO_FILE => 'Silakan pilih file terlebih dahulu.',
                default => 'File gagal diupload (kode: ' . $code . ').',
            };

            return back()->withErrors(['file_image' => $msg]);
        }

        $file = $request->file('file_image');

        if (! $file->isValid()) {
            return back()->withErrors(['file_image' => 'File rusak atau gagal diupload.']);
        }

        $originalName = $file->getClientOriginalName();
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

        if (! in_array($ext, $allowed)) {
            return back()->withErrors(['file_image' => 'Format file tidak didukung. Gunakan: ' . implode(', ', $allowed)]);
        }

        $size = $file->getSize();
        if ($size > 20_971_520) {
            return back()->withErrors(['file_image' => 'File maksimal 20MB.']);
        }

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $dir = storage_path('app/public/boiler_images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $targetPath = $dir . DIRECTORY_SEPARATOR . $safeName;
        if (! copy($file->getRealPath(), $targetPath)) {
            return back()->withErrors(['file_image' => 'Gagal menyimpan file ke server.']);
        }

        BoilerImage::create([
            'unit' => $unit,
            'section' => $section,
            'nama_file' => $originalName,
            'path' => 'boiler_images/' . $safeName,
        ]);

        return redirect()
            ->route('input-data.image', ['unit' => $unit, 'section' => $section])
            ->with('status', "Gambar boiler {$unit} — {$section} berhasil diupload.");
    }

    public function imageFile(BoilerImage $image)
    {
        $full = storage_path('app/public/' . $image->path);
        if (! file_exists($full)) {
            abort(404, 'Gambar tidak ditemukan.');
        }

        $mime = match (strtolower(pathinfo($image->nama_file, PATHINFO_EXTENSION))) {
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return response()->file($full, ['Content-Type' => $mime]);
    }

    public function imageDestroy(Request $request, BoilerImage $image)
    {
        $full = storage_path('app/public/' . $image->path);
        if (file_exists($full)) {
            unlink($full);
        }
        $unit = $image->unit;
        $section = $image->section;
        $image->delete();

        return redirect()
            ->route('input-data.image', ['unit' => $unit, 'section' => $section])
            ->with('status', "Gambar {$image->nama_file} berhasil dihapus.");
    }
}
