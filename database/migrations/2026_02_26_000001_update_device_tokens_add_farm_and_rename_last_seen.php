<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Add farm_id (nullable — admin users have no farm)
            $table->foreignId('farm_id')
                ->nullable()
                ->after('user_id')
                ->constrained('farms')
                ->nullOnDelete();

            // Rename last_seen_at → last_updated_at
            $table->renameColumn('last_seen_at', 'last_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->renameColumn('last_updated_at', 'last_seen_at');
            $table->dropConstrainedForeignId('farm_id');
        });
    }
};
