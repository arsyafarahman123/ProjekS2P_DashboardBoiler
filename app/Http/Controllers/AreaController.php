<?php

namespace App\Http\Controllers;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    // Tambah area baru untuk sebuah unit (dipanggil via fetch dari dashboard, khusus admin).
    // Area baru mulai kosong (0 tube); pengisian datanya menyusul.
    public function store(Request $request)
    {
        $data = $request->validate([
            'unit' => 'required|string|in:' . implode(',', BoilerTube::UNITS),
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s\-\.\/&]+$/u'],
        ], [
            'name.regex' => 'Nama area hanya boleh berisi huruf, angka, spasi, dan karakter - . / &',
        ]);

        $name = trim($data['name']);

        $exists = BoilerArea::where('unit', $data['unit'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => "Area \"{$name}\" sudah ada di {$data['unit']}.",
            ], 422);
        }

        $area = BoilerArea::create([
            'unit' => $data['unit'],
            'name' => $name,
            'code' => BoilerArea::makeCode($data['unit'], $name),
        ]);

        return response()->json([
            'area' => [
                'unit' => $area->unit,
                'name' => $area->name,
                'code' => $area->code,
            ],
        ], 201);
    }

    // Hapus area beserta seluruh data tube miliknya (khusus admin)
    public function destroy(BoilerArea $area)
    {
        BoilerTube::where('unit', $area->unit)
            ->where('section', $area->name)
            ->delete();

        $area->delete();

        return response()->json([
            'message' => "Area \"{$area->name}\" dihapus.",
        ]);
    }
}
