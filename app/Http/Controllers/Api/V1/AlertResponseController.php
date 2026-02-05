<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResponseResource;
use App\Models\AlertResponse;
use Illuminate\Http\Request;

class AlertResponseController extends Controller
{
    /**
     * Display a listing of all responses.
     */
    public function index(Request $request)
    {
        $query = AlertResponse::query()->with(['alert', 'creator', 'responder']);

        if ($request->has('alert_id')) {
            $query->where('alert_id', $request->alert_id);
        }

        if ($request->has('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->has('responder_id')) {
            $query->where('responder_id', $request->responder_id);
        }

        if ($request->has('creator_id')) {
            $query->where('creator_id', $request->creator_id);
        }

        if ($request->has('start_date')) {
            $query->where('responded_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('responded_at', '<=', $request->end_date);
        }

        $responses = $query->latest()->paginate(20);

        return AlertResponseResource::collection($responses);
    }

    /**
     * Store a newly created response.
     */
    public function store(Request $request)
    {
        $validated = $request->validated();

        // Set default values
        $validated['responded_at'] = $validated['responded_at'] ?? now();
        $validated['creator_id'] = $validated['creator_id'] ?? auth()->id();
        $validated['responder_id'] = $validated['responder_id'] ?? auth()->id();

        $response = AlertResponse::create($validated);

        // Update alert status based on response
        if (in_array($response->action_type, ['Resolved', 'Dismissed'])) {
            $alert = Alert::find($validated['alert_id']);
            if ($alert && !$alert->is_dismissed) {
                if ($response->action_type === 'Resolved') {
                    $alert->update(['status' => 'resolved']);
                } elseif ($response->action_type === 'Dismissed') {
                    $alert->dismiss();
                }
            }
        }

        return new AlertResponseResource($response->load(['alert', 'creator', 'responder']));
    }

    /**
     * Display the specified response.
     */
    public function show(AlertResponse $response)
    {
        $response->load(['alert', 'creator', 'responder']);

        return new AlertResponseResource($response);
    }

    /**
     * Update the specified response.
     */
    public function update(Request $request, AlertResponse $response)
    {
        $response->update($request->validated());

        return new AlertResponseResource($response->fresh(['alert', 'creator', 'responder']));
    }

    /**
     * Remove the specified response.
     */
    public function destroy(AlertResponse $response)
    {
        $response->delete();

        return response()->json([
            'message' => 'Response deleted successfully',
            'response_id' => $response->id,
        ]);
    }
}
