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

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // Use ON CONFLICT to skip duplicates on PostgreSQL
            $sql = str_replace(
                'INSERT INTO countries',
                'INSERT INTO countries',
                $sql
            );
            // Remove trailing semicolon, add ON CONFLICT clause
            $sql = rtrim(trim($sql), ';');
            $sql .= ' ON CONFLICT (country) DO NOTHING;';
        }

        DB::unprepared($sql);

        $this->command?->info('Countries seeded from SQL file.');
    }
}
