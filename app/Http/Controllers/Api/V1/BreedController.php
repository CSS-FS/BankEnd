<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\BreedResource;
use App\Models\Breed;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BreedController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $breeds = QueryBuilder::for(Breed::class)
            ->withCount('flocks')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
                'category',
            ])
            ->allowedSorts(['id', 'name', 'created_at'])
            ->get();

        return BreedResource::collection($breeds);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:broiler,layer'],
        ]);

        $breed = Breed::create($validated);

        return response()->json([
            'message' => 'Breed created successfully.',
            'breed' => BreedResource::make($breed),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Breed $breed)
    {
        return BreedResource::make(
            Breed::with(['flocks.shed.farm'])
                ->withCount('flocks')
                ->findOrFail($breed->id)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Breed $breed)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'in:broiler,layer'],
        ]);

        $breed->update($validated);

        return response()->json([
            'message' => 'Breed updated successfully.',
            'breed' => BreedResource::make($breed),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Breed $breed)
    {
        $breed->delete();

        return response()->json([
            'message' => 'Breed deleted successfully.',
        ]);
    }
}
