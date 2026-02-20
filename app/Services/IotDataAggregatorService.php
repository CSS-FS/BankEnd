<?php

namespace App\Services;

use App\Models\Device;
use App\Models\IotDataLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IotDataAggregatorService
{
    public function __construct(
        protected DynamoDbService $dynamoDbService,
        protected IotAlertService $alertService
    ) {}

    /**
     * Main entry: aggregate last hour’s data for all devices.
     */
    public function aggregate(): void
    {
        $devices = Device::all();

        foreach ($devices as $device) {
            try {
                $this->aggregateDeviceData($device->id);
            } catch (\Throwable $e) {
                Log::error("[IotDataAggregator] Failed for device {$device->id}: ".$e->getMessage());
            }
        }
    }

    /**
     * Aggregate data for a single device — only for the last hour.
     */
    protected function aggregateDeviceData(int $deviceId): void
    {
        Log::info("[IotDataAggregator] Aggregating device {$deviceId}");

        $now = Carbon::now();

        $hourStart = $now->copy()->subHour()->startOfMinute();
        $hourEnd = $now->copy();

        // Get sensor data from DynamoDB
        $records = $this->dynamoDbService->getSensorData(
            [$deviceId],
            $hourStart->timestamp,
            $hourEnd->timestamp,
            false
        );

        // Handle both possible return types (old and new)
        if (empty($records)) {
            Log::info("[IotDataAggregator] No sensor data for device {$deviceId}");

            return;
        }

        // If the result looks like [deviceId => record], flatten it
        if (array_keys($records) === [$deviceId]) {
            $records = [$records[$deviceId]];
        }

        // Some DynamoDB responses may be nested arrays, normalize that
        if (isset($records[$deviceId]) && is_array($records[$deviceId]) && isset($records[$deviceId][0])) {
            $records = $records[$deviceId];
        }

        // Defensive check
        if (empty($records) || ! is_array($records)) {
            Log::warning("[IotDataAggregator] Device {$deviceId} has no valid record structure.");

            return;
        }

        // Ensure the first record exists
        $firstRecord = is_array(reset($records)) ? reset($records) : $records;
        if (empty($firstRecord)) {
            Log::warning("[IotDataAggregator] Empty record content for device {$deviceId}");

            return;
        }

        // Extract parameter keys dynamically
        $parameters = collect($firstRecord)
            ->keys()
            ->reject(fn ($key) => in_array(strtolower($key), ['device_id', 'timestamp', 'Timestamp']));

        if ($parameters->isEmpty()) {
            Log::warning("[IotDataAggregator] No measurable parameters for device {$deviceId}");

            return;
        }

        // Define 1-hour window
        $windowSeconds = 3600;
        $this->processWindow(
            $deviceId,
            is_array($records) ? $records : [$records],
            $parameters,
            'hourly',
            $windowSeconds,
            $hourStart->timestamp
        );

        // Process latest snapshot as well
        $this->processLatest($deviceId, is_array($records) ? $records : [$records], $parameters);

        Log::info("[IotDataAggregator] Completed hourly aggregation for device {$deviceId}");
    }

    /**
     * Aggregate all readings within the hourly window.
     */
    protected function processWindow(
        int $deviceId,
        array $records,
        $parameters,
        string $windowName,
        int $windowStart,
        int $windowEnd
    ): void {
        foreach ($parameters as $param) {
            $values = collect($records)
                ->map(fn ($r) => $r[$param] ?? null)
                ->filter(fn ($v) => $v !== null && is_numeric($v))
                ->map(fn ($v) => (float) $v);

            if ($values->isEmpty()) {
                Log::warning("[IotDataAggregator] No valid {$param} values for device {$deviceId} this hour.");

                continue;
            }

            $min = $values->min();
            $max = $values->max();
            $avg = round($values->avg(), 2);

            $recordTime = Carbon::createFromTimestamp($windowStart)->format('Y-m-d H:i:s');

            $shedId = $this->getShedIdForDevice($deviceId);

            IotDataLog::updateOrCreate(
                [
                    'device_id' => $deviceId,
                    'parameter' => $param,
                    'time_window' => $windowName,
                    'record_time' => $recordTime,
                ],
                [
                    'shed_id' => $shedId,
                    'min_value' => $min,
                    'max_value' => $max,
                    'avg_value' => $avg,
                ]
            );

            // 🚨 Check aggregated values against thresholds
            if ($shedId) {
                $this->alertService->checkAggregatedThreshold(
                    $deviceId,
                    $shedId,
                    $param,
                    $min,
                    $max,
                    $avg
                );
            }
        }
    }

    /**
     * Store the most recent snapshot (latest reading per parameter).
     */
    protected function processLatest(int $deviceId, array $records, $parameters): void
    {
        $latest = collect($records)
            ->sortByDesc(fn ($r) => (int) ($r['timestamp'] ?? $r['Timestamp'] ?? 0))
            ->first();

        if (! $latest) {
            Log::warning("[IotDataAggregator] No latest record found for device {$deviceId}");

            return;
        }

        $recordTime = Carbon::createFromTimestamp((int) ($latest['timestamp'] ?? $latest['Timestamp']))
            ->format('Y-m-d H:i:s');

        foreach ($parameters as $param) {
            $val = $latest[$param] ?? null;

            if ($val === null || ! is_numeric($val)) {
                continue;
            }

            $val = (float) $val;

            IotDataLog::updateOrCreate(
                [
                    'device_id' => $deviceId,
                    'parameter' => $param,
                    'time_window' => 'latest',
                    'record_time' => $recordTime,
                ],
                [
                    'shed_id' => $this->getShedIdForDevice($deviceId),
                    'min_value' => $val,
                    'max_value' => $val,
                    'avg_value' => $val,
                ]
            );
        }
    }

    /**
     * Lookup shed_id for the given device.
     */
    protected function getShedIdForDevice(int $deviceId): ?int
    {
        return DB::table('shed_devices')
            ->where('device_id', $deviceId)
            ->value('shed_id');
    }
}

//
// namespace App\Services;
//
// use App\Models\IotDataLog;
// use App\Models\Device;
// use Carbon\Carbon;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
//
// class IotDataAggregatorService
// {
//    public function __construct(protected DynamoDbService $dynamoDbService)
//    {
//    }
//
//    /**
//     * Aggregate sensor data for all devices into time windows.
//     */
//    public function aggregate(): void
//    {
//        $devices = Device::all();
//
//        foreach ($devices as $device) {
//            try {
//                $this->aggregateDeviceData($device->id);
//            } catch (\Throwable $e) {
//                Log::error("[IotDataAggregator] Failed for device {$device->id}: " . $e->getMessage());
//            }
//        }
//    }
//
//    /**
//     * Aggregate data for a single device.
//     */
//    protected function aggregateDeviceData(int $deviceId): void
//    {
//        Log::info("[IotDataAggregator] Aggregating device {$deviceId}");
//
//        $now = Carbon::now();
//        $dayStart = $now->copy()->startOfDay();
//        $dayEnd = $now->copy()->endOfDay();
//
//        // try today's data
//        $records = $this->dynamoDbService->getSensorData(
//            [$deviceId],
//            $dayStart->timestamp,
//            $dayEnd->timestamp,
//            false
//        );
//
//        // if empty, fetch latest and then fetch that day
//        if (empty($records)) {
//            $latest = $this->dynamoDbService->getSensorData([$deviceId], null, null, true);
//
//            if (empty($latest)) {
//                Log::info("[IotDataAggregator] No sensor data at all for device {$deviceId}");
//                return;
//            }
//
//            $latestTs = (int)($latest[0]['timestamp'] ?? $latest[0]['Timestamp'] ?? 0);
//            if (!$latestTs) {
//                Log::warning("[IotDataAggregator] Latest record did not contain a timestamp for device {$deviceId}");
//                return;
//            }
//
//            $dayStart = Carbon::createFromTimestamp($latestTs)->startOfDay();
//            $dayEnd = Carbon::createFromTimestamp($latestTs)->endOfDay();
//
//            $records = $this->dynamoDbService->getSensorData(
//                [$deviceId],
//                $dayStart->timestamp,
//                $dayEnd->timestamp,
//                false
//            );
//
//            if (empty($records)) {
//                Log::info("[IotDataAggregator] No records found for latest day ({$dayStart->toDateString()}) for device {$deviceId}");
//                return;
//            }
//
//            Log::info("[IotDataAggregator] Found " . count($records) . " records for device {$deviceId} on {$dayStart->toDateString()}");
//        } else {
//            Log::info("[IotDataAggregator] Found today's " . count($records) . " records for device {$deviceId}");
//        }
//
//        // extract parameter keys
//        $parameters = collect($records[0])
//            ->keys()
//            ->reject(fn($key) => in_array(strtolower($key), ['device_id', 'timestamp', 'Timestamp']));
//
//        // define time windows
//        $windows = [
//            'hourly' => 3600,
//            '3h' => 3 * 3600,
//            '6h' => 6 * 3600,
//            '12h' => 12 * 3600,
//            'daily' => 24 * 3600,
//        ];
//
//        foreach ($windows as $windowName => $windowSeconds) {
//            $this->processWindow(
//                $deviceId,
//                $records,
//                $parameters,
//                $windowName,
//                $windowSeconds,
//                $dayStart->timestamp
//            );
//        }
//
//        // process latest separately
//        $this->processLatest($deviceId, $records, $parameters);
//
//        Log::info("[IotDataAggregator] Completed aggregation for device {$deviceId} for day {$dayStart->toDateString()}");
//    }
//
//    /**
//     * Process one time window and store aggregated logs.
//     */
//    protected function processWindow(
//        int    $deviceId,
//        array  $records,
//               $parameters,
//        string $windowName,
//        int    $windowSeconds,
//        int    $startTimestamp
//    ): void
//    {
//        $daySeconds = 86400;
//        $endTimestamp = $startTimestamp + $daySeconds;
//
//        // pre-build bucket ranges
//        $bucketRanges = [];
//        for ($ts = $startTimestamp; $ts < $endTimestamp; $ts += $windowSeconds) {
//            $bucketRanges[$ts] = [];
//        }
//
//        // assign records
//        foreach ($records as $record) {
//            $recTs = (int)($record['timestamp'] ?? $record['Timestamp'] ?? 0);
//            if ($recTs <= 0) {
//                continue;
//            }
//
//            $bucketIndex = (int)floor(($recTs - $startTimestamp) / $windowSeconds);
//            $bucketStart = $startTimestamp + $bucketIndex * $windowSeconds;
//
//            if ($bucketStart < $startTimestamp || $bucketStart >= $endTimestamp) {
//                continue;
//            }
//
//            $bucketRanges[$bucketStart][] = $record;
//        }
//
//        // aggregate per bucket
//        foreach ($bucketRanges as $bucketStart => $bucketRecords) {
//            foreach ($parameters as $param) {
//                $values = collect($bucketRecords)
//                    ->map(fn($rec) => $rec[$param] ?? null)
//                    ->filter(fn($v) => $v !== null && is_numeric($v))
//                    ->map(fn($v) => (float)$v);
//
//                if ($values->isEmpty()) {
//                    Log::warning("[IotDataAggregator] Skipping {$param} for device {$deviceId}, bucket {$bucketStart} - no numeric values");
//                    continue;
//                }
//
//                $min = $values->min();
//                $max = $values->max();
//                $avg = round($values->avg(), 2);
//
//                $recordTime = Carbon::createFromTimestamp($bucketStart)->format('Y-m-d H:i:s');
//
//                IotDataLog::updateOrCreate(
//                    [
//                        'device_id' => $deviceId,
//                        'parameter' => $param,
//                        'time_window' => $windowName,
//                        'record_time' => $recordTime,
//                    ],
//                    [
//                        'shed_id' => $this->getShedIdForDevice($deviceId),
//                        'min_value' => $min,
//                        'max_value' => $max,
//                        'avg_value' => $avg,
//                    ]
//                );
//            }
//        }
//    }
//
//    /**
//     * Process the latest single record snapshot.
//     */
//    protected function processLatest(int $deviceId, array $records, $parameters): void
//    {
//        $latest = collect($records)->sortByDesc(fn($r) => (int)($r['timestamp'] ?? $r['Timestamp'] ?? 0))->first();
//
//        if (!$latest) {
//            Log::warning("[IotDataAggregator] No latest record found for device {$deviceId}");
//            return;
//        }
//
//        $recordTime = Carbon::createFromTimestamp((int)($latest['timestamp'] ?? $latest['Timestamp']))->format('Y-m-d H:i:s');
//
//        foreach ($parameters as $param) {
//            $val = $latest[$param] ?? null;
//
//            if ($val === null || !is_numeric($val)) {
//                Log::warning("[IotDataAggregator] Latest record missing {$param} for device {$deviceId}");
//                continue;
//            }
//
//            $val = (float)$val;
//
//            IotDataLog::updateOrCreate(
//                [
//                    'device_id' => $deviceId,
//                    'parameter' => $param,
//                    'time_window' => 'latest',
//                    'record_time' => $recordTime,
//                ],
//                [
//                    'shed_id' => $this->getShedIdForDevice($deviceId),
//                    'min_value' => $val,
//                    'max_value' => $val,
//                    'avg_value' => $val,
//                ]
//            );
//        }
//
//        Log::info("[IotDataAggregator] Stored latest snapshot for device {$deviceId} at {$recordTime}");
//    }
//
//    /**
//     * Lookup shed_id for a device (optional helper).
//     */
//    protected function getShedIdForDevice(int $deviceId): ?int
//    {
//        return DB::table('shed_devices')->where('device_id', $deviceId)->value('shed_id');
//    }
// }
