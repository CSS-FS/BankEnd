<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GrowthForecastService
{
    public function buildGrowthSeries(int $flockId, string $type = 'General', int $forecastDays = 10): array
    {
        // 1) Actual weights by day (age)
        $actualRows = DB::table('weight_logs as w')
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->where('w.flock_id', $flockId)
            ->select('p.age', 'w.avg_weight')
            ->orderBy('p.age')
            ->get();

        $actualMap = [];
        foreach ($actualRows as $r) {
            $actualMap[(int) $r->age] = (float) $r->avg_weight;
        }

        $lastActualDay = count($actualMap) ? max(array_keys($actualMap)) : null;

        // 2) Target weights by day (chart_data)
        $targetRows = DB::table('chart_data')
            ->where('type', $type)
            ->select('day', 'weight')
            ->orderBy('day')
            ->get();

        $targetMap = [];
        foreach ($targetRows as $r) {
            $targetMap[(int) $r->day] = (float) $r->weight;
        }

        $lastTargetDay = count($targetMap) ? max(array_keys($targetMap)) : null;

        // 3) Decide plotting horizon
        $baseDay = $lastActualDay ?? $lastTargetDay ?? 0;
        $endDay = $baseDay + max(1, $forecastDays);

        // 4) Forecast (only after last actual day)
        $forecastMap = [];
        if ($lastActualDay !== null && count($actualMap) >= 2) {
            $forecastMap = $this->forecastByRegression($actualMap, $lastActualDay, $endDay);
        } elseif ($lastActualDay !== null && count($actualMap) === 1) {
            // only 1 data point: carry forward + small gain (fallback)
            $onlyVal = reset($actualMap);
            for ($d = $lastActualDay + 1; $d <= $endDay; $d++) {
                $onlyVal += 0; // or add a small default gain if you want
                $forecastMap[$d] = $onlyVal;
            }
        }

        // 5) Build aligned arrays for Chart.js (null means gap)
        $labels = range(0, $endDay);

        $actualWeights = [];
        $targetWeights = [];
        $forecastWeights = [];

        foreach ($labels as $day) {
            $actualWeights[] = $actualMap[$day] ?? null;
            $targetWeights[] = $targetMap[$day] ?? null;
            // show forecast only for future days (after last actual)
            $forecastWeights[] = ($lastActualDay !== null && $day > $lastActualDay)
                ? ($forecastMap[$day] ?? null)
                : null;
        }

        return [
            'labels' => $labels,
            'actual_weights' => $actualWeights,
            'target_weights' => $targetWeights,
            'forecast_weights' => $forecastWeights,
            'forecast_days' => $forecastDays,
            'meta' => [
                'flock_id' => $flockId,
                'type' => $type,
                'last_actual_day' => $lastActualDay,
                'end_day' => $endDay,
            ],
        ];
    }

    /**
     * Linear regression on last N points (default 14), then extrapolate.
     * Enforces non-decreasing weights.
     */
    private function forecastByRegression(array $actualMap, int $lastActualDay, int $endDay, int $window = 14): array
    {
        ksort($actualMap);

        $days = array_keys($actualMap);
        $vals = array_values($actualMap);

        $n = count($days);
        $startIdx = max(0, $n - $window);

        $x = array_slice($days, $startIdx);
        $y = array_slice($vals, $startIdx);

        // compute slope and intercept
        $xMean = array_sum($x) / count($x);
        $yMean = array_sum($y) / count($y);

        $num = 0.0;
        $den = 0.0;
        for ($i = 0; $i < count($x); $i++) {
            $dx = $x[$i] - $xMean;
            $dy = $y[$i] - $yMean;
            $num += $dx * $dy;
            $den += $dx * $dx;
        }

        $slope = ($den != 0.0) ? ($num / $den) : 0.0;
        $intercept = $yMean - ($slope * $xMean);

        // extrapolate
        $forecast = [];
        $prev = $actualMap[$lastActualDay] ?? end($y);

        for ($d = $lastActualDay + 1; $d <= $endDay; $d++) {
            $pred = $intercept + $slope * $d;

            // enforce monotonic increasing (growth cannot go down)
            if ($pred < $prev) {
                $pred = $prev;
            }

            // optional: round to 2 decimals
            $pred = round($pred, 2);

            $forecast[$d] = $pred;
            $prev = $pred;
        }

        return $forecast;
    }

    public function compliancePie(int $flockId, string $type = 'General', int $lastDays = 55): array
    {
        // Pull actual + target by day (age)
        // Assumption: production_logs has `age` and weight_logs links via production_log_id
        $rows = DB::table('production_logs as p')
            ->leftJoin('weight_logs as w', function ($join) use ($flockId) {
                $join->on('w.production_log_id', '=', 'p.id')
                    ->where('w.flock_id', '=', $flockId);
            })
            ->leftJoin('chart_data as cd', function ($join) use ($type) {
                $join->on('cd.day', '=', 'p.age')
                    ->where('cd.type', '=', $type);
            })
            ->select(
                'p.age',
                DB::raw('w.avg_weight as actual_weight'),
                DB::raw('cd.weight as target_weight')
            )
            ->whereNotNull('p.age')
            ->orderBy('p.age', 'desc')
            ->limit(max(1, $lastDays))
            ->get()
            ->reverse() // keep chronological if you ever need it later
            ->values();

        $buckets = [
            'Severely Under (≤ -10%)' => 0,
            'Slightly Under (-10% to -3%)' => 0,
            'On Target (-3% to +3%)' => 0,
            'Slightly Over (+3% to +10%)' => 0,
            'Severely Over (≥ +10%)' => 0,
            'Missing Actual' => 0,
        ];

        foreach ($rows as $r) {
            $actual = is_numeric($r->actual_weight) ? (float) $r->actual_weight : null;
            $target = is_numeric($r->target_weight) ? (float) $r->target_weight : null;

            if ($actual === null) {
                $buckets['Missing Actual']++;

                continue;
            }

            // If target curve missing for some day, treat as missing decision baseline
            if ($target === null || $target == 0.0) {
                // You can add another bucket if you want; for now count as missing actual baseline
                $buckets['Missing Actual']++;

                continue;
            }

            $pct = (($actual - $target) / $target) * 100.0;

            if ($pct <= -10.0) {
                $buckets['Severely Under (≤ -10%)']++;
            } elseif ($pct < -3.0) {
                $buckets['Slightly Under (-10% to -3%)']++;
            } elseif ($pct <= 3.0) {
                $buckets['On Target (-3% to +3%)']++;
            } elseif ($pct < 10.0) {
                $buckets['Slightly Over (+3% to +10%)']++;
            } else {
                $buckets['Severely Over (≥ +10%)']++;
            }
        }

        // Return Chart-friendly JSON
        return [
            'labels' => array_keys($buckets),
            'data' => array_values($buckets),
            'meta' => [
                'flock_id' => $flockId,
                'type' => $type,
                'days_window' => $lastDays,
            ],
        ];
    }
}
