<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ManagerAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(public ManagerAnalyticsService $managerAnalyticsService) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->hasRole('admin')) {
            return view('dashboards.admin', compact('user'));
        } elseif ($user->hasRole('owner')) {
            $farm = $user->farms()
                ->with('sheds.latestFlock')
                ->first();

            if (! $farm) {
                return view('dashboards.owner', [
                    'user' => $user,
                    'farm' => null,
                    'data' => null,
                    'datasets' => [],
                    'adgData' => [],
                    'shedEnvironment' => [],
                    'environmentAlerts' => [],
                ]);
            }

            $filters['farm_id'] = $farm->id;
            $flocks = $farm->sheds->pluck('latestFlock')->toArray();

            foreach ($flocks as $flock) {
                if ($flock == null) {
                    continue;
                }
                $filters['start_date'] = Carbon::parse($flock['start_date'])->format('Y-m-d');
                $filters['end_date'] = Carbon::parse($flock['end_date'])->format('Y-m-d');
            }

            $data = $this->managerAnalyticsService->getAnalyticsData($filters)[0];
            $mortality_data = $this->managerAnalyticsService->getMortalityRateData($filters);
            $adgData = $this->managerAnalyticsService->adgData($filters);
            $shedEnvironment = $this->managerAnalyticsService->shedEnvironmentData($filters);
            $environmentAlerts = $this->managerAnalyticsService->environmentAlerts($filters);

            return view(
                'dashboards.owner',
                [
                    'user' => $user,
                    'farm' => $farm,
                    'data' => $data,
                    'datasets' => $mortality_data,
                    'adgData' => $adgData,
                    'shedEnvironment' => $shedEnvironment,
                    'environmentAlerts' => $environmentAlerts,
                ]
            );
        } elseif ($user->hasRole('manager')) {
            $farms = $user->managedFarms()
                ->with('sheds.latestFlock')
                ->get();

            return view(
                'dashboards.flocksense',
                [
                    'user' => $user,
                    'farms' => $farms,
                ]
            );
        }

        return abort(403, 'Unauthorized access: No appropriate role found.');
    }
}
