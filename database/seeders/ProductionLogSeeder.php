<?php

namespace Database\Seeders;

use App\Models\Flock;
use App\Models\ProductionLog;
use App\Models\Shed;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProductionLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataRows = Excel::toArray([], public_path('assets/data/ProductionData.xlsx'))[0];
        $timezone = 'Asia/Karachi';

        foreach ($dataRows as $index => $row) {
            // Optionally skip header
            if ($index == 0) {
                continue;
            }

            // 1. Parse date
            $production_log_date = Carbon::instance(
                ExcelDate::excelToDateTimeObject($row[0])
            )->setTimezone($timezone);

            // 2. Get related flock and opening count
            $flock_id = (int) $row[1];
            $shed_id = (int) $row[2];

            $flock = Flock::find($flock_id);
            $shed = Shed::find($shed_id);

            if (! $shed or ! $flock) {
                continue;
            }

            // 3. Find previous log for net_count
            $lastLog = ProductionLog::where('flock_id', $flock_id)
                ->where('production_log_date', '<', $production_log_date)
                ->orderByDesc('production_log_date')
                ->first();

            $lastNetCount = $lastLog ? $lastLog->net_count : $flock->chicken_count;

            // 4. Calculate net_count and livability
            $day_mortality = (int) $row[3];
            $night_mortality = (int) $row[4];
            $todate_mortality = (int) $row[5];
            $net_count = $lastNetCount - ($day_mortality + $night_mortality);

            $livability = $flock->chicken_count > 0
                ? daily_livability($net_count, $flock->chicken_count)
                : 0;

            // 5. Calculate age
            $age = (int) ($flock->start_date->diffInDays($production_log_date) ?? 0);

            // 6. Create ProductionLog
            ProductionLog::firstOrCreate([
                'production_log_date' => $production_log_date,
                'shed_id' => $shed_id,
                'flock_id' => $flock_id,
                'age' => $age,
                'day_mortality_count' => $day_mortality,
                'night_mortality_count' => $night_mortality,
                'todate_mortality_count' => $todate_mortality,
                'net_count' => $net_count,
                'livability' => $livability,
                'day_feed_consumed' => (float) $row[6],
                'night_feed_consumed' => (float) $row[7],
                'todate_feed_consumed' => (float) $row[8],
                'day_water_consumed' => (float) $row[9],
                'night_water_consumed' => (float) $row[10],
                'todate_water_consumed' => (float) $row[11],
                'is_vaccinated' => (bool) $row[12],
                'day_medicine' => $row[13] ?? '',
                'night_medicine' => $row[14] ?? '',
                'user_id' => 1,
            ]);
        }
    }
}
