<?php

namespace App\Http\Controllers\Web;

use App\Events\AbnormalKPIDetected;
use App\Events\DailyReportSubmitted;
use App\Exports\ProductionLogsExport;
use App\Services\KPIAlertService;
use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Flock;
use App\Models\ProductionLog;
use App\Models\Shed;
use App\Services\WeightLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductionLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $farms = $user->hasRole('admin')
            ? Farm::with('sheds.flocks')->orderBy('name')->get()
            : Farm::with('sheds.flocks')
                ->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                      ->orWhereHas('managers', fn ($q) => $q->where('users.id', $user->id))
                      ->orWhereHas('staff', fn ($q) => $q->where('users.id', $user->id));
                })
                ->orderBy('name')
                ->get();

        $logs = collect();
        $farmId = null;
        $ages = null;
        $dailyMortality = null;
        $livability = null;
        $dailyFeed = null;
        $feedConversionRatio = null;
        $coefficientOfVariation = null;
        $productionEfficiencyFactor = null;

        // Only show logs if all three selected
        if ($request->filled('filter.shed_id') && $request->filled('filter.flock_id')) {
            $logs = QueryBuilder::for(ProductionLog::class)
                ->with(['shed', 'flock', 'user', 'weightLog'])
                ->allowedFilters([
                    AllowedFilter::exact('shed_id'),
                    AllowedFilter::exact('flock_id'),
                ])
                ->latest('production_log_date')
                ->get();

            $farmId = Shed::find($request->filled('filter.shed_id'))->farm->id;
            $reverseLogs = $logs->sortBy('age')->values();
            // Age labels in ascending order
            $ages = $reverseLogs->pluck('age')->values()->toArray();
            // Daily Mortality = day + night
            $dailyMortality = $reverseLogs->map(function ($log) {
                return $log->day_mortality_count + $log->night_mortality_count;
            })->values()->toArray();
            // Daily Feed = day + night
            $dailyFeed = $reverseLogs->map(function ($log) {
                return ($log->day_feed_consumed + $log->night_feed_consumed) / 1000;
            })->values()->toArray();
            // Livability as before
            $livability = $reverseLogs->pluck('livability')->values()->toArray();
            // Prepare bar data
            $feedConversionRatio = $reverseLogs->map(fn ($l) => $l->weightLog->feed_conversion_ratio ?? null)->values()->toArray();
            $coefficientOfVariation = $reverseLogs->map(fn ($l) => $l->weightLog->coefficient_of_variation ?? null)->values()->toArray();
            $productionEfficiencyFactor = $reverseLogs->map(fn ($l) => $l->weightLog->production_efficiency_factor ?? null)->values()->toArray();

        }

        return view(
            'admin.logs.index',
            compact('logs',
                'farms', 'farmId', 'ages', 'dailyMortality', 'livability', 'dailyFeed',
                'feedConversionRatio', 'coefficientOfVariation', 'productionEfficiencyFactor'
            )
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Load required relationships for the form (sheds, flocks, users, etc.)
        return view('admin.logs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
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
            'day_medicine' => $validated['day_medicine'] ?? null,
            'night_medicine' => $validated['night_medicine'] ?? null,
            'user_id' => Auth::id(),
        ]);

        // Notify all stakeholders that a daily report has been submitted
        event(new DailyReportSubmitted($productionLog, $productionLog->shed, $flock));

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

        // Check KPIs and fire alert if any are abnormal
        $breaches = app(KPIAlertService::class)->check($productionLog);
        if (! empty($breaches)) {
            event(new AbnormalKPIDetected($productionLog, $productionLog->shed, $flock, $breaches));
        }

        return redirect()
            ->route('productions.index')
            ->with('success', 'Production log created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductionLog $productionLog)
    {
        $productionLog->load(['shed', 'flock', 'user', 'weightLog']);

        return view(
            'productions.show',
            compact('productionLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductionLog $productionLog)
    {
        $productionLog->load(['shed', 'flock', 'user', 'weightLog']);

        return view(
            'productions.edit',
            compact('productionLog'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductionLog $productionLog)
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

        DB::transaction(function () use ($productionLog, $validated, $flock) {
            $lastLog = ProductionLog::where('flock_id', $validated['flock_id'])
                ->where('shed_id', $validated['shed_id'])
                ->whereDate('production_log_date', '<', $productionLog->production_log_date)
                ->orderBy('production_log_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $lastNetCount = $lastLog ? $lastLog->net_count : $flock->chicken_count;
            $net_count = $lastNetCount - ($validated['day_mortality_count'] + $validated['night_mortality_count']);
            $age = $flock->start_date->diffInDays(today()) ?? 0;
            $livability = $flock->chicken_count > 0
                ? daily_livability($net_count, $flock->chicken_count)
                : 0;

            $productionLog->update([
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

            $this->recalculateSubsequentLogs($productionLog, $flock);

            if (
                ! empty($validated['with_weight_log']) &&
                ! empty($validated['weighted_chickens_count']) &&
                ! empty($validated['total_weight'])
            ) {
                app(WeightLogService::class)->createOrUpdateWeightLog(
                    $productionLog,
                    $validated['weighted_chickens_count'],
                    $validated['total_weight'],
                    ['before_production_log_date' => $productionLog->production_log_date]
                );
            }
        });

        return redirect()
            ->route('productions.index')
            ->with('success', 'Production log updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductionLog $productionLog)
    {
        $productionLog->delete();

        return redirect()
            ->route('productions.index')
            ->with('success', 'Production log deleted.');
    }

    public function exportExcel(Request $request)
    {
        // Only allow shed_id and flock_id as filters
        $query = QueryBuilder::for(ProductionLog::class)
            ->with(['shed', 'flock', 'user', 'weightLog'])
            ->allowedFilters([
                AllowedFilter::exact('shed_id'),
                AllowedFilter::exact('flock_id'),
            ])
            ->orderBy('production_log_date');

        // If you want to export all filtered records (no pagination)
        $logs = $query->get();

        return Excel::download(new ProductionLogsExport($logs), 'production-logs.xlsx');
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

            $log = $log->refresh()->load('weightLog');
            if ($log->weightLog && $log->weightLog->weighted_chickens_count && $log->weightLog->total_weight) {
                app(WeightLogService::class)->createOrUpdateWeightLog(
                    $log,
                    (int) $log->weightLog->weighted_chickens_count,
                    (float) $log->weightLog->total_weight,
                    ['before_production_log_date' => $log->production_log_date]
                );
                $log = $log->refresh();
            }

            $previousLog = $log->fresh(['weightLog']);
        }
    }
}
