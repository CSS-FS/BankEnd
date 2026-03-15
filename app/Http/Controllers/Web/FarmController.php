<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // Build query based on user role
        $farmsQuery = Farm::with(['owner', 'managers', 'sheds', 'province', 'district', 'city']);

        if ($user->hasRole('admin')) {
            // Admin: No restriction, get all farms
            $farms = $farmsQuery->get();
        } elseif ($user->hasRole('owner')) {
            // Owner: Only farms they own
            $farms = $farmsQuery->where('owner_id', $user->id)->get();
        } else {
            // Default: No access
            $farms = collect([]);
        }

        $owners = User::all();
        $managers = User::all();

        $provinces = Province::select('id', 'name')
            ->orderBy('name')
            ->get();

        $cities = $farms->pluck('city')->unique();

        return view(
            'admin.farms.index',
            compact('farms', 'managers', 'provinces', 'owners', 'cities')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No implementation is required
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user->hasRole('admin') && ! $user->hasRole('owner')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'province_id'    => 'nullable|exists:pakistan_provinces,id',
            'district_id'    => 'nullable|exists:pakistan_districts,id',
            'city_id'        => 'nullable|exists:pakistan_tehsils,id',
            'address'        => 'required|string|max:500',
            'owner_id'       => 'nullable|exists:users,id',
            'country'        => 'required|string|max:100',
            'phone_number'   => 'nullable|digits_between:1,11',
            'contact_person' => 'nullable|string|max:50',
            'alerts'         => 'nullable|boolean',
            'notifications'  => 'nullable|boolean',
        ]);

        // Force owner_id for owner role
        if ($user->hasRole('owner')) {
            $validated['owner_id'] = $user->id;
        } elseif (! isset($validated['owner_id'])) {
            $validated['owner_id'] = $user->id;
        }

        $validated['alerts']        = $request->boolean('alerts');
        $validated['notifications'] = $request->boolean('notifications');

        $farm = Farm::create($validated);

        return redirect()
            ->back()
            ->with('success', 'Farm is added successfully.');
    }

    /**
     * Display the specified resource (JSON — used by Edit Farm modal AJAX).
     */
    public function show(Farm $farm)
    {
        return response()
            ->json($farm->load(['owner', 'managers', 'sheds', 'province', 'district', 'city']));
    }

    /**
     * Display a full detail page for a farm.
     */
    public function detail(Farm $farm)
    {
        $farm->load([
            'owner',
            'managers',
            'province',
            'district',
            'city',
            'sheds.latestFlock.breed',
            'sheds.latestFlocks.breed',
            'sheds.devices',
        ]);

        return view('admin.farms.show', compact('farm'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Farm $farm)
    {
        // No implementation is required
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Farm $farm)
    {
        $user = auth()->user();
        if (! $user->hasRole('admin') && ! ($user->hasRole('owner') && $farm->owner_id === $user->id)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'province_id'    => 'nullable|exists:pakistan_provinces,id',
            'district_id'    => 'nullable|exists:pakistan_districts,id',
            'city_id'        => 'nullable|exists:pakistan_tehsils,id',
            'address'        => 'required|string|max:500',
            'owner_id'       => 'required|exists:users,id',
            'country'        => 'required|string|max:100',
            'phone_number'   => 'nullable|digits_between:1,11',
            'contact_person' => 'nullable|string|max:50',
            'alerts'         => 'nullable|boolean',
            'notifications'  => 'nullable|boolean',
        ]);

        $validated['alerts']        = $request->boolean('alerts');
        $validated['notifications'] = $request->boolean('notifications');

        $farm->update($validated);

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Farm has been updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Farm $farm)
    {
        $user = auth()->user();
        if (! $user->hasRole('admin') && ! ($user->hasRole('owner') && $farm->owner_id === $user->id)) {
            abort(403, 'Unauthorized action.');
        }

        // Check if the farm has any sheds assigned
        if ($farm->sheds()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete farm. One or more sheds are assigned to this farm.');
        }

        $farm->delete();

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Farm has been deleted successfully.');
    }

    public function farmData($farmId, Request $request)
    {
        $farm = Farm::with('owner', 'sheds.latestFlocks.breed', 'sheds.latestFlock')
            ->find($farmId);

        $types = [
            'default',
            'brooder',
            'layer',
            'broiler',
            'hatchery',
        ];

        $context = $request->query('context', 'default');

        $view = view(
            'admin.farms.farm_card',
            compact('farm', 'types', 'context')
        )->render();

        return response()->json(['html' => $view]);
    }

    public function assignManager(Request $request, Farm $farm)
    {
        // Admins, Owners, and Managers can assign managers
        $user = auth()->user();

        if (!$user->hasRole('admin') && !$user->hasRole('owner') && !$user->hasRole('manager')) {
            abort(403, 'Unauthorized action. You do not have permission to assign managers.');
        }

        $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        // Remove old managers by ID (fires Observer → null their tokens)
        $oldManagerIds = $farm->managers()->pluck('users.id')->toArray();
        if (! empty($oldManagerIds)) {
            $farm->managers()->detach($oldManagerIds);
        }

        // Assign new manager (fires Observer → sets their tokens farm_id)
        $farm->managers()->attach($request->manager_id, ['link_date' => now()]);

        return redirect()->back()
            ->with('success', 'Manager has been assigned successfully.');
    }
}
