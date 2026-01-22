<?php

namespace App\Services;

use App\Models\Chart;
use App\Models\Flock;
use App\Models\ProductionLog;
use App\Models\WeightLog;
use Illuminate\Support\Facades\DB;

class ShedAnalyticsService
{
    private $shedId;

    private $latestFlock;

    private $latestProductionLog;

    private $latestWeightLog;

    private $chartData;

    public function __construct($shedId)
    {
        $this->shedId = $shedId;
        $this->latestFlock = Flock::where('shed_id', $this->shedId)
            ->latest('start_date')
            ->first();

        $this->latestProductionLog = ProductionLog::with('weightLog')
            ->where('flock_id', $this->latestFlock->id)
            ->latest('production_log_date')
            ->first();

        $this->latestWeightLog = WeightLog::where('flock_id', $this->latestFlock->id)
            ->latest('production_log_id')
            ->first();

        $chart = Chart::with('data')
            ->where('is_active', true)
            ->first();

        $this->chartData = $chart?->data
            ?->filter(fn ($row) => $row->type === 'General' &&
                $row->day <= $this->latestProductionLog->age
            )
            ->sortBy('day')        // ensure last() is the max day
            ->values()
            ?? collect();
    }

    public function ShedOverview()
    {
        $startFlockSize = number_format($this->latestFlock->chicken_count, 0);
        $currentFlockSize = number_format($this->latestProductionLog->net_count, 0);
        $currentFlockAge = $this->latestProductionLog->age;
        $dailyMortalityRate = number_format(($this->latestProductionLog->total_mortality_count / $this->latestProductionLog->net_count) * 100, 2);
        $cumulativeMortalityRate = number_format(($this->latestProductionLog->todate_mortality_count / $this->latestFlock->chicken_count) * 100, 2);
        $livability = number_format($this->latestProductionLog->livability, 2);
        $feedConversionRatio = number_format($this->latestWeightLog?->feed_conversion_ratio, 3);
        $targetFCR = number_format(optional($this->chartData->last())->fcr, 3);   // null-safe
        $fcrDiff = number_format(($feedConversionRatio - $targetFCR) / $targetFCR, 2);
        $pefScore = number_format($this->latestWeightLog?->production_efficiency_factor, 2);
        $pefRatio = number_format(1 / $fcrDiff, 2);
        $avgDailyGain = number_format($this->latestWeightLog?->avg_weight_gain, 2);
        $targetDG = number_format(optional($this->chartData->last())->daily_gain, 2);
        /*
        General Rule for Poultry Uniformity: A CV of 8–10% is considered good for market-age broilers,
        meaning 80% or more of the birds fall within ±10% of the average body weight.
        Highly Stable/Uniform: A CV below 10% is generally considered low variability (high stability) in poultry experiments.
        Acceptable Stability: A CV between 10% and 20% is considered average or acceptable.
        High Variability (Low Stability): A CV from 20% to 30% is considered high, and above 30% is considered very high.
        */
        $cv = number_format($this->latestWeightLog?->coefficient_of_variation, 2);
        if ($cv < 10) {
            $cvDesc = 'Excellent - Highly Stable';
        } elseif ($cv < 20) {
            $cvDesc = 'Moderate - Acceptable Stability';
        } elseif ($cv < 30) {
            $cvDesc = 'Poor - Low Stability';
        } else {
            $cvDesc = 'Unacceptable - Very High Variability';
        }

        $uniformity = $this->latestWeightLog?->uniformity;

        // Growth Performance Vs Target
        $growthData = DB::table('weight_logs as w')
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->join('chart_data as cd', function ($join) {
                $join->on('cd.day', '=', 'p.age')
                    ->where('cd.type', '=', 'General');
            })
            ->select(
                'p.age',
                DB::raw('w.avg_weight AS actual_weight'), // Use DB::raw for aliasing column names
                DB::raw('cd.weight AS target_weight')
            )
            ->where('w.flock_id', $this->latestFlock->id)
            ->get();

        $growthPerformance = [
            'labels' => $growthData->pluck('age')
                ->map(fn ($v) => 'Day '.$v),
            'actual_weights' => $growthData->pluck('actual_weight')->values()->all(),
            'target_weights' => $growthData->pluck('target_weight')->values()->all(),
        ];

        // Last 7 days feed and water consumption
        $consumptionData = DB::table('production_logs as p')
            ->join('chart_data as cd', function ($join) {
                $join->on('cd.day', '=', 'p.age')
                    ->where('cd.type', 'General');
            })
            ->where('p.flock_id', $this->latestFlock->id)
            ->select(
                'p.age',
                DB::raw('(p.total_feed_consumed / 1000) AS feed_consumed'), // Use DB::raw for calculation and alias
                DB::raw('(p.total_water_consumed) AS water_consumed')
            )
            ->orderBy('p.age', 'DESC')
            ->take(7)
            ->get();

        $feedWaterConsumption = [
            'labels' => $consumptionData->pluck('age')
                ->map(fn ($v) => 'Day '.$v),
            'feed_consumed' => $consumptionData->pluck('feed_consumed')->values()->all(),
            'water_consumed' => $consumptionData->pluck('water_consumed')->values()->all(),
        ];

        // Actual and target fcr comparisons
        $fcrData = DB::table('weight_logs as w')
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->join('chart_data as cd', function ($join) {
                $join->on('cd.day', '=', 'p.age')
                    ->where('cd.type', '=', 'General');   // literal value
            })
            ->select(
                'p.age',
                DB::raw('w.feed_conversion_ratio AS actual_fcr'),
                DB::raw('cd.fcr AS target_fcr')
            )
            ->where('w.flock_id', $this->latestFlock->id)
            ->get();

        $fcrComparison = [
            'labels' => $fcrData->pluck('age')
                ->map(fn ($v) => 'Day '.$v),
            'actual_fcr' => $fcrData->pluck('actual_fcr')->values()->all(),
            'target_fcr' => $fcrData->pluck('target_fcr')->values()->all(),
        ];

        // Current flock expense analysis
        $expenseData = DB::table('farm_expenses as e')
            ->join('expense_heads as h', 'h.id', '=', 'e.expense_head_id')
            ->where('e.flock_id', $this->latestFlock->id)
            ->select(
                'h.category',
                DB::raw('SUM(e.amount) AS amount'), // Use DB::raw for aggregate functions
                'e.currency'
            )
            ->groupBy('h.category', 'e.currency')
            ->get();

        $currentFlockExpenses = [
            'labels' => $expenseData->pluck('category'),
            'data' => $expenseData->pluck('amount')->all(),
        ];

        // Critical Alerts
        $alerts = [];

        return compact('currentFlockSize',
            'startFlockSize', 'currentFlockAge', 'dailyMortalityRate', 'cumulativeMortalityRate', 'livability', 'feedConversionRatio',
            'fcrDiff', 'pefScore', 'pefRatio', 'avgDailyGain', 'targetDG', 'cv', 'cvDesc', 'uniformity', 'growthPerformance', 'feedWaterConsumption',
            'targetFCR', 'fcrComparison', 'currentFlockExpenses', 'alerts'
        );
    }

    public function EnvironmentalMonitoring()
    {
        $temperature = 0.0;
        $humidity = 0.0;
        $nh3Level = 0.0;
        $co2Level = 0.0;
        $airVelocity = 0.0;
        $airPressure = 0.0;

        // 24-hours temperature trend
        $temperatureTrend = [];

        // 24-hours humidity trend
        $humidityTrend = [];

        // last 12-hours In-door and Out-door temperatures
        $temperatureIO = [];

        return compact('temperature',
            'humidity', 'nh3Level', 'co2Level', 'airVelocity', 'airPressure',
            'temperatureTrend', 'humidityTrend', 'temperatureIO'
        );
    }

    public function FlockHealth()
    {
        $healthScore = [];
        $cv = 0.0;
        $todayMortality = 0;
        $vaccinationStatus = [];

        // CV chart
        $weightDistribution = [];

        // Mortality Pattern
        $mortalityRate = [];

        return compact('healthScore',
            'cv', 'todayMortality', 'vaccinationStatus',
            'weightDistribution', 'mortalityRate'
        );
    }

    public function OperationalEfficiency()
    {
        $feedEfficiency = 0.0;
        $energyCostPerBird = 0.0;
        $feedWaterRatio = '1.82 : 1';
        $projectedHarvest = [
            'age' => 'Day 42',
            'avg_weight' => '0.0 kg',
        ];

        // Weekly Resource Consumption Trend
        $resourceConsumption = [
            'week_names' => [],
            'feed_consumption' => [],
            'water_consumption' => [],
        ];

        // Energy Break Down
        $energyBreakDown = [];

        // AI-Powered Growth Forecast
        $aiPoweredGrowth = [
            'age' => [],
            'avg_weight' => [
                'value' => 0,
                'flag' => 'actual|predicted',
                'confidence_range' => 0,
            ],
        ];

        return compact('feedEfficiency',
            'energyCostPerBird', 'feedWaterRatio', 'projectedHarvest',
            'resourceConsumption', 'energyBreakDown', 'aiPoweredGrowth'
        );
    }
}
