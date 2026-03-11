<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appliance_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('appliance_key');
            $table->boolean('status')->default(false);
            $table->bigInteger('timestamp');
            $table->jsonb('metrics')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['device_id', 'timestamp'], 'idx_appliance_device_timestamp');
            $table->index(
                ['device_id', 'appliance_key', 'timestamp'],
                'idx_appliance_device_key_time'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appliance_status_history');
    }
};
