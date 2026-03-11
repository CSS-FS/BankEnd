<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\FcmService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Start with base query based on user role
        if ($user->hasRole('admin')) {
            $query = Alert::query();
        } elseif ($user->hasRole('owner')) {
            $farm_ids = $user->farms()->pluck('id')->toArray();
            $query = Alert::whereIn('farm_id', $farm_ids);
        } elseif ($user->hasRole('manager')) {
            $farm_id = $user->managedFarms()->pluck('id')->toArray();
            $query = Alert::where('farm_id', $farm_id);
        } else {
            return response()->json(['data' => []]);
        }

        $query = Alert::query();

        // Apply filters
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        if ($request->has('channel')) {
            $query->withChannel($request->channel);
        }

        if ($request->has('farm_id')) {
            $query->forFarm($request->farm_id);
        }

        if ($request->has('shed_id')) {
            $query->forShed($request->shed_id);
        }

        if ($request->has('flock_id')) {
            $query->forFlock($request->flock_id);
        }

        if ($request->boolean('unread')) {
            $query->unread();
        }

        if ($request->boolean('critical')) {
            $query->critical();
        }

        if ($request->boolean('active')) {
            $query->active();
        }

        if ($request->boolean('dismissed')) {
            $query->dismissed();
        }

        // Apply sorting
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Eager load relationships
        $query->with(['user', 'farm', 'shed', 'flock', 'latestResponse.responder']);
        $alerts = $query->paginate(10);

        return AlertResource::collection($alerts);
    }

    public function unreadAlerts()
    {
        $user = auth()->user();

        // Start with base query based on user role
        if ($user->hasRole('admin')) {
            $query = Alert::query();
        } elseif ($user->hasRole('owner')) {
            $farm_ids = $user->farms()->pluck('id')->toArray();
            $query = Alert::whereIn('farm_id', $farm_ids);
        } elseif ($user->hasRole('manager')) {
            $farm_id = $user->managedFarms()->pluck('id')->toArray();
            $query = Alert::where('farm_id', $farm_id);
        } else {
            return response()->json(['data' => []]);
        }

        $alerts = $query->unread()
            ->active()
            ->with(['user', 'farm', 'shed'])
            ->latest()
            ->paginate(10);

        return AlertResource::collection($alerts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validated([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:system,security,billing,activity,maintenance',
            'severity' => 'required|in:critical,major,warning,notice,info,debug,success',
            'channel' => 'required|in:in_app,email,sms,push',
            'data' => 'nullable|array',
            'status' => 'nullable|in:queued,sent,failed,delivered',
            'scheduled_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'farm_id' => 'nullable|exists:farms,id',
            'shed_id' => 'nullable|exists:sheds,id',
            'flock_id' => 'nullable|exists:flocks,id',
        ]);

        // Set default values if not provided
        $validated['status'] = $validated['status'] ?? 'queued';
        $validated['severity'] = $validated['severity'] ?? 'info';
        $validated['type'] = $validated['type'] ?? 'system';
        $validated['channel'] = $validated['channel'] ?? 'push';
        $validated['data'] = $validated['data'] ?? [];

        $alert = Alert::create($validated);

        return AlertResource::make($alert->load(['user', 'farm', 'shed', 'flock']));
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
        $alert->update($request->validated(
            [
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'required|in:system,security,billing,activity,maintenance',
                'severity' => 'required|in:critical,major,warning,notice,info,debug,success',
                'channel' => 'required|in:in_app,email,sms,push',
                'data' => 'nullable|array',
                'status' => 'nullable|in:queued,sent,failed,delivered',
                'scheduled_at' => 'nullable|date',
                'user_id' => 'nullable|exists:users,id',
                'farm_id' => 'nullable|exists:farms,id',
                'shed_id' => 'nullable|exists:sheds,id',
                'flock_id' => 'nullable|exists:flocks,id',
            ]
        ));

        return AlertResource::make($alert->fresh(['user', 'farm', 'shed', 'flock']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alert $alert)
    {
        $id = $alert->id;
        $alert->delete();

        return response()->json([
            'message' => 'Alert deleted successfully',
            'alert_id' => $id,
        ]);
    }

    // Extra Actions
    public function markAsRead(Request $request, Alert $alert)
    {
        return AlertResource::make($alert->markAsRead());
    }

    public function markAsUnread(Request $request, Alert $alert)
    {
        return AlertResource::make($alert->markAsUnread());
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
     * Get only active critical alerts
     */
    public function critical()
    {
        $alerts = Alert::with('user')
            ->severity('critical')
            ->latest()
            ->paginate(10);

        return AlertResource::collection($alerts);
    }

    public function responseStore(Request $request, Alert $alert)
    {
        $response = $alert->responses()
            ->create($request->all());

        return AlertResource::make($response);
    }

    /**
     * Get alert statistics.
     */
    public function statistics(Request $request)
    {
        $query = Alert::query();

        // Apply filters
        if ($request->has('farm_id')) {
            $query->forFarm($request->farm_id);
        }

        if ($request->has('shed_id')) {
            $query->forShed($request->shed_id);
        }

        if ($request->has('flock_id')) {
            $query->forFlock($request->flock_id);
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $total = $query->count();
        $unread = $query->clone()->unread()->count();
        $critical = $query->clone()->critical()->count();
        $warning = $query->clone()->warning()->count();
        $dismissed = $query->clone()->dismissed()->count();
        $active = $query->clone()->active()->count();

        // Group by type
        $byType = $query->clone()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Group by severity
        $bySeverity = $query->clone()
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        // Group by status
        $byStatus = $query->clone()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Response statistics
        $withResponses = $query->clone()
            ->has('responses')
            ->count();

        $avgResponseTime = $query->clone()
            ->join('alert_responses', 'alert.id', '=', 'alert_responses.alert_id')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (alert_responses.responded_at - alert.created_at)) / 60) as avg_minutes')
            ->value('avg_minutes');

        return response()->json([
            'statistics' => [
                'total' => $total,
                'unread' => $unread,
                'critical' => $critical,
                'warning' => $warning,
                'dismissed' => $dismissed,
                'active' => $active,
                'with_responses' => $withResponses,
                'avg_response_time_minutes' => round($avgResponseTime ?? 0, 2),
            ],
            'distribution' => [
                'by_type' => $byType,
                'by_severity' => $bySeverity,
                'by_status' => $byStatus,
            ],
        ]);
    }

    /**
     * Get alerts by priority (most urgent first).
     */
    public function byPriority(Request $request)
    {
        $query = Alert::query()->active();

        // Apply filters
        if ($request->has('farm_id')) {
            $query->forFarm($request->farm_id);
        }

        // Get alerts and calculate priority score
        $alerts = $query->with(['user', 'farm'])
            ->get()
            ->map(function ($alert) {
                $alert->priority_score = $alert->getPriorityScore();
                return $alert;
            })
            ->sortByDesc('priority_score')
            ->values();

        // Paginate manually
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginated = $alerts->slice($offset, $perPage);

        return new AlertCollection(new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $alerts->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        ));
    }

    public function sendToUser(Request $request)
    {
        $fcmService = new FcmService;

    }
}
