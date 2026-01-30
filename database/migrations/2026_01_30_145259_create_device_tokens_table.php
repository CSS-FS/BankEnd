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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');

            $table->string('token', 512)->unique();
            $table->string('platform', 20); // android|ios/web
            $table->string('device_id', 128)->index();
            $table->string('device_model', 100)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('revoked_at')->nullable();


            $table->string('last_error', 500)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']); // one active token per device per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
