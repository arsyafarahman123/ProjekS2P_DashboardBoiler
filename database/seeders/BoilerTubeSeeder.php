<?php

namespace Database\Seeders;

use App\Models\BoilerTube;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BoilerTubeSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan semua data lama dulu supaya tidak ada sisa section/nilai dummy sebelumnya
        BoilerTube::truncate();

        $rows = [];
        $years = BoilerTube::YEARS;
        sort($years);

        // Data inspeksi hanya untuk Unit 3A; unit lain sengaja dibiarkan kosong
        $unit = BoilerTube::DEFAULT_UNIT;

        foreach (BoilerTube::SECTION_COUNTS as $section => $count) {
            $code = BoilerTube::SECTION_CODES[$section];

            for ($i = 1; $i <= $count; $i++) {
                $tubeId = sprintf('%s-U3A-%02d', $code, $i);

                foreach ($years as $year) {
                    // Baseline bersih: semua tube 0% creep, status Safe
                    $rows[] = [
                        'unit' => $unit,
                        'year' => $year,
                        'section' => $section,
                        'tube_id' => $tubeId,
                        'creep_pct' => 0.0,
                        'remaining_life_months' => 120,
                        'status' => 'Safe',
                        'recommended_action' => BoilerTube::actionFromStatus('Safe'),
                        'scan_date' => Carbon::create($year, 1, 1)->toDateString(),
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

        if (! empty($rows)) {
            BoilerTube::insert($rows);
        }
    }
}
