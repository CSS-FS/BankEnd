<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class PakistanTablesSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('SET session_replication_role = replica');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA FOREIGN_KEYS=0');
        }

        $this->call(PakistanProvinceTableSeeder::class);
        $this->call(PakistanProvinceLocaleTableSeeder::class);
        $this->call(PakistanDivisionsTableSeeder::class);
        $this->call(PakistanDivisionsLocaleTableSeeder::class);
        $this->call(PakistanDistrictTableSeeder::class);
        $this->call(PakistanDistrictLocaleTableSeeder::class);
        $this->call(PakistanTehsilTableSeeder::class);
        $this->call(PakistanTehsilLocaleTableSeeder::class);

        if ($driver === 'pgsql') {
            DB::statement('SET session_replication_role = DEFAULT');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA FOREIGN_KEYS=1');
        }
    }
}
