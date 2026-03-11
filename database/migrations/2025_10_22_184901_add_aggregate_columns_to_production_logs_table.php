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
        Schema::table('production_logs', function (Blueprint $table) {
            // 1. total_mortality (computed/generated column)
            $table->integer('total_mortality_count')
                ->storedAs('day_mortality_count + night_mortality_count');

            // 2. todate_mortality (cumulative, default 0)
            $table->integer('todate_mortality_count')
                ->default(0);

            // 3. total_feed_consumed (computed/generated column)
            $table->double('total_feed_consumed', 10, 2)
                ->storedAs('day_feed_consumed + night_feed_consumed');

            // 4. todate_feed_consumed (cumulative, default 0)
            $table->double('todate_feed_consumed', 10, 2)
                ->default(0);

            // 5. total_water_consumed (computed/generated column)
            $table->double('total_water_consumed', 10, 2)
                ->storedAs('day_water_consumed + night_water_consumed');

            // 6. todate_water_consumed (cumulative, default 0)
            $table->double('todate_water_consumed', 10, 2)
                ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropColumn([
                'total_mortality_count',
                'todate_mortality_count',
                'total_feed_consumed',
                'todate_feed_consumed',
                'total_water_consumed',
                'todate_water_consumed',
            ]);
        });
    }
};
