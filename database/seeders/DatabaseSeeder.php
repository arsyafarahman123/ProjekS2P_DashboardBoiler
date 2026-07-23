<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin untuk fitur Add Area di dashboard (password cast 'hashed' di model User)
        User::updateOrCreate(
            ['email' => 'admin@ssprimadaya.co.id'],
            ['name' => 'Admin', 'password' => 'admin123'],
        );

        $this->call([
            TubeScanSeeder::class,
            BoilerTubeSeeder::class,
            BoilerAreaSeeder::class,
        ]);
    }
}