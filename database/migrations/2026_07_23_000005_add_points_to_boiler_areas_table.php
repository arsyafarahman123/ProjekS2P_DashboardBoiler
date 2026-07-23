<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Susunan titik ukur per AREA (berlaku untuk semua pipa di area itu),
// disimpan sebagai daftar huruf dipisah koma, mis. "A,B,C,D,E".
// NULL = memakai bawaan A-D.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boiler_areas', function (Blueprint $table) {
            $table->string('points', 100)->nullable()->after('tube_count');
        });
    }

    public function down(): void
    {
        Schema::table('boiler_areas', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};
