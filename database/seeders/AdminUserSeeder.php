<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Satu akun login bersama, dipakai semua tim.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'ssprimadaya.co.id'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123'),
            ]
        );
    }
}