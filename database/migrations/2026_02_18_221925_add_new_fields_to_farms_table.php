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
        Schema::table('farms', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);

            $table->string('country')->default('Pakistan')->after('address');
            $table->string('phone_number', 11)->nullable()->after('country');
            $table->string('contact_person', 50)->nullable()->after('phone_number');
            $table->boolean('alerts')->default(false)->after('contact_person');
            $table->boolean('notifications')->default(false)->after('alerts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->dropColumn(['country', 'phone_number', 'contact_person', 'alerts', 'notifications']);
        });
    }
};
