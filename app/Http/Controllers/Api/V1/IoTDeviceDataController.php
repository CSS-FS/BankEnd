<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Device;
use App\Models\DeviceAppliance;
use App\Services\DynamoDbService;
use App\Services\IotAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IoTDeviceDataController extends ApiController
{
    public function __construct(
        protected DynamoDbService $dynamoDbService,
        protected IotAlertService $iotAlertService,
    ) {
        parent::__construct();
    }

    /**
     * ===============================
     * 1. SENSOR DATA (SINGLE RECORD)
     * ===============================
     */
    public function storeSensor(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'timestamp' => 'nullable|integer',
        ]);

        $device = $this->resolveDevice($validated['device_serial']);
        $timestamp = $validated['timestamp'] ?? Carbon::now()->timestamp;

        $sensorData = array_merge(
            [
                'device_id' => $device->id,
                'timestamp' => $timestamp,
            ],
            $request->except(['device_serial'])
        );

        $this->dynamoDbService->putSensorData($sensorData);

        // Real-time threshold check
        $this->checkSensorThresholds($device, $request->except(['device_serial', 'timestamp']));

        return response()->json(['message' => 'Sensor data stored successfully.'], 201);
    }

    /**
     * ===============================
     * 2. SENSOR DATA (MULTIPLE RECORDS)
     * ===============================
     */
    public function storeMultipleSensor(Request $request)
    {
        $validated = $request->validate([
            'records' => 'required|array',
            'records.*.device_serial' => 'required|string',
            'records.*.timestamp' => 'nullable|integer',
        ]);

        foreach ($validated['records'] as $record) {
            $device = $this->resolveDevice($record['device_serial']);
            $timestamp = $record['timestamp'] ?? Carbon::now()->timestamp;

            $sensorData = array_merge(
                [
                    'device_id' => $device->id,
                    'timestamp' => $timestamp,
                ],
                collect($record)->except(['device_serial'])->toArray()
            );

            $this->dynamoDbService->putSensorData($sensorData);

            // Real-time threshold check
            $this->checkSensorThresholds($device, collect($record)->except(['device_serial', 'timestamp'])->toArray());
        }

        return response()->json(['message' => 'All sensor data records processed successfully.'], 201);
    }

    /**
     * ===============================
     * 3. APPLIANCE DATA (SINGLE RECORD)
     * ===============================
     */
    public function updateAppliance(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'appliances' => 'required|array',
            'appliances.*' => 'required|boolean',
            'timestamp' => 'nullable|integer',
        ]);

        $device = $this->resolveDevice($validated['device_serial']);
        $timestamp = $validated['timestamp'] ?? Carbon::now()->timestamp;

        $updated = [];

        foreach ($validated['appliances'] as $key => $status) {
            $appliance = DeviceAppliance::firstOrNew([
                'device_id' => $device->id,
                'key' => $key,
            ]);

            if (!$appliance->exists) {
                $appliance->fill([
                    'type' => $this->getApplianceTypeFromKey($key),
                    'name' => ucfirst($this->getApplianceTypeFromKey($key)) . ' ' . strtoupper($key),
                ]);
            }

            // Update latest in MySQL
            $appliance->status = $status;
            $appliance->status_updated_at = Carbon::createFromTimestamp($timestamp);
            $appliance->save();

            // Log in DynamoDB
            $this->dynamoDbService->putApplianceData([
                'device_id' => $device->id,
                'key' => $key,
                'status' => $status ? 1 : 0,
                'timestamp' => $timestamp,
            ]);

            $updated[] = $appliance;
        }

        return response()->json([
            'message' => 'Appliances updated successfully.',
        ], 201);
    }

    /**
     * ===============================
     * 4. APPLIANCE DATA (MULTIPLE RECORDS)
     * ===============================
     */
    public function updateMultipleAppliances(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'records' => 'required|array',
            'records.*.appliances' => 'required|array',
            'records.*.appliances.*' => 'required|boolean',
            'records.*.timestamp' => 'nullable|integer',
        ]);

        $device = $this->resolveDevice($validated['device_serial']);
        $allUpdated = [];

        $latestRecord = null;

        foreach ($validated['records'] as $record) {
            $timestamp = $record['timestamp'] ?? Carbon::now()->timestamp;

            foreach ($record['appliances'] as $key => $status) {
                // Store history in DynamoDB
                $this->dynamoDbService->putApplianceData([
                    'device_id' => $device->id,
                    'key' => $key,
                    'status' => $status ? 1 : 0,
                    'timestamp' => $timestamp,
                ]);
            }

            // Track latest record by timestamp
            if (!$latestRecord || $timestamp > $latestRecord['timestamp']) {
                $latestRecord = [
                    'timestamp' => $timestamp,
                    'appliances' => $record['appliances'],
                ];
            }

            $allUpdated[] = [
                'timestamp' => $timestamp,
                'appliances' => $record['appliances'],
            ];
        }

        // ✅ Update MySQL with latest snapshot
        if ($latestRecord) {
            foreach ($latestRecord['appliances'] as $key => $status) {
                $appliance = DeviceAppliance::firstOrNew([
                    'device_id' => $device->id,
                    'key' => $key,
                ]);

                if (!$appliance->exists) {
                    $appliance->fill([
                        'type' => $this->getApplianceTypeFromKey($key),
                        'name' => ucfirst($this->getApplianceTypeFromKey($key)) . ' ' . strtoupper($key),
                    ]);
                }

                $appliance->status = $status;
                $appliance->status_updated_at = Carbon::createFromTimestamp($latestRecord['timestamp']);
                $appliance->save();
            }
        }

        return response()->json([
            'message' => 'Multiple appliance records processed successfully.',
        ], 201);
    }

    /**
     * ===============================
     * 5. SYNC DEVICE DATA (SINGLE RECORD)
     * ===============================
     */
    public function syncDeviceData(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'timestamp' => 'nullable|integer',
            'appliances' => 'nullable|array',
            'appliances.*' => 'boolean',
            // dynamic sensor fields allowed
        ]);

        $device = $this->resolveDevice($validated['device_serial']);
        $timestamp = $validated['timestamp'] ?? Carbon::now()->timestamp;

        // ✅ 1. Handle Appliances (update MySQL latest + log history in DynamoDB)
        if (!empty($validated['appliances'])) {
            foreach ($validated['appliances'] as $key => $status) {
                $appliance = DeviceAppliance::firstOrNew([
                    'device_id' => $device->id,
                    'key' => $key,
                ]);

                if (!$appliance->exists) {
                    $appliance->fill([
                        'type' => $this->getApplianceTypeFromKey($key),
                        'name' => ucfirst($this->getApplianceTypeFromKey($key)) . ' ' . strtoupper($key),
                    ]);
                }

                $appliance->status = $status;
                $appliance->status_updated_at = Carbon::createFromTimestamp($timestamp);
                $appliance->save();

                $this->dynamoDbService->putApplianceData([
                    'device_id' => $device->id,
                    'key' => $key,
                    'status' => $status ? 1 : 0,
                    'timestamp' => $timestamp,
                ]);
            }
        }

        // ✅ 2. Handle Sensor Data (store only in DynamoDB)
        $sensorData = array_merge(
            [
                'device_id' => $device->id,
                'timestamp' => $timestamp,
            ],
            collect($request->except(['device_serial', 'appliances']))->toArray()
        );
        $this->dynamoDbService->putSensorData($sensorData);

        // Real-time threshold check
        $this->checkSensorThresholds($device, collect($request->except(['device_serial', 'appliances', 'timestamp']))->toArray());

        return response()->json([
            'message' => 'Device data (appliances + sensors) synced successfully.',
        ], 201);
    }

    /**
     * ===============================
     * 6. SYNC MULTIPLE DEVICE DATA RECORDS
     * ===============================
     */
    public function syncMultipleDeviceData(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'records' => 'required|array',
            'records.*.timestamp' => 'nullable|integer',
            'records.*.appliances' => 'nullable|array',
            'records.*.appliances.*' => 'boolean',
        ]);

        $device = $this->resolveDevice($validated['device_serial']);

        $latestRecord = null;
        $allProcessed = [];

        foreach ($validated['records'] as $record) {
            $timestamp = $record['timestamp'] ?? Carbon::now()->timestamp;

            // ✅ 1. Handle Appliances (log history in DynamoDB)
            if (!empty($record['appliances'])) {
                foreach ($record['appliances'] as $key => $status) {
                    $this->dynamoDbService->putApplianceData([
                        'device_id' => $device->id,
                        'key' => $key,
                        'status' => $status ? 1 : 0,
                        'timestamp' => $timestamp,
                    ]);
                }
            }

            // ✅ 2. Handle Sensor Data (store all other fields except appliances/device_serial)
            $sensorData = array_merge(
                [
                    'device_id' => $device->id,
                    'timestamp' => $timestamp,
                ],
                collect($record)->except(['appliances'])->toArray()
            );
            $this->dynamoDbService->putSensorData($sensorData);

            // Real-time threshold check
            $this->checkSensorThresholds($device, collect($record)->except(['appliances', 'timestamp'])->toArray());

            // Track latest record for MySQL update
            if (!$latestRecord || $timestamp > $latestRecord['timestamp']) {
                $latestRecord = [
                    'timestamp' => $timestamp,
                    'appliances' => $record['appliances'] ?? [],
                ];
            }

            $allProcessed[] = [
                'timestamp' => $timestamp,
                'appliances' => $record['appliances'] ?? [],
                'sensors' => collect($record)->except(['appliances'])->toArray(),
            ];
        }

        // ✅ Update MySQL with latest appliance snapshot only
        if ($latestRecord && !empty($latestRecord['appliances'])) {
            foreach ($latestRecord['appliances'] as $key => $status) {
                $appliance = DeviceAppliance::firstOrNew([
                    'device_id' => $device->id,
                    'key' => $key,
                ]);

                if (!$appliance->exists) {
                    $appliance->fill([
                        'type' => $this->getApplianceTypeFromKey($key),
                        'name' => ucfirst($this->getApplianceTypeFromKey($key)) . ' ' . strtoupper($key),
                    ]);
                }

                $appliance->status = $status;
                $appliance->status_updated_at = Carbon::createFromTimestamp($latestRecord['timestamp']);
                $appliance->save();
            }
        }

        return response()->json([
            'message' => 'Multiple device data records processed successfully.',
        ], 201);
    }

    /**
     * Helper: resolve device by serial
     */
    private function resolveDevice(string $serial): Device
    {
        return Device::where('serial_no', $serial)->firstOrFail();
    }

    /**
     * Helper: check sensor parameter values against shed thresholds in real-time.
     */
    private function checkSensorThresholds(Device $device, array $sensorValues): void
    {
        $shedId = DB::table('shed_devices')
            ->where('device_id', $device->id)
            ->where('is_active', true)
            ->value('shed_id');

        if (! $shedId) {
            return;
        }

        $tracked = ['temperature', 'humidity', 'co2', 'ammonia'];

        foreach ($tracked as $param) {
            if (isset($sensorValues[$param]) && is_numeric($sensorValues[$param])) {
                $this->iotAlertService->checkParameterThreshold(
                    $shedId,
                    $device->id,
                    $param,
                    (float) $sensorValues[$param]
                );
            }
        }
    }

    /**
     * Helper: map appliance type
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
            default => 'appliance',
        };
    }
}
