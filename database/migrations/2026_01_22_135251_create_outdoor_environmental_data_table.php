<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outdoor_environmental_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id');
            $table->dateTime('recorded_at');
            $table->decimal('temperature', 5, 2);
            $table->decimal('humidity', 5, 2)->unsigned();
            $table->decimal('wind_speed', 5, 2)->nullable();
            $table->decimal('pressure', 6, 2)->nullable();
            $table->json('extra_metrics')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outdoor_environmental_data');
    }
};
