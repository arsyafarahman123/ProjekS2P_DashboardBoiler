<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Hasil ukur ketebalan dinding pipa per titik (A-D).
// Satu pipa punya 4 titik ukur; nilainya diisi lewat menu input admin.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tube_measurements', function (Blueprint $table) {
            $table->id();
            $table->string('unit');
            $table->string('section');
            $table->unsignedSmallInteger('tube_number'); // nomor urut pipa (1-200 untuk PSH)
            $table->char('point', 1);                    // titik ukur: A, B, C, atau D
            $table->decimal('thickness_mm', 6, 2)->nullable();
            $table->date('measured_at')->nullable();
            $table->timestamps();

            $table->unique(['unit', 'section', 'tube_number', 'point']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tube_measurements');
    }
};
