<?php

namespace App\Services;

use App\Models\ProductionLog;

class KPIAlertService
{
    /**
     * Industry-standard KPI thresholds for broiler poultry.
     */
    private const THRESHOLDS = [
        'mortality_rate' => [
            'warning'  => 0.5,   // > 0.5% per day
            'critical' => 1.0,   // > 1.0% per day
        ],
        'livability' => [
            'warning'  => 95.0,  // < 95%
            'critical' => 90.0,  // < 90%
        ],
        'fcr' => [
            'warning'  => 2.0,   // > 2.0
            'critical' => 2.5,   // > 2.5
        ],
        'cv' => [
            'warning'  => 10.0,  // > 10%
            'critical' => 15.0,  // > 15%
        ],
    ];

    /**
     * Check KPIs for a production log and return list of breaches.
     *
     * @return array  Empty if all KPIs are normal. Otherwise list of breaches.
     */
    public function check(ProductionLog $log): array
    {
        $log->loadMissing(['flock', 'weightLog']);

        $breaches = [];

        // -------------------------------------------------------
        // 1. Daily Mortality Rate
        // -------------------------------------------------------
        $previousLog      = ProductionLog::where('flock_id', $log->flock_id)
            ->where('id', '<', $log->id)
            ->latest('production_log_date')
            ->first();

        $previousNetCount = $previousLog
            ? $previousLog->net_count
            : ($log->flock->chicken_count ?? 1);

        $totalMortality  = $log->day_mortality_count + $log->night_mortality_count;
        $mortalityRate   = $previousNetCount > 0
            ? round(($totalMortality / $previousNetCount) * 100, 3)
            : 0.0;

        if ($mortalityRate > self::THRESHOLDS['mortality_rate']['critical']) {
            $breaches[] = $this->breach('mortality_rate', 'Mortality Rate', $mortalityRate, self::THRESHOLDS['mortality_rate']['critical'], 'critical', '%');
        } elseif ($mortalityRate > self::THRESHOLDS['mortality_rate']['warning']) {
            $breaches[] = $this->breach('mortality_rate', 'Mortality Rate', $mortalityRate, self::THRESHOLDS['mortality_rate']['warning'], 'warning', '%');
        }

        // -------------------------------------------------------
        // 2. Livability
        // -------------------------------------------------------
        $livability = (float) $log->livability;

        if ($livability < self::THRESHOLDS['livability']['critical']) {
            $breaches[] = $this->breach('livability', 'Livability', $livability, self::THRESHOLDS['livability']['critical'], 'critical', '%');
        } elseif ($livability < self::THRESHOLDS['livability']['warning']) {
            $breaches[] = $this->breach('livability', 'Livability', $livability, self::THRESHOLDS['livability']['warning'], 'warning', '%');
        }

        // -------------------------------------------------------
        // 3. FCR (Feed Conversion Ratio) — only if weight log exists
        // -------------------------------------------------------
        $weightLog = $log->weightLog;

        if ($weightLog && $weightLog->feed_conversion_ratio > 0) {
            $fcr = (float) $weightLog->feed_conversion_ratio;

            if ($fcr > self::THRESHOLDS['fcr']['critical']) {
                $breaches[] = $this->breach('fcr', 'Feed Conversion Ratio (FCR)', $fcr, self::THRESHOLDS['fcr']['critical'], 'critical', '');
            } elseif ($fcr > self::THRESHOLDS['fcr']['warning']) {
                $breaches[] = $this->breach('fcr', 'Feed Conversion Ratio (FCR)', $fcr, self::THRESHOLDS['fcr']['warning'], 'warning', '');
            }
        }

        // -------------------------------------------------------
        // 4. CV / Uniformity — only if weight log exists
        // CV is stored as percentage (e.g. 8.5 = 8.5%)
        // -------------------------------------------------------
        if ($weightLog && $weightLog->coefficient_of_variation > 0) {
            $cv = (float) $weightLog->coefficient_of_variation;

            if ($cv > self::THRESHOLDS['cv']['critical']) {
                $breaches[] = $this->breach('cv', 'Uniformity (CV)', $cv, self::THRESHOLDS['cv']['critical'], 'critical', '%');
            } elseif ($cv > self::THRESHOLDS['cv']['warning']) {
                $breaches[] = $this->breach('cv', 'Uniformity (CV)', $cv, self::THRESHOLDS['cv']['warning'], 'warning', '%');
            }
        }

        return $breaches;
    }

    /**
     * Get the highest severity from a list of breaches.
     */
    public function highestSeverity(array $breaches): string
    {
        $hasCritical = collect($breaches)->contains('severity', 'critical');

        return $hasCritical ? 'critical' : 'warning';
    }

    /**
     * Build a human-readable summary of all breaches.
     */
    public function buildBreachSummary(array $breaches): string
    {
        return collect($breaches)->map(function ($breach) {
            $direction = in_array($breach['kpi'], ['livability'])
                ? 'below'
                : 'above';

            return "• {$breach['label']}: {$breach['value']}{$breach['unit']} "
                 . "({$direction} threshold of {$breach['threshold']}{$breach['unit']}) — "
                 . strtoupper($breach['severity']);
        })->implode("\n");
    }

    /**
     * Build a single breach array.
     */
    private function breach(string $kpi, string $label, float $value, float $threshold, string $severity, string $unit): array
    {
        return [
            'kpi'       => $kpi,
            'label'     => $label,
            'value'     => $value,
            'threshold' => $threshold,
            'severity'  => $severity,
            'unit'      => $unit,
        ];
    }
}
