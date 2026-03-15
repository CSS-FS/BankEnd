<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DeviceController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $devices = QueryBuilder::for(Device::class)
            ->with('sheds')
            ->withCount(['sheds', 'appliances'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'serial_no',
                'firmware_version',
            ])
            ->allowedIncludes(['sheds', 'appliances'])
            ->allowedSorts(['id', 'serial_no', 'created_at'])
            ->get();

        return DeviceResource::collection($devices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial_no' => ['required', 'string', 'unique:devices,serial_no'],
            'firmware_version' => ['nullable', 'string'],
            'capabilities' => ['required', 'array'],
        ]);

        $device = Device::create([
            'serial_no' => $validated['serial_no'],
            'firmware_version' => $validated['firmware_version'] ?? null,
            'capabilities' => json_encode($validated['capabilities']),
        ]);

        return response()->json([
            'message' => 'Device created successfully.',
            'device' => DeviceResource::make($device),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device)
    {
        return DeviceResource::make(
            Device::with([
                'sheds',
                'appliances' => fn($query) => $query->orderBy('type')->orderBy('name'),
            ])
                ->withCount(['sheds', 'appliances'])
                ->findOrFail($device->id)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'serial_no' => ['sometimes', 'string', 'unique:devices,serial_no,' . $device->id],
            'firmware_version' => ['nullable', 'string'],
            'capabilities' => ['sometimes', 'array'],
        ]);

        $device->update([
            'serial_no' => $validated['serial_no'] ?? $device->serial_no,
            'firmware_version' => $validated['firmware_version'] ?? $device->firmware_version,
            'capabilities' => array_key_exists('capabilities', $validated)
                ? json_encode($validated['capabilities'])
                : $device->capabilities,
        ]);

        return response()->json([
            'message' => 'Device updated successfully.',
            'device' => DeviceResource::make($device),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device)
    {
        $device->delete();

        return response()->json([
            'message' => 'Device deleted successfully.',
        ]);
    }
}
