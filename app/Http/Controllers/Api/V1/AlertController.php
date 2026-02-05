<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $alerts = Alert::latest()
                ->Unread()
                ->paginate(5);
        } elseif ($user->hasRole('owner')) {
            $farm_ids = $user->farms()->pluck('id')->toArray();
            $alerts = Alert::whereIn('farm_id', $farm_ids)
                ->Unread()
                ->latest()
                ->paginate(5);
        } elseif ($user->hasRole('manager')) {
            $farm_id = $user->managedFarms()->pluck('id')->toArray();
            $alerts = Alert::where('farm_id', $farm_id)
                ->Unread()
                ->latest()
                ->paginate(5);
        } else {
            $alerts = [];
        }

        return AlertResource::collection($alerts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Alert $alert)
    {
        // Eager load all relationships including responses
        $alert->load([
            'user',
            'farm',
            'shed',
            'flock',
            'responses.creator',
            'responses.responder',
            'latestResponse',
        ]);

        return AlertResource::make($alert);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Alert $alert)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alert $alert)
    {
        //
    }

    // Extra Actions
    public function markAsRead(Request $request, Alert $alert)
    {
        return AlertResource::make($alert->markAsRead());
    }

    public function markAsDismiss(Request $request, Alert $alert)
    {
        return AlertResource::make($alert->dismiss());
    }

    public function markAsUndismiss(Request $request, Alert $alert)
    {
        return AlertResource::make($alert->undismiss());
    }

    /**
     * Get only critical alerts
     */
    public function critical()
    {
        $alerts = Alert::with('user')
            ->severity('fatal')
            ->latest()
            ->paginate(10);

        return AlertResource::collection($alerts);
    }

    public function unread()
    {
        $alerts = Alert::unread()
            ->undismissed()
            ->with(['user', 'farm', 'shed'])
            ->latest()
            ->paginate(10);

        return AlertResource::collection($alerts);
    }

    public function responseStore(Request $request, Alert $alert)
    {
        // TODO:: Raw code - refine later
        return AlertResource::make($alert->responses()->create($request->all()));
    }
}
