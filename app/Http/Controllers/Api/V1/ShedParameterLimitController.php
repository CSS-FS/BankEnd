<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\ShedParameterLimitResource;
use App\Models\Shed;
use App\Models\ShedParameterLimit;
use Illuminate\Http\Request;

class ShedParameterLimitController extends ApiController
{
    /**
     * Display a listing of parameter limits for a shed.
     */
    public function index(int $shedId)
    {
        $shed = Shed::findOrFail($shedId);

        $limits = ShedParameterLimit::where('shed_id', $shedId)
            ->orderBy('parameter_name')
            ->get();

        return ShedParameterLimitResource::collection($limits);
    }

    /**
     * Store a newly created parameter limit.
     */
    public function store(Request $request, int $shedId)
    {
        $shed = Shed::findOrFail($shedId);

        $validated = $request->validate([
            'parameter_name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:255'],
            'min_value' => ['required', 'numeric'],
            'max_value' => ['required', 'numeric', 'gte:min_value'],
            'avg_value' => ['nullable', 'numeric'],
        ]);

        $validated['shed_id'] = $shedId;

        // Check if parameter limit already exists
        $existing = ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $validated['parameter_name'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Parameter limit already exists for this shed.',
            ], 409);
        }

        $limit = ShedParameterLimit::create($validated);

        return response()->json([
            'message' => 'Parameter limit created successfully.',
            'data' => ShedParameterLimitResource::make($limit),
        ], 201);
    }

    /**
     * Display the specified parameter limit.
     */
    public function show(int $shedId, string $parameter)
    {
        $shed = Shed::findOrFail($shedId);

        $limit = ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->firstOrFail();

        return ShedParameterLimitResource::make($limit);
    }

    /**
     * Update the specified parameter limit.
     */
    public function update(Request $request, int $shedId, string $parameter)
    {
        $shed = Shed::findOrFail($shedId);

        $validated = $request->validate([
            'unit' => ['sometimes', 'string', 'max:255'],
            'min_value' => ['sometimes', 'numeric'],
            'max_value' => ['sometimes', 'numeric'],
            'avg_value' => ['nullable', 'numeric'],
        ]);

        // Validate max >= min if both are provided
        if (isset($validated['min_value']) && isset($validated['max_value'])) {
            if ($validated['max_value'] < $validated['min_value']) {
                return response()->json([
                    'message' => 'Max value must be greater than or equal to min value.',
                ], 422);
            }
        }

        $limit = ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->first();

        if (!$limit) {
            // Create if doesn't exist
            $validated['shed_id'] = $shedId;
            $validated['parameter_name'] = $parameter;
            $limit = ShedParameterLimit::create($validated);

            return response()->json([
                'message' => 'Parameter limit created successfully.',
                'data' => ShedParameterLimitResource::make($limit),
            ], 201);
        }

        $limit->update($validated);

        return response()->json([
            'message' => 'Parameter limit updated successfully.',
            'data' => ShedParameterLimitResource::make($limit),
        ]);
    }

    /**
     * Remove the specified parameter limit.
     */
    public function destroy(int $shedId, string $parameter)
    {
        $shed = Shed::findOrFail($shedId);

        $limit = ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->firstOrFail();

        $limit->delete();

        return response()->json([
            'message' => 'Parameter limit deleted successfully.',
        ]);
    }
}
