<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shed_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shed_id')->constrained('sheds')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('location_in_shed')->nullable();
            $table->datetime('link_date')->useCurrent();
            $table->timestamps();

            $table->unique(['shed_id', 'device_id'], 'shed_devices_unique_shed_device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shed_devices', function (Blueprint $table) {
            // Drop by index name
            $table->dropUnique('shed_devices_unique_shed_device');
        });
        Schema::dropIfExists('shed_devices');
    }
};
