<?php

namespace App\Services;

use App\Models\IotDataLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManagerAnalyticsService
{
    public function getAnalyticsData(array $filters = []): array
    {
        $start_date = $filters['start_date'] ?: now()->subDays(30)->toDateString();
        $end_date = $filters['end_date'] ?: now()->toDateString();

        $sql = "WITH active_flocks AS (
                  SELECT f.id, f.shed_id, f.chicken_count, f.start_date, f.end_date
                  FROM flocks f
                  JOIN sheds s ON s.id = f.shed_id
                  WHERE s.farm_id = {$filters['farm_id']}
                    AND f.start_date <= CURRENT_DATE()
                    AND (f.end_date IS NULL OR f.end_date >= CURRENT_DATE())
                ),
                latest_pl AS (
                  -- Latest production log per active flock within window (falls back to latest overall)
                  SELECT pl.*
                  FROM production_logs pl
                  JOIN (
                    SELECT flock_id, MAX(production_log_date) AS max_d
                    FROM production_logs
                    WHERE production_log_date BETWEEN '{$start_date}' AND '{$end_date}'
                    GROUP BY flock_id
                  ) x ON x.flock_id = pl.flock_id AND x.max_d = pl.production_log_date
                ),
                fallback_pl AS (
                  -- If a flock has no log in the window, take its latest ever
                  SELECT pl.*
                  FROM production_logs pl
                  JOIN (
                    SELECT flock_id, MAX(production_log_date) AS max_d
                    FROM production_logs
                    GROUP BY flock_id
                  ) x ON x.flock_id = pl.flock_id AND x.max_d = pl.production_log_date
                ),
                chosen_pl AS (
                  -- Prefer latest within window; otherwise fallback latest
                  SELECT af.id AS flock_id,
                         COALESCE(lp.net_count, fp.net_count)           AS net_count,
                         COALESCE(lp.livability, fp.livability)         AS livability,
                         COALESCE(lp.day_mortality_count,0)+COALESCE(lp.night_mortality_count,0) AS win_mortality,
                         COALESCE(fp.day_mortality_count,0)+COALESCE(fp.night_mortality_count,0) AS fb_mortality
                  FROM active_flocks af
                  LEFT JOIN latest_pl lp   ON lp.flock_id = af.id
                  LEFT JOIN fallback_pl fp ON fp.flock_id = af.id
                ),
                farm_window AS (
                  -- Window aggregate for the farm in the period
                  SELECT
                    SUM(pl.day_mortality_count + pl.night_mortality_count) AS period_mortalities,
                    SUM(pl.day_feed_consumed + pl.night_feed_consumed)/1000     AS period_feed_kg,
                    SUM(pl.day_water_consumed + pl.night_water_consumed)   AS period_water_l
                  FROM production_logs pl
                  JOIN flocks f   ON f.id = pl.flock_id
                  JOIN sheds  s   ON s.id = f.shed_id
                  WHERE s.farm_id = {$filters['farm_id']}
                    AND pl.production_log_date BETWEEN '{$start_date}' AND '{$end_date}'
                ),
                fcr_pef AS (
                  -- FCR/PEF via weight_logs (latest per flock within window)
                  SELECT
                    AVG(wl.feed_conversion_ratio)                  AS avg_fcr,
                    AVG(wl.adjusted_feed_conversion_ratio)         AS avg_adj_fcr,
                    AVG(wl.production_efficiency_factor)           AS avg_pef
                  FROM weight_logs wl
                  JOIN production_logs pl ON pl.id = wl.production_log_id
                  JOIN flocks f ON f.id = wl.flock_id
                  JOIN sheds  s ON s.id = f.shed_id
                  WHERE s.farm_id = {$filters['farm_id']}
                    AND pl.production_log_date BETWEEN '{$start_date}' AND '{$end_date}'
                )
                SELECT
                  (SELECT COUNT(*) FROM active_flocks)                             AS active_flocks,
                  COALESCE(SUM(ch.net_count), 0)                                   AS total_birds_current,
                  -- Mortality rate over window relative to (sum of latest net_count or stocked birds if you prefer)
                  COALESCE((SELECT period_mortalities FROM farm_window),0)         AS total_mortalities_window,
                  CASE
                    WHEN COALESCE(SUM(ch.net_count),0) > 0
                    THEN ROUND((SELECT period_mortalities FROM farm_window) * 100.0 / SUM(ch.net_count), 2)
                    ELSE 0
                  END                                                              AS mortality_rate_pct,
                  ROUND(AVG(ch.livability),2)                                      AS avg_livability_pct,
                  ROUND(COALESCE((SELECT avg_fcr FROM fcr_pef),0), 2)              AS avg_fcr,
                  ROUND(COALESCE((SELECT avg_adj_fcr FROM fcr_pef),0), 2)          AS avg_adj_fcr,
                  ROUND(COALESCE((SELECT avg_pef FROM fcr_pef),0), 1)              AS avg_pef,
                  ROUND(COALESCE((SELECT period_feed_kg FROM farm_window),0), 2)   AS feed_kg_window,
                  ROUND(COALESCE((SELECT period_water_l FROM farm_window),0), 2)   AS water_l_window
                FROM chosen_pl ch";

        return DB::select($sql);
    }

    public function getMortalityRateData(array $filters = []): array
    {
        $start_date = $filters['start_date'] ?: now()->subDays(30)->toDateString();
        $end_date = $filters['end_date'] ?: now()->toDateString();

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
                    AND s.farm_id = {$filters['farm_id']}
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

        $seriesByFlock = [];
        foreach ($rows as $r) {
            $key = $r->flock_id;
            if (! isset($seriesByFlock[$key])) {
                $seriesByFlock[$key] = [
                    'label' => "{$r->flock_name} ({$r->shed_name})",
                    'data' => [],
                ];
            }
            // Chart.js expects y as number; convert to percentage later in options
            $seriesByFlock[$key]['data'][] = [
                'x' => $r->age,                                // YYYY-MM-DD
                'y' => round(((float) $r->mortality_rate) * 100, 4),  // % for nicer tooltips
            ];
        }

        return array_values($seriesByFlock);
    }

    public function adgData(array $filters = []): array
    {
        $start_date = $filters['start_date'] ?: now()->subDays(30)->toDateString();
        $end_date = $filters['end_date'] ?: now()->toDateString();

        $sql = "WITH base AS (
                  SELECT
                    w.flock_id,
                    f.name AS flock_name,
                    s.name AS shed_name,
                    DATE(p.production_log_date) AS d,
                    p.age,
                    AVG(w.avg_weight_gain) AS adg_g_per_bird,
                    SUM(p.day_feed_consumed + p.night_feed_consumed) / 1000 AS feed_kg_day
                  FROM
                    weight_logs AS w
                    JOIN production_logs p ON p.id = w.production_log_id
                    JOIN flocks f ON f.id = w.flock_id
                    JOIN sheds s ON s.id = f.shed_id
                  WHERE
                    ({$filters['farm_id']} IS NULL OR s.farm_id = {$filters['farm_id']})
                    AND ('{$start_date}' IS NULL OR p.production_log_date >= '{$start_date}')
                    AND ('{$end_date}' IS NULL OR p.production_log_date < '{$end_date}' + INTERVAL 1 DAY)
                  GROUP BY
                    w.flock_id,
                    f.name,
                    s.name,
                    DATE(p.production_log_date),
                    p.age
                ),
                cum_feed AS (
                  SELECT
                    b.*,
                    SUM(b.feed_kg_day) OVER (PARTITION BY b.flock_id ORDER BY b.d ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS feed_kg_cum
                  FROM
                    base b
                ) SELECT
                  flock_id,
                  flock_name,
                  shed_name,
                  d,
                  age,
                  ROUND(adg_g_per_bird, 0) AS adg_g_per_bird,
                  ROUND(feed_kg_cum, 2) AS feed_kg_cum
                FROM
                  cum_feed
                ORDER BY
                  flock_id,
                  d";

        $rows = DB::select($sql);

        // ---- Build labels (ages) and datasets (one per flock) ----
        $ageSet = [];
        $flocks = [];
        $palette = [
            // pair colors (barColor, lineColor)
            ['#fb9a99', '#e31a1c'], // light red/pink bars, strong red line
            ['#a6cee3', '#1f78b4'], // light blue bars, dark blue line
            ['#b2df8a', '#33a02c'], // light green bars, dark green line
            ['#fdbf6f', '#ff7f00'], // soft orange bars, deep orange line
            ['#cab2d6', '#6a3d9a'], // lavender bars, dark purple line
            ['#ffff99', '#b15928'], // yellow bars, brown line
        ];

        foreach ($rows as $r) {
            $age = (int) $r->age;
            $ageSet[$age] = true;

            if (! isset($flocks[$r->flock_id])) {
                $flocks[$r->flock_id] = [
                    'label' => "{$r->flock_name} ({$r->shed_name})",
                    'adg' => [],
                    'feed' => [],
                ];
            }
            $flocks[$r->flock_id]['adg'][(int) $r->age] = (float) $r->adg_g_per_bird;
            $flocks[$r->flock_id]['feed'][(int) $r->age] = (float) $r->feed_kg_cum;
        }

        // Sorted unique ages as x-axis labels
        $labels = array_keys($ageSet);
        sort($labels, SORT_NUMERIC);

        // Align each dataset’s data array to the labels (null where missing)
        $datasets = [];
        $i = 0;
        foreach ($flocks as $flockId => $series) {
            $colors = $palette[$i % count($palette)];
            $i++;

            // Data aligned to labels (ages)
            $adgData = [];
            $feedData = [];
            foreach ($labels as $age) {
                $adgData[] = $series['adg'][$age] ?? null; // null -> skip bar for that age
                $feedData[] = $series['feed'][$age] ?? null; // null -> skip line for that age
            }

            $datasets[] = [
                'type' => 'bar',
                'label' => 'ADG',
                'data' => $adgData,
                'yAxisID' => 'yAdg',
                'borderWidth' => 1,
                'backgroundColor' => $this->hexToRgba($colors[0], 0.5),
                'borderColor' => $colors[0],
                'order' => 1,  // bars drawn first
            ];

            $datasets[] = [
                'type' => 'line',
                'label' => 'Feed (cum)',
                'data' => $feedData,
                'yAxisID' => 'yFeed',
                'borderWidth' => 2,
                'pointRadius' => 2,
                'tension' => 0.2,
                'borderColor' => $colors[1],
                'fill' => false,
                'spanGaps' => true, // connect across nulls (if desired)
                'order' => 2,  // bars drawn first
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    public function getIoTChartData(int $shedId, int $deviceId, $start_date, $end_date): array
    {
        $query = IotDataLog::query()
            ->hourly()
            ->forPeriod($start_date, $end_date)
            ->orderBy('record_time');

        // Apply device/shed scope
        if ($deviceId != 0) {
            $query->forDevice($shedId, $deviceId);
        } else {
            $query->forShed($shedId);
        }

        $logs = $query->get();

        $firstTime = Carbon::parse($start_date);
        $grouped = $logs->groupBy('record_time');

        $labels = array_keys($grouped->toArray()) ?? [];
        foreach ($labels as $k => $l) {
            $labels[$k] = Carbon::parse($l)->format('d, M H:i');
        }
        $temp1Values = [];
        $temp2Values = [];
        $tempSafeLower = [];
        $tempSafeUpper = [];

        $humidityValues = [];
        $humSafeLower = [];
        $humSafeUpper = [];

        $nh3Values = [];
        $nh3SafeLower = [];
        $nh3SafeUpper = [];

        $co2Values = [];
        $co2SafeLower = [];
        $co2SafeUpper = [];

        foreach ($grouped as $timeStr => $records) {
            $time = Carbon::parse($timeStr);
            $dayIndex = $firstTime->startOfDay()->diffInDays($time->startOfDay());

            // Helper to get avg for each parameter
            $byParam = $records->keyBy('parameter');

            $temp1 = optional($byParam->get('temp1'))->avg_value;
            $temp2 = optional($byParam->get('temp2'))->avg_value;
            $hum = optional($byParam->get('humidity'))->avg_value;
            $nh3 = optional($byParam->get('nh3'))->avg_value;
            $co2 = optional($byParam->get('co2'))->avg_value;

            // -------- TEMPERATURE SAFE RANGE (depends on age) -------
            [$tempLower, $tempUpper] = $this->temperatureRangeForDay($dayIndex);

            $temp1Values[] = $temp1;
            $temp2Values[] = $temp2;
            $tempSafeLower[] = $tempLower;
            $tempSafeUpper[] = $tempUpper;

            // -------- HUMIDITY SAFE RANGE --------
            if ($dayIndex < 7) {
                // Brooding: 60–80%
                $hLower = 60;
                $hUpper = 80;
            } else {
                // Grower/Finisher: 50–70%
                $hLower = 50;
                $hUpper = 70;
            }

            $humidityValues[] = $hum;
            $humSafeLower[] = $hLower;
            $humSafeUpper[] = $hUpper;

            // -------- NH3 SAFE RANGE --------
            // Recommended upper: 20–25 ppm; ideal below 10 ppm
            // We'll treat 0–20 as safe zone visually.
            $nh3SafeLower[] = 0;
            $nh3SafeUpper[] = 20;
            $nh3Values[] = $nh3;

            // -------- CO2 SAFE RANGE --------
            // Recommended upper: 3000 ppm; ideal <1000–1500 ppm
            // We'll treat 0–3000 as safe zone visually.
            $co2SafeLower[] = 0;
            $co2SafeUpper[] = 3000;
            $co2Values[] = $co2;
        }

        $chartData = [
            'labels' => $labels,

            'temp1' => $temp1Values,
            'temp2' => $temp2Values,
            'tempSafeLower' => $tempSafeLower,
            'tempSafeUpper' => $tempSafeUpper,

            'humidity' => $humidityValues,
            'humSafeLower' => $humSafeLower,
            'humSafeUpper' => $humSafeUpper,

            'nh3' => $nh3Values,
            'nh3SafeLower' => $nh3SafeLower,
            'nh3SafeUpper' => $nh3SafeUpper,

            'co2' => $co2Values,
            'co2SafeLower' => $co2SafeLower,
            'co2SafeUpper' => $co2SafeUpper,
        ];

        return $chartData;
    }

    /**
     * Same schedule you used in the seeder.
     */
    protected function temperatureRangeForDay(int $dayIndex): array
    {
        if ($dayIndex <= 6) {
            // day 0–7
            return [33.9, 35.0];
        } elseif ($dayIndex <= 13) {
            // day 8–14
            return [31.1, 32.2];
        } elseif ($dayIndex <= 20) {
            // day 15–21
            return [28.3, 29.4];
        } elseif ($dayIndex <= 27) {
            // day 22–28
            return [25.6, 26.7];
        } elseif ($dayIndex <= 34) {
            // day 29–35 (around 23.9)
            return [23.4, 24.4];
        } else {
            // day 36–42 (around 21.1)
            return [20.6, 21.6];
        }
    }

    public function hexToRgba($hex, $alpha = 1)
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return "rgba($r, $g, $b, $alpha)";
    }

    public function shedEnvironmentData(array $filters = []): array
    {
        $start_date = $filters['start_date'] ?: now()->subDays(30)->toDateString();
        $end_date = $filters['end_date'] ?: now()->toDateString();
        $co2_limit = 3000;
        $nh3_limit = 25;

        $sql = "WITH last_param AS (
                  SELECT l.*
                  FROM iot_data_logs l
                  JOIN (
                    SELECT shed_id, parameter, MAX(record_time) AS mx
                    FROM iot_data_logs
                    GROUP BY shed_id, parameter
                  ) x ON x.shed_id = l.shed_id AND x.parameter = l.parameter AND x.mx = l.record_time
                )
                SELECT
                  s.id AS shed_id,
                  s.name AS shed_name,
                  MAX(CASE WHEN lp.parameter='temp1' THEN lp.avg_value END) AS shed_temperature_c,
                  MAX(CASE WHEN lp.parameter='temp2' THEN lp.avg_value END) AS brooder_temperature_c,
                  MAX(CASE WHEN lp.parameter='humidity'    THEN lp.avg_value END) AS humidity_pct,
                  MAX(CASE WHEN lp.parameter='co2'         THEN lp.avg_value END) AS co2_ppm,
                  MAX(CASE WHEN lp.parameter='nh3'         THEN lp.avg_value END) AS nh3_ppm,
                  MAX(lp.record_time) AS last_reading,
                  CASE
                    -- WHEN MAX(CASE WHEN lp.parameter='temp1' THEN lp.avg_value END) IS NULL THEN 'NO DATA'
                    -- WHEN MAX(CASE WHEN lp.parameter='temp2' THEN lp.avg_value END) IS NULL THEN 'NO DATA'
                    WHEN MAX(CASE WHEN lp.parameter='temp1' THEN lp.avg_value END) NOT BETWEEN 20 AND 25
                      OR MAX(CASE WHEN lp.parameter='temp2' THEN lp.avg_value END) NOT BETWEEN 29 AND 33
                      OR MAX(CASE WHEN lp.parameter='humidity'    THEN lp.avg_value END) NOT BETWEEN 0.6 AND 0.7
                      OR COALESCE(MAX(CASE WHEN lp.parameter='co2' THEN lp.avg_value END),0) > $co2_limit
                      OR COALESCE(MAX(CASE WHEN lp.parameter='nh3' THEN lp.avg_value END),0) > $nh3_limit
                    THEN 'ALERT' ELSE 'OK'
                  END AS status
                FROM sheds s
                LEFT JOIN last_param lp ON lp.shed_id = s.id
                WHERE s.farm_id = {$filters['farm_id']}
                GROUP BY s.id, s.name
                ORDER BY s.name";

        return DB::select($sql);
    }

    public function environmentAlerts(array $filters = []): array
    {
        $start_date = $filters['start_date'] ?: now()->subDays(30)->toDateString();
        $end_date = $filters['end_date'] ?: now()->toDateString();
        $co2_limit = 3000;
        $nh3_limit = 25;
        $mortality_pct_alert = 0.274;

        $sql = "WITH env_last AS (
                  SELECT l.*
                  FROM iot_data_logs l
                  JOIN (
                    SELECT shed_id, parameter, MAX(record_time) AS mx
                    FROM iot_data_logs
                    GROUP BY shed_id, parameter
                  ) x ON x.shed_id = l.shed_id AND x.parameter = l.parameter AND x.mx = l.record_time
                ),
                env_alerts AS (
                  SELECT
                    s.farm_id, s.id AS shed_id, s.name AS shed_name,
                    NULL AS flock_id, NULL AS flock_name,
                    'ENV' AS alert_type,
                    CASE el.parameter
                      WHEN 'temp1' THEN 'Shed Temperature'
                      WHEN 'temp2' THEN 'Brooder Temperature'
                      WHEN 'humidity'    THEN 'Relative Humidity'
                      WHEN 'co2'         THEN 'CO2 Exposure'
                      WHEN 'nh3'         THEN 'Ammonia Exposure'
                      ELSE ''
                    END AS parameter,
                    el.avg_value,
                    CASE el.parameter
                      WHEN 'temp1' THEN CONCAT(20,'-',25)
                      WHEN 'temp2' THEN CONCAT(29,'-',33)
                      WHEN 'humidity'    THEN CONCAT(0.6,'-',0.8)
                      WHEN 'co2'         THEN CONCAT('<=', $co2_limit)
                      WHEN 'nh3'         THEN CONCAT('<=', $nh3_limit)
                      ELSE ''
                    END AS threshold,
                    el.record_time AS alert_time
                  FROM env_last el
                  JOIN sheds s ON s.id = el.shed_id
                  WHERE s.farm_id = {$filters['farm_id']}
                    AND (
                      (el.parameter='temp1' AND (el.avg_value < 20 OR el.avg_value > 25)) OR
                      (el.parameter='temp2' AND (el.avg_value < 29 OR el.avg_value > 33)) OR
                      (el.parameter='humidity'    AND (el.avg_value < 0.6 OR el.avg_value > 0.8)) OR
                      (el.parameter='co2'         AND el.avg_value > $co2_limit) OR
                      (el.parameter='nh3'         AND el.avg_value > $nh3_limit)
                    )
                ),
                mortality_alerts AS (
                  SELECT
                    s.farm_id, s.id AS shed_id, s.name AS shed_name,
                    f.id AS flock_id, f.name AS flock_name,
                    'MORTALITY' AS alert_type,
                    'Daily Mortality Rate (%)' AS parameter,
                    CASE
                      WHEN COALESCE(LAG(pl.net_count) OVER (PARTITION BY pl.flock_id ORDER BY pl.production_log_date), pl.net_count + (pl.day_mortality_count + pl.night_mortality_count)) = 0
                      THEN 0
                      ELSE (pl.day_mortality_count + pl.night_mortality_count) * 100.0 /
                           COALESCE(LAG(pl.net_count) OVER (PARTITION BY pl.flock_id ORDER BY pl.production_log_date), pl.net_count + (pl.day_mortality_count + pl.night_mortality_count))
                    END AS value,
                    CONCAT('>=', $mortality_pct_alert) AS threshold,
                    pl.production_log_date AS alert_time
                  FROM production_logs pl
                  JOIN flocks f ON f.id = pl.flock_id
                  JOIN sheds  s ON s.id = f.shed_id
                  WHERE s.farm_id = {$filters['farm_id']}
                    AND (pl.production_log_date BETWEEN '{$start_date}' AND '{$end_date}')
                )
                SELECT * FROM env_alerts
                UNION ALL
                SELECT * FROM mortality_alerts
                WHERE value >= $mortality_pct_alert
                ORDER BY alert_time DESC";

        return DB::select($sql);
    }


}
