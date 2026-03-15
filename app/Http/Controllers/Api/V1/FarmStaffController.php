<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\StaffResource;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FarmStaffController extends ApiController
{
    public function index(Request $request, Farm $farm)
    {
        $staff = QueryBuilder::for($farm->staff())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
                'email',
            ])
            ->allowedSorts(['id', 'name', 'email'])
            ->get();
        return StaffResource::collection($staff);
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

        $user->assignRole('worker');
        $farm->staff()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => 'Staff created and assigned to farm successfully.',
            'data' => new StaffResource($user)
        ], 201);
    }

    public function show(Farm $farm, User $user)
    {
        // Ensure the user is a staff of this farm
        if (!$farm->staff()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Staff not found for this farm.'], 404);
        }
        $staff = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(['staffFarms'])
            ->firstOrFail();
        return response()->json([
            'message' => 'Staff fetched successfully.',
            'data' => new StaffResource($staff)
        ]);
    }

    public function update(Request $request, Farm $farm, User $user)
    {
        // Only allow updating staff's user info
        if (!$farm->staff()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Staff not found for this farm.'], 404);
        }
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
            'phone' => ['sometimes', 'string'],
        ]);
        $user->update($validated);
        return response()->json([
            'message' => 'Staff updated successfully.',
            'data' => new StaffResource($user)
        ]);
    }

    public function destroy(Farm $farm, User $user)
    {
        $farm->staff()->detach($user->id);
        return response()->json(['message' => 'Staff removed successfully.']);
    }
}
