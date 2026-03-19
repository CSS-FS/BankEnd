<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breed_performance_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('breed_id')->constrained('breeds')->onDelete('cascade');
            $table->enum('type', ['as_hatched', 'male', 'female'])->default('as_hatched');
            $table->unsignedSmallInteger('day');
            $table->unsignedInteger('weight_g')->comment('On-farm body weight in grams');
            $table->float('daily_gain_g')->nullable()->comment('Daily weight gain in grams');
            $table->float('avg_daily_gain_g')->nullable()->comment('Average daily gain in grams');
            $table->float('daily_intake_g')->nullable()->comment('Daily feed intake in grams');
            $table->float('cum_intake_g')->nullable()->comment('Cumulative feed intake in grams');
            $table->float('fcr')->nullable()->comment('Feed Conversion Ratio');
            $table->timestamps();

            $table->unique(['breed_id', 'type', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breed_performance_standards');
    }
};
