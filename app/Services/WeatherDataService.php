<?php

namespace App\Services;

use App\Models\Farm;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\Facades\Http;

class WeatherDataService
{
    protected $client;

    protected $table;

    protected $weatherApiKey;

    public function __construct()
    {
        $this->client = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $this->table = 'weather-data';
        $this->weatherApiKey = env('WEATHER_API_KEY');
    }

    /**
     * Fetch weather for all farms and save to DynamoDB.
     */
    public function fetchAndStoreWeather()
    {
        $farms = Farm::all();

        foreach ($farms as $farm) {
            $weatherData = $this->fetchWeather($farm);

            if ($weatherData) {
                $this->saveToDynamoDB($farm->id, $weatherData);
            }
        }
    }

    /**
     * Call WeatherAPI to get realtime weather.
     */
    protected function fetchWeather(Farm $farm)
    {
        // Prefer lat/long if available, otherwise use city name
        $location = $farm->latitude && $farm->longitude
            ? "{$farm->latitude},{$farm->longitude}"
            : optional($farm->city)->name;

        if (! $location) {
            return null; // Skip if no location
        }

        $response = Http::get('http://api.weatherapi.com/v1/current.json', [
            'key' => $this->weatherApiKey,
            'q' => $location,
            'aqi' => 'yes',
        ]);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Save weather data to DynamoDB.
     */
    protected function saveToDynamoDB($farmId, $weatherData)
    {
        $timestamp = time();

        $this->client->putItem([
            'TableName' => $this->table,
            'Item' => [
                'farm_id' => ['N' => (string) $farmId],
                'timestamp' => ['N' => (string) $timestamp],
                'data' => ['S' => json_encode($weatherData)],
            ],
        ]);
    }
}
