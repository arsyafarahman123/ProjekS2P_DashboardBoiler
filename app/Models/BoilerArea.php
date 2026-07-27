<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Daftar area/section boiler per unit yang tampil di Risk Summary by Section.
// Bisa ditambah lewat fitur admin "Add Area" di dashboard.
class BoilerArea extends Model
{
    protected $fillable = ['unit', 'name', 'code', 'tube_count', 'points'];

    protected $casts = ['tube_count' => 'integer'];

    // Susunan titik ukur area ini (berlaku untuk semua pipanya).
    // Bawaan A-D; berubah kalau admin menambah/menghapus titik.
    public function pointList(): array
    {
        return $this->points
            ? explode(',', $this->points)
            : TubeMeasurement::POINTS;
    }

    // Bikin kode singkat unik per unit dari inisial nama area
    // (mis. "Furnace Bottom Slope" -> FBS). Kalau tabrakan, ditambah angka.
    public static function makeCode(string $unit, string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $initials = strtoupper(implode('', array_map(fn ($w) => $w[0], array_slice($words, 0, 3))));
        if ($initials === '') {
            $initials = 'AREA';
        }

        $code = $initials;
        $suffix = 2;
        while (self::where('unit', $unit)->where('code', $code)->exists()) {
            $code = $initials . $suffix;
            $suffix++;
        }

        return $code;
    }
}
