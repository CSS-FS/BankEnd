<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Muhammad Tariq',
            'email' => 'saaim01@gmail.com',
            'phone' => '+03346031105',
        ]);

        User::factory()->create([
            'name' => 'Abdullah Abid',
            'email' => 'abdrps2004@gmail.com',
            'phone' => '03326334598',
        ]);

        $this->call([
            RoleSeeder::class,
            BreedSeeder::class,
            FeedSeeder::class,
            FarmSeeder::class,
            ShedSeeder::class,
            FlockSeeder::class,
            MedicineSeeder::class,
            ChartSeeder::class,
            ProductionLogSeeder::class,
            WeightLogSeeder::class,
            // ExpenseSeeder::class,
            PricingSeeder::class,
            CapabilitySeeder::class,
            ConnectivitySeeder::class,
            DeviceSeeder::class,
            PakistanTablesSeeder::class,
            SettingsTableSeeder::class,
            ShortcutSeeder::class,
            IotDataLogsSeeder::class,
        ]);
    }
}
