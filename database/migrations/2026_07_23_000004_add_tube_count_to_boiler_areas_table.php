<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jumlah pipa yang tersedia per area, dipakai halaman admin input titik ukur.
// 0 = belum ditentukan (admin bisa mengisinya dari halaman input data).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boiler_areas', function (Blueprint $table) {
            $table->unsignedSmallInteger('tube_count')->default(0)->after('code');
        });

        // Primary Superheater sudah disepakati berisi pipa nomor 1-200
        DB::table('boiler_areas')
            ->where('name', 'Primary Superheater')
            ->update(['tube_count' => 200]);
    }

    public function down(): void
    {
        Schema::table('boiler_areas', function (Blueprint $table) {
            $table->dropColumn('tube_count');
        });
    }
};
