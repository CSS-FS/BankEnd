<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\ParameterDataExport;
use App\Http\Controllers\ApiController;
use App\Http\Resources\SensorDataResource;
use App\Models\Device;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\DeviceAppliance;
use App\Models\IotDataLog;
use App\Models\Shed;
use App\Models\ShedDevice;
use App\Services\DynamoDbService;
use App\Services\IotAlertService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SensorDataController extends ApiController
{
    public function __construct(
        protected DynamoDbService $dynamoDbService,
        protected IotAlertService $alertService
    ) {
        parent::__construct();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'timestamp' => 'nullable|integer', // 👈 allow optional timestamp
        ]);

        $device = Device::where('serial_no', $validated['device_serial'])->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        $validated['device_id'] = $device->id;

        // 👇 Use request timestamp if provided, otherwise current time
        $validated['timestamp'] = $validated['timestamp'] ?? Carbon::now()->timestamp;

        unset($validated['device_serial']);

        // 👇 Merge dynamic sensor fields back into validated array
        $sensorData = array_merge(
            $validated,
            collect($request->except(['device_serial']))  // all except serial
            ->reject(fn($value) => is_null($value))   // skip nulls
            ->toArray()
        );

        // 👇 New condition: if humidity exists, set it to zero
        if (array_key_exists('humidity', $sensorData)) {
            $sensorData['humidity'] = 0;
        }

        // ✅ Store in DynamoDB
        $this->dynamoDbService->putSensorData($sensorData);

        // 🚨 Check for parameter alerts
        $this->checkParameterAlerts($device->id, $sensorData);

        return response()->json(['message' => 'Sensor data stored successfully.'], 201);
    }

    /**
     * Update all appliance statuses & store sensor data
     */
    public function syncDeviceData(Request $request)
    {
        // ✅ Validate request
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'appliances' => 'required|array',
            'appliances.*' => 'boolean',
        ]);

        // 🔍 Get device
        $device = Device::where('serial_no', $validated['device_serial'])->firstOrFail();

        $updatedAppliances = [];

        // 🔄 Update or create appliances
        foreach ($validated['appliances'] as $key => $status) {
            $appliance = DeviceAppliance::firstOrNew([
                'device_id' => $device->id,
                'key' => $key,
            ]);

            if (!$appliance->exists) {
                $type = $this->getApplianceTypeFromKey($key);
                $appliance->fill([
                    'type' => $type,
                    'name' => ucfirst($type) . ' ' . strtoupper($key),
                ]);
            }

            $appliance->status = $status;
            $appliance->status_updated_at = now();
            $appliance->save();

            $updatedAppliances[] = $appliance;
        }

        // 📦 Prepare DynamoDB payload
        $sensorData = array_merge(
            [
                'device_id' => $device->id,
                'timestamp' => $validated['timestamp'] ?? Carbon::now()->timestamp,
            ],
            collect($request->except(['device_serial', 'appliances']))->toArray() // 👈 exclude appliances
        );


        // 💾 Store in DynamoDB
        $this->dynamoDbService->putSensorData($sensorData);

        // 🚨 Check for parameter alerts
        $this->checkParameterAlerts($device->id, $sensorData);

        return response()->json([
            'message' => 'Device appliances updated and sensor data stored successfully.',
        ], 201);
    }

    public function storeWithTimestamp(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'timestamp' => 'required|integer', // Allow timestamp from client
        ]);

        $device = Device::where('serial_no', $validated['device_serial'])->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        $validated['device_id'] = $device->id;
        unset($validated['device_serial']);

        // 👇 Merge dynamic sensor fields
        $sensorData = array_merge(
            $validated,
            collect($request->except(['device_serial'])) // Keep everything else
            ->reject(fn($value) => is_null($value))
                ->toArray()
        );

        // ✅ Store in DynamoDB
        $this->dynamoDbService->putSensorData($sensorData);

        // 🚨 Check for parameter alerts
        $this->checkParameterAlerts($device->id, $sensorData);

        return response()->json(['message' => 'Sensor data stored successfully.'], 201);
    }

    public function storeMultiple(Request $request)
    {
        // ✅ Only validate envelope + appliance structure
        $request->validate([
            'device_serial' => 'required|string',
            'records' => 'required|array',
            'records.*.timestamp' => 'nullable|integer',
            'records.*.appliances' => 'nullable|array',
            'records.*.appliances.*' => 'boolean',
        ]);

        // ✅ Get the device
        $device = Device::where('serial_no', $request->input('device_serial'))->firstOrFail();

        // ✅ Use raw records to preserve dynamic sensor fields
        $rawRecords = $request->input('records', []);

        $latestRecord = null;
        $allProcessed = [];

        foreach ($rawRecords as $idx => $record) {
            $timestamp = $record['timestamp'] ?? Carbon::now()->timestamp;

            // --- SENSOR DATA ---
            $sensorFields = collect($record)
                ->except(['appliances', 'timestamp'])
                ->filter(fn($v) => $v !== null) // remove only nulls
                ->toArray();

            if (!empty($sensorFields)) {
                $dynamoSensorData = array_merge([
                    'device_id' => $device->id,
                    'timestamp' => $timestamp,
                ], $sensorFields);

                Log::info("[storeMultiple] Storing sensor data for record #$idx", $dynamoSensorData);
                $this->dynamoDbService->putSensorData($dynamoSensorData);

                // 🚨 Check for parameter alerts
                $this->checkParameterAlerts($device->id, $dynamoSensorData);
            } else {
                Log::info("[storeMultiple] No sensor fields in record #$idx");
            }

            // --- APPLIANCE DATA ---
            $dynamoApplianceData = [
                'device_id' => $device->id,
                'timestamp' => $timestamp,
            ];

            if (!empty($record['appliances']) && is_array($record['appliances'])) {
                foreach ($record['appliances'] as $key => $status) {
                    $appliance = DeviceAppliance::firstOrNew([
                        'device_id' => $device->id,
                        'key' => $key,
                    ]);

                    if (!$appliance->exists) {
                        $type = $this->getApplianceTypeFromKey($key);
                        $appliance->fill([
                            'type' => $type,
                            'name' => ucfirst($type) . ' ' . strtoupper($key),
                        ]);
                    }

                    $appliance->status = $status;
                    $appliance->status_updated_at = Carbon::createFromTimestamp($timestamp);
                    $appliance->save();

                    $dynamoApplianceData[$key] = [
                        'status' => (bool)$status,
                        'metrics' => $appliance->metrics ?? new \stdClass(),
                        'config' => $appliance->config ?? new \stdClass(),
                    ];
                }

                Log::info("[storeMultiple] Storing appliance data for record #$idx", $dynamoApplianceData);
                $this->dynamoDbService->putApplianceData($dynamoApplianceData);
            } else {
                Log::info("[storeMultiple] No appliances in record #$idx");
            }

            // --- Response + MySQL latest snapshot ---
            if (!$latestRecord || $timestamp > $latestRecord['timestamp']) {
                $latestRecord = [
                    'timestamp' => $timestamp,
                    'sensors' => $sensorFields,
                    'appliances' => $record['appliances'] ?? [],
                ];
            }

            $allProcessed[] = [
                'timestamp' => $timestamp,
                'sensors' => $sensorFields,
                'appliances' => $record['appliances'] ?? [],
            ];
        }

        return response()->json([
            'message' => 'All sensor + appliance records processed successfully.',
        ], 201);
    }

    /**
     * Update all appliance statuses & store sensor data (with timestamp)
     */
    public function syncDeviceDataWithTimestamp(Request $request)
    {
        // ✅ Validate request
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'timestamp' => 'nullable|integer',       // <-- NEW FIELD
            'appliances' => 'required|array',
            'appliances.*' => 'required|boolean',
        ]);

        // 🔍 Get device
        $device = Device::where('serial_no', $validated['device_serial'])->firstOrFail();

        $updatedAppliances = [];

        // 🔄 Update or create appliances
        foreach ($validated['appliances'] as $key => $status) {
            $appliance = DeviceAppliance::firstOrNew([
                'device_id' => $device->id,
                'key' => $key,
            ]);

            if (!$appliance->exists) {
                $type = $this->getApplianceTypeFromKey($key);
                $appliance->fill([
                    'type' => $type,
                    'name' => ucfirst($type) . ' ' . strtoupper($key),
                ]);
            }

            $appliance->status = $status;
            $appliance->status_updated_at = now();
            $appliance->save();

            $updatedAppliances[] = $appliance;
        }

        // 📦 Prepare DynamoDB payload with timestamp
        $sensorData = array_merge(
            [
                'device_id' => $device->id,
                'timestamp' => $validated['timestamp'] ?? Carbon::now()->timestamp,
            ],
            collect($request->except(['device_serial', 'appliances']))->toArray()
        );

        // 💾 Store in DynamoDB
        $this->dynamoDbService->putSensorData($sensorData);

        return response()->json([
            'message' => 'Device appliances updated and sensor data stored successfully.',
        ], 201);
    }

    public function fetchByShed(Request $request, int $shedId)
    {
        $validated = $request->validate([
            'range' => 'nullable|in:latest,last_hour,last_12_hours,day,week,month,custom',
            'from' => 'required_if:range,custom|date_format:Y-m-d H:i:s',
            'to' => 'required_if:range,custom|date_format:Y-m-d H:i:s|after_or_equal:from',
        ]);

        $deviceIds = ShedDevice::where('shed_id', $shedId)->pluck('device_id')->toArray();
        if (empty($deviceIds)) {
            return response()->json(['data' => []], 200);
        }

        $query = IotDataLog::whereIn('device_id', $deviceIds);

        // 🔍 Range filtering
        if (($validated['range'] ?? null) === 'custom') {
            $query->whereBetween('record_time', [$validated['from'], $validated['to']]);
        } elseif (($validated['range'] ?? null) === 'latest') {
            $query->where('time_window', 'latest');
        } elseif (!empty($validated['range'])) {
            $from = Carbon::createFromTimestamp($this->getTimeRange($validated['range']))->toDateTimeString();
            $query->where('record_time', '>=', $from);
        }

        $logs = $query->get();

        // 🔄 Group by device + timestamp + window
        $grouped = $logs->groupBy(fn($log) => $log->device_id . '|' . $log->record_time . '|' . $log->time_window)
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'device_id' => $first->device_id,
                    'record_time' => $first->record_time,
                    'time_window' => $first->time_window,
                    'parameters' => $rows->mapWithKeys(fn($row) => [
                        $row->parameter => [
                            'min' => $row->min_value,
                            'max' => $row->max_value,
                            'avg' => $row->avg_value,
                        ]
                    ]),
                ];
            })->values();

        return SensorDataResource::collection($grouped);
    }

    public function fetchByFarm(Request $request, int $farmId)
    {
        $validated = $request->validate([
            'range' => 'nullable|in:latest,last_hour,last_12_hours,day,week,month,custom',
            'from' => 'required_if:range,custom|date_format:Y-m-d H:i:s',
            'to' => 'required_if:range,custom|date_format:Y-m-d H:i:s|after_or_equal:from',
        ]);

        $shedIds = Shed::where('farm_id', $farmId)->pluck('id')->toArray();
        if (empty($shedIds)) {
            return response()->json(['data' => []], 200);
        }

        $deviceIds = ShedDevice::whereIn('shed_id', $shedIds)->pluck('device_id')->toArray();
        if (empty($deviceIds)) {
            return response()->json(['data' => []], 200);
        }

        $query = IotDataLog::whereIn('device_id', $deviceIds);

        // 🔍 Range filtering
        if (($validated['range'] ?? null) === 'custom') {
            $query->whereBetween('record_time', [$validated['from'], $validated['to']]);
        } elseif (($validated['range'] ?? null) === 'latest') {
            $query->where('time_window', 'latest');
        } elseif (!empty($validated['range'])) {
            $from = Carbon::createFromTimestamp($this->getTimeRange($validated['range']))->toDateTimeString();
            $query->where('record_time', '>=', $from);
        }

        $logs = $query->get();

        // 🔄 Group by device + timestamp + window
        $grouped = $logs->groupBy(fn($log) => $log->device_id . '|' . $log->record_time . '|' . $log->time_window)
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'device_id' => $first->device_id,
                    'record_time' => $first->record_time,
                    'time_window' => $first->time_window,
                    'parameters' => $rows->mapWithKeys(fn($row) => [
                        $row->parameter => [
                            'min' => $row->min_value,
                            'max' => $row->max_value,
                            'avg' => $row->avg_value,
                        ]
                    ]),
                ];
            })->values();

        return SensorDataResource::collection($grouped);
    }

    /**
     * Helper: Infer type from key
     */
    private function getApplianceTypeFromKey(string $key): string
    {
        $firstChar = strtolower(substr($key, 0, 1));
        return match ($firstChar) {
            'f' => 'fan',
            'b' => 'brooder',
            'c' => 'cooling_pad',
            'l' => 'light',
            'e' => 'exhaust',
            'h' => 'heater',
            default => 'appliance'
        };
    }

    private function getTimeRange(string $range): ?int
    {
        return match ($range) {
            'latest' => null,
            'last_hour' => now()->subHour()->timestamp,
            'last_12_hours' => now()->subHours(12)->timestamp,
            'day' => now()->subDay()->timestamp,
            'week' => now()->subWeek()->timestamp,
            'month' => now()->subMonth()->timestamp,
            default => null,
        };
    }

    /**
     * Fetch parameter-specific data with statistics and chart data
     */
    public function fetchParameterData(Request $request, int $shedId, string $parameter)
    {
        $validated = $request->validate([
            'time_range' => 'nullable|in:24hour,last_week,current_month,custom',
            'from' => 'required_if:time_range,custom|date',
            'to' => 'required_if:time_range,custom|date|after_or_equal:from',
        ]);

        // Map parameter aliases to actual database parameter names
        $parameterMapping = [
            'temperature' => 'temp1', // Default to temp1 when "temperature" is requested
            'ammonia' => 'nh3',
        ];

        $dbParameter = $parameterMapping[$parameter] ?? $parameter;

        $shed = Shed::findOrFail($shedId);
        $deviceIds = ShedDevice::where('shed_id', $shedId)->pluck('device_id')->toArray();

        if (empty($deviceIds)) {
            return response()->json([
                'parameter' => $parameter,
                'current_value' => null,
                'unit' => null,
                'statistics' => ['min' => null, 'average' => null, 'max' => null],
                'chart_data' => [],
                'alert_thresholds' => null,
            ], 200);
        }

        // Determine time range
        $timeRange = $validated['time_range'] ?? '24hour';
        if ($timeRange === 'custom') {
            $from = Carbon::parse($validated['from'])->startOfDay();
            $to = Carbon::parse($validated['to'])->endOfDay();
        } else {
            $to = Carbon::now();
            $from = match ($timeRange) {
                '24hour' => Carbon::now()->subDay(),
                'last_week' => Carbon::now()->subWeek(),
                'current_month' => Carbon::now()->startOfMonth(),
                default => Carbon::now()->subDay(),
            };
        }

        // Fetch aggregated data from iot_data_logs
        $logs = IotDataLog::whereIn('device_id', $deviceIds)
            ->where('parameter', $dbParameter)
            ->whereBetween('record_time', [$from, $to])
            ->orderBy('record_time', 'asc')
            ->get();

        // Calculate overall statistics
        $minValue = $logs->min('min_value');
        $maxValue = $logs->max('max_value');
        $avgValue = $logs->avg('avg_value');

        // Get current/latest value
        $latestLog = IotDataLog::whereIn('device_id', $deviceIds)
            ->where('parameter', $dbParameter)
            ->where('time_window', 'latest')
            ->orderBy('record_time', 'desc')
            ->first();

        $currentValue = $latestLog ? $latestLog->avg_value : null;

        // Prepare chart data
        $chartData = $logs->map(function ($log) {
            return [
                'timestamp' => Carbon::parse($log->record_time)->format('Y-m-d H:i:s'),
                'value' => $log->avg_value,
                'min' => $log->min_value,
                'max' => $log->max_value,
            ];
        })->values()->toArray();

        // Get alert thresholds
        $limit = \App\Models\ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->first();

        $alertThresholds = $limit ? [
            'high' => $limit->max_value,
            'low' => $limit->min_value,
        ] : null;

        // Get unit from limit or default
        $unit = $limit ? $limit->unit : $this->getDefaultUnit($parameter);

        return response()->json([
            'parameter' => $parameter,
            'current_value' => $currentValue,
            'unit' => $unit,
            'statistics' => [
                'min' => $minValue,
                'average' => round($avgValue, 2),
                'max' => $maxValue,
            ],
            'chart_data' => $chartData,
            'alert_thresholds' => $alertThresholds,
        ]);
    }

    /**
     * Export parameter data to Excel
     */
    public function exportParameterExcel(Request $request, int $shedId, string $parameter)
    {
        $validated = $request->validate([
            'time_range' => 'nullable|in:24hour,last_week,current_month,custom',
            'from' => 'required_if:time_range,custom|date',
            'to' => 'required_if:time_range,custom|date|after_or_equal:from',
        ]);

        // Map parameter aliases
        $parameterMapping = [
            'temperature' => 'temp1',
            'ammonia' => 'nh3',
        ];
        $dbParameter = $parameterMapping[$parameter] ?? $parameter;

        $shed = Shed::findOrFail($shedId);
        $deviceIds = ShedDevice::where('shed_id', $shedId)->pluck('device_id')->toArray();

        if (empty($deviceIds)) {
            return response()->json([
                'message' => 'No devices found for this shed',
            ], 404);
        }

        // Determine time range
        $timeRange = $validated['time_range'] ?? '24hour';
        if ($timeRange === 'custom') {
            $from = Carbon::parse($validated['from'])->startOfDay();
            $to = Carbon::parse($validated['to'])->endOfDay();
        } else {
            $to = Carbon::now();
            $from = match ($timeRange) {
                '24hour' => Carbon::now()->subDay(),
                'last_week' => Carbon::now()->subWeek(),
                'current_month' => Carbon::now()->startOfMonth(),
                default => Carbon::now()->subDay(),
            };
        }

        // Fetch data from iot_data_logs
        $logs = IotDataLog::whereIn('device_id', $deviceIds)
            ->where('parameter', $dbParameter)
            ->whereBetween('record_time', [$from, $to])
            ->orderBy('record_time', 'asc')
            ->get();

        // Prepare data for export
        $exportData = $logs->map(function ($log) {
            return [
                'timestamp' => Carbon::parse($log->record_time)->format('Y-m-d H:i:s'),
                'value' => $log->avg_value,
                'min' => $log->min_value,
                'max' => $log->max_value,
            ];
        });

        // Calculate statistics
        $statistics = [
            'min'     => $logs->min('min_value'),
            'average' => round($logs->avg('avg_value'), 2),
            'max'     => $logs->max('max_value'),
        ];

        // Get unit + alert thresholds
        $limit = \App\Models\ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->first();
        $unit = $limit ? $limit->unit : $this->getDefaultUnit($parameter);
        $alertThresholds = $limit ? ['low' => $limit->min_value, 'high' => $limit->max_value] : null;

        // Generate filename
        $filename = sprintf(
            '%s_%s_data_%s.xlsx',
            str_replace(' ', '_', $shed->name),
            $parameter,
            Carbon::now()->format('Y-m-d_His')
        );

        return Excel::download(
            new ParameterDataExport(
                $shed, $parameter, $exportData, $unit,
                $statistics, $alertThresholds, $timeRange,
                $from->format('Y-m-d'), $to->format('Y-m-d')
            ),
            $filename
        );
    }

    /**
     * Export parameter data to PDF
     */
    public function exportParameterPdf(Request $request, int $shedId, string $parameter)
    {
        $validated = $request->validate([
            'time_range' => 'nullable|in:24hour,last_week,current_month,custom',
            'from' => 'required_if:time_range,custom|date',
            'to' => 'required_if:time_range,custom|date|after_or_equal:from',
        ]);

        // Map parameter aliases
        $parameterMapping = [
            'temperature' => 'temp1',
            'ammonia' => 'nh3',
        ];
        $dbParameter = $parameterMapping[$parameter] ?? $parameter;

        $shed = Shed::findOrFail($shedId);
        $deviceIds = ShedDevice::where('shed_id', $shedId)->pluck('device_id')->toArray();

        if (empty($deviceIds)) {
            return response()->json([
                'message' => 'No devices found for this shed',
            ], 404);
        }

        // Determine time range
        $timeRange = $validated['time_range'] ?? '24hour';
        if ($timeRange === 'custom') {
            $from = Carbon::parse($validated['from'])->startOfDay();
            $to = Carbon::parse($validated['to'])->endOfDay();
        } else {
            $to = Carbon::now();
            $from = match ($timeRange) {
                '24hour' => Carbon::now()->subDay(),
                'last_week' => Carbon::now()->subWeek(),
                'current_month' => Carbon::now()->startOfMonth(),
                default => Carbon::now()->subDay(),
            };
        }

        // Fetch data from iot_data_logs
        $logs = IotDataLog::whereIn('device_id', $deviceIds)
            ->where('parameter', $dbParameter)
            ->whereBetween('record_time', [$from, $to])
            ->orderBy('record_time', 'asc')
            ->get();

        // Get statistics
        $minValue = $logs->min('min_value');
        $maxValue = $logs->max('max_value');
        $avgValue = $logs->avg('avg_value');

        // Get unit and alert thresholds
        $limit = \App\Models\ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->first();
        $unit = $limit ? $limit->unit : $this->getDefaultUnit($parameter);

        // Prepare data
        $exportData = $logs->map(function ($log) {
            return [
                'timestamp' => Carbon::parse($log->record_time)->format('Y-m-d H:i:s'),
                'value' => $log->avg_value,
                'min' => $log->min_value,
                'max' => $log->max_value,
            ];
        });

        // Generate filename
        $filename = sprintf(
            '%s_%s_data_%s.pdf',
            str_replace(' ', '_', $shed->name),
            $parameter,
            Carbon::now()->format('Y-m-d_His')
        );

        $pdf = Pdf::loadView('exports.parameter-data-pdf', [
            'shed' => $shed,
            'parameter' => $parameter,
            'unit' => $unit,
            'logs' => $exportData,
            'statistics' => [
                'min' => $minValue,
                'avg' => round($avgValue, 2),
                'max' => $maxValue,
            ],
            'alert_thresholds' => $limit ? [
                'high' => $limit->max_value,
                'low' => $limit->min_value,
            ] : null,
            'time_range' => $timeRange,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Get default unit for parameter
     */
    private function getDefaultUnit(string $parameter): string
    {
        return match (strtolower($parameter)) {
            'temperature', 'temp1', 'temp2' => '°C',
            'humidity' => '%',
            'co2', 'carbon_dioxide' => 'ppm',
            'ammonia' => 'ppm',
            'electricity' => 'kWh',
            default => '',
        };
    }

    /**
     * Check sensor data against parameter thresholds and trigger alerts
     */
    private function checkParameterAlerts(int $deviceId, array $sensorData): void
    {
        // Get shed_id for this device
        $shedId = ShedDevice::where('device_id', $deviceId)
            ->where('is_active', true)
            ->value('shed_id');

        if (!$shedId) {
            Log::info("[checkParameterAlerts] Device {$deviceId} not linked to any active shed, skipping alert check.");
            return;
        }

        // Define monitored parameters (exclude device_id and timestamp)
        $monitoredParams = ['temperature', 'temp1', 'temp2', 'humidity', 'co2', 'ammonia', 'nh3'];

        // Check each parameter in the sensor data
        foreach ($sensorData as $param => $value) {
            // Skip non-parameter fields
            if (in_array(strtolower($param), ['device_id', 'timestamp'])) {
                continue;
            }

            // Only check if it's a monitored parameter and value is numeric
            if (in_array(strtolower($param), $monitoredParams) && is_numeric($value)) {
                $this->alertService->checkParameterThreshold(
                    $shedId,
                    $deviceId,
                    $param,
                    (float) $value
                );
            }
        }
    }
}
