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
        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->id();

            // Targets
            $table->enum('target_type', ['token', 'user', 'topic']);
            $table->unsignedBigInteger('target_id')->nullable(); // token id or user id
            $table->string('target_topic', 191)->nullable();     // topic name if target_type=topic

            // Payload
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable(); // deep link payload, etc.

            // Delivery control
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable()->index();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending')->index();

            // Retry tracking
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(6);
            $table->dateTime('next_retry_at')->nullable()->index();
            $table->string('last_error', 800)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_outboxes');
    }
};
