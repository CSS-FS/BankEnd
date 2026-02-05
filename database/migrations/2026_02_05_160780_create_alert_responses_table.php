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
        Schema::create('alert_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts');
            $table->foreignId('creator_id')->nullable();
            $table->foreignId('responder_id')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->enum('action_type', ['Pending', 'Acknowledged', 'Resolved', 'Dismissed', 'Escalated'])->default('Pending');
            $table->text('action_details')->nullable();
            $table->timestamps();

            $table->index(['alert_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_responses');
    }
};
