<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boiler_tubes', function (Blueprint $table) {
            $table->id();
            $table->string('unit');
            $table->unsignedSmallInteger('year');
            $table->string('section');
            $table->string('tube_id');
            $table->decimal('creep_pct', 5, 2);
            $table->unsignedSmallInteger('remaining_life_months');
            $table->string('status');
            $table->string('recommended_action');
            $table->date('scan_date');
            $table->timestamps();

            $table->index(['unit', 'year', 'section']);
            $table->index(['unit', 'tube_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boiler_tubes');
    }
};