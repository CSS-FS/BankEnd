<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('iot_data_logs', function (Blueprint $table) {
                $table->enum('time_window', ['hourly', '3h', '6h', '12h', 'daily', 'latest'])->default('hourly');
                $table->index(['device_id', 'parameter', 'record_time', 'time_window'], 'idx_device_param_window');
            });
        } else {
            // MySQL approach
            Schema::table('iot_data_logs', function (Blueprint $table) {
                $table->enum('time_window', ['hourly', '3h', '6h', '12h', 'daily', 'latest'])
                    ->after('record_time')  // MySQL supports AFTER
                    ->default('hourly');
                
                $table->index(['device_id', 'parameter', 'record_time', 'time_window'], 'idx_device_param_window');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iot_data_logs', function (Blueprint $table) {
            // Drop the column if the migration is rolled back
            $table->dropColumn('time_window');
        });
    }
};
