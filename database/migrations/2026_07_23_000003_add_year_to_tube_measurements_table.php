<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sebelumnya tube_measurements cuma nyimpen 1 snapshot per titik (A-D)
// tanpa kolom tahun (unique: unit+section+tube_number+point), jadi
// kalau ganti tahun di dropdown Tube Mapping, data titik ukurnya cuma
// ada buat 1 tahun (2025) dan tahun lain kosong/dikit.
// Sekarang ditambah kolom `year` supaya tiap tahun (2021-2025) punya
// titik ukur A-D sendiri-sendiri.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tube_measurements', function (Blueprint $table) {
            $table->dropUnique(['unit', 'section', 'tube_number', 'point']);
            $table->unsignedSmallInteger('year')->nullable()->after('tube_number');
        });

        Schema::table('tube_measurements', function (Blueprint $table) {
            $table->unique(['unit', 'section', 'tube_number', 'point', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('tube_measurements', function (Blueprint $table) {
            $table->dropUnique(['unit', 'section', 'tube_number', 'point', 'year']);
            $table->dropColumn('year');
            $table->unique(['unit', 'section', 'tube_number', 'point']);
        });
    }
};
