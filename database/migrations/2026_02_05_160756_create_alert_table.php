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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            // Foreign Ids
            $table->foreignId('user_id')->nullable();
            $table->foreignId('farm_id')->nullable();
            $table->foreignId('shed_id')->nullable();
            $table->foreignId('flock_id')->nullable();
            // Content
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('system')->index();      // system, security, billing, activity, maintenance
            $table->string('severity')->default('info')->index();  // info, success, warning, critical
            $table->enum('channel', ['in_app', 'email', 'sms', 'push'])->default('push');
            $table->json('data')->nullable();
            // State / lifecycle
            $table->string('status')->default('queued')->index(); // queued, sent, failed, delivered
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable()->index();
            $table->boolean('is_read')->default(false)->index();
            $table->dateTime('read_at')->nullable();
            $table->boolean('is_dismissed')->default(false)->index();
            $table->timestamp('dismissed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert');
    }
};
