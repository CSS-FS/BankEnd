<?php

namespace App\Services;

use App\Models\IotDataLog;
use Illuminate\Support\Facades\DB;

class GraphDataService
{
    /**
     * Get Daily Mortality Rate by Flock
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDailyMortalityRate(int $flockId)
    {
        $sql = '
            WITH x AS (
              SELECT
                pl.flock_id,
                DATE(pl.production_log_date) AS d,
                pl.day_mortality_count + pl.night_mortality_count AS deaths,
                pl.net_count AS end_count,
                LAG(pl.net_count) OVER (PARTITION BY pl.flock_id ORDER BY pl.production_log_date) AS prev_end
              FROM production_logs pl
              WHERE pl.flock_id = :flockId
            )
            SELECT
              flock_id,
              d,
              CASE
                WHEN COALESCE(prev_end, end_count + deaths) = 0 THEN 0
                ELSE deaths * 1.0 / COALESCE(prev_end, end_count + deaths)
              END AS mortality_rate
            FROM x
            ORDER BY flock_id, d
        ';

        return DB::select($sql, ['flockId' => $flockId]);
    }

    /**
     * Get Daily ADG and Aggregated Weight
     */
    public function getDailyAdgAndWeight(int $flockId)
    {
        $sql = '
            SELECT
              w.flock_id,
              DATE(p.production_log_date) AS d,
              p.age,
              AVG(w.avg_weight_gain) AS adg_g_per_bird,
              AVG(w.aggregated_total_weight)/1000 AS adg_kg_per_flock
            FROM
              weight_logs w
              JOIN production_logs p ON p.id = w.production_log_id
            WHERE
              w.flock_id = :flockId
            GROUP BY
              w.flock_id,
              DATE(p.production_log_date),
              p.age
            ';

        return DB::select($sql, ['flockId' => $flockId]);
    }

    /**
     * Get Feed/Weight Data with Cumulative Metrics
     */
    public function getFeedWeightCumulativeData(?int $farmId = null, ?string $startDate = null, ?string $endDate = null)
    {
        $sql = '
            WITH base AS (
              SELECT
                w.flock_id,
                f.name AS flock_name,
                s.name AS shed_name,
                DATE(p.production_log_date) AS d,
                p.age,
                AVG(w.avg_weight_gain) AS adg_g_per_bird,
                SUM(p.day_feed_consumed + p.night_feed_consumed) / NULLIF(MAX(p.net_count), 0) AS feed_g_day_bird,
                AVG(w.aggregated_total_weight) / 1000 AS weight_kg_per_flock,
                SUM(p.day_feed_consumed + p.night_feed_consumed) / 1000 AS feed_kg_day_flock
              FROM
                weight_logs AS w
                JOIN production_logs p ON p.id = w.production_log_id
                JOIN flocks f ON f.id = w.flock_id
                JOIN sheds s ON s.id = f.shed_id
              WHERE
                (:farmId IS NULL OR s.farm_id = :farmId)
                AND (:startDate IS NULL OR p.production_log_date >= :startDate)
                AND (:endDate IS NULL OR p.production_log_date < (:endDate::date + INTERVAL \'1 day\'))
              GROUP BY
                w.flock_id,
                f.name,
                s.name,
                DATE(p.production_log_date),
                p.age
            ),
            cum AS (
              SELECT
                b.*,
                SUM(b.feed_g_day_bird) OVER (
                  PARTITION BY b.flock_id
                  ORDER BY b.d
                  ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                ) AS feed_g_bird_cum,
                SUM(b.feed_kg_day_flock) OVER (
                  PARTITION BY b.flock_id
                  ORDER BY b.d
                  ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                ) AS feed_kg_cum
              FROM base b
            )
            SELECT
              flock_id,
              flock_name,
              shed_name,
              d,
              age,
              ROUND(adg_g_per_bird::numeric, 0)   AS adg_g_per_bird,
              ROUND(feed_g_bird_cum::numeric, 0)  AS feed_g_bird_cum,
              ROUND(weight_kg_per_flock::numeric, 2) AS weight_kg_per_flock,
              ROUND(feed_kg_cum::numeric, 2)      AS feed_kg_cum
            FROM cum
            ORDER BY flock_id, d
        ';

        return DB::select($sql, [
            'farmId' => $farmId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get Daily FCR
     */
    public function getDailyFcr(int $flockId)
    {
        $sql = "
            WITH daily AS (
              SELECT
                w.flock_id,
                DATE(p.production_log_date) AS d,
                p.age,
                AVG(w.feed_conversion_ratio)             AS fcr_daily,
                AVG(w.adjusted_feed_conversion_ratio)    AS adj_fcr_daily
              FROM weight_logs AS w
              JOIN production_logs AS p
                ON p.id = w.production_log_id
              WHERE w.flock_id = :flockId
              GROUP BY
                w.flock_id,
                DATE(p.production_log_date),
                p.age
            )
            SELECT
              flock_id,
              d,
              age,
              fcr_daily,
              adj_fcr_daily,
              CASE
                WHEN fcr_daily = 0 THEN 0
                ELSE ROUND(((fcr_daily - COALESCE(
                  LAG(fcr_daily) OVER (PARTITION BY flock_id ORDER BY d, age),
                  0
                )) * 100 / fcr_daily)::numeric, 3)
              END AS fcr_difference
            FROM daily
            ORDER BY d, age";

        return DB::select($sql, ['flockId' => $flockId]);
    }

    /**
     * Get Water-to-Feed Ratio
     */
    public function getWaterToFeedRatio()
    {
        $sql = '
            SELECT
              flock_id,
              DATE(production_log_date) AS d,
              age,
              SUM(day_water_consumed + night_water_consumed) / NULLIF(SUM(day_feed_consumed + night_feed_consumed), 0) AS water_to_feed_ratio
            FROM
              production_logs
            GROUP BY
              flock_id,
              DATE(production_log_date),
              age
        ';

        return DB::select($sql);
    }

    /**
     * Get Uniformity (1 - CV)
     */
    public function getUniformity()
    {
        $sql = '
            SELECT
              p.flock_id,
              DATE(p.production_log_date) AS d,
              age,
              AVG(1 - coefficient_of_variation) AS uniformity_proxy
            FROM
              weight_logs w
              JOIN production_logs p ON p.id = w.production_log_id
            GROUP BY
              p.flock_id,
              DATE(p.production_log_date),
              age
        ';

        return DB::select($sql);
    }

    /**
     * Get Vaccination History
     */
    public function getVaccinationHistory(int $flockId)
    {
        $sql = '
        SELECT
          flock_id,
          DATE(production_log_date) AS d,
          age,
          day_medicine,
          night_medicine
        FROM production_logs
        WHERE flock_id = :flockId
          AND is_vaccinated IS TRUE
        ORDER BY d
    ';

        return DB::select($sql, ['flockId' => $flockId]);
    }

    /**
     * Get Feed Consumption History
     */
    public function getFeedConsumptionHistory(int $flockId)
    {
        $sql = '
        SELECT
          flock_id,
          DATE(production_log_date) AS d,
          age,
          day_feed_consumed,
          night_feed_consumed,
          (day_feed_consumed + night_feed_consumed) AS total_feed,
          avg_feed_consumed
        FROM production_logs
        WHERE flock_id = :flockId
        ORDER BY d
    ';

        return DB::select($sql, ['flockId' => $flockId]);
    }

    /**
     * Get Water Consumption History
     */
    public function getWaterConsumptionHistory(int $flockId)
    {
        $sql = '
        SELECT
          flock_id,
          DATE(production_log_date) AS d,
          age,
          day_water_consumed,
          night_water_consumed,
          (day_water_consumed + night_water_consumed) AS total_water,
          avg_water_consumed
        FROM production_logs
        WHERE flock_id = :flockId
        ORDER BY d
    ';

        return DB::select($sql, ['flockId' => $flockId]);
    }
}
