<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoilerTube extends Model
{
    protected $fillable = [
        'unit', 'year', 'section', 'tube_id', 'creep_pct',
        'remaining_life_months', 'status', 'recommended_action', 'scan_date',
    ];

    protected $casts = [
        'scan_date' => 'date',
        'creep_pct' => 'float',
        'year' => 'integer',
    ];

    // ==== Konfigurasi dasar (setara UNITS / YEARS / SECTION_COUNTS di data_generator.py) ====

    const UNITS = ['Unit 1', 'Unit 2', 'Unit 3'];

    const YEARS = [2021, 2022, 2023, 2024, 2025];

    const SECTION_COUNTS = [
        'Waterwall'     => 40,
        'Superheater'   => 24,
        'Reheater'      => 16,
        'Economizer'    => 16,
        'Air Preheater' => 12,
    ];

    const STATUS_COLOR = [
        'Safe'     => '#22c55e',
        'Watch'    => '#eab308',
        'Critical' => '#ef4444',
    ];

    // Bounding box tiap section untuk render boiler 3D (persis SECTION_LAYOUT di app.py)
    const SECTION_LAYOUT = [
        'Waterwall'     => ['x0' => 0,   'x1' => 3,   'y0' => 0,   'y1' => 3,   'z0' => 0, 'z1' => 10],
        'Superheater'   => ['x0' => 3.4, 'x1' => 5,   'y0' => 0.5, 'y1' => 2.5, 'z0' => 2, 'z1' => 9],
        'Reheater'      => ['x0' => 5.2, 'x1' => 6.4, 'y0' => 0.5, 'y1' => 2.5, 'z0' => 2, 'z1' => 9],
        'Economizer'    => ['x0' => 6.8, 'x1' => 8,   'y0' => 0.3, 'y1' => 2.7, 'z0' => 1, 'z1' => 8],
        'Air Preheater' => ['x0' => 8.4, 'x1' => 9.8, 'y0' => 0.5, 'y1' => 2.5, 'z0' => 1, 'z1' => 6],
    ];

    public static function sections(): array
    {
        return array_keys(self::SECTION_COUNTS);
    }

    public static function statusFromCreep(float $creep): string
    {
        if ($creep > 80) {
            return 'Critical';
        }
        if ($creep >= 40) {
            return 'Watch';
        }

        return 'Safe';
    }

    public static function actionFromStatus(string $status): string
    {
        return match ($status) {
            'Critical' => 'REPLACE IMMEDIATELY',
            'Watch' => 'REPLACE NEXT SHUTDOWN',
            default => 'MONITOR',
        };
    }
}
