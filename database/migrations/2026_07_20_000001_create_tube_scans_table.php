<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("tube_scans", function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger("year");
            $table->string("unit", 20);
            $table->string("section", 30);
            $table->string("tube_id", 40);
            $table->unsignedTinyInteger("row_no")->nullable();
            $table->unsignedTinyInteger("col_no")->nullable();
            $table->decimal("creep_pct", 5, 1);
            $table->unsignedSmallInteger("remaining_life_months");
            $table->enum("status", ["Safe", "Warning", "Critical"]);
            $table->string("recommended_action", 60);
            $table->date("scan_date");
            $table->string("material", 10)->default("T91");
            $table->decimal("current_thickness_mm", 4, 1)->nullable();
            $table->decimal("min_design_thickness_mm", 4, 1)->nullable();
            $table->boolean("corrosion_detected")->default(false);
            $table->timestamps();

            $table->index(["unit", "section", "year"]);
            $table->index("tube_id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tube_scans");
    }
};
