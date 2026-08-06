<?php

namespace Database\Seeders;

use App\Models\BoilerArea;
use App\Models\BoilerTube;
use Illuminate\Database\Seeder;

class BoilerAreaSeeder extends Seeder
{
    public function run(): void
    {
        BoilerArea::truncate();

        // Area awal hanya untuk Unit 3A (mengikuti section drawing);
        // unit lain kosong sampai admin menambahkan sendiri lewat dashboard.
        foreach (BoilerTube::SECTION_CODES as $name => $code) {
            BoilerArea::create([
                'unit' => BoilerTube::DEFAULT_UNIT,
                'name' => $name,
                'code' => $code,
                'tube_count' => 200,
            ]);
        }
    }
}
