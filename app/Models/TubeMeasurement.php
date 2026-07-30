<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Hasil ukur ketebalan dinding pipa per titik.
// Satu pipa punya 4 titik ukur (A-D); nilainya diisi lewat menu input admin.
class TubeMeasurement extends Model
{
    public const POINTS = ['A', 'B', 'C', 'D'];
    
    protected $fillable = [
    'unit', 'section', 'tube_number', 'year', 'point', 'thickness_mm', 'measured_at',
];


    protected $casts = [
        'tube_number' => 'integer',
        'thickness_mm' => 'float',
        'measured_at' => 'date',
    ];
}
