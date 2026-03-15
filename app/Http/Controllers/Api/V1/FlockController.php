<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\FlockResource;
use App\Models\Flock;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FlockController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $flocks = QueryBuilder::for(Flock::class)
            ->whereIn('shed_id', $request->user()->farms()->with('sheds')->get()->pluck('sheds.*.id')->flatten())
            ->with(['shed.farm', 'breed'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
                AllowedFilter::exact('shed_id'),
            ])
            ->allowedIncludes(['shed', 'breed'])
            ->allowedSorts(['id', 'name', 'start_date', 'created_at'])
            ->get();

        return FlockResource::collection($flocks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'shed_id' => ['required', 'exists:sheds,id'],
            'breed_id' => ['required', 'exists:breeds,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'chicken_count' => ['required', 'integer', 'min:1'],
        ]);

        $flock = Flock::create($validated);

        return response()->json([
            'message' => 'Flock created successfully.',
            'flock' => FlockResource::make($flock),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Flock $flock)
    {
        return FlockResource::make(
            Flock::with([
                'shed.farm',
                'breed',
            ])
                ->findOrFail($flock->id)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Flock $flock)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'shed_id' => ['sometimes', 'exists:sheds,id'],
            'breed_id' => ['sometimes', 'exists:breeds,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'chicken_count' => ['sometimes', 'integer', 'min:1'],
        ]);

        $flock->update($validated);

        return response()->json([
            'message' => 'Flock updated successfully.',
            'flock' => FlockResource::make($flock),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Flock $flock)
    {
        $flock->delete();

        return response()->json([
            'message' => 'Flock deleted successfully.',
        ]);
    }
}
