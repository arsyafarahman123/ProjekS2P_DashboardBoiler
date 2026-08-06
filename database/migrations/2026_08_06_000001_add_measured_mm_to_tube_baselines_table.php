<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NILAI UKUR (measured_mm) sebagai nilai independen per pipa.
// Terpisah dari nilai per titik (tube_measurements): mengubah NILAI UKUR
// hanya mengubah kolom NILAI UKUR di rekap, TIDAK mengubah titik mana pun.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tube_baselines', function (Blueprint $table) {
            $table->decimal('measured_mm', 6, 2)->nullable()->after('initial_thickness_mm');
        });
    }

    public function down(): void
    {
        Schema::table('tube_baselines', function (Blueprint $table) {
            $table->dropColumn('measured_mm');
        });
    }
};