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

    const UNITS = ['Unit 1', 'Unit 2', 'Unit 3', 'Unit 3A'];

    // Satu-satunya unit yang punya gambar section drawing & data inspeksi.
    // Unit lain sengaja dibiarkan kosong (tanpa gambar dan tanpa data).
    const DEFAULT_UNIT = 'Unit 3A';

    const YEARS = [2021, 2022, 2023, 2024, 2025];

    const SECTION_COUNTS = [
        'Furnace Bottom Slope'         => 16,
        'Furnace Waterwall Tube'      => 40,
        'Platen Superheater'          => 20,
        'Final Superheater'           => 20,
        'Low Temperature Superheater' => 20,
        'Primary Superheater'         => 24,
        'Secondary Superheater'       => 24,
        'Economizer'                  => 16,
        'Sootblower Area'             => 12,
    ];

    // Kode singkat per section, dipakai untuk tube_id (mis. FBS-U1-01) dan label chart
    const SECTION_CODES = [
        'Furnace Bottom Slope'         => 'FBS',
        'Furnace Waterwall Tube'      => 'FWT',
        'Platen Superheater'          => 'PLS',
        'Final Superheater'           => 'FSH',
        'Low Temperature Superheater' => 'LTS',
        'Primary Superheater'         => 'PSH',
        'Secondary Superheater'       => 'SSH',
        'Economizer'                  => 'ECO',
        'Sootblower Area'             => 'SBA',
    ];

    const STATUS_COLOR = [
        'Safe'     => '#22c55e',
        'Watch'    => '#eab308',
        'Critical' => '#ef4444',
    ];

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