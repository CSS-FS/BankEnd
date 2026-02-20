<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NotificationTriggered;
use App\Http\Controllers\ApiController;
use App\Models\Flock;
use App\Models\ProductionLog;
use App\Models\Shed;
use App\Services\DailyReportService;
use App\Services\WeightLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductionLogController extends ApiController
{
    public function __construct(private DailyReportService $dailyReportService) {}

    public function index(Request $request)
    {
        $logs = QueryBuilder::for(ProductionLog::class)
            ->with(['shed', 'flock', 'user', 'weightLog'])
            ->allowedFilters([
                AllowedFilter::exact('shed_id'),
                AllowedFilter::exact('flock_id'),
            ])
            ->latest()
            ->get();

        return response()->json($logs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shed_id' => 'required|exists:sheds,id',
            'flock_id' => 'required|exists:flocks,id',
            'day_mortality_count' => 'required|integer|min:0',
            'night_mortality_count' => 'required|integer|min:0',
            'net_count' => 'nullable|integer|min:0',
            'day_feed_consumed' => 'required|numeric|min:0',
            'night_feed_consumed' => 'required|numeric|min:0',
            'day_water_consumed' => 'required|numeric|min:0',
            'night_water_consumed' => 'required|numeric|min:0',
            'is_vaccinated' => 'required|boolean',
            'day_medicine' => 'nullable|string',
            'night_medicine' => 'nullable|string',
            'with_weight_log' => 'nullable|boolean',
            'weighted_chickens_count' => 'nullable|numeric|min:0',
            'total_weight' => 'nullable|numeric|min:0',
        ]);

        $today = Carbon::today()->toDateString();

        $existingLog = ProductionLog::where('shed_id', $validated['shed_id'])
            ->where('flock_id', $validated['flock_id'])
            ->whereDate('production_log_date', $today)
            ->first();

        if ($existingLog) {
            return response()->json([
                'message' => 'Production log already exists for today.',
                'production_log_id' => $existingLog->id,
            ], 200);
        }

        $flock = Flock::findOrFail($validated['flock_id']);

        // Find the last production log to calculate net_count correctly
        $lastLog = ProductionLog::where('flock_id', $flock->id)->latest()->first();
        $lastNetCount = $lastLog ? $lastLog->net_count : $flock->chicken_count;

        // Calculate net_count for the new log
        $net_count = $lastNetCount - ($validated['day_mortality_count'] + $validated['night_mortality_count']);

        // Calculate age (days since flock's start_date)
        $age = $flock->start_date->diffInDays(today()) ?? 0;

        // Calculate livability
        $livability = $flock->chicken_count > 0
            ? daily_livability($net_count, $flock->chicken_count)
            : 0;

        // Prepare data for new ProductionLog
        $productionLog = ProductionLog::create([
            'shed_id' => $validated['shed_id'],
            'flock_id' => $validated['flock_id'],
            'age' => $age,
            'day_mortality_count' => $validated['day_mortality_count'],
            'night_mortality_count' => $validated['night_mortality_count'],
            'todate_mortality_count' => $lastLog
                ? $lastLog->todate_mortality_count + $validated['day_mortality_count'] + $validated['night_mortality_count']
                : $validated['day_mortality_count'] + $validated['night_mortality_count'],
            'net_count' => $net_count,
            'livability' => $livability,
            'day_feed_consumed' => $validated['day_feed_consumed'],
            'night_feed_consumed' => $validated['night_feed_consumed'],
            'todate_feed_consumed' => $lastLog
                ? $lastLog->todate_feed_consumed + $validated['day_feed_consumed'] + $validated['night_feed_consumed']
                : $validated['day_feed_consumed'] + $validated['night_feed_consumed'],
            'day_water_consumed' => $validated['day_water_consumed'],
            'night_water_consumed' => $validated['night_water_consumed'],
            'todate_water_consumed' => $lastLog
                ? $lastLog->todate_water_consumed + $validated['day_water_consumed'] + $validated['night_water_consumed']
                : $validated['day_water_consumed'] + $validated['night_water_consumed'],
            'is_vaccinated' => $validated['is_vaccinated'],
            'day_medicine' => $validated['day_medicine'],
            'night_medicine' => $validated['night_medicine'],
            'user_id' => Auth::id(),
        ]);

        // Trigger notification for farm owner
        $farm = $productionLog->shed->farm;
        if ($farm && $farm->owner) {
            event(new NotificationTriggered(
                type: 'report',
                notifiable: $productionLog,
                userId: $farm->owner->id,
                farmId: $farm->id,
                title: 'New Daily Report Submitted',
                message: "A new daily report for Shed '{$productionLog->shed->name}' in Flock '{$productionLog->flock->name}' has been submitted by {$productionLog->user->name}.",
                data: [
                    'shed_id' => $productionLog->shed_id,
                    'flock_id' => $productionLog->flock_id,
                    'production_log_id' => $productionLog->id,
                    'submitter_id' => $productionLog->user_id,
                ]
            ));
        }

        // Optionally: Only create weight log if provided and valid
        if (
            ! empty($validated['with_weight_log']) &&
            ! empty($validated['weighted_chickens_count']) &&
            ! empty($validated['total_weight'])
        ) {
            app(WeightLogService::class)->createOrUpdateWeightLog(
                $productionLog,
                $validated['weighted_chickens_count'],
                $validated['total_weight']
            );
        }

        return response()->json($productionLog, 201);
    }

    public function show(ProductionLog $productionLog)
    {
        $productionLog->load(['shed', 'flock', 'user', 'weightLog']);

        if (! $productionLog->shed || ! $productionLog->flock) {
            return response()->json(['message' => 'Invalid production log data.'], 404);
        }

        return response()->json($productionLog);
    }

    public function update(Request $request, ProductionLog $production)
    {
        $validated = $request->validate([
            'shed_id' => 'required|exists:sheds,id',
            'flock_id' => 'required|exists:flocks,id',
            'day_mortality_count' => 'required|integer|min:0',
            'night_mortality_count' => 'required|integer|min:0',
            'net_count' => 'nullable|integer|min:0',
            'day_feed_consumed' => 'required|numeric|min:0',
            'night_feed_consumed' => 'required|numeric|min:0',
            'day_water_consumed' => 'required|numeric|min:0',
            'night_water_consumed' => 'required|numeric|min:0',
            'is_vaccinated' => 'required|boolean',
            'day_medicine' => 'nullable|string',
            'night_medicine' => 'nullable|string',
            'with_weight_log' => 'nullable|boolean',
            'weighted_chickens_count' => 'nullable|numeric|min:0',
            'total_weight' => 'nullable|numeric|min:0',
        ]);

        $flock = Flock::findOrFail($validated['flock_id']);

        DB::transaction(function () use ($production, $validated, $flock) {
            // Find the last production log in the same flock/shed (by production_log_date) to calculate cumulative fields correctly
            $lastLog = ProductionLog::where('flock_id', $validated['flock_id'])
                ->where('shed_id', $validated['shed_id'])
                ->whereDate('production_log_date', '<', $production->production_log_date)
                ->orderBy('production_log_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $lastNetCount = $lastLog ? $lastLog->net_count : $flock->chicken_count;

            // Calculate net_count for the updated log
            $net_count = $lastNetCount - ($validated['day_mortality_count'] + $validated['night_mortality_count']);

            // Calculate age (days since flock's start_date)
            $age = $flock->start_date->diffInDays(today()) ?? 0;

            // Calculate livability
            $livability = $flock->chicken_count > 0
                ? daily_livability($net_count, $flock->chicken_count)
                : 0;

            // Update ProductionLog
            $production->update([
                'shed_id' => $validated['shed_id'],
                'flock_id' => $validated['flock_id'],
                'age' => $age,
                'day_mortality_count' => $validated['day_mortality_count'],
                'night_mortality_count' => $validated['night_mortality_count'],
                'todate_mortality_count' => $lastLog
                    ? $lastLog->todate_mortality_count + $validated['day_mortality_count'] + $validated['night_mortality_count']
                    : $validated['day_mortality_count'] + $validated['night_mortality_count'],
                'net_count' => $net_count,
                'livability' => $livability,
                'day_feed_consumed' => $validated['day_feed_consumed'],
                'night_feed_consumed' => $validated['night_feed_consumed'],
                'todate_feed_consumed' => $lastLog
                    ? $lastLog->todate_feed_consumed + $validated['day_feed_consumed'] + $validated['night_feed_consumed']
                    : $validated['day_feed_consumed'] + $validated['night_feed_consumed'],
                'day_water_consumed' => $validated['day_water_consumed'],
                'night_water_consumed' => $validated['night_water_consumed'],
                'todate_water_consumed' => $lastLog
                    ? $lastLog->todate_water_consumed + $validated['day_water_consumed'] + $validated['night_water_consumed']
                    : $validated['day_water_consumed'] + $validated['night_water_consumed'],
                'is_vaccinated' => $validated['is_vaccinated'],
                'day_medicine' => $validated['day_medicine'],
                'night_medicine' => $validated['night_medicine'],
                'user_id' => Auth::id(),
            ]);

            // Recalculate downstream logs to keep cumulative values consistent
            $this->recalculateSubsequentLogs($production, $flock);

            // Update weight log if provided and valid
            if (
                ! empty($validated['with_weight_log']) &&
                ! empty($validated['weighted_chickens_count']) &&
                ! empty($validated['total_weight'])
            ) {
                app(WeightLogService::class)->createOrUpdateWeightLog(
                    $production,
                    $validated['weighted_chickens_count'],
                    $validated['total_weight'],
                    [
                        'mode' => 'update',
                        'before_production_log_date' => $production->production_log_date,
                    ]
                );
            }
        });

        return response()->json($production->refresh());
    }

    public function destroy(ProductionLog $productionLog)
    {
        $productionLog->delete();

        return response()->json(null, 204);
    }

    public function dailyReportHeaders($shedId, $flockId)
    {
        $shed = Shed::where('id', $shedId)
            ->with(['flocks' => function ($query) use ($flockId) {
                $query->where('id', $flockId)->with('productionLogs');
            }])
            ->first();

        if (! $shed) {
            return response()->json(['message' => 'Shed not found'], 404);
        }

        $flock = $shed->flocks->first();

        if (! $flock) {
            return response()->json(['message' => 'Flock not found in this shed'], 404);
        }

        $productionLogDates = $flock->productionLogs
            ->pluck('production_log_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            });

        return response()->json([
            'shed_id' => $shed->id,
            'shed_name' => $shed->name,
            'flock_id' => $flock->id,
            'flock_name' => $flock->name,
            'production_log_dates' => $productionLogDates,
        ], 200);
    }

    public function productionDatesByFlock($flockId)
    {
        if ($flockId) {
            $flock = Flock::with('shed')->findOrFail($flockId);

            $productionLogDates = ProductionLog::where('flock_id', $flockId)
                ->pluck('production_log_date')
                ->map(function ($date) {
                    return Carbon::parse($date)->format('Y-m-d');
                });

            return response()->json([
                'shed_id' => $flock->shed->id,
                'shed_name' => $flock->shed->name,
                'flock_id' => $flockId,
                'flock_name' => $flock->name,
                'production_log_dates' => $productionLogDates,
            ], 200);
        } else {
            return response()->json(['message' => 'Flock id is not provided.'], 404);
        }
    }

    public function dailyReport(Request $request, $version = 'en')
    {
        $request->validate([
            'shed_id' => 'required|integer|exists:sheds,id',
            'date' => 'required|date_format:Y-m-d', // Ensure date format
        ]);

        try {
            $payload = $this->dailyReportService->build(
                (int) $request->input('shed_id'),
                (string) $request->input('date'),
                (string) $version
            );

            return response()->json($payload, 200);

        } catch (NotFoundHttpException $e) {
            // Preserve your original 404 messages
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function history(Request $request)
    {
        $request->validate([
            'shed_id' => 'required|integer|exists:sheds,id',
            'range' => 'nullable|string|in:today,7days,30days,this_month,last_month,all,custom',
            'start_date' => 'nullable|date_format:Y-m-d|required_if:range,custom',
            'end_date' => 'nullable|date_format:Y-m-d|required_if:range,custom|after_or_equal:start_date',
        ]);

        $shedId = $request->input('shed_id');
        $range = $request->input('range', 'all'); // default all

        $query = ProductionLog::where('shed_id', $shedId);

        // Apply range filters
        switch ($range) {
            case 'today':
                $query->whereDate('production_log_date', today());
                break;

            case '7days':
                $query->whereBetween('production_log_date', [now()->subDays(7)->startOfDay(), now()->endOfDay()]);
                break;

            case '30days':
                $query->whereBetween('production_log_date', [now()->subDays(30)->startOfDay(), now()->endOfDay()]);
                break;

            case 'this_month':
                $query->whereMonth('production_log_date', now()->month)
                    ->whereYear('production_log_date', now()->year);
                break;

            case 'last_month':
                $lastMonth = now()->subMonth();
                $query->whereMonth('production_log_date', $lastMonth->month)
                    ->whereYear('production_log_date', $lastMonth->year);
                break;

            case 'custom':
                $query->whereBetween('production_log_date', [
                    $request->input('start_date'),
                    $request->input('end_date'),
                ]);
                break;

            case 'all':
            default:
                // No filter applied
                break;
        }

        $logs = $query->orderBy('production_log_date', 'asc')->get();

        if ($logs->isEmpty()) {
            return response()->json(['message' => 'No production data found for the given criteria.'], 404);
        }

        // Format response only with production_logs table data
        $formatted = $logs->map(function ($data) {
            return [
                'date' => $data->production_log_date->format('d-m-Y'),
                'age' => $data->age.' Days',
                'day_mortality_count' => $data->day_mortality_count,
                'night_mortality_count' => $data->night_mortality_count,
                'net_count' => $data->net_count,
                'livability' => $data->livability.' %',
                'day_feed_consumed' => round($data->day_feed_consumed / 1000, 2).' Kg',
                'night_feed_consumed' => round($data->night_feed_consumed / 1000, 2).' Kg',
                'avg_feed_consumed' => round($data->avg_feed_consumed / 1000, 2).' Kg',
                'day_water_consumed' => round($data->day_water_consumed / 1000, 2).' L',
                'night_water_consumed' => round($data->night_water_consumed / 1000, 2).' L',
                'avg_water_consumed' => round($data->avg_water_consumed / 1000, 2).' L',
                'is_vaccinated' => ($data->is_vaccinated ? 'Yes' : 'No'),
                'day_medicine' => $data->day_medicine ?? '',
                'night_medicine' => $data->night_medicine ?? '',
                'submit_at' => $data->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'shed_id' => $shedId,
            'total_records' => $logs->count(),
            'range' => $range,
            'history' => $formatted,
        ], 200);
    }

    public function latestHistory(Request $request)
    {
        $request->validate([
            'shed_id' => 'required|integer|exists:sheds,id',
        ]);

        $shedId = $request->input('shed_id');

        // ✅ Find the latest flock for this shed
        $latestFlock = Flock::where('shed_id', $shedId)
            ->latest('start_date') // adjust if different column
            ->first();

        if (! $latestFlock) {
            return response()->json(['message' => 'No flock found for this shed.'], 404);
        }

        // ✅ Fetch the latest production log for this flock
        $latestLog = ProductionLog::where('shed_id', $shedId)
            ->where('flock_id', $latestFlock->id)
            ->latest('production_log_date')
            ->first();

        if (! $latestLog) {
            return response()->json(['message' => 'No production data found for the latest flock.'], 404);
        }

        // ------------------------------
        // 🔹 Consumption Aggregates
        // ------------------------------
        $consumption = function ($days = null) use ($latestFlock, $shedId) {
            $query = ProductionLog::where('shed_id', $shedId)
                ->where('flock_id', $latestFlock->id);

            if ($days) {
                $query->where('production_log_date', '>=', now()->subDays($days));
            }

            $logs = $query->get();

            return [
                'feed' => round(($logs->sum('day_feed_consumed') + $logs->sum('night_feed_consumed')) / 1000, 2).' kg',
                'water' => round(($logs->sum('day_water_consumed') + $logs->sum('night_water_consumed')) / 1000, 2).' L',
            ];
        };

        $last24h = $consumption(1);
        $last7d = $consumption(7);
        $last30d = $consumption(30);
        $allTime = $consumption(null);

        // ------------------------------
        // 🔹 Format Latest Log Response
        // ------------------------------
        $formatted = [
            'date' => $latestLog->production_log_date->format('d-m-Y'),
            'age' => $latestLog->age.' Days',
            'day_mortality_count' => $latestLog->day_mortality_count,
            'night_mortality_count' => $latestLog->night_mortality_count,
            '24h_mortality_count' => $latestLog->total_mortality_count,
            'net_count' => $latestLog->net_count,
            'livability' => $latestLog->livability.' %',
            'day_feed_consumed' => round($latestLog->day_feed_consumed / 1000, 2).' kg',
            'night_feed_consumed' => round($latestLog->night_feed_consumed / 1000, 2).' kg',
            '24h_feed_consumed' => round($latestLog->total_feed_consumed / 1000, 2).' kg',
            'avg_feed_consumed' => round($latestLog->avg_feed_consumed / 1000, 2).' kg',
            'day_water_consumed' => round($latestLog->day_water_consumed / 1000, 2).' L',
            'night_water_consumed' => round($latestLog->night_water_consumed / 1000, 2).' L',
            '24h_water_consumed' => round($latestLog->total_water_consumed / 1000, 2).' L',
            'avg_water_consumed' => round($latestLog->avg_water_consumed / 1000, 2).' L',
            'is_vaccinated' => ($latestLog->is_vaccinated ? 'Yes' : 'No'),
            'day_medicine' => $latestLog->day_medicine ?? '',
            'night_medicine' => $latestLog->night_medicine ?? '',
            'submit_at' => $latestLog->created_at->diffForHumans(),
        ];

        return response()->json([
            'shed_id' => $shedId,
            'flock_id' => $latestFlock->id,
            'flock_name' => $latestFlock->name ?? null,
            'latest_history' => $formatted,
            'consumption_summary' => [
                'last_24h' => $last24h,
                'last_7_days' => $last7d,
                'last_30_days' => $last30d,
                'all_time' => $allTime,
            ],
        ], 200);
    }

    /**
     * Propagate recalculated cumulative fields to all logs after the provided one within the same flock/shed.
     */
    private function recalculateSubsequentLogs(ProductionLog $startingLog, Flock $flock): void
    {
        $subsequentLogs = ProductionLog::with('weightLog')
            ->where('flock_id', $flock->id)
            ->where('shed_id', $startingLog->shed_id)
            ->whereDate('production_log_date', '>', $startingLog->production_log_date)
            ->orderBy('production_log_date')
            ->orderBy('id')
            ->get();

        $previousLog = $startingLog->fresh(['weightLog']);

        foreach ($subsequentLogs as $log) {
            $dayMortality = $log->day_mortality_count;
            $nightMortality = $log->night_mortality_count;

            $netCount = $previousLog->net_count - ($dayMortality + $nightMortality);

            $log->update([
                'todate_mortality_count' => $previousLog->todate_mortality_count + $dayMortality + $nightMortality,
                'net_count' => $netCount,
                'livability' => $flock->chicken_count > 0
                    ? daily_livability($netCount, $flock->chicken_count)
                    : 0,
                'todate_feed_consumed' => $previousLog->todate_feed_consumed + $log->day_feed_consumed + $log->night_feed_consumed,
                'todate_water_consumed' => $previousLog->todate_water_consumed + $log->day_water_consumed + $log->night_water_consumed,
            ]);

            // Re-run weight log calculations if a weight log exists for this entry
            $log = $log->refresh()->load('weightLog');
            if ($log->weightLog && $log->weightLog->weighted_chickens_count && $log->weightLog->total_weight) {
                app(WeightLogService::class)->createOrUpdateWeightLog(
                    $log,
                    (int) $log->weightLog->weighted_chickens_count,
                    (float) $log->weightLog->total_weight,
                    ['before_production_log_date' => $log->production_log_date]
                );
                // Refresh after weight log recalculation to keep totals in sync for the next loop iteration
                $log = $log->refresh();
            }

            $previousLog = $log->fresh(['weightLog']);
        }
    }
}
