<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProductionAnalyticsService
{
    /**
     * Daily Mortality Rate per flock (DoD), with safe 0-division handling.
     * mortality = (day_mortality + night_mortality) / previous_day_closing_count
     */
    public function mortalityRateDaily(array $filters = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(28)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        $base = DB::table('production_logs as pl')
            ->selectRaw('
                pl.flock_id,
                pl.production_log_date::date as d,
                (pl.day_mortality_count + pl.night_mortality_count) as deaths,
                pl.net_count as end_count,
                LAG(pl.net_count) OVER (PARTITION BY pl.flock_id ORDER BY pl.production_log_date) as prev_end
            ')
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'pl.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('pl.shed_id', $filters['shed_id'])
            )
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('pl.flock_id', $filters['flock_id'])
            )
            ->whereBetween(DB::raw('pl.production_log_date::date'), [$dateFrom, $dateTo]);

        return DB::query()->fromSub($base, 'x')
            ->selectRaw('
              flock_id, d,
              CASE
                WHEN COALESCE(prev_end, end_count + deaths) = 0 THEN 0
                ELSE deaths * 1.0 / COALESCE(prev_end, end_count + deaths)
              END as mortality_rate
            ')
            ->orderBy('flock_id')->orderBy('d')
            ->get();
    }

    /**
     * Livability % per day (uses stored field `livability`)
     */
    public function livabilityDaily(array $filters = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(28)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        return DB::table('production_logs as pl')
            ->selectRaw('pl.flock_id, pl.production_log_date::date as d, 100*AVG(pl.livability) as livability_pct')
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'pl.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('pl.shed_id', $filters['shed_id']))
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('pl.flock_id', $filters['flock_id']))
            ->whereBetween(DB::raw('pl.production_log_date::date'), [$dateFrom, $dateTo])
            ->groupBy('pl.flock_id', DB::raw('pl.production_log_date::date'))
            ->get();
    }

    /**
     * Average Daily Gain (g/bird/day), from weight_logs joined to production day.
     */
    public function adgDaily(array $filters = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(28)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        $q = DB::table('weight_logs as w')
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->selectRaw('w.flock_id, p.production_log_date::date as d, AVG(w.avg_weight_gain) as adg_g_per_bird')
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'p.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('p.shed_id', $filters['shed_id']))
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('p.flock_id', $filters['flock_id']))
            ->whereBetween(DB::raw('p.production_log_date::date'), [$dateFrom, $dateTo])
            ->groupBy('w.flock_id', DB::raw('p.production_log_date::date'));

        return $q->get();
    }

    /**
     * FCR and Adjusted FCR daily.
     */
    public function fcrDaily(array $filters = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(28)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        return DB::table('weight_logs as w')
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->selectRaw('w.flock_id, p.production_log_date::date as d,
                         AVG(w.feed_conversion_ratio) as fcr_daily,
                         AVG(w.adjusted_feed_conversion_ratio) as adj_fcr_daily')
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'p.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('p.shed_id', $filters['shed_id']))
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('p.flock_id', $filters['flock_id']))
            ->whereBetween(DB::raw('p.production_log_date::date'), [$dateFrom, $dateTo])
            ->groupBy('w.flock_id', DB::raw('p.production_log_date::date'))
            ->get();
    }

    /**
     * Water-to-Feed Ratio per day.
     */
    public function waterToFeedRatioDaily(array $filters = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(28)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        return DB::table('production_logs as pl')
            ->selectRaw('
                pl.flock_id,
                pl.production_log_date::date as d,
                SUM(pl.day_water_consumed + pl.night_water_consumed) /
                NULLIF(SUM(pl.day_feed_consumed + pl.night_feed_consumed), 0) as water_to_feed_ratio
            ')
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'pl.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('pl.shed_id', $filters['shed_id']))
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('pl.flock_id', $filters['flock_id']))
            ->whereBetween(DB::raw('pl.production_log_date::date'), [$dateFrom, $dateTo])
            ->groupBy('pl.flock_id', DB::raw('pl.production_log_date::date'))
            ->get();
    }

    /**
     * Uniformity (proxy: 1 - CV) and PEF from weight logs.
     */
    public function uniformityAndPefDaily(array $filters = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(28)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        return DB::table('weight_logs as w')
            ->join('production_logs as p', 'p.id', '=', 'w.production_log_id')
            ->selectRaw('w.flock_id, p.production_log_date::date as d,
                         AVG(1 - w.coefficient_of_variation)  as uniformity_proxy,
                         AVG(w.production_efficiency_factor) as pef')
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'p.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('p.shed_id', $filters['shed_id']))
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('p.flock_id', $filters['flock_id']))
            ->whereBetween(DB::raw('p.production_log_date::date'), [$dateFrom, $dateTo])
            ->groupBy('w.flock_id', DB::raw('p.production_log_date::date'))
            ->get();
    }

    /**
     * Per-bird consumption (already stored as generated columns).
     */
    public function perBirdConsumptionDaily(array $filters = [], ?string $dateFrom = null, ?string $dateTo = null)
    {
        $dateFrom = $dateFrom ?: now()->subDays(28)->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        return DB::table('production_logs as pl')
            ->selectRaw('pl.flock_id, pl.production_log_date::date as d,
                         AVG(pl.avg_feed_consumed)  as feed_per_bird,
                         AVG(pl.avg_water_consumed) as water_per_bird')
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'pl.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('pl.shed_id', $filters['shed_id']))
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('pl.flock_id', $filters['flock_id']))
            ->whereBetween(DB::raw('pl.production_log_date::date'), [$dateFrom, $dateTo])
            ->groupBy('pl.flock_id', DB::raw('pl.production_log_date::date'))
            ->get();
    }

    /**
     * Helper: bundle a “Flock Summary” row for tables.
     */
    public function flockSummary(array $filters = [], ?string $dateTo = null)
    {
        $dateTo = $dateTo ?: now()->toDateString();

        $latest = DB::table('production_logs as pl')
            ->selectRaw('pl.flock_id, MAX(pl.production_log_date) as last_day')
            ->groupBy('pl.flock_id');

        $base = DB::table('production_logs as pl')
            ->joinSub($latest, 'lt', fn ($j) => $j->on('lt.flock_id', '=', 'pl.flock_id')->on('lt.last_day', '=', 'pl.production_log_date'))
            ->selectRaw('pl.flock_id, pl.age, pl.net_count, pl.livability,
                         pl.avg_feed_consumed as feed_per_bird,
                         pl.avg_water_consumed as water_per_bird');

        return $base
            ->when(isset($filters['farm_id']), fn ($q) => $q->join('flocks as f', 'f.id', '=', 'pl.flock_id')
                ->join('sheds as s', 's.id', '=', 'f.shed_id')
                ->where('s.farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['shed_id']), fn ($q) => $q->where('pl.shed_id', $filters['shed_id']))
            ->when(isset($filters['flock_id']), fn ($q) => $q->where('pl.flock_id', $filters['flock_id']))
            ->whereDate('pl.production_log_date', '<=', $dateTo)
            ->get();
    }
}
