<?php

namespace Database\Seeders;

use App\Models\Farm;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FarmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $farm = Farm::firstOrCreate(
            [
                'name' => 'Sadoki Farm',
                'address' => 'Sadoki',
                'owner_id' => 1,
            ],
            [
                'country' => 'Pakistan',
                'phone_number' => null,
                'contact_person' => null,
                'alerts' => false,
                'notifications' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        DB::table('farm_managers')->updateOrInsert(
            [
                'farm_id' => $farm->id,
                'manager_id' => 2,
            ],
            [
                'link_date' => Carbon::now(),
            ]
        );
    }
}
