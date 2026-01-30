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
        Schema::create('topic_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_token_id')->constrained('device_tokens')->cascadeOnDelete();

            $table->dateTime('subscribed_at')->nullable();
            $table->dateTime('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_topic_id', 'device_token_id'], 'topic_device_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_subscriptions');
    }
};
