<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ManagerAnalyticsService;
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
            $farms = $user->farms()
                ->with('sheds.latestFlock')
                ->get();

            return view(
                'dashboards.owner',
                [
                    'user' => $user,
                    'farms' => $farms,
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
