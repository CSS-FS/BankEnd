<?php

use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

if (! function_exists('format_date_for_display')) {
    /**
     * Format date for display in d/m/Y format
     *
     * @param  mixed  $date
     */
    function format_date_for_display($date, string $format = 'd/m/Y'): ?string
    {
        try {
            if (empty($date)) {
                return null;
            }

            return Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            Log::error('Date formatting error: '.$e->getMessage(), [
                'date' => $date,
                'format' => $format,
            ]);

            return null;
        }
    }
}

if (! function_exists('settings')) {
    /**
     * Get setting value by key with optional default value
     *
     * @param  string  $key  Format: "group.key" or just "key"
     * @param  mixed  $default
     * @return mixed
     */
    function settings(string $key, $default = null)
    {
        try {
            return app(SettingsService::class)->get($key, $default);
        } catch (\Exception $e) {
            Log::error('Settings helper error: '.$e->getMessage(), [
                'key' => $key,
                'default' => $default,
            ]);

            return $default;
        }
    }
}

if (! function_exists('settings_group')) {
    /**
     * Get all settings from a specific group
     */
    function settings_group(string $group): array
    {
        try {
            return app(SettingsService::class)->getGroup($group);
        } catch (\Exception $e) {
            Log::error('Settings group error: '.$e->getMessage());

            return [];
        }
    }
}

if (! function_exists('daily_livability')) {
    function daily_livability(int $netCount, int $flockCount): float
    {
        try {
            return round($netCount * 100 / $flockCount, 3);
        } catch (\Exception $e) {
            Log::error('Settings group error: '.$e->getMessage());

            return 0.0;
        }
    }
}

// Mortality Rate
if (! function_exists('mortality_rate')) {
    /**
     * Calculate mortality rate (%).
     *
     * @param  int  $deaths  Number of deaths in period (day, cycle, etc.)
     * @param  int  $starting_birds  Birds alive at period start
     */
    function mortality_rate($deaths, $starting_birds): float
    {
        if ($starting_birds <= 0) {
            return 0.0;
        }

        return round(($deaths / $starting_birds) * 100, 3);
    }
}

// Cumulative Mortality Rate (for flock cycle)
if (! function_exists('cumulative_mortality_rate')) {
    /**
     * Calculate cumulative mortality rate (%) for the flock.
     *
     * @param  int  $total_deaths
     * @param  int  $original_birds
     */
    function cumulative_mortality_rate($total_deaths, $original_birds): float
    {
        if ($original_birds <= 0) {
            return 0.0;
        }

        return round(($total_deaths / $original_birds) * 100, 3);
    }
}

// Flock Weight
if (! function_exists('flock_weight')) {
    /**
     * Calculate flock weight.
     *
     * @param  float|int  $avgWeight
     * @param  float|int  $netCount
     */
    function flock_weight($avgWeight, $netCount): float
    {
        return round($avgWeight * $netCount, 3);
    }
}

// Daily weight gain per bird
if (! function_exists('daily_weight_gain')) {
    /**
     * Calculate daily weight gain per bird.
     *
     * @param  float|int  $currentAvgWeight
     * @param  float|int  $previousAvgWeight
     * @param  int  $days
     */
    function daily_weight_gain($currentAvgWeight, $previousAvgWeight, $days = 1): float
    {
        if ($days <= 0) {
            return 0.0;
        }

        return round($currentAvgWeight - $previousAvgWeight, 3);
    }
}

// Daily Weight Gain for Flock
if (! function_exists('flock_daily_weight_gain')) {
    /**
     * Calculate daily weight gain for a flock.
     *
     * @param  float|int  $dailyGainPerBird
     * @param  int  $netCount
     */
    function flock_daily_weight_gain($dailyGainPerBird, $netCount): float
    {
        return round($dailyGainPerBird * $netCount, 3);
    }
}

// Feed Efficiency
if (! function_exists('feed_efficiency')) {
    /**
     * Calculate feed efficiency.
     *
     * @param  float|int  $weightGain
     * @param  float|int  $feedConsumed
     */
    function feed_efficiency($totalWeightGain, $totalFeedConsumed): float
    {
        if ($totalFeedConsumed <= 0) {
            return 0.0;
        }

        return round($totalWeightGain / $totalFeedConsumed, 3);
    }
}

// Feed Conversion Ratio (FCR)
if (! function_exists('feed_conversion_ratio')) {
    /**
     * Calculate feed conversion ratio.
     *
     * @param  float|int  $feedConsumed
     * @param  float|int  $weightGain
     */
    function feed_conversion_ratio($totalFeedConsumed, $totalWeightGain): float
    {
        if ($totalWeightGain <= 0) {
            return 0.0;
        }

        return round($totalFeedConsumed / $totalWeightGain, 3);
    }
}

// Adjusted FCR
if (! function_exists('adjusted_fcr')) {
    /**
     * Calculate adjusted FCR.
     *
     * @param  float|int  $actualFCR
     * @param  float|int  $standardWeight
     * @param  float|int  $actualAvgWeight
     */
    function adjusted_fcr($actualFCR, $standardWeight, $actualAvgWeight): float
    {
        return round($actualFCR + ($standardWeight - $actualAvgWeight) / 4500, 3);
    }
}

// Flock Standard Deviation
if (! function_exists('flock_standard_deviation')) {
    /**
     * Calculate standard deviation for a sample (array of weights).
     * Suppose 5 birds: [900g, 950g, 1000g, 1050g, 1100g]
     */
    function flock_standard_deviation(array $weights): float
    {
        $n = count($weights);
        if ($n === 0) {
            return 0.0;
        }
        $mean = array_sum($weights) / $n;
        $variance = array_reduce($weights, function ($carry, $x) use ($mean) {
            return $carry + pow($x - $mean, 2);
        }, 0) / $n;

        return round(sqrt($variance), 3);
    }
}

// Flock Coefficient of Variation (CV)
if (! function_exists('flock_cv')) {
    /**
     * Calculate coefficient of variation (%).
     */
    function flock_cv(array $weights): float
    {
        $avg = count($weights) ? array_sum($weights) / count($weights) : 0;
        if ($avg == 0) {
            return 0.0;
        }
        $std = flock_standard_deviation($weights);

        return round(($std / $avg) * 100, 3);
    }
}

// Flock Uniformity
if (! function_exists('flock_uniformity')) {
    /**
     * Calculate flock uniformity (% within ±pctRange of avg).
     *
     * @param  float  $pctRange  Default is 10 (%)
     */
    function flock_uniformity(array $weights, float $pctRange = 10.0): float
    {
        $n = count($weights);
        if ($n === 0) {
            return 0.0;
        }
        $avg = array_sum($weights) / $n;
        $min = $avg * (1 - $pctRange / 100);
        $max = $avg * (1 + $pctRange / 100);
        $within = array_filter($weights, fn ($w) => $w >= $min && $w <= $max);

        return round((count($within) / $n) * 100, 3);
    }
}

// Production Efficiency Factor (PEF)
if (! function_exists('production_efficiency_factor')) {
    /**
     * Calculate Production Efficiency Factor (PEF).
     *
     * @param  float|int  $livability  e.g. 95.5
     * @param  float|int  $live_weight_kg  e.g. 2.35 (kg per bird)
     * @param  int  $age_days  e.g. 38
     * @param  float|int  $fcr  e.g. 1.6
     */
    function production_efficiency_factor($livability, $live_weight_kg, $age_days, $fcr): float
    {
        if ($age_days <= 0 || $fcr <= 0) {
            return 0.0;
        }

        return round(($livability * $live_weight_kg) / ($age_days * $fcr) * 100, 3);
    }
}

if (! function_exists('get_alert_levels')) {
    /**
     * Get all alert criticality levels.
     */
    function get_alert_levels(): array
    {
        return [
            'critical',
            'major',
            'warning',
            'notice',
            'info',
            'debug',
            'success',
        ];
    }
}

if (! function_exists('get_alert_types')) {
    /**
     * Get all alert types.
     */
    function get_alert_types(): array
    {
        return [
            'system',
            'security',
            'billing',
            'activity',
            'maintenance',
        ];
    }
}

if (! function_exists('get_alert_channels')) {
    /**
     * Get all alert channels
     */
    function get_alert_channels(): array
    {
        return [
            'in_app',
            'email',
            'sms',
            'push',
        ];
    }
}

if (! function_exists('get_alert_statuses')) {
    /**
     * Get all alert statuses
     */
    function get_alert_statuses(): array
    {
        return [
            'queued',
            'sent',
            'failed',
            'delivered',
        ];
    }
}
