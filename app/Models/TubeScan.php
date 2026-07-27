<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TubeScan extends Model
{
    use HasFactory;

    protected $fillable = [
        "year", "unit", "section", "tube_id", "row_no", "col_no",
        "creep_pct", "remaining_life_months", "status", "recommended_action",
        "scan_date", "material", "current_thickness_mm", "min_design_thickness_mm",
        "corrosion_detected",
    ];

    protected $casts = [
        "scan_date" => "date",
        "corrosion_detected" => "boolean",
        "creep_pct" => "float",
        "current_thickness_mm" => "float",
        "min_design_thickness_mm" => "float",
    ];

    public function statusColor(): string
    {
        return match ($this->status) {
            "Critical" => "red",
            "Watch" => "yellow",
            default => "green",
        };
    }
}
