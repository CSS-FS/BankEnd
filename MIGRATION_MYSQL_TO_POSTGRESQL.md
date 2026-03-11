# FlockSense: MySQL + DynamoDB → PostgreSQL Migration Guide

> **Date:** 2026-03-10
> **Branch:** `feature/user-should-not-login-with-blocked-status`
> **Scope:** Replace MySQL + AWS DynamoDB with a single PostgreSQL database

---

## Table of Contents

1. [Overview](#1-overview)
2. [Current State](#2-current-state)
3. [What Changes](#3-what-changes)
4. [Script 1 — New Migration: `sensor_data_raw` Table](#4-script-1--new-migration-sensor_data_raw-table)
5. [Script 2 — New Migration: `appliance_status_history` Table](#5-script-2--new-migration-appliance_status_history-table)
6. [Script 3 — `DynamoDbService` Rewrite (PostgreSQL)](#6-script-3--dynamodbservice-rewrite-postgresql)
7. [Script 4 — Raw Query Fixes (MySQL → PostgreSQL)](#7-script-4--raw-query-fixes-mysql--postgresql)
8. [Script 5 — `.env` Changes](#8-script-5--env-changes)
9. [Script 6 — Migration Compatibility Fixes](#9-script-6--migration-compatibility-fixes)
10. [Files Affected Summary](#10-files-affected-summary)
11. [DynamoDbService Callers (No Change Needed)](#11-dynamodbservice-callers-no-change-needed)
12. [Step-by-Step Execution Plan](#12-step-by-step-execution-plan)
13. [Risks & Rollback](#13-risks--rollback)

---

## 1. Overview

This migration moves FlockSense from a **dual-database architecture** (MySQL + DynamoDB) to a **single PostgreSQL database**.

### Benefits

| Benefit | Detail |
|---|---|
| **Single database** | No more managing MySQL + DynamoDB separately |
| **JSONB support** | Schema-less flexibility (like DynamoDB) with SQL power |
| **DISTINCT ON** | Latest record per device in one query (was a loop in DynamoDB) |
| **Better JSON operators** | `@>`, `->>`, `#>` (superior to MySQL JSON_EXTRACT) |
| **Native partitioning** | Time-series performance without DynamoDB cost |
| **Cost saving** | No AWS DynamoDB charges |

---

## 2. Current State

| Component | Details |
|---|---|
| **MySQL** | `flock_sende` database, 81 migrations, 63 models |
| **DynamoDB** | 2 tables: `sensor-data`, `device-appliance-status` |
| **DynamoDbService** | `app/Services/DynamoDbService.php` — 6 public methods |
| **Callers** | 14 files call DynamoDbService (controllers + services) |
| **Raw queries** | 7 files use MySQL-specific SQL (`DB::raw`, `whereRaw`, etc.) |

---

## 3. What Changes

| Category | Files | Effort |
|---|---|---|
| New migration (`sensor_data_raw`) | 1 new file | Low |
| New migration (`appliance_status_history`) | 1 new file | Low |
| DynamoDbService rewrite | 1 file replace | Medium |
| JSON query fix (`IotAlertService`) | 1 file, 2 lines | Low |
| TIMESTAMPDIFF fix (`AlertController`) | 1 file, 1 line | Low |
| DATE() cast fix (`ProductionAnalyticsService`) | 1 file, ~12 lines | Low |
| IFNULL→COALESCE (`ShedAnalyticsService`) | 1 file, few lines | Low |
| `.env` update | 1 file | Trivial |
| Migration `->after()` removal | 1 file, 1 line | Trivial |
| **Callers — NO changes needed** | **14 files untouched** | Same interface |

---

## 4. Script 1 — New Migration: `sensor_data_raw` Table

> Replaces DynamoDB `sensor-data` table

**File:** `database/migrations/2026_03_10_000001_create_sensor_data_raw_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_data_raw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->bigInteger('timestamp');
            $table->jsonb('readings');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            // Composite indexes for fast time-range queries
            $table->index(['device_id', 'timestamp'], 'idx_sensor_device_timestamp');
            $table->index(['device_id', 'recorded_at'], 'idx_sensor_device_recorded');
        });

        DB::statement("
            COMMENT ON TABLE sensor_data_raw
            IS 'Replaces DynamoDB sensor-data table. Stores raw IoT sensor readings per device.';
        ");

        DB::statement("
            COMMENT ON COLUMN sensor_data_raw.readings
            IS 'JSONB — all sensor values: {temp1, temp2, humidity, nh3, co2, air_velocity, air_pressure, ...}';
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_data_raw');
    }
};
```

### Table Structure

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `device_id` | bigint FK → devices | Which device sent this data |
| `timestamp` | bigint | Unix timestamp from the IoT device |
| `readings` | jsonb | All sensor values: `{temp1, temp2, humidity, nh3, co2, ...}` |
| `recorded_at` | timestamp | Server-side receive time |
| `created_at` | timestamp | Laravel timestamp |
| `updated_at` | timestamp | Laravel timestamp |

### Why JSONB for readings?

DynamoDB is schema-less — devices can send any combination of sensor fields. JSONB preserves this flexibility while adding PostgreSQL's powerful JSON querying (`->>`, `@>`, indexing).

---

## 5. Script 2 — New Migration: `appliance_status_history` Table

> Replaces DynamoDB `device-appliance-status` table

**File:** `database/migrations/2026_03_10_000002_create_appliance_status_history_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appliance_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('appliance_key');
            $table->boolean('status')->default(false);
            $table->bigInteger('timestamp');
            $table->jsonb('metrics')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            // Composite indexes
            $table->index(['device_id', 'timestamp'], 'idx_appliance_device_timestamp');
            $table->index(
                ['device_id', 'appliance_key', 'timestamp'],
                'idx_appliance_device_key_time'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appliance_status_history');
    }
};
```

### Table Structure

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `device_id` | bigint FK → devices | Which device |
| `appliance_key` | string | e.g., `f1` (fan), `b1` (brooder), `c1` (cooler), `l1` (light) |
| `status` | boolean | On/Off |
| `timestamp` | bigint | Unix timestamp from device |
| `metrics` | jsonb nullable | Speed, intensity, power, etc. |
| `source` | string nullable | `auto`, `manual`, `api` |
| `recorded_at` | timestamp | Server-side receive time |

---

## 6. Script 3 — `DynamoDbService` Rewrite (PostgreSQL)

> Same class name, same method signatures — callers don't need changes

**File:** `app/Services/DynamoDbService.php` (replace entire file)

```php
<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DynamoDbService
{
    // =========================================================================
    // WRITE METHODS
    // =========================================================================

    /**
     * Store sensor data.
     *
     * Previously: DynamoDB putItem on 'sensor-data' table
     * Now: PostgreSQL INSERT into 'sensor_data_raw'
     *
     * @param array $data Must include 'device_id', 'timestamp'. All other keys become JSONB readings.
     */
    public function putSensorData(array $data): void
    {
        if (empty($data['device_id'])) {
            return;
        }

        try {
            $deviceId  = $data['device_id'];
            $timestamp = $data['timestamp'] ?? now()->timestamp;

            // Everything except device_id and timestamp goes into readings JSONB
            $readings = collect($data)
                ->except(['device_id', 'timestamp'])
                ->toArray();

            DB::table('sensor_data_raw')->insert([
                'device_id'   => $deviceId,
                'timestamp'   => $timestamp,
                'readings'    => json_encode($readings),
                'recorded_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (Exception $e) {
            Log::error('[DynamoDbService] Failed to store sensor data.', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
        }
    }

    /**
     * Store appliance status history.
     *
     * Previously: DynamoDB putItem on 'device-appliance-status' table
     * Now: PostgreSQL INSERT into 'appliance_status_history'
     *
     * @param array $data Must include 'device_id'. Expects 'key'/'appliance_key', 'status', 'timestamp'.
     */
    public function putApplianceData(array $data): void
    {
        if (empty($data['device_id'])) {
            return;
        }

        try {
            DB::table('appliance_status_history')->insert([
                'device_id'     => $data['device_id'],
                'appliance_key' => $data['key'] ?? $data['appliance_key'] ?? 'unknown',
                'status'        => $data['status'] ?? false,
                'timestamp'     => $data['timestamp'] ?? now()->timestamp,
                'metrics'       => isset($data['metrics']) ? json_encode($data['metrics']) : null,
                'source'        => $data['source'] ?? null,
                'recorded_at'   => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (Exception $e) {
            Log::error('[DynamoDbService] Failed to store appliance status.', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
        }
    }

    // =========================================================================
    // READ METHODS
    // =========================================================================

    /**
     * Get sensor data for one or more devices with optional time range.
     *
     * Previously: DynamoDB Query per device_id (loop)
     * Now: PostgreSQL SELECT with DISTINCT ON for latest, or range query
     *
     * @param  array     $deviceIds      List of device IDs
     * @param  int|null  $fromTimestamp   Start of range (unix)
     * @param  int|null  $toTimestamp     End of range (unix)
     * @param  bool      $latest         If true, return only latest record per device
     * @param  bool      $ascOrder       Sort direction
     * @return array     [device_id => record] for latest, [device_id => [records]] for range
     */
    public function getSensorData(
        array $deviceIds,
        ?int $fromTimestamp,
        ?int $toTimestamp = null,
        bool $latest = false,
        bool $ascOrder = true
    ): array {
        $results = [];

        if (empty($deviceIds)) {
            return $results;
        }

        try {
            if ($latest) {
                // ✅ PostgreSQL DISTINCT ON — one query for ALL devices
                // (DynamoDB required a separate query per device)
                $rows = DB::table('sensor_data_raw')
                    ->select(DB::raw('DISTINCT ON (device_id) *'))
                    ->whereIn('device_id', $deviceIds)
                    ->orderBy('device_id')
                    ->orderByDesc('timestamp')
                    ->get();

                foreach ($rows as $row) {
                    $record = json_decode($row->readings, true) ?? [];
                    $record['device_id'] = $row->device_id;
                    $record['timestamp'] = $row->timestamp;
                    $results[$row->device_id] = $record;
                }
            } else {
                // Range query
                $query = DB::table('sensor_data_raw')
                    ->whereIn('device_id', $deviceIds);

                if ($fromTimestamp !== null) {
                    $query->where('timestamp', '>=', $fromTimestamp);
                }
                if ($toTimestamp !== null) {
                    $query->where('timestamp', '<=', $toTimestamp);
                }

                $query->orderBy('timestamp', $ascOrder ? 'asc' : 'desc');
                $rows = $query->get();

                foreach ($rows as $row) {
                    $record = json_decode($row->readings, true) ?? [];
                    $record['device_id'] = $row->device_id;
                    $record['timestamp'] = $row->timestamp;
                    $results[$row->device_id][] = $record;
                }
            }

            // Fill missing devices with null (same behavior as DynamoDB version)
            foreach ($deviceIds as $id) {
                if (! isset($results[$id])) {
                    $results[$id] = null;
                }
            }
        } catch (Exception $e) {
            Log::error('[DynamoDbService] Failed to fetch sensor data.', [
                'error'     => $e->getMessage(),
                'deviceIds' => $deviceIds,
            ]);

            foreach ($deviceIds as $id) {
                $results[$id] = null;
            }
        }

        return $results;
    }

    /**
     * Get latest sensor data per device.
     * Convenience wrapper around getSensorData with latest=true.
     */
    public function getLatestSensorData(array $deviceIds): array
    {
        return $this->getSensorData($deviceIds, null, null, true);
    }

    /**
     * Get appliance history for device(s).
     *
     * Previously: DynamoDB Query with optional FilterExpression
     * Now: PostgreSQL SELECT with optional WHERE on appliance_key
     *
     * @param  array        $deviceIds
     * @param  int|null     $fromTimestamp
     * @param  int|null     $toTimestamp
     * @param  bool         $latest          Return only latest per device+key
     * @param  string|null  $applianceKey    Optional filter by appliance_key
     * @param  bool         $ascOrder
     * @return array
     */
    public function getApplianceHistory(
        array $deviceIds,
        ?int $fromTimestamp,
        ?int $toTimestamp = null,
        bool $latest = false,
        ?string $applianceKey = null,
        bool $ascOrder = true
    ): array {
        $results = [];

        if (empty($deviceIds)) {
            return $results;
        }

        try {
            if ($latest) {
                // Latest per device + appliance_key combination
                $rows = DB::table('appliance_status_history')
                    ->select(DB::raw('DISTINCT ON (device_id, appliance_key) *'))
                    ->whereIn('device_id', $deviceIds)
                    ->when($applianceKey, fn ($q) => $q->where('appliance_key', $applianceKey))
                    ->orderBy('device_id')
                    ->orderBy('appliance_key')
                    ->orderByDesc('timestamp')
                    ->get();

                foreach ($rows as $row) {
                    $record = (array) $row;
                    $record['metrics'] = $row->metrics
                        ? json_decode($row->metrics, true)
                        : null;
                    $results[$row->device_id] = $record;
                }
            } else {
                // Range query
                $query = DB::table('appliance_status_history')
                    ->whereIn('device_id', $deviceIds)
                    ->when($applianceKey, fn ($q) => $q->where('appliance_key', $applianceKey));

                if ($fromTimestamp !== null) {
                    $query->where('timestamp', '>=', $fromTimestamp);
                }
                if ($toTimestamp !== null) {
                    $query->where('timestamp', '<=', $toTimestamp);
                }

                $rows = $query->orderBy('timestamp', $ascOrder ? 'asc' : 'desc')->get();

                foreach ($rows as $row) {
                    $record = (array) $row;
                    $record['metrics'] = $row->metrics
                        ? json_decode($row->metrics, true)
                        : null;
                    $results[$row->device_id][] = $record;
                }
            }

            // Fill missing devices with null
            foreach ($deviceIds as $id) {
                if (! isset($results[$id])) {
                    $results[$id] = null;
                }
            }
        } catch (Exception $e) {
            Log::error('[DynamoDbService] Failed to fetch appliance history.', [
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Get latest appliance status for a single device.
     */
    public function getLatestApplianceStatus(int $deviceId, ?string $applianceKey = null)
    {
        $items = $this->getApplianceHistory([$deviceId], null, null, true, $applianceKey, false);

        return $items[$deviceId] ?? null;
    }
}
```

### Key Improvements Over DynamoDB Version

| Feature | DynamoDB (Before) | PostgreSQL (After) |
|---|---|---|
| Latest per device | Loop: 1 query per device_id | Single `DISTINCT ON` query |
| Key type guessing | Tries numeric then string partition key | Not needed — typed columns |
| Pagination | Manual `LastEvaluatedKey` loop | Not needed — SQL handles it |
| Filter expressions | DynamoDB FilterExpression | Simple `WHERE` clause |
| Debug logging | Extensive per-attempt logging | Clean single query logging |
| Error handling | Per-device try/catch in loop | Single try/catch block |

---

## 7. Script 4 — Raw Query Fixes (MySQL → PostgreSQL)

### 4a. `app/Services/IotAlertService.php` — JSON Query Syntax

**Lines ~156-157**

```php
// ❌ BEFORE (MySQL JSON_EXTRACT):
->whereRaw("JSON_EXTRACT(details, '$.parameter') = ?", [$parameter])
->whereRaw("JSON_EXTRACT(details, '$.alert_type') = ?", [$alertType])

// ✅ AFTER (PostgreSQL JSONB ->> operator):
->whereRaw("details->>'parameter' = ?", [$parameter])
->whereRaw("details->>'alert_type' = ?", [$alertType])
```

**Why:** MySQL uses `JSON_EXTRACT()` function, PostgreSQL uses `->>` operator to extract JSON text values. The `->>` operator returns text, `->` returns JSON.

---

### 4b. `app/Http/Controllers/Api/V1/AlertController.php` — TIMESTAMPDIFF

**Line ~313**

```php
// ❌ BEFORE (MySQL TIMESTAMPDIFF):
->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, alerts.created_at, alert_responses.responded_at)) as avg_minutes')

// ✅ AFTER (PostgreSQL EXTRACT + EPOCH):
->selectRaw('AVG(EXTRACT(EPOCH FROM (alert_responses.responded_at - alerts.created_at)) / 60) as avg_minutes')
```

**Why:** MySQL has `TIMESTAMPDIFF(unit, start, end)`. PostgreSQL uses `EXTRACT(EPOCH FROM interval)` to get seconds, then divide by 60 for minutes.

---

### 4c. `app/Services/ProductionAnalyticsService.php` — DATE() Function

**~12 occurrences across the file**

```php
// ❌ BEFORE (MySQL DATE()):
->selectRaw('... DATE(pl.production_log_date) as d ...')
->whereBetween(DB::raw('DATE(pl.production_log_date)'), [$dateFrom, $dateTo])
->groupBy('pl.flock_id', DB::raw('DATE(pl.production_log_date)'))

// ✅ AFTER (PostgreSQL ::date cast):
->selectRaw('... pl.production_log_date::date as d ...')
->whereBetween(DB::raw('pl.production_log_date::date'), [$dateFrom, $dateTo])
->groupBy('pl.flock_id', DB::raw('pl.production_log_date::date'))
```

**Why:** MySQL uses `DATE()` function, PostgreSQL uses `::date` cast syntax. Both extract the date part from a datetime.

**Full list of lines to change:**

| Line(s) | Context |
|---|---|
| ~19-25 | `selectRaw` in mortality query |
| ~34 | `whereBetween` in mortality query |
| ~37-42 | `selectRaw` in mortality rate subquery |
| ~57 | `selectRaw` in livability query |
| ~64-65 | `whereBetween` + `groupBy` in livability |
| ~79 | `selectRaw` in ADG query |
| ~86-87 | `whereBetween` + `groupBy` in ADG |
| ~102-104 | `selectRaw` in FCR query |
| ~111-112 | `whereBetween` + `groupBy` in FCR |
| ~125-130 | `selectRaw` in water/feed ratio |
| ~137-138 | `whereBetween` + `groupBy` in water/feed |
| ~152-154 | `selectRaw` in uniformity/PEF |
| ~161-162 | `whereBetween` + `groupBy` in uniformity |
| ~175-177 | `selectRaw` in feed/water per bird |
| ~184-185 | `whereBetween` + `groupBy` in feed/water |

---

### 4d. `app/Services/ShedAnalyticsService.php` — IFNULL and Complex Queries

```php
// ❌ BEFORE (MySQL IFNULL):
DB::raw('IFNULL(some_column, 0)')

// ✅ AFTER (PostgreSQL COALESCE — already used in most places):
DB::raw('COALESCE(some_column, 0)')
```

**Note:** The complex CTEs and window functions (`ROW_NUMBER() OVER`, `LAG() OVER`, `CASE WHEN`, `WITH ... AS`) used in `ShedAnalyticsService` are **fully compatible** with PostgreSQL — no changes needed for those.

---

### 4e. `app/Models/Shed.php` — No Change Needed

```php
// This is already PostgreSQL-compatible:
->whereRaw('1=0')  // ✅ Works in both MySQL and PostgreSQL
```

---

### 4f. Other Files — No Change Needed

These files use standard SQL that works in both MySQL and PostgreSQL:

- `app/Services/FlockChartsService.php` — `selectRaw('MAX(id) as id')` ✅
- `app/Services/GrowthForecastService.php` — `DB::raw('w.avg_weight as actual_weight')` ✅

---

## 8. Script 5 — `.env` Changes

**File:** `.env`

```env
# ============================================================
# BEFORE (MySQL):
# ============================================================
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=flock_sende
# DB_USERNAME=root
# DB_PASSWORD=

# ============================================================
# AFTER (PostgreSQL):
# ============================================================
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flock_sense
DB_USERNAME=postgres
DB_PASSWORD=your_password_here
```

---

## 9. Script 6 — Migration Compatibility Fixes

### What Works Without Changes

Laravel's Schema Builder is database-agnostic. These column types work in PostgreSQL:

| Laravel Method | MySQL | PostgreSQL | Status |
|---|---|---|---|
| `$table->json()` | JSON | jsonb | ✅ Works |
| `$table->enum()` | ENUM type | CHECK constraint | ✅ Works |
| `$table->boolean()` | tinyint(1) | boolean | ✅ Works |
| `$table->tinyInteger()` | tinyint | smallint | ✅ Works |
| `$table->unsignedBigInteger()` | bigint unsigned | bigint | ✅ Works |
| `$table->unsignedTinyInteger()` | tinyint unsigned | smallint | ✅ Works |
| `$table->softDeletes()` | timestamp | timestamp | ✅ Works |
| `$table->morphs()` | bigint + varchar | bigint + varchar | ✅ Works |
| `$table->foreignId()` | bigint unsigned | bigint | ✅ Works |
| `$table->longText()` | longtext | text | ✅ Works |
| `$table->text()` | text | text | ✅ Works |
| `$table->dateTime()` | datetime | timestamp | ✅ Works |
| `$table->timestamp()` | timestamp | timestamp | ✅ Works |

### What Needs Fixing

**File:** `database/migrations/2025_09_12_213701_add_time_window_field_to_iot_data_logs_table.php`

```php
// ❌ BEFORE (MySQL-specific ->after()):
$table->enum('time_window', ['hourly', '3h', '6h', '12h', 'daily', 'latest'])
    ->default('hourly')
    ->after('record_time');  // ← PostgreSQL does NOT support column ordering

// ✅ AFTER (Remove ->after()):
$table->enum('time_window', ['hourly', '3h', '6h', '12h', 'daily', 'latest'])
    ->default('hourly');
```

**Why:** PostgreSQL always adds new columns at the end of the table. The `->after()` modifier is MySQL-only and will throw an error in PostgreSQL.

---

## 10. Files Affected Summary

### Files That CHANGE

| # | File | Change Type | Lines Changed |
|---|---|---|---|
| 1 | `database/migrations/2026_03_10_000001_create_sensor_data_raw_table.php` | **NEW FILE** | ~30 |
| 2 | `database/migrations/2026_03_10_000002_create_appliance_status_history_table.php` | **NEW FILE** | ~25 |
| 3 | `app/Services/DynamoDbService.php` | **FULL REWRITE** | ~250 |
| 4 | `app/Services/IotAlertService.php` | 2 lines | `JSON_EXTRACT` → `->>'` |
| 5 | `app/Http/Controllers/Api/V1/AlertController.php` | 1 line | `TIMESTAMPDIFF` → `EXTRACT(EPOCH)` |
| 6 | `app/Services/ProductionAnalyticsService.php` | ~12 lines | `DATE()` → `::date` |
| 7 | `app/Services/ShedAnalyticsService.php` | ~2 lines | `IFNULL` → `COALESCE` |
| 8 | `database/migrations/2025_09_12_213701_add_time_window_field_to_iot_data_logs_table.php` | 1 line | Remove `->after()` |
| 9 | `.env` | 4 lines | Connection config |

### Files That DON'T Change (14 DynamoDbService Callers)

These files call `DynamoDbService` but need **zero changes** because the method signatures remain identical:

| # | File | Methods Called |
|---|---|---|
| 1 | `app/Http/Controllers/Api/V1/IoTDeviceDataController.php` | `putSensorData()`, `putApplianceData()` |
| 2 | `app/Http/Controllers/Api/V1/SensorDataController.php` | `putSensorData()`, `putApplianceData()` |
| 3 | `app/Services/IotDataAggregatorService.php` | `getSensorData()` |
| 4 | `app/Services/FarmService.php` | `getSensorData()` |
| 5 | `app/Http/Controllers/Web/LogsController.php` | `getSensorData()` |
| 6 | `app/Http/Controllers/Api/V1/ShedController.php` | `getSensorData()` |
| 7 | `app/Http/Controllers/Api/V1/FarmController.php` | `getSensorData()` |

---

## 11. DynamoDbService Callers (No Change Needed)

Detailed call map showing exactly which lines call which methods:

### `IoTDeviceDataController.php`

| Line | Method | Caller Function |
|---|---|---|
| 46 | `putSensorData()` | `storeSensor()` |
| 79 | `putSensorData()` | `storeMultipleSensor()` |
| 126 | `putApplianceData()` | `updateAppliance()` |
| 166 | `putApplianceData()` | `updateMultipleAppliances()` |
| 251 | `putApplianceData()` | `syncDeviceData()` |
| 268 | `putSensorData()` | `syncDeviceData()` |
| 304 | `putApplianceData()` | `syncMultipleDeviceData()` |
| 321 | `putSensorData()` | `syncMultipleDeviceData()` |

### `SensorDataController.php`

| Line | Method | Caller Function |
|---|---|---|
| 64 | `putSensorData()` | `store()` |
| 122 | `putSensorData()` | `syncDeviceData()` |
| 157 | `putSensorData()` | `storeWithTimestamp()` |
| 201 | `putSensorData()` | `storeMultiple()` |
| 242 | `putApplianceData()` | `storeMultiple()` |
| 318 | `putSensorData()` | `syncDeviceDataWithTimestamp()` |

### Other Callers

| File | Line | Method | Context |
|---|---|---|---|
| `IotDataAggregatorService.php` | 47 | `getSensorData()` | Hourly aggregation job |
| `FarmService.php` | 104 | `getSensorData()` | Farm detail with latest sensor data |
| `LogsController.php` | 174 | `getSensorData()` | Web log viewer |
| `ShedController.php` | 38, 93 | `getSensorData()` | Shed list + detail |
| `FarmController.php` | 118 | `getSensorData()` | Farm detail API |

---

## 12. Step-by-Step Execution Plan

### Prerequisites

```bash
# 1. Install PostgreSQL (if not installed)
# Windows: Download from https://www.postgresql.org/downloads/windows/
# Default port: 5432

# 2. Create database
psql -U postgres
CREATE DATABASE flock_sense;
\q

# 3. Enable PHP pdo_pgsql extension
# In php.ini, uncomment:
# extension=pdo_pgsql
# extension=pgsql
```

### Execution Order

```bash
# Step 1: Update .env (Script 5)
# Change DB_CONNECTION, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Step 2: Fix migration compatibility (Script 6)
# Remove ->after() from add_time_window migration

# Step 3: Run all existing migrations on PostgreSQL
php artisan migrate

# Step 4: Add new DynamoDB replacement tables (Scripts 1 & 2)
# Place migration files in database/migrations/
php artisan migrate

# Step 5: Replace DynamoDbService (Script 3)
# Overwrite app/Services/DynamoDbService.php

# Step 6: Apply raw query fixes (Script 4)
# Fix IotAlertService, AlertController, ProductionAnalyticsService, ShedAnalyticsService

# Step 7: Test
php artisan tinker
# > App\Services\DynamoDbService::class
# > DB::connection()->getPdo()  // should show pgsql

# Step 8: Run application
php artisan serve
# Test IoT endpoints, dashboard, analytics
```

---

## 13. Risks & Rollback

### Known Risks

| Risk | Severity | Mitigation |
|---|---|---|
| MySQL-specific raw queries missed | Medium | Grep for `DB::raw`, `whereRaw`, `selectRaw` — all found and documented |
| DynamoDB data loss | Low | Old DynamoDB tables remain untouched — this migration only adds PostgreSQL |
| `->after()` in migrations | Low | Only 1 occurrence — easy fix |
| JSONB query performance | Low | Proper indexes defined; can add GIN index on readings if needed |
| `ENUM` behavior difference | Low | PostgreSQL creates CHECK constraints — functionally identical |

### Rollback Plan

```bash
# To rollback to MySQL:
# 1. Revert .env to MySQL settings
# 2. Revert DynamoDbService.php from git
# 3. Revert raw query fixes from git
# PostgreSQL database can remain — no data destroyed
```

### Optional Future Improvements

```
1. Add GIN index on sensor_data_raw.readings for JSONB queries:
   CREATE INDEX idx_sensor_readings_gin ON sensor_data_raw USING GIN (readings);

2. Partition sensor_data_raw by month for large datasets:
   CREATE TABLE sensor_data_raw (...) PARTITION BY RANGE (recorded_at);

3. Remove aws/aws-sdk-php from composer.json if DynamoDB fully deprecated

4. Rename DynamoDbService → TimeSeriesService (optional, cosmetic)
```

---

> **Author:** Generated by Claude Code
> **Last Updated:** 2026-03-10
