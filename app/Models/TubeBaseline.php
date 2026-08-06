<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Nilai awal (ketebalan awal) per pipa yang diisi admin.
// Hasil ukur 4 titik (TubeMeasurement) dibandingkan terhadap nilai ini.
class TubeBaseline extends Model
{
    protected $fillable = [
        'unit', 'section', 'tube_number', 'initial_thickness_mm', 'measured_mm',
    ];

    protected $casts = [
        'tube_number' => 'integer',
        'initial_thickness_mm' => 'float',
        'measured_mm' => 'float',
    ];
}
