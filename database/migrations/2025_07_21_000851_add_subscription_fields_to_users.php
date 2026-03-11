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
        Schema::table('users', function (Blueprint $table) {
            // Track the user's current plan, trial, etc.
            // pricing_id and trial_ends_at already exist in the table
            $table->string('subscription_status')->nullable(); // active, cancelled, past_due, trial, expired
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop columns that this migration adds, not ones added by Cashier or other migrations
            $table->dropColumn(['subscription_status', 'stripe_customer_id', 'stripe_subscription_id']);
        });
    }
};
