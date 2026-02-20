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

        // Admins: unlimited, Owners: only one, Managers: not allowed
        if ($user->hasRole('manager')) {
            abort(403, 'Unauthorized action. Managers cannot create farms.');
        }

        if ($user->hasRole('owner')) {
            $existing = Farm::where('owner_id', $user->id)->count();
            if ($existing >= 1) {
                return redirect()
                    ->back()
                    ->with('error', 'You can only create one farm.');
            }
        } elseif (! $user->hasRole('admin')) {
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
     * Display the specified resource.
     */
    public function show(Farm $farm)
    {
        return response()
            ->json($farm->load(['owner', 'managers', 'sheds', 'province', 'district', 'city']));
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
        // Only admins can update farms
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action. Only admins can update farms.');
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
        // Only admins can delete farms
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action. Only admins can delete farms.');
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

    public function farmData($farmId)
    {
        $farm = Farm::with('owner', 'sheds.latestFlocks.breed')
            ->find($farmId);

        $types = [
            'default',
            'brooder',
            'layer',
            'broiler',
            'hatchery',
        ];

        $view = view(
            'admin.farms.farm_card',
            compact('farm', 'types')
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

        // Remove old managers
        $farm->managers()->detach();

        // Assign new manager
        $farm->managers()->attach($request->manager_id);

        return redirect()->back()
            ->with('success', 'Manager has been assigned successfully.');
    }
}
