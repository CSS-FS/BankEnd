<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\ShedResource;
use App\Models\Shed;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\DynamoDbService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ShedController extends ApiController
{
    protected $dynamoDbService;

    public function __construct(DynamoDbService $dynamoDbService)
    {
        $this->dynamoDbService = $dynamoDbService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sheds = QueryBuilder::for(Shed::class)
            ->whereIn('farm_id', $request->user()->farms()->pluck('id'))
            ->with('farm')
            ->withCount(['flocks', 'devices'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
                'type',
                AllowedFilter::exact('farm_id'),
            ])
            ->allowedIncludes(['farm', 'flocks', 'devices'])
            ->allowedSorts(['id', 'name', 'capacity', 'created_at'])
            ->get();

        foreach ($sheds as $shed) {
            $deviceIds = $shed->devices->pluck('id')->all();
            if (!empty($deviceIds)) {
                $sensorDataArr = $this->dynamoDbService->getSensorData($deviceIds, null, null, true);
                $sensorData = collect($sensorDataArr)->keyBy('device_id');
                foreach ($shed->devices as $device) {
                    $device->latest_sensor_data = $sensorData[$device->id] ?? null;
                }
            }
        }

        return ShedResource::collection($sheds);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'exists:farms,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('sheds')
                    ->where('farm_id', $request->farm_id)
                    ->where(fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($request->name))])),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:default,brooder,layer,broiler,hatchery'],
            'description' => ['nullable', 'string'],
            'device_id' => ['nullable', 'exists:devices,id'],
        ], [
            'name.unique' => 'Shed name must be unique within the selected farm.',
        ]);

        $shed = Shed::create($validated);

        // Link device to shed if device_id is provided
        if (isset($validated['device_id'])) {
            $shed->devices()->attach($validated['device_id'], [
                'link_date' => now()
            ]);
        }

        return response()->json([
            'message' => 'Shed created successfully.',
            'shed' => ShedResource::make($shed),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Shed $shed)
    {
        $shed = Shed::with([
            'farm',
            'flocks.breed',
            'devices.appliances',
        ])->withCount(['flocks', 'devices'])->findOrFail($shed->id);

        // Fetch latest sensor data for each device
        $deviceIds = $shed->devices->pluck('id')->all();
        $sensorData = [];
        if (!empty($deviceIds)) {
            $sensorDataArr = $this->dynamoDbService->getSensorData($deviceIds, null, null, true);
            \Log::debug('Fetched sensor data from DynamoDB:', $sensorDataArr);
            foreach ($sensorDataArr as $data) {
                if (isset($data['device_id'])) {
                    $sensorData[$data['device_id']] = $data;
                }
            }
        }
        // Attach sensor data to each device (for resource usage)
        foreach ($shed->devices as $device) {
            $device->latest_sensor_data = $sensorData[$device->id] ?? null;
        }

        return ShedResource::make($shed);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shed $shed)
    {
        $farmId = $request->farm_id ?? $shed->farm_id;

        $validated = $request->validate([
            'farm_id' => ['sometimes', 'exists:farms,id'],
            'name' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('sheds')
                    ->where('farm_id', $farmId)
                    ->where(fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($request->name))]))
                    ->ignore($shed->id),
            ],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'in:default,brooder,layer,broiler,hatchery'],
            'description' => ['nullable', 'string'],
        ], [
            'name.unique' => 'Shed name must be unique within the selected farm.',
        ]);

        $shed->update($validated);

        return response()->json([
            'message' => 'Shed updated successfully.',
            'shed' => ShedResource::make($shed),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shed $shed)
    {
        $shed->delete();

        return response()->json([
            'message' => 'Shed deleted successfully.',
        ]);
    }
}
