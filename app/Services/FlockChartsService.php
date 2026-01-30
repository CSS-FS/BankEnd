<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * You’ll get 4 pie payloads. Render each as doughnut/pie:
 * growth_compliance
 * underperformance_cost (unit PKR)
 * feed_intake_compliance
 * fcr_compliance
 * GET /api/flocks/9/pie-charts?type=General&days=60&feed_price_per_kg=180
 */
class FlockChartsService
{
    public function buildAll(int $flockId, string $type = 'General', int $daysWindow = 60, float $feedPricePerKg = 0.0): array
    {
        $rows = $this->fetchAlignedRows($flockId, $type, $daysWindow);

        // Heuristic: convert production avg_feed_consumed to grams if it looks like kg
        // chart_data.daily_intake is like 12, 28, 48 (grams). avg_feed_consumed may be 0.012, 0.028, 0.048 (kg).
        $needsKgToG = $this->needsKgToGrams($rows);

        $growthCompliance = $this->growthCompliancePie($rows);
        $fcrCompliance = $this->fcrCompliancePie($rows);
        $feedCompliance = $this->feedIntakeCompliancePie($rows, $needsKgToG);
        $underCost = $this->underperformanceCostByPhasePie($rows, $feedPricePerKg);

        return [
            'meta' => [
                'flock_id' => $flockId,
                'type' => $type,
                'days_window' => $daysWindow,
                'feed_price_per_kg' => $feedPricePerKg,
                'feed_unit_conversion' => $needsKgToG ? 'avg_feed_consumed * 1000 (kg→g)' : 'avg_feed_consumed assumed grams',
            ],
            'growth_compliance' => $growthCompliance,
            'underperformance_cost' => $underCost,
            'feed_intake_compliance' => $feedCompliance,
            'fcr_compliance' => $fcrCompliance,
        ];
    }

    /**
     * Align by day(age):
     * - latest production_log per age for this flock
     * - left join weight log
     * - left join chart_data by type & day
     */
    private function fetchAlignedRows(int $flockId, string $type, int $daysWindow)
    {
        $sub = DB::table('production_logs')
            ->selectRaw('MAX(id) as id')
            ->where('flock_id', $flockId)
            ->groupBy('age');

        return DB::query()
            ->fromSub($sub, 'px')
            ->join('production_logs as p', 'p.id', '=', 'px.id')
            ->leftJoin('weight_logs as w', function ($join) use ($flockId) {
                $join->on('w.production_log_id', '=', 'p.id')
                    ->where('w.flock_id', '=', $flockId);
            })
            ->leftJoin('chart_data as cd', function ($join) use ($type) {
                $join->on('cd.day', '=', 'p.age')
                    ->where('cd.type', '=', $type);
            })
            ->select([
                'p.age',
                'p.production_log_date',
                'p.net_count',
                'p.avg_feed_consumed',   // generated column in your SQL
                'w.avg_weight',
                'w.feed_conversion_ratio',
                'cd.weight as target_weight',
                'cd.daily_intake as target_daily_intake',
                'cd.fcr as target_fcr',
            ])
            ->orderByDesc('p.age')
            ->limit(max(1, $daysWindow))
            ->get()
            ->reverse()
            ->values();
    }

    private function needsKgToGrams($rows): bool
    {
        $targetIntake = $rows->pluck('target_daily_intake')->filter(fn ($v) => is_numeric($v))->take(10)->avg();
        $actualFeed = $rows->pluck('avg_feed_consumed')->filter(fn ($v) => is_numeric($v))->take(10)->avg();

        if (! $targetIntake || ! $actualFeed) {
            return false;
        }

        // if target is ~10-200 and actual is ~0.01-0.2, then actual is likely kg
        return $targetIntake > 5 && $actualFeed > 0 && $actualFeed < 2;
    }

    // ----------------------------
    // A) Growth Compliance Pie
    // ----------------------------
    private function growthCompliancePie($rows): array
    {
        $buckets = [
            'Severely Under (≤ -10%)' => 0,
            'Slightly Under (-10% to -3%)' => 0,
            'On Target (-3% to +3%)' => 0,
            'Slightly Over (+3% to +10%)' => 0,
            'Severely Over (≥ +10%)' => 0,
            'Missing (Actual/Target)' => 0,
        ];

        foreach ($rows as $r) {
            $actual = is_numeric($r->avg_weight) ? (float) $r->avg_weight : null;
            $target = is_numeric($r->target_weight) ? (float) $r->target_weight : null;

            if ($actual === null || $target === null || $target <= 0) {
                $buckets['Missing (Actual/Target)']++;

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

        return $this->toChartJsPie('Growth Compliance (Days)', $buckets);
    }

    // ----------------------------
    // B) Underperformance Cost Pie (PKR) by Phase
    // ----------------------------
    private function underperformanceCostByPhasePie($rows, float $feedPricePerKg): array
    {
        // If feed price not provided, still return zeros (front-end can show warning)
        $phase = [
            'Starter (0–14)' => 0.0,
            'Grower (15–28)' => 0.0,
            'Finisher (29+)' => 0.0,
            'Missing (Inputs)' => 0.0,
        ];

        foreach ($rows as $r) {
            $age = (int) $r->age;
            $actual = is_numeric($r->avg_weight) ? (float) $r->avg_weight : null;        // grams/bird
            $target = is_numeric($r->target_weight) ? (float) $r->target_weight : null;  // grams/bird
            $count = is_numeric($r->net_count) ? (float) $r->net_count : null;          // birds
            $tfcr = is_numeric($r->target_fcr) ? (float) $r->target_fcr : null;        // kg feed / kg gain

            if ($actual === null || $target === null || $count === null || $target <= 0 || $count <= 0 || $feedPricePerKg <= 0) {
                // Don’t inflate missing too much; keep as “missing inputs”
                // Only count missing when there was a target & we expected a comparison
                if ($target !== null) {
                    $phase['Missing (Inputs)'] += 0.0;
                }

                continue;
            }

            $gapGPerBird = max(0.0, $target - $actual); // grams
            if ($gapGPerBird <= 0) {
                continue;
            } // not under target => no cost

            // total gap kg = grams/bird * birds / 1000 / 1000
            $gapKgTotal = ($gapGPerBird * $count) / 1000000.0;

            // Use target FCR if available; otherwise approximate with 1.6
            $fcr = ($tfcr !== null && $tfcr > 0) ? $tfcr : 1.6;

            $extraFeedKg = $gapKgTotal * $fcr;
            $costPkr = $extraFeedKg * $feedPricePerKg;

            $bucket = ($age <= 14) ? 'Starter (0–14)' : (($age <= 28) ? 'Grower (15–28)' : 'Finisher (29+)');
            $phase[$bucket] += $costPkr;
        }

        // Round for display
        foreach ($phase as $k => $v) {
            $phase[$k] = round($v, 0);
        }

        return [
            'title' => 'Estimated Underperformance Cost (PKR) by Phase',
            'labels' => array_keys($phase),
            'data' => array_values($phase),
            'unit' => 'PKR',
        ];
    }

    // ----------------------------
    // C) Feed Intake Compliance Pie (Days)
    // ----------------------------
    private function feedIntakeCompliancePie($rows, bool $kgToG): array
    {
        $buckets = [
            'Under Intake (≤ -10%)' => 0,
            'Slightly Under (-10% to -3%)' => 0,
            'On Target (-3% to +3%)' => 0,
            'Slightly Over (+3% to +10%)' => 0,
            'Over Intake (≥ +10%)' => 0,
            'Missing (Actual/Target)' => 0,
        ];

        foreach ($rows as $r) {
            $target = is_numeric($r->target_daily_intake) ? (float) $r->target_daily_intake : null; // grams/bird/day
            $actual = is_numeric($r->avg_feed_consumed) ? (float) $r->avg_feed_consumed : null;     // maybe kg or g

            if ($target === null || $target <= 0 || $actual === null) {
                $buckets['Missing (Actual/Target)']++;

                continue;
            }

            // Convert to grams if needed
            $actualG = $kgToG ? ($actual * 1000.0) : $actual;

            $pct = (($actualG - $target) / $target) * 100.0;

            if ($pct <= -10.0) {
                $buckets['Under Intake (≤ -10%)']++;
            } elseif ($pct < -3.0) {
                $buckets['Slightly Under (-10% to -3%)']++;
            } elseif ($pct <= 3.0) {
                $buckets['On Target (-3% to +3%)']++;
            } elseif ($pct < 10.0) {
                $buckets['Slightly Over (+3% to +10%)']++;
            } else {
                $buckets['Over Intake (≥ +10%)']++;
            }
        }

        return $this->toChartJsPie('Feed Intake Compliance (Days)', $buckets);
    }

    // ----------------------------
    // D) FCR Compliance Pie (Days)  (lower is better)
    // ----------------------------
    private function fcrCompliancePie($rows): array
    {
        $buckets = [
            'Much Better (≤ -8%)' => 0,     // actual much lower than target
            'Slightly Better (-8% to -2%)' => 0,
            'On Target (-2% to +2%)' => 0,
            'Slightly Worse (+2% to +8%)' => 0,
            'Much Worse (≥ +8%)' => 0,
            'Missing (Actual/Target)' => 0,
        ];

        foreach ($rows as $r) {
            $actual = is_numeric($r->feed_conversion_ratio) ? (float) $r->feed_conversion_ratio : null;
            $target = is_numeric($r->target_fcr) ? (float) $r->target_fcr : null;

            if ($actual === null || $target === null || $target <= 0) {
                $buckets['Missing (Actual/Target)']++;

                continue;
            }

            $pct = (($actual - $target) / $target) * 100.0;

            if ($pct <= -8.0) {
                $buckets['Much Better (≤ -8%)']++;
            } elseif ($pct < -2.0) {
                $buckets['Slightly Better (-8% to -2%)']++;
            } elseif ($pct <= 2.0) {
                $buckets['On Target (-2% to +2%)']++;
            } elseif ($pct < 8.0) {
                $buckets['Slightly Worse (+2% to +8%)']++;
            } else {
                $buckets['Much Worse (≥ +8%)']++;
            }
        }

        return $this->toChartJsPie('FCR Compliance (Days)', $buckets);
    }

    private function toChartJsPie(string $title, array $buckets): array
    {
        return [
            'title' => $title,
            'labels' => array_keys($buckets),
            'data' => array_values($buckets),
        ];
    }
}
