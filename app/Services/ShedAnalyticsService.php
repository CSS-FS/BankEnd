<?php

namespace App\Services;

use App\Models\Chart;
use App\Models\Flock;
use App\Models\IotDataLog;
use App\Models\ProductionLog;
use App\Models\Shed;
use App\Models\WeightLog;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShedAnalyticsService
{
    private $shedId;

    private $shed;

    private $farm;

    private $latestFlock;

    private $latestProductionLog;

    private $latestWeightLog;

    private $chartData;

    public function __construct($shedId)
    {
        $this->shedId = $shedId;
        $this->shed = Shed::with('farm')->find($this->shedId);
        $this->farm = $this->shed->farm;

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

    public function ShedOverview(): array
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
        $cvDesc = $this->cvLevel($cv);
        $uniformity = number_format(1 / $cv, 2);

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

    private function cvLevel($cv)
    {
        if ($cv < 10) {
            return 'Excellent - Highly Stable';
        } elseif ($cv < 20) {
            return 'Moderate - Acceptable Stability';
        } elseif ($cv < 30) {
            return 'Poor - Low Stability';
        } else {
            return 'Unacceptable - Very High Variability';
        }
    }

    public function EnvironmentalMonitoring(): array
    {
        $outdoorDataService = new OutdoorEnvironmentalDataService;

        // -----------------------------
        // 1) Outdoor snapshot (safe)
        // -----------------------------
        $currentOutdoorData = $outdoorDataService->getLatest($this->farm->id);

        $outTemperature = (float) ($currentOutdoorData->temperature ?? 0.0);

        // FIX: number_format() must receive a number; also it returns string, so cast to float if needed
        $outHumidity = (float) number_format((float) ($currentOutdoorData->humidity ?? 0.0), 1);

        $outAirVelocity = (int) ceil((float) ($currentOutdoorData->wind_speed ?? 0.0));
        $outAirPressure = (int) ceil((float) ($currentOutdoorData->pressure ?? 0.0));

        // -----------------------------
        // 2) Indoor snapshot (safe)
        // -----------------------------
        $currentIotData = $this->getLatestHourlyMetrics($this->shedId) ?? [];

        $inTemperature = (float) data_get($currentIotData, 'temp1.avg', 0.0);
        $inHumidity = (float) data_get($currentIotData, 'humidity.avg', 0.0);

        // Anchor time: prefer latest temp1 record_time, else now()
        $anchorTime = data_get($currentIotData, 'temp1.record_time')
            ? Carbon::parse(data_get($currentIotData, 'temp1.record_time'))
            : now();

        // -----------------------------
        // 3) Gas cards (NH3 / CO2)
        // -----------------------------
        $inNH3 = $this->makeThresholdCard(
            (float) data_get($currentIotData, 'nh3.avg', 0.0),
            threshold: 25,
            unit: 'ppm'
        );

        $inCO2 = $this->makeThresholdCard(
            (float) data_get($currentIotData, 'co2.avg', 0.0),
            threshold: 3000,
            unit: 'ppm'
        );

        $inAirVelocity = (int) ceil((float) data_get($currentIotData, 'air_velocity.avg', 0.0));
        $inAirPressure = (int) ceil((float) data_get($currentIotData, 'air_pressure.avg', 0.0));

        // -----------------------------
        // 4) Last 24 hours chart (consistent 24 points)
        // -----------------------------
        $since24 = $anchorTime->copy()->subHours(24)->startOfHour();
        $end24 = $anchorTime->copy()->startOfHour(); // last completed hour boundary (stable)

        // Create continuous hourly labels (24)
        $hourSlots = collect(range(0, 23))->map(function ($i) use ($since24) {
            $dt = $since24->copy()->addHours($i);

            return [
                'key' => $dt->format('Y-m-d H:00:00'), // stable hour key
                'label1' => $dt->format('H:i'),
                'label2' => $dt->format('H A'),
            ];
        });

        $labels = $hourSlots->pluck('label1')->all();
        $labels2 = $hourSlots->pluck('label2')->all();

        // Fetch indoor hourly averages for last 24h window
        $rows24 = IotDataLog::query()
            ->select(['record_time', 'parameter', 'avg_value'])
            ->where('shed_id', $this->shedId)
            ->where('time_window', 'hourly')
            ->whereIn('parameter', ['temp1', 'temp2', 'humidity'])
            ->whereBetween('record_time', [$since24, $end24])
            ->orderBy('record_time')
            ->get();

        // Map: [parameter][hourKey] => avg_value
        $map24 = $rows24->groupBy('parameter')->map(function (Collection $group) {
            // Key by hour bucket
            return $group->keyBy(fn ($r) => Carbon::parse($r->record_time)->format('Y-m-d H:00:00'));
        });

        $seriesTemp1 = $hourSlots->map(function ($slot) use ($map24) {
            $r = data_get($map24, 'temp1.'.$slot['key']);

            return $r ? (float) $r->avg_value : null;
        })->all();

        $seriesHum = $hourSlots->map(function ($slot) use ($map24) {
            $r = data_get($map24, 'humidity.'.$slot['key']);

            return $r ? (float) $r->avg_value : null;
        })->all();

        $temperatureHumidityTrend = [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Shed Temperature',   'data' => $seriesTemp1],
                ['label' => 'Relative Humidity',  'data' => $seriesHum],
            ],
        ];

        // Outdoor hourly for same 24h slots and align
        $outdoorRows = $outdoorDataService->getHourlyData($since24, $end24);

        // Build outdoor map by hourKey (adjust field name if different)
        $outdoorMap = collect($outdoorRows)->keyBy(function ($r) {
            // try record_time first; adjust if your service uses another field name
            $rt = data_get($r, 'recorded_at') ?? data_get($r, 'time') ?? null;

            return $rt ? Carbon::parse($rt)->format('Y-m-d H:00:00') : null;
        });

        $seriesOutdoor = $hourSlots->map(function ($slot) use ($outdoorMap) {
            $r = $outdoorMap->get($slot['key']);

            return $r ? (float) data_get($r, 'temperature', null) : null;
        })->all();

        $temperatureIO = [
            'labels' => $labels2,
            'datasets' => [
                'indoor' => $seriesTemp1,
                'outdoor' => $seriesOutdoor,
            ],
        ];

        // -----------------------------
        // 5) Heatmap: 24 hours × last 7 days (complete grid)
        // -----------------------------
        $days = 7;
        $startDay = $anchorTime->copy()->subDays($days - 1)->startOfDay();
        $endDay = $anchorTime->copy()->endOfDay();

        $dateCols = collect(range(0, $days - 1))
            ->map(fn ($i) => $startDay->copy()->addDays($i)->format('Y-m-d'))
            ->all();

        $hours = collect(range(0, 23))
            ->map(fn ($h) => str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00')
            ->all();

        $rowsHeat = IotDataLog::query()
            ->select(['record_time', 'max_value'])
            ->where('shed_id', $this->shedId)
            ->where('time_window', 'hourly')
            ->where('parameter', 'temp1')
            ->whereBetween('record_time', [$startDay, $endDay])
            ->orderBy('record_time')
            ->get();

        // mapHeat[hour][date] => max_value
        $mapHeat = [];
        foreach ($rowsHeat as $r) {
            $dt = Carbon::parse($r->record_time);
            $hour = $dt->format('H:00');
            $date = $dt->format('Y-m-d');
            $mapHeat[$hour][$date] = round((float) $r->max_value, 2);
        }

        // build full series with null fills
        $heatMapData = [];
        foreach ($hours as $hour) {
            $dataPoints = [];
            foreach ($dateCols as $date) {
                $dataPoints[] = [
                    'x' => $date,
                    'y' => $mapHeat[$hour][$date] ?? null,
                ];
            }
            $heatMapData[] = [
                'name' => $hour,
                'data' => $dataPoints,
            ];
        }

        // -----------------------------
        // Final return (stable keys)
        // -----------------------------
        return [
            'outTemperature' => $outTemperature,
            'outHumidity' => $outHumidity,
            'outAirVelocity' => $outAirVelocity,
            'outAirPressure' => $outAirPressure,

            'inTemperature' => $inTemperature,
            'inHumidity' => $inHumidity,
            'inNH3' => $inNH3,
            'inCO2' => $inCO2,
            'inAirVelocity' => $inAirVelocity,
            'inAirPressure' => $inAirPressure,

            'temperatureHumidityTrend' => $temperatureHumidityTrend,
            'temperatureIO' => $temperatureIO,
            'heatMapData' => $heatMapData,
        ];
    }

    /**
     * Create threshold card used by frontend: {v,d,c}
     */
    private function makeThresholdCard(float $raw, float $threshold, string $unit = ''): array
    {
        $val = (int) ceil($raw);
        $ok = $val < $threshold;

        return [
            'v' => $val,
            'd' => $ok ? 'Within Limits' : '↑ Above threshold',
            'c' => $ok ? 'text-success' : 'text-danger',
            'u' => $unit, // optional, helpful for frontend
        ];
    }

    public function FlockHealth(): array
    {
        $cv = number_format($this->latestWeightLog?->coefficient_of_variation, 2);
        $cvDesc = $this->cvLevel($cv);
        $uniformity = number_format(1 / $cv, 2);

        $todayMortality = $this->latestProductionLog->total_mortality_count ?? 0;

        if ($this->latestProductionLog->day_medicine == null && $this->latestProductionLog->night_medicine == null) {
            $vaccinationStatus = [
                'status' => 'Pending',
                'since' => (int) abs(Carbon::now()->diffInDays($this->latestProductionLog->production_log_date)),
            ];
        } else {
            $vaccinationStatus = [
                'status' => 'Done',
                'since' => (int) abs(Carbon::now()->diffInDays($this->latestProductionLog->production_log_date)),
            ];
        }

        // Mortality Pattern
        $filters = [
            'start_date' => $this->latestFlock->start_date,
            'end_date' => Carbon::now(),
            'flock_id' => $this->latestFlock->id,
        ];
        $mortalityRate = $this->getMortalityRateData($filters);

        $healthScore = $this->flockHealthScorer(
            $cv,
            $todayMortality,
            $this->latestProductionLog->net_count,
            $mortalityRate,
            vaccinationStatus: ['due' => 3, 'done' => 7, 'late' => 0],
            days: $filters['start_date']->diffInDays($filters['end_date'])
        );

        // CV chart
        $results = DB::table('weight_logs as w')
            ->select([
                'p.age',
                DB::raw('COALESCE(ROUND((1 / w.coefficient_of_variation), 2), 0) AS uniformity'),
            ])
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->where('w.flock_id', '=', $this->latestFlock->id)
            ->orderBy('p.age')
            ->get();

        $weightDistribution = [
            'labels' => $results->pluck('age')->toArray(),
            'data' => $results->pluck('uniformity')->toArray(),
        ];

        return [
            'healthScore' => $healthScore,
            'cv' => $cv,
            'uniformity' => $uniformity,
            'cvDesc' => $cvDesc,
            'todayMortality' => $todayMortality,
            'totalMortality' => $this->latestProductionLog->todate_mortality_count ?? 0,
            'vaccinationStatus' => $vaccinationStatus,
            'weightDistribution' => $weightDistribution,
            'mortalityRate' => [
                'x' => is_array($mortalityRate)
                    ? array_column($mortalityRate, 'x')
                    : $mortalityRate->pluck('x')->toArray(),
                'y' => is_array($mortalityRate)
                    ? array_column($mortalityRate, 'y')
                    : $mortalityRate->pluck('y')->toArray(),
            ],
        ];
    }

    public function OperationalEfficiency(): array
    {
        $rows = DB::table('weight_logs as w')
            ->select([
                'p.age',
                'w.feed_efficiency',
                'w.avg_weight',
                'w.avg_weight_gain',
                'cd.avg_daily_gain as expected_gain',
                DB::raw('p.total_feed_consumed / 1000 as feed'),
                'p.total_water_consumed as water',
            ])
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->join('chart_data as cd', function ($join) {
                $join->on('cd.day', '=', 'p.age')
                    ->where('cd.type', '=', 'General');
            })
            ->where('w.flock_id', '=', $this->latestFlock->id)
            ->get();

        $lastRow = $rows->last();
        $g = $this->gcd($lastRow->feed, $lastRow->water);
        $f = $lastRow->feed / $g;
        $w = $lastRow->water / $g;
        $feedWaterRatio = "{$f} : {$w}";

        $val = number_format($this->latestWeightLog->feed_efficiency * 100, 2);
        if ($val >= 90) {
            $desc = 'Excellent';
        } elseif ($val >= 80) {
            $desc = 'Good';
        } elseif ($val >= 70) {
            $desc = 'Average';
        } else {
            $desc = 'Need Improvements';
        }

        $feedEfficiency = [
            'v' => $val,
            'desc' => $desc,
            'level' => ($val >= 70) ? 'positive' : 'negative',
        ];

        $results = collect(DB::select('
                        WITH FlockTotals AS (
                                SELECT
                                    e.flock_id,
                                    SUM(e.amount) AS total,
                                    SUM(e.amount) / f.chicken_count AS bird_cost
                                FROM farm_expenses AS e
                                INNER JOIN flocks AS f ON f.id = e.flock_id
                                WHERE e.shed_id = ?
                                GROUP BY e.flock_id
                            )
                            SELECT
                                cur.flock_id,
                                cur.total,
                                cur.bird_cost,
                                CASE
                                    WHEN cur.total = 0 THEN 0
                                    ELSE ((cur.total - IFNULL(prev.total, 0)) * 100 / cur.total)
                                END AS diff_amount,
                                (cur.bird_cost - IFNULL(prev.bird_cost, 0)) AS diff_bird_cost
                            FROM FlockTotals cur
                            LEFT JOIN FlockTotals prev
                                ON prev.flock_id = (
                                    SELECT MAX(x.flock_id)
                                    FROM FlockTotals x
                                    WHERE x.flock_id < cur.flock_id
                                )
                            ORDER BY cur.flock_id;',
            [$this->shedId]
        ))->last();

        $trend = ($results->diff_amount > 0) ? ' Increased from Last Flock' : ' Decreased from Last Flock';
        $expense = [
            'totalExpense' => 'PKR '.number_format($results->total, 1),
            'diffExpense' => number_format($results->diff_amount, 2).'%'.$trend,
            'levelExpense' => ($results->diff_amount > 0) ? 'negative' : 'positive',
        ];

        $trend = ($results->diff_bird_cost) ? ' Increased from Last Flock' : ' Decreased from Last Flock';
        $cost = [
            'birdCost' => number_format($results->bird_cost, 2),
            'diffBirdCost' => 'PKR '.number_format($results->diff_bird_cost, 2).$trend,
            'levelBirdCost' => ($results->diff_bird_cost > 0) ? 'negative' : 'positive',
        ];

        $projectedHarvest = [
            'age' => 'Day '.$this->latestProductionLog->age,
            'weight' => number_format($this->latestWeightLog->avg_weight, 1).' g',
            'expected_weight' => number_format($this->chartData->last()->weight, 1).' g',
        ];

        // Feed Efficiency and Weight Gain
        $feedUtilization = [
            'feed' => $rows->pluck('feed')->toArray(),
            'feed_efficiency' => $rows->pluck('feed_efficiency')->toArray(),
            'weight_gain' => $rows->pluck('avg_weight_gain')->toArray(),
            'expected_gain' => $rows->pluck('expected_gain')->toArray(),
        ];

        // Energy Break Down
        $forecaster = new GrowthForecastService;
        $complianceChart = $forecaster->compliancePie($this->latestFlock->id);

        // AI-Powered Growth Forecast

        $aiPoweredGrowth = $forecaster->buildGrowthSeries($this->latestFlock->id);

        return [
            'feedEfficiency' => $feedEfficiency,
            'expense' => $expense,
            'cost' => $cost,
            'feedWaterRatio' => $feedWaterRatio,
            'projectedHarvest' => $projectedHarvest,
            'feedUtilization' => $feedUtilization,
            'complianceChart' => $complianceChart,
            'aiPoweredGrowth' => $aiPoweredGrowth,
        ];
    }

    // Helper Functions
    /**
     * Get latest metric rows within the last hour for the selected parameters.
     */
    public function getLatestHourlyMetrics(
        int $shedId,
        ?int $deviceId = null,
        array $parameters = ['temp1', 'temp2', 'humidity', 'nh3', 'co2']
    ): Collection {
        $base = DB::table('iot_data_logs')
            ->select([
                'id',
                'shed_id',
                'device_id',
                'parameter',
                'min_value',
                'max_value',
                'avg_value',
                'record_time',
                'time_window',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY parameter ORDER BY record_time DESC, id DESC) AS rn'),
            ])
            ->where('shed_id', '=', $shedId)
            ->whereIn('parameter', $parameters);

        if ($deviceId !== null) {
            $base->where('device_id', $deviceId);
        }

        $result = DB::table(DB::raw("({$base->toSql()}) as cte"))
            ->mergeBindings($base)
            ->select('cte.*')
            ->where('cte.rn', '=', 1)
            ->get();

        return $result->mapWithKeys(function ($r) {
            return [
                $r->parameter => [
                    'min' => $r->min_value,
                    'max' => $r->max_value,
                    'avg' => $r->avg_value,
                    'record_time' => $r->record_time,
                    'device_id' => $r->device_id,
                    'shed_id' => $r->shed_id,
                ],
            ];
        });
    }

    public function getMortalityRateData(array $filters = []): array
    {
        $start_date = $filters['start_date'] ?: now()->subDays(30)->toDateString();
        $end_date = $filters['end_date'] ?: now()->toDateString();
        $flock_id = $filters['flock_id'] ?: null;

        if ($flock_id === null) {
            return [];
        }

        $sql = "WITH plw AS (
                  SELECT
                    pl.flock_id,
                    f.NAME AS flock_name,
                    s.name AS shed_name,
                    DATE(pl.production_log_date) AS d,
                    pl.age,
                    (pl.day_mortality_count + pl.night_mortality_count) AS deaths,
                    pl.net_count AS end_count
                  FROM
                    production_logs pl
                    JOIN flocks f ON f.id = pl.flock_id
                    JOIN sheds s ON s.id = f.shed_id
                  WHERE
                    pl.production_log_date BETWEEN '{$start_date}'
                    AND '{$end_date}'
                    AND pl.flock_id = {$flock_id}
                ),
                x AS (
                  SELECT
                    plw.*,
                    LAG(plw.end_count) OVER (PARTITION BY plw.flock_id ORDER BY plw.d) AS prev_end
                  FROM
                    plw
                ) SELECT
                  x.flock_id,
                  x.flock_name,
                  x.shed_name,
                  x.d,
                  x.age,
                  CASE
                    WHEN COALESCE(x.prev_end, x.end_count + x.deaths) = 0 THEN
                      0
                    ELSE
                      (x.deaths * 1.0) / COALESCE(x.prev_end, x.end_count + x.deaths)
                  END AS mortality_rate
                FROM
                  x
                ORDER BY
                  x.flock_id,
                  x.d";

        $rows = DB::select($sql);

        $series = [];
        foreach ($rows as $r) {
            $series[] = [
                'x' => $r->age,
                'y' => round(((float) $r->mortality_rate) * 100, 4),
            ];
        }

        return $series;
    }

    /**
     * Calculate flock health score (0..100) and sub-scores for debugging/UI.
     *
     * Expected inputs:
     * - $cv: float (coefficient of variation of body weight) e.g., 11.23
     * - $todayMortality: int (deaths today)
     * - $aliveBirds: int (current alive birds; must be > 0 for % calculations)
     * - $mortalityRate: array|Collection (daily mortality stats) OR already aggregated data.
     *      Supported shapes:
     *      A) list of rows with ['date' => 'YYYY-MM-DD', 'mortality_percent' => 0.08] (percent, not fraction)
     *      B) list of rows with ['date' => 'YYYY-MM-DD', 'mortality_count' => 12] (count per day)
     *         -> will be converted to percent using $aliveBirds (approx) if no daily alive is provided
     * - $vaccinationStatus: array with:
     *      ['due' => int, 'done' => int, 'late' => int (optional)]
     *
     * Returns:
     *  [
     *    'score' => 0..100,
     *    'subscores' => [...],
     *    'inputs' => [...],
     *    'bands' => 'Excellent|Good|Watch|Risk|Critical'
     *  ]
     */
    private function flockHealthScorer(
        float $cv,
        int $todayMortality,
        int $aliveBirds,
        $mortalityRate,
        array $vaccinationStatus = [],
        int $days = 7
    ) {
        $aliveBirds = max(1, $aliveBirds);

        // ---------- Helpers ----------
        $clip = static fn ($v, $min = 0, $max = 100) => max($min, min($max, $v));

        // ---------- A) CV score ----------
        // CV <= 8 => 100, CV >= 20 => 0
        $S_cv = $clip(100 * (20 - $cv) / (20 - 8));

        // ---------- B) Today mortality score ----------
        $m_today_pct = 100 * ($todayMortality / $aliveBirds);          // percent
        $S_today = $clip(100 * exp(-($m_today_pct / 0.15)));           // smoother penalty

        // ---------- C) Mortality last-7-days + trend ----------
        // Normalize mortalityRate into daily percentages list: [['date'=>..., 'pct'=>...], ...]
        $daily = self::normalizeDailyMortality($mortalityRate, $aliveBirds);

        // Sort by date asc
        usort($daily, fn ($a, $b) => strcmp($a['date'], $b['date']));

        // last N days (including today-1 if you want; here: last N dates available)
        $lastN = array_slice($daily, -$days);
        $prevN = array_slice($daily, -2 * $days, $days);

        $m7 = self::avg(array_column($lastN, 'pct'));      // avg daily mortality percent
        $m7_prev = self::avg(array_column($prevN, 'pct'));

        $trend = $m7 - $m7_prev; // positive => worse

        $S_m7 = $clip(100 * exp(-($m7 / 0.12)));
        $S_trend = $clip(100 - 400 * max(0, $trend));     // penalize only if increasing
        $S_mort = 0.7 * $S_m7 + 0.3 * $S_trend;

        // ---------- D) Vaccination compliance ----------
        $due = (int) Arr::get($vaccinationStatus, 'due', 0);
        $done = (int) Arr::get($vaccinationStatus, 'done', 0);
        $late = (int) Arr::get($vaccinationStatus, 'late', 0);

        $dueSafe = max(1, $due);
        $S_vac = $clip(100 * ($done / $dueSafe) - 10 * $late);

        // ---------- Final weighted score ----------
        $score = 0.30 * $S_cv + 0.20 * $S_today + 0.35 * $S_mort + 0.15 * $S_vac;
        $score = (float) $clip(round($score, 2));

        return [
            'score' => $score,
            'band' => self::band($score),
            'subscores' => [
                'cv' => round($S_cv, 2),
                'today_mortality' => round($S_today, 2),
                'mortality_7day' => round($S_m7, 2),
                'mortality_trend' => round($S_trend, 2),
                'mortality_combined' => round($S_mort, 2),
                'vaccination' => round($S_vac, 2),
            ],
            'inputs' => [
                'cv' => round($cv, 2),
                'todayMortality' => $todayMortality,
                'aliveBirds' => $aliveBirds,
                'today_mortality_percent' => round($m_today_pct, 4),
                'm7_avg_daily_percent' => round($m7, 4),
                'm7_prev_avg_daily_percent' => round($m7_prev, 4),
                'trend_percent_points' => round($trend, 4),
                'vaccination' => [
                    'due' => $due,
                    'done' => $done,
                    'late' => $late,
                ],
                'days_used' => $days,
            ],
        ];
    }

    /**
     * Convert mortalityRate into a list of ['date'=>'YYYY-MM-DD','pct'=>float] (pct not fraction).
     *
     * Supported:
     * - row['mortality_percent'] (already percent)
     * - row['mortality_rate'] (assumed percent)
     * - row['mortality_count'] or row['count'] (converted to percent using aliveBirds approximation)
     */
    private static function normalizeDailyMortality($mortalityRate, int $aliveBirds): array
    {
        $rows = collect($mortalityRate)->map(function ($r) use ($aliveBirds) {
            $date = $r['date'] ?? $r['day'] ?? $r['record_date'] ?? null;
            if (! $date) {
                // If your series uses record_time, normalize to date:
                $rt = $r['record_time'] ?? null;
                $date = $rt ? Carbon::parse($rt)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            }

            if (isset($r['mortality_percent'])) {
                $pct = (float) $r['mortality_percent'];
            } elseif (isset($r['mortality_rate'])) {
                $pct = (float) $r['mortality_rate'];
            } elseif (isset($r['mortality_count']) || isset($r['count'])) {
                $cnt = (int) ($r['mortality_count'] ?? $r['count']);
                $pct = 100 * ($cnt / max(1, $aliveBirds));
            } else {
                $pct = 0.0;
            }

            return [
                'date' => Carbon::parse($date)->format('Y-m-d'),
                'pct' => (float) $pct,
            ];
        });

        // If multiple rows for same date, keep max (or avg). Here: sum is usually correct for daily counts,
        // but for percents it depends. We'll take average by date for safety.
        return $rows
            ->groupBy('date')
            ->map(function ($g, $date) {
                return [
                    'date' => $date,
                    'pct' => self::avg($g->pluck('pct')->all()),
                ];
            })
            ->values()
            ->all();
    }

    private static function avg(array $vals): float
    {
        $vals = array_values(array_filter($vals, fn ($v) => $v !== null));
        if (count($vals) === 0) {
            return 0.0;
        }

        return array_sum($vals) / count($vals);
    }

    private static function band(float $score): array
    {
        if ($score >= 90) {
            return [
                'level' => 'Excellent',
                'progress' => 'success',
            ];
        }
        if ($score >= 75) {
            return [
                'level' => 'Good',
                'progress' => 'success',
            ];
        }
        if ($score >= 60) {
            return [
                'level' => 'Watch',
                'progress' => 'warning',
            ];
        }
        if ($score >= 40) {
            return [
                'level' => 'Rist',
                'progress' => 'danger',
            ];
        }

        return [
            'level' => 'Critical',
            'progress' => 'danger',
        ];
    }

    /**
     * @return mixed
     */
    private function gcd($a, $b)
    {
        return $b ? $this->gcd($b, $a % $b) : $a;
    }
}
