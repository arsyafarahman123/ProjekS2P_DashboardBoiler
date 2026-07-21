<?php

namespace Database\Seeders;

use App\Models\BoilerTube;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BoilerTubeSeeder extends Seeder
{
    // Kode singkat per section, dipakai untuk bikin tube_id (mis. WW-01, SH-04, dst)
    protected array $sectionCode = [
        'Waterwall'     => 'WW',
        'Superheater'   => 'SH',
        'Reheater'      => 'RH',
        'Economizer'    => 'EC',
        'Air Preheater' => 'AP',
    ];

    public function run(): void
    {
        mt_srand(42); // reproducible

        $rows = [];
        $years = BoilerTube::YEARS;
        sort($years);

        foreach (BoilerTube::UNITS as $unitIndex => $unit) {
            foreach (BoilerTube::SECTION_COUNTS as $section => $count) {
                $code = $this->sectionCode[$section];

                for ($i = 1; $i <= $count; $i++) {
                    $tubeId = sprintf('%s-U%d-%02d', $code, $unitIndex + 1, $i);

                    // Kondisi awal & laju degradasi tiap tube berbeda-beda (acak tapi konsisten per tube)
                    $baseCreep = mt_rand(5, 30) + mt_rand(0, 99) / 100;
                    $yearlyRate = mt_rand(300, 1100) / 100; // 3% - 11% per tahun

                    foreach ($years as $yi => $year) {
                        $creep = min(100, $baseCreep + $yearlyRate * $yi + mt_rand(-150, 150) / 100);
                        $creep = max(0, round($creep, 1));

                        $status = BoilerTube::statusFromCreep($creep);
                        $action = BoilerTube::actionFromStatus($status);

                        // Perkiraan sisa umur pipa (bulan) menurun seiring creep naik
                        $remainingLife = (int) max(0, round((100 - $creep) * 1.2));

                        $scanDate = Carbon::create($year, mt_rand(1, 12), mt_rand(1, 28));

                        $rows[] = [
                            'unit' => $unit,
                            'year' => $year,
                            'section' => $section,
                            'tube_id' => $tubeId,
                            'creep_pct' => $creep,
                            'remaining_life_months' => $remainingLife,
                            'status' => $status,
                            'recommended_action' => $action,
                            'scan_date' => $scanDate->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Insert per-batch supaya query tidak terlalu besar sekali jalan
                        if (count($rows) >= 500) {
                            BoilerTube::insert($rows);
                            $rows = [];
                        }
                    }
                }
            }
        }

        if (! empty($rows)) {
            BoilerTube::insert($rows);
        }
    }
}