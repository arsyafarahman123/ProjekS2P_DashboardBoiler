<?php

namespace Database\Seeders;

use App\Models\TubeScan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TubeScanSeeder extends Seeder
{
    public function run(): void
    {
        $units = ["Unit 1", "Unit 3", "Unit 3A"];
        $sections = ["Waterwall", "Superheater", "Reheater", "Economizer", "Air Preheater"];
        $years = [2022, 2023, 2024, 2025, 2026];

        $batch = [];
        $counters = [];

        foreach ($years as $year) {
            foreach ($units as $unit) {
                foreach ($sections as $section) {
                    $key = $year . "|" . $unit . "|" . $section;
                    $counters[$key] = 0;

                    for ($i = 1; $i <= 600; $i++) {
                        $counters[$key]++;
                        $idx = $counters[$key] - 1;

                        $rowNo = intdiv($idx, 30) + 1;
                        $colNo = ($idx % 30) + 1;

                        $creep = round(mt_rand(20, 400) / 10, 1);
                        $remainingLife = mt_rand(1, 60);

                        if ($creep > 35 || $remainingLife <= 2) {
                            $status = "Critical";
                            $action = "REPLACE IMMEDIATELY";
                        } elseif ($creep > 22 || $remainingLife <= 12) {
                            $status = "Watch";
                            $action = "REPLACE NEXT SHUTDOWN";
                        } else {
                            $status = "Safe";
                            $action = "MONITOR ROUTINE";
                        }

                        $minDesign = 2.1;
                        $maxThickness = 5.6;
                        $currentThickness = round($maxThickness - ($creep / 100) * ($maxThickness - $minDesign), 1);

                        $prefix = $section === "Waterwall" ? "WW" : ($section === "Superheater" ? "SH" : ($section === "Reheater" ? "RH" : ($section === "Economizer" ? "EC" : "AP")));
                        $unitSlug = str_replace(" ", "", $unit);
                        $tubeId = sprintf("%s-%s-%03d", $prefix, $unitSlug, $i);

                        $month = mt_rand(1, 12);
                        $day = mt_rand(1, 28);
                        $scanDate = sprintf("%04d-%02d-%02d", $year, $month, $day);

                        $batch[] = [
                            "year" => $year,
                            "unit" => $unit,
                            "section" => $section,
                            "tube_id" => $tubeId,
                            "row_no" => $rowNo,
                            "col_no" => $colNo,
                            "creep_pct" => $creep,
                            "remaining_life_months" => $remainingLife,
                            "status" => $status,
                            "recommended_action" => $action,
                            "scan_date" => $scanDate,
                            "material" => "T91",
                            "current_thickness_mm" => $currentThickness,
                            "min_design_thickness_mm" => $minDesign,
                            "corrosion_detected" => $status === "Critical" && $creep > 30,
                        ];

                        if (count($batch) >= 500) {
                            DB::table("tube_scans")->insert($batch);
                            $batch = [];
                        }
                    }
                }
            }
        }

        if (!empty($batch)) {
            DB::table("tube_scans")->insert($batch);
        }

        $this->command->info("TubeScan seeding selesai: " . TubeScan::count() . " baris.");
    }
}
