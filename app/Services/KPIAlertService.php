<?php

namespace App\Services;

use App\Models\BreedPerformanceStandard;
use App\Models\ProductionLog;

class KPIAlertService
{
    /**
     * Fixed thresholds (not breed-specific).
     */
    private const FIXED_THRESHOLDS = [
        'mortality_rate' => [
            'warning'  => 0.5,   // > 0.5% per day
            'critical' => 1.0,   // > 1.0% per day
        ],
        'livability' => [
            'warning'  => 95.0,  // < 95%
            'critical' => 90.0,  // < 90%
        ],
        'cv' => [
            'warning'  => 10.0,  // > 10%
            'critical' => 15.0,  // > 15%
        ],
    ];

    /**
     * Percentage deviation thresholds for breed-standard KPIs.
     * "lower" means actual is BELOW standard (bad for weight/gain).
     * "higher" means actual is ABOVE standard (bad for FCR).
     */
    private const DEVIATION_THRESHOLDS = [
        'weight' => [
            'warning'  => 10,  // actual < 90% of standard
            'critical' => 20,  // actual < 80% of standard
        ],
        'fcr' => [
            'warning'  => 10,  // actual > 110% of standard
            'critical' => 20,  // actual > 120% of standard
        ],
        'daily_gain' => [
            'warning'  => 15,  // actual < 85% of standard
            'critical' => 30,  // actual < 70% of standard
        ],
    ];

    /**
     * Check KPIs for a production log and return list of breaches.
     */
    public function check(ProductionLog $log): array
    {
        $log->loadMissing(['flock.breed', 'weightLog']);

        $breaches = [];

        // -------------------------------------------------------
        // 1. Daily Mortality Rate (fixed threshold)
        // -------------------------------------------------------
        $previousLog = ProductionLog::where('flock_id', $log->flock_id)
            ->where('id', '<', $log->id)
            ->latest('production_log_date')
            ->first();

        $previousNetCount = $previousLog
            ? $previousLog->net_count
            : ($log->flock->chicken_count ?? 1);

        $totalMortality = $log->day_mortality_count + $log->night_mortality_count;
        $mortalityRate  = $previousNetCount > 0
            ? round(($totalMortality / $previousNetCount) * 100, 3)
            : 0.0;

        if ($mortalityRate > self::FIXED_THRESHOLDS['mortality_rate']['critical']) {
            $breaches[] = $this->breach('mortality_rate', 'Mortality Rate', $mortalityRate, self::FIXED_THRESHOLDS['mortality_rate']['critical'], 'critical', '%');
        } elseif ($mortalityRate > self::FIXED_THRESHOLDS['mortality_rate']['warning']) {
            $breaches[] = $this->breach('mortality_rate', 'Mortality Rate', $mortalityRate, self::FIXED_THRESHOLDS['mortality_rate']['warning'], 'warning', '%');
        }

        // -------------------------------------------------------
        // 2. Livability (fixed threshold)
        // -------------------------------------------------------
        $livability = (float) $log->livability;

        if ($livability < self::FIXED_THRESHOLDS['livability']['critical']) {
            $breaches[] = $this->breach('livability', 'Livability', $livability, self::FIXED_THRESHOLDS['livability']['critical'], 'critical', '%');
        } elseif ($livability < self::FIXED_THRESHOLDS['livability']['warning']) {
            $breaches[] = $this->breach('livability', 'Livability', $livability, self::FIXED_THRESHOLDS['livability']['warning'], 'warning', '%');
        }

        // -------------------------------------------------------
        // 3. CV / Uniformity (fixed threshold)
        // -------------------------------------------------------
        $weightLog = $log->weightLog;

        if ($weightLog && $weightLog->coefficient_of_variation > 0) {
            $cv = (float) $weightLog->coefficient_of_variation;

            if ($cv > self::FIXED_THRESHOLDS['cv']['critical']) {
                $breaches[] = $this->breach('cv', 'Uniformity (CV)', $cv, self::FIXED_THRESHOLDS['cv']['critical'], 'critical', '%');
            } elseif ($cv > self::FIXED_THRESHOLDS['cv']['warning']) {
                $breaches[] = $this->breach('cv', 'Uniformity (CV)', $cv, self::FIXED_THRESHOLDS['cv']['warning'], 'warning', '%');
            }
        }

        // -------------------------------------------------------
        // 4. Breed-Standard KPIs (weight, FCR, daily gain)
        // -------------------------------------------------------
        $standard = $this->getBreedStandard($log);

        if ($standard) {
            // 4a. Weight check
            if ($weightLog && $weightLog->avg_weight > 0 && $standard->weight_g > 0) {
                $actualWeight = (float) $weightLog->avg_weight;
                $stdWeight    = (float) $standard->weight_g;
                $weightDev    = (($stdWeight - $actualWeight) / $stdWeight) * 100;

                if ($weightDev >= self::DEVIATION_THRESHOLDS['weight']['critical']) {
                    $breaches[] = $this->breedBreach('weight', 'Body Weight', $actualWeight, $stdWeight, 'critical', 'g', $weightDev);
                } elseif ($weightDev >= self::DEVIATION_THRESHOLDS['weight']['warning']) {
                    $breaches[] = $this->breedBreach('weight', 'Body Weight', $actualWeight, $stdWeight, 'warning', 'g', $weightDev);
                }
            }

            // 4b. FCR check
            if ($weightLog && $weightLog->feed_conversion_ratio > 0 && $standard->fcr > 0) {
                $actualFcr = (float) $weightLog->feed_conversion_ratio;
                $stdFcr    = (float) $standard->fcr;
                $fcrDev    = (($actualFcr - $stdFcr) / $stdFcr) * 100;

                if ($fcrDev >= self::DEVIATION_THRESHOLDS['fcr']['critical']) {
                    $breaches[] = $this->breedBreach('fcr', 'Feed Conversion Ratio (FCR)', $actualFcr, $stdFcr, 'critical', '', $fcrDev);
                } elseif ($fcrDev >= self::DEVIATION_THRESHOLDS['fcr']['warning']) {
                    $breaches[] = $this->breedBreach('fcr', 'Feed Conversion Ratio (FCR)', $actualFcr, $stdFcr, 'warning', '', $fcrDev);
                }
            }

            // 4c. Daily Gain check
            if ($weightLog && $weightLog->avg_weight_gain > 0 && $standard->daily_gain_g > 0) {
                $actualGain = (float) $weightLog->avg_weight_gain;
                $stdGain    = (float) $standard->daily_gain_g;
                $gainDev    = (($stdGain - $actualGain) / $stdGain) * 100;

                if ($gainDev >= self::DEVIATION_THRESHOLDS['daily_gain']['critical']) {
                    $breaches[] = $this->breedBreach('daily_gain', 'Daily Weight Gain', $actualGain, $stdGain, 'critical', 'g', $gainDev);
                } elseif ($gainDev >= self::DEVIATION_THRESHOLDS['daily_gain']['warning']) {
                    $breaches[] = $this->breedBreach('daily_gain', 'Daily Weight Gain', $actualGain, $stdGain, 'warning', 'g', $gainDev);
                }
            }
        }

        return $breaches;
    }

    /**
     * Get the breed performance standard for the flock's breed and age.
     */
    private function getBreedStandard(ProductionLog $log): ?BreedPerformanceStandard
    {
        $flock = $log->flock;

        if (! $flock || ! $flock->breed_id) {
            return null;
        }

        $day = (int) $log->age;

        return BreedPerformanceStandard::getStandard($flock->breed_id, $day);
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
            if (isset($breach['deviation'])) {
                $direction = $breach['deviation'] > 0
                    ? ($breach['kpi'] === 'fcr' ? 'above' : 'below')
                    : ($breach['kpi'] === 'fcr' ? 'below' : 'above');

                return "• {$breach['label']}: {$breach['value']}{$breach['unit']} "
                     . "(standard: {$breach['standard']}{$breach['unit']}, "
                     . round(abs($breach['deviation']), 1) . "% {$direction}) — "
                     . strtoupper($breach['severity']);
            }

            $direction = in_array($breach['kpi'], ['livability'])
                ? 'below'
                : 'above';

            return "• {$breach['label']}: {$breach['value']}{$breach['unit']} "
                 . "({$direction} threshold of {$breach['threshold']}{$breach['unit']}) — "
                 . strtoupper($breach['severity']);
        })->implode("\n");
    }

    /**
     * Build a fixed-threshold breach array.
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

    /**
     * Build a breed-standard breach array.
     */
    private function breedBreach(string $kpi, string $label, float $value, float $standard, string $severity, string $unit, float $deviation): array
    {
        return [
            'kpi'       => $kpi,
            'label'     => $label,
            'value'     => $value,
            'standard'  => $standard,
            'severity'  => $severity,
            'unit'      => $unit,
            'deviation' => $deviation,
        ];
    }
}
