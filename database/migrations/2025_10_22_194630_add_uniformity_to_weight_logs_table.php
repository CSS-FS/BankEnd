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
        Schema::table('weight_logs', function (Blueprint $table) {
            $table->double('flock_weight_gain', 10, 3)->default(0);
            $table->double('uniformity', 10, 3)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weight_logs', function (Blueprint $table) {
            $table->dropColumn('flock_weight_gain');
            $table->dropColumn('uniformity');
        });
    }
};
