<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Breed;
use App\Models\Farm;
use App\Models\Shed;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShedController extends Controller
{
    protected array $types = [
        'default',
        'brooder',
        'layer',
        'broiler',
        'hatchery',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // Build query based on user role
        $shedsQuery = Shed::with(['farm.owner', 'latestFlock']);

        if ($user->hasRole('admin')) {
            // Admin: No restriction, get all sheds and farms
            $sheds = $shedsQuery->get();
            $farms = Farm::all();
        } elseif ($user->hasRole('owner')) {
            // Owner: Only sheds from farms they own
            $sheds = $shedsQuery->whereHas('farm', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })->get();
            $farms = Farm::where('owner_id', $user->id)->get();
        } elseif ($user->hasRole('manager')) {
            // Manager: Only sheds from farms they manage
            $managedFarmIds = $user->managedFarms()->pluck('id')->toArray();
            $sheds = $shedsQuery->whereHas('farm', function ($query) use ($managedFarmIds) {
                $query->whereIn('id', $managedFarmIds);
            })->get();
            $farms = Farm::whereIn('id', $managedFarmIds)->get();
        } else {
            // Default: No access
            $sheds = collect([]);
            $farms = collect([]);
        }

        $cities = $sheds->pluck('farm.city.name')
            ->unique()
            ->values()
            ->toArray();

        return view(
            'admin.sheds.index',
            [
                'sheds' => $sheds,
                'cities' => $cities,
                'farms' => $farms,
                'types' => $this->types,
            ]
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
            'farm_id' => 'required|exists:farms,id',
            'name' => [
                'required', 'string', 'min:3', 'max:190',
                Rule::unique('sheds')
                    ->where('farm_id', $request->farm_id)
                    ->where(fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($request->name))])),
            ],
            'capacity' => 'required|integer|min:1',
            'type' => 'required|string|in:'.implode(',', $this->types),
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'Shed name must be unique within the selected farm.',
        ]);

        // Ownership guard for owners
        if ($user->hasRole('owner')) {
            $farmOwnerId = Farm::where('id', $validated['farm_id'])->value('owner_id');
            if ($farmOwnerId !== $user->id) {
                abort(403, 'You can only create sheds for your own farm.');
            }
        }

        Shed::create($validated);

        return redirect()
            ->back()
            ->with('success', 'Shed has been created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Shed $shed)
    {
        // Return shed as JSON for the edit modal (can add relationships if needed)
        return response()->json($shed);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shed $shed)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shed $shed)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'min:3', 'max:190',
                Rule::unique('sheds')
                    ->where('farm_id', $request->farm_id)
                    ->where(fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($request->name))]))
                    ->ignore($shed->id),
            ],
            'farm_id' => 'required|exists:farms,id',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|string|in:'.implode(',', $this->types),
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'Shed name must be unique within the selected farm.',
        ]);

        $shed->update($validated);

        // Refresh same page
        return redirect()->back()
            ->with('success', 'Shed has been updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shed $shed)
    {
        // Check if the shed has any flocks assigned
        if ($shed->flocks()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete shed. One or more flocks are assigned to this shed.');
        }

        $shed->delete();
        return redirect()
            ->route('admin.sheds.index')
            ->with('success', 'Shed has been deleted successfully.');
    }

    public function shedData($shedId)
    {
        $shed = Shed::with('farm.owner', 'latestFlock', 'latestFlocks.breed')
            ->find($shedId);
        $farm = $shed->farm;
        $breeds = Breed::all();
        $types = [
            'default',
            'brooder',
            'layer',
            'broiler',
            'hatchery',
        ];

        $view = view(
            'admin.sheds.shed_card',
            compact('shed', 'farm', 'breeds')
        )->render();

        return response()->json(['html' => $view]);
    }
}
