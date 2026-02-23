<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CountriesSqlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/sql/countries.sql');

        if (! File::exists($path)) {
            $this->command?->error("SQL file not found: {$path}");

            return;
        }

        // Optional: wipe table before seeding
        // DB::table('countries')->truncate();

        $sql = File::get($path);

        // Handle multi-statement SQL safely by splitting on semicolons would be fragile.
        // Instead, just run as unprepared if your driver supports it (MySQL does).
        DB::unprepared($sql);

        $this->command?->info('Countries seeded from SQL file.');
    }
}
