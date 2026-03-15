<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\ManagerResource;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FarmManagerController extends ApiController
{
    public function index(Request $request, Farm $farm)
    {
        $managers = QueryBuilder::for($farm->managers())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
                'email',
            ])
            ->allowedSorts(['id', 'name', 'email'])
            ->get();
        return ManagerResource::collection($managers);
    }

    public function store(Request $request, Farm $farm)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
        ]);

        $user->assignRole('manager');
        $farm->managers()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => 'Manager created and assigned to farm successfully.',
            'data' => new ManagerResource($user)
        ], 201);
    }

    public function show(Farm $farm, User $user)
    {
        // Ensure the user is a manager of this farm
        if (!$farm->managers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Manager not found for this farm.'], 404);
        }
        $manager = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(['managedFarms'])
            ->firstOrFail();
        return response()->json([
            'message' => 'Manager fetched successfully.',
            'data' => new ManagerResource($manager)
        ]);
    }

    public function update(Request $request, Farm $farm, User $user)
    {
        // Only allow updating manager's user info
        if (!$farm->managers()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Manager not found for this farm.'], 404);
        }
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
            'phone' => ['sometimes', 'string'],
        ]);
        $user->update($validated);
        return response()->json([
            'message' => 'Manager updated successfully.',
            'data' => new ManagerResource($user)
        ]);
    }

    public function destroy(Farm $farm, User $user)
    {
        $farm->managers()->detach($user->id);
        return response()->json(['message' => 'Manager removed successfully.']);
    }
}
