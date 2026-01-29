<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IotDataLogsSeeder extends Seeder
{
    public function run(): void
    {
        $shedId = 1; // TODO: adjust according to your actual shed
        $deviceId = 1; // TODO: adjust according to your actual device

        $days = 30;                 // at least 46 days according to production logs
        $hoursPerDay = 24;
        $totalSlots = $days * $hoursPerDay;

        $start = Carbon::now()->subDays($days)->startOfHour();
//        $start = Carbon::parse('2025-07-15')->subDays($days)->startOfHour();

        // --- Indices for anomalies/spikes (based on hour-slot index) ---
        $tempAnomalyIndices = $this->randomIndices($totalSlots, rand(5, 10));
        $humiditySpikeIndices = $this->randomIndices($totalSlots, rand(5, 10));
        $nh3SpikeIndices = $this->randomIndices($totalSlots, rand(3, 5));
        $co2SpikeIndices = $this->randomIndices($totalSlots, rand(3, 5));

        $rows = [];

        for ($slot = 0; $slot < $totalSlots; $slot++) {
            $timestamp = (clone $start)->addHours($slot);
            $dayIndex = (int) floor($slot / $hoursPerDay); // 0..41

            // ---------------- TEMPERATURES (temp1, temp2) ----------------
            [$tempMin, $tempMax] = $this->temperatureRangeForDay($dayIndex);

            // Base temp2 in the scheduled range
            $temp2Value = $this->randFloat($tempMin, $tempMax, 1);

            // By default temp1 slightly lower than temp2
            $tempDiff = $this->randFloat(0.2, 0.8, 1);
            $temp1Value = $temp2Value - $tempDiff;

            // For anomaly hours, invert or distort relationship
            if (in_array($slot, $tempAnomalyIndices, true)) {
                // temp1 higher than or equal to temp2
                $temp1Value = $temp2Value + $this->randFloat(0.2, 1.0, 1);
            }

            // Build min/max/avg for temp1
            [$t1Min, $t1Max, $t1Avg] = $this->tripleAround($temp1Value, 0.3);
            $rows[] = $this->makeRow($shedId, $deviceId, 'temp1', $t1Min, $t1Max, $t1Avg, $timestamp);

            // Build min/max/avg for temp2
            [$t2Min, $t2Max, $t2Avg] = $this->tripleAround($temp2Value, 0.3);
            $rows[] = $this->makeRow($shedId, $deviceId, 'temp2', $t2Min, $t2Max, $t2Avg, $timestamp);

            // ---------------- HUMIDITY ----------------
            // Brooding (first week) vs grower/finisher
            if ($dayIndex < 7) {
                // First 7 days (0..6): 60–80%
                $humLower = 60;
                $humUpper = 80;
            } else {
                // After first week: 50–70%
                $humLower = 50;
                $humUpper = 70;
            }

            if (in_array($slot, $humiditySpikeIndices, true)) {
                // Spike outside range (either lower or higher)
                if (mt_rand(0, 1) === 0) {
                    $humValue = $this->randFloat(max(30, $humLower - 20), $humLower - 5, 1);
                } else {
                    $humValue = $this->randFloat($humUpper + 5, min(95, $humUpper + 20), 1);
                }
            } else {
                $humValue = $this->randFloat($humLower, $humUpper, 1);
            }

            [$hMin, $hMax, $hAvg] = $this->tripleAround($humValue, 3);
            $rows[] = $this->makeRow($shedId, $deviceId, 'humidity', $hMin, $hMax, $hAvg, $timestamp);

            // ---------------- NH3 (ammonia) ----------------
            // Typical values near ideal (<10 ppm) but within recommended (<20–25 ppm)
            if (in_array($slot, $nh3SpikeIndices, true)) {
                // Spike: above recommended or abnormally low
                if (mt_rand(0, 1) === 0) {
                    // Very high spike
                    $nh3Value = $this->randFloat(30, 60, 1);
                } else {
                    // Very low (near 0)
                    $nh3Value = $this->randFloat(0, 2, 1);
                }
            } else {
                // Mostly in 5–15 ppm (around ideal)
                $nh3Value = $this->randFloat(5, 15, 1);
            }

            [$nh3Min, $nh3Max, $nh3Avg] = $this->tripleAround($nh3Value, 1.5);
            $rows[] = $this->makeRow($shedId, $deviceId, 'nh3', $nh3Min, $nh3Max, $nh3Avg, $timestamp);

            // ---------------- CO2 ----------------
            // Typical values: ideal <1000–1500, below recommended 3000 most of the time
            if (in_array($slot, $co2SpikeIndices, true)) {
                // Spike: above recommended or abnormally low
                if (mt_rand(0, 1) === 0) {
                    $co2Value = $this->randFloat(3500, 6000, 0);
                } else {
                    $co2Value = $this->randFloat(100, 500, 0);
                }
            } else {
                // Normal values: mostly within 800–2000 ppm
                $co2Value = $this->randFloat(800, 2000, 0);
            }

            [$co2Min, $co2Max, $co2Avg] = $this->tripleAround($co2Value, 150);
            $rows[] = $this->makeRow($shedId, $deviceId, 'co2', $co2Min, $co2Max, $co2Avg, $timestamp);
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('iot_data_logs')->insert($chunk);
        }
    }

    /**
     * Decide temp range based on flock age (day index 0-based).
     */
    protected function temperatureRangeForDay(int $dayIndex): array
    {
        // Mapping your schedule into 0-based day ranges
        if ($dayIndex <= 6) {
            // day 0–7 -> approx 0–6
            return [33.9, 35.0];
        } elseif ($dayIndex <= 13) {
            // day 8–14
            return [31.1, 32.2];
        } elseif ($dayIndex <= 20) {
            // day 15–21
            return [28.3, 29.4];
        } elseif ($dayIndex <= 27) {
            // day 22–28
            return [25.6, 26.7];
        } elseif ($dayIndex <= 34) {
            // day 29–35 (around 23.9)
            return [23.4, 24.4];
        } else {
            // day 36–42 (around 21.1)
            return [20.6, 21.6];
        }
    }

    /**
     * Build a row for insertion.
     */
    protected function makeRow(
        int $shedId,
        int $deviceId,
        string $parameter,
        float $min,
        float $max,
        float $avg,
        Carbon $timestamp
    ): array {
        return [
            'shed_id' => $shedId,
            'device_id' => $deviceId,
            'parameter' => $parameter,
            'min_value' => round($min, 2),
            'max_value' => round($max, 2),
            'avg_value' => round($avg, 2),
            'record_time' => $timestamp->toDateTimeString(),
            'time_window' => 'hourly',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Generate min, max, avg around a base value with a small spread.
     */
    protected function tripleAround(float $base, float $spread): array
    {
        $min = $base - $this->randFloat(0, $spread, 2);
        $max = $base + $this->randFloat(0, $spread, 2);

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        $avg = ($min + $max) / 2;

        return [$min, $max, $avg];
    }

    /**
     * Generate a random float between min and max.
     */
    protected function randFloat(float $min, float $max, int $precision = 1): float
    {
        $factor = 10 ** $precision;

        return mt_rand((int) round($min * $factor), (int) round($max * $factor)) / $factor;
    }

    /**
     * Get N unique random indices from 0..($total-1).
     */
    protected function randomIndices(int $total, int $count): array
    {
        $indices = range(0, $total - 1);
        shuffle($indices);

        return array_slice($indices, 0, min($count, $total));
    }

}
