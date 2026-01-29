<?php

namespace App\Services;

use App\Models\OutdoorEnvironmentalData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OutdoorEnvironmentalDataService
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';

    public function storeHourlyData(array $data): OutdoorEnvironmentalData
    {
        $data['recorded_at'] = Carbon::parse($data['recorded_at'])->startOfHour();

        return OutdoorEnvironmentalData::updateOrCreate(
            ['recorded_at' => $data['recorded_at']],
            $data
        );
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection
     */
    public function getHourlyData(Carbon $start, Carbon $end): Collection
    {
        return OutdoorEnvironmentalData::whereBetween('recorded_at', [$start->startOfHour(), $end->endOfHour()])
            ->orderBy('recorded_at')
            ->get();
    }

    /**
     * @param int $farm_id
     * @return OutdoorEnvironmentalData|null
     */
    public function getLatest(int $farm_id): ?OutdoorEnvironmentalData
    {
        return OutdoorEnvironmentalData::where('farm_id', $farm_id)
            ->latest('recorded_at')
            ->first();
    }

    public function bulkStore(array $dataArray): void
    {
        DB::transaction(function () use ($dataArray) {
            foreach ($dataArray as $data) {
                $this->storeHourlyData($data);
            }
        });
    }

    /**
     * Fetch hourly outdoor data and store in DB.
     *
     * @return int Number of rows upserted (approx; DB upsert returns affected rows depending on driver)
     */
    public function fetchAndStore(
        int $farm_id,
        float $latitude,
        float $longitude,
        int $pastDays = 30): int
    {
        $pastDays = max(1, min($pastDays, 92)); // Open-Meteo usually supports limited history; keep safe

        //        $response = Http::timeout(20)->retry(2, 300)->get(self::BASE_URL, [
        //            'latitude' => $latitude,
        //            'longitude' => $longitude,
        //            'timezone' => 'auto',
        //            'past_days' => $pastDays,
        //            'hourly' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,surface_pressure',
        //        ]);

        $response = Http::timeout(20)
            ->retry(2, 300)
            ->withoutVerifying()
            ->get(self::BASE_URL, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => 'auto',
                'past_days' => $pastDays,
                'hourly' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,surface_pressure',
            ]);

        if (! $response->ok()) {
            throw new \RuntimeException("Open-Meteo API error: HTTP {$response->status()} - {$response->body()}");
        }

        $json = $response->json();

        $hourly = $json['hourly'] ?? null;
        if (! is_array($hourly)) {
            throw new \RuntimeException('Open-Meteo response missing hourly data.');
        }

        $times = $hourly['time'] ?? [];
        $temps = $hourly['temperature_2m'] ?? [];
        $humidity = $hourly['relative_humidity_2m'] ?? [];
        $wind = $hourly['wind_speed_10m'] ?? [];
        $pressure = $hourly['surface_pressure'] ?? [];

        $count = count($times);
        if ($count === 0) {
            return 0;
        }

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $t = $times[$i] ?? null;
            if (! $t) {
                continue;
            }

            // Open-Meteo returns local-time strings based on timezone param.
            // Example: "2026-01-22T13:00"
            $recordedAt = Carbon::parse($t)->format('Y-m-d H:i:s');

            $rows[] = [
                'farm_id' => $farm_id,
                'recorded_at' => $recordedAt,
                'temperature' => $this->toDecimal($temps[$i] ?? null),
                'humidity' => $this->toDecimal($humidity[$i] ?? null),
                'wind_speed' => $this->toDecimalOrNull($wind[$i] ?? null),
                'pressure' => $this->toDecimalOrNull($pressure[$i] ?? null),
                'extra_metrics' => json_encode([
                    'latitude' => $json['latitude'] ?? $latitude,
                    'longitude' => $json['longitude'] ?? $longitude,
                    'timezone' => $json['timezone'] ?? 'auto',
                    'source' => 'open-meteo',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert efficiently in chunks + upsert by recorded_at
        $affected = 0;
        foreach (array_chunk($rows, 1000) as $chunk) {
            $affected += DB::table('outdoor_environmental_data')->upsert(
                $chunk,
                ['farm_id', 'recorded_at'], // unique key
                ['temperature', 'humidity', 'wind_speed', 'pressure', 'extra_metrics', 'updated_at']
            );
        }

        return $affected;
    }

    private function toDecimal($value): float
    {
        // Your schema requires non-null temperature/humidity.
        // If missing, default to 0.00 or throw - choose what you prefer.
        // I default to 0.00 to avoid failing inserts.
        $n = is_numeric($value) ? (float) $value : 0.0;

        return round($n, 2);
    }

    private function toDecimalOrNull($value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
