<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
use App\Models\RlaDocument;
use App\Models\TubeBaseline;
use App\Models\TubeMeasurement;
use Illuminate\Http\Request;
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

        if ($area) {
            // Susunan titik mengikuti pengaturan area (menu Add/Delete Titik)
            $points = $area->pointList();

            $baselines = TubeBaseline::where('unit', $unit)
                ->where('section', $area->name)
                ->get()
                ->keyBy('tube_number');

            $measurements = TubeMeasurement::where('unit', $unit)
                ->where('section', $area->name)
                ->get()
                ->groupBy('tube_number')
                ->map(fn ($r) => $r->keyBy('point'));

            $filled = $this->tubesWithData($unit, $area->name)->all();
            $filledCount = count($filled);

            // Semua pipa area ditampilkan sekaligus; pipa tanpa data bernilai null
            $rows = collect(range(1, max(1, $area->tube_count)))
                ->mapWithKeys(fn ($no) => [
                    $no => in_array($no, $filled, true)
                        ? $this->tubeData($no, $baselines, $measurements)
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
            'points' => $points,
            'rows' => $rows,
            'filledCount' => $filledCount,
            'measuredAtDefault' => $measuredAtDefault,
        ]);
    }

    // Simpan seluruh grid sekaligus. Nilai dikirim sebagai satu paket JSON
    // (bukan ribuan input form) supaya tidak terpotong batas max_input_vars PHP.
    public function ukurStore(Request $request)
    {
        $area = $this->targetArea($request);

        if ($area->tube_count < 1) {
            return back()->withErrors(['section' => 'Area ini belum punya pipa. Tambahkan lewat menu Add/Delete Pipa.']);
        }

        $data = $request->validate([
            'measured_at' => 'nullable|date',
            'payload' => 'required|string',
        ]);

        $rowsInput = json_decode($data['payload'], true);
        if (! is_array($rowsInput) || $rowsInput === []) {
            return back()->withErrors(['payload' => 'Belum ada nilai yang diisi.']);
        }

        $pointNames = $area->pointList();
        $measuredAt = $data['measured_at'] ?? now()->toDateString();
        $savedTubes = 0;

        foreach ($rowsInput as $row) {
            $no = (int) ($row['tube'] ?? 0);
            if ($no < 1 || $no > $area->tube_count) {
                continue;
            }

            $key = [
                'unit' => $area->unit,
                'section' => $area->name,
                'tube_number' => $no,
            ];
            $savedAny = false;

            $initial = $row['initial'] ?? null;
            if ($this->validThickness($initial)) {
                TubeBaseline::updateOrCreate($key, [
                    'initial_thickness_mm' => (float) $initial,
                ]);
                $savedAny = true;
            }

            foreach ($pointNames as $p) {
                $val = $row['points'][$p] ?? null;
                if ($this->validThickness($val)) {
                    TubeMeasurement::updateOrCreate($key + ['point' => $p], [
                        'thickness_mm' => (float) $val,
                        'measured_at' => $measuredAt,
                    ]);
                    $savedAny = true;
                }
            }

            if ($savedAny) {
                $savedTubes++;
            }
        }

        return redirect()
            ->route('input-data.ukur', ['unit' => $area->unit, 'section' => $area->name])
            ->with('status', "Data {$savedTubes} pipa ({$area->name}) tersimpan. Tanggal ukur: {$measuredAt}.");
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
    private function tubesWithData(string $unit, string $section)
    {
        $a = TubeBaseline::where('unit', $unit)->where('section', $section)->pluck('tube_number');
        $b = TubeMeasurement::where('unit', $unit)
            ->where('section', $section)
            ->whereNotNull('thickness_mm')
            ->pluck('tube_number');

        return $a->merge($b)->unique()->sort()->values();
    }

    // Rangkum nilai awal + nilai titik satu pipa untuk tabel dan prefill form
    private function tubeData(int $no, $baselines, $points): array
    {
        $pts = $points->get($no);

        return [
            'tube_number' => $no,
            'initial' => $baselines->get($no)?->initial_thickness_mm,
            'points' => $pts
                ? $pts->map(fn ($m) => $m->thickness_mm)->all()
                : [],
            'measured_at' => $pts?->first(fn ($m) => $m->measured_at)?->measured_at?->format('Y-m-d'),
        ];
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
        $data = $request->validate([
            'unit' => 'required|string|in:' . implode(',', BoilerTube::UNITS),
            'tanggal' => 'required|date',
            'file_rla' => 'required|file|mimes:pdf,xlsx,xls,csv,png,jpg,jpeg|max:20480',
        ]);

        $file = $request->file('file_rla');
        $path = $file->store('rla_documents');

        RlaDocument::create([
            'unit' => $data['unit'],
            'tanggal' => $data['tanggal'],
            'nama_file' => $file->getClientOriginalName(),
            'path' => $path,
        ]);

        return redirect()
            ->route('input-data.rla')
            ->with('status', "Dokumen RLA {$data['unit']} tanggal {$data['tanggal']} berhasil diupload.");
    }

    public function rlaFile(RlaDocument $document)
    {
        if (! Storage::exists($document->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::response($document->path);
    }

    public function rlaDownload(RlaDocument $document)
    {
        if (! Storage::exists($document->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::download($document->path, $document->nama_file);
    }

    public function rlaDestroy(RlaDocument $document)
    {
        Storage::delete($document->path);
        $document->delete();

        return redirect()
            ->route('input-data.rla')
            ->with('status', "Dokumen {$document->nama_file} berhasil dihapus.");
    }
}
