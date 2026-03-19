<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'saaim01@gmail.com'],
            [
                'name' => 'Muhammad Tariq',
                'phone' => '03346031105',
                'email_verified_at' => now(),
                'password' => 'ChangeMe123!',
                'remember_token' => Str::random(10),
                'password_reset_required' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'abdrps2004@gmail.com'],
            [
                'name' => 'Abdullah Abid',
                'phone' => '03326334598',
                'email_verified_at' => now(),
                'password' => 'ChangeMe123!',
                'remember_token' => Str::random(10),
                'password_reset_required' => true,
            ]
        );

        $this->call([
            RoleSeeder::class,
            BreedSeeder::class,
            BreedPerformanceStandardSeeder::class,
            FeedSeeder::class,
            FarmSeeder::class,
            ShedSeeder::class,
            FlockSeeder::class,
            MedicineSeeder::class,
            ChartSeeder::class,
            ProductionLogSeeder::class,
            WeightLogSeeder::class,
            ExpenseSeeder::class,
            PricingSeeder::class,
            CapabilitySeeder::class,
            ConnectivitySeeder::class,
            DeviceSeeder::class,
            PakistanTablesSeeder::class,
            SettingsTableSeeder::class,
            ShortcutSeeder::class,
            IotDataLogsSeeder::class,
            CountriesSqlSeeder::class,
        ]);
    }
}
