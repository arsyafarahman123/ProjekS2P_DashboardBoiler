<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tube_photos', function (Blueprint $table) {
            $table->id();
            $table->string('unit');
            $table->string('section');
            $table->integer('tube_number');
            $table->string('nama_file');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tube_photos');
    }
};