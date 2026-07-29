<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lepas unique constraint lama yg cuma (unit, section, tube_number, point)
        // — ini bikin cuma bisa simpan 1 tahun doang.
        Schema::table('tube_measurements', function (Blueprint $table) {
            try {
                $table->dropUnique(['unit', 'section', 'tube_number', 'point']);
            } catch (\Throwable) {
                // constraint mungkin sudah dihapus / tidak ada — lanjut saja
            }
        });

        // Tambah unique baru yg memasukkan measured_at supaya tiap tahun
        // bisa punya data pengukuran sendiri-sendiri.
        try {
            Schema::table('tube_measurements', function (Blueprint $table) {
                $table->unique(['unit', 'section', 'tube_number', 'point', 'measured_at']);
            });
        } catch (\Throwable) {
            // SQLite kadang strict; kalau gagal, skip
        }
    }

    public function down(): void
    {
        Schema::table('tube_measurements', function (Blueprint $table) {
            try {
                $table->dropUnique(['unit', 'section', 'tube_number', 'point', 'measured_at']);
            } catch (\Throwable) {
            }

            try {
                $table->unique(['unit', 'section', 'tube_number', 'point']);
            } catch (\Throwable) {
            }
        });
    }
};