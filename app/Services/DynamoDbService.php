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
     * @param  array     $deviceIds
     * @param  int|null  $fromTimestamp  Start of range (unix)
     * @param  int|null  $toTimestamp    End of range (unix)
     * @param  bool      $latest         If true, return only latest record per device
     * @param  bool      $ascOrder       Sort direction
     * @return array  [device_id => record] for latest, [device_id => [records]] for range
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
                // PostgreSQL DISTINCT ON — one query for ALL devices
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
                $query = DB::table('sensor_data_raw')
                    ->whereIn('device_id', $deviceIds);

                if ($fromTimestamp !== null) {
                    $query->where('timestamp', '>=', $fromTimestamp);
                }
                if ($toTimestamp !== null) {
                    $query->where('timestamp', '<=', $toTimestamp);
                }

                $rows = $query->orderBy('timestamp', $ascOrder ? 'asc' : 'desc')->get();

                foreach ($rows as $row) {
                    $record = json_decode($row->readings, true) ?? [];
                    $record['device_id'] = $row->device_id;
                    $record['timestamp'] = $row->timestamp;
                    $results[$row->device_id][] = $record;
                }
            }

            // Fill missing devices with null (same behaviour as old DynamoDB version)
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
                    $record['metrics'] = $row->metrics ? json_decode($row->metrics, true) : null;
                    $results[$row->device_id] = $record;
                }
            } else {
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
                    $record['metrics'] = $row->metrics ? json_decode($row->metrics, true) : null;
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
