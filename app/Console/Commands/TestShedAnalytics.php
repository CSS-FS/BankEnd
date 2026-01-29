<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShedAnalyticsService;
use App\Models\Shed;

class TestShedAnalytics extends Command
{
    protected $signature = 'test:analytics {shed_id}';
    protected $description = 'Test ShedAnalyticsService output';

    public function handle()
    {
        $shedId = $this->argument('shed_id');
        $this->info("Testing analytics for Shed ID: $shedId");

        $shed = Shed::find($shedId);
        if (!$shed) {
            $this->error("Shed not found");
            return;
        }

        try {
            $service = new ShedAnalyticsService($shedId);
            $monitoring = $service->EnvironmentalMonitoring();

            $this->info("Monitoring Data Keys:");
            $this->info(json_encode(array_keys($monitoring), JSON_PRETTY_PRINT));

            if (isset($monitoring['temperatureIO'])) {
                $io = $monitoring['temperatureIO'];
                $this->info("\nTemperature IO Labels Count: " . count($io['labels']));
                $this->info("Indoor Data Count: " . count($io['datasets']['indoor']));
                $this->info("Outdoor Data Count: " . count($io['datasets']['outdoor']));

                $this->info("\nFirst 5 Labels: " . json_encode(array_slice($io['labels'], 0, 5)));
                $this->info("First 5 Indoor: " . json_encode(array_slice($io['datasets']['indoor'], 0, 5)));
                $this->info("First 5 Outdoor: " . json_encode(array_slice($io['datasets']['outdoor']->toArray(), 0, 5)));
            }

            if (isset($monitoring['temperatureHumidityTrend'])) {
                $trend = $monitoring['temperatureHumidityTrend'];
                 $this->info("\nTrend Labels Count: " . count($trend['labels']));
                 // Inspect datasets
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
