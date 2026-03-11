<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_data_raw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->bigInteger('timestamp');
            $table->jsonb('readings');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['device_id', 'timestamp'], 'idx_sensor_device_timestamp');
            $table->index(['device_id', 'recorded_at'], 'idx_sensor_device_recorded');
        });

        DB::statement("
            COMMENT ON TABLE sensor_data_raw
            IS 'Replaces DynamoDB sensor-data table. Stores raw IoT sensor readings per device.';
        ");

        DB::statement("
            COMMENT ON COLUMN sensor_data_raw.readings
            IS 'JSONB — all sensor values: {temp1, temp2, humidity, nh3, co2, air_velocity, air_pressure, ...}';
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_data_raw');
    }
};
