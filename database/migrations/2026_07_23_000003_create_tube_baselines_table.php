<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nilai awal (ketebalan desain/awal) per pipa, diisi admin.
// Hasil pengetesan 4 titik (tube_measurements) nantinya dibandingkan ke nilai ini.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tube_baselines', function (Blueprint $table) {
            $table->id();
            $table->string('unit');
            $table->string('section');
            $table->unsignedSmallInteger('tube_number');
            $table->decimal('initial_thickness_mm', 6, 2);
            $table->timestamps();

            $table->unique(['unit', 'section', 'tube_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tube_baselines');
    }
};
