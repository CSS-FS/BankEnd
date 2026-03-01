<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token'     => ['required', 'string', 'max:512'],
            'platform'  => ['required', 'in:android,ios'],
            'device_id' => ['required', 'string', 'max:128'],
        ]);

        $user = $request->user();

        // Resolve farm — admin users have no farm association
        $farmId = null;
        if (! $user->hasRole('admin')) {
            $farm = $user->farms()->first()
                ?? $user->managedFarms()->first()
                ?? $user->staffFarms()->first();
            $farmId = $farm?->id;
        }

        // Upsert by user_id + device_id
        DeviceToken::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $data['device_id']],
            [
                'token'            => $data['token'],
                'platform'         => $data['platform'],
                'farm_id'          => $farmId,
                'last_updated_at'  => now(),
                'revoked_at'       => null,
            ]
        );

        return response()->json(['message' => 'Token saved'], 200);
    }

    /**
     * @param Request $request
     * @param string $deviceId
     * @return JsonResponse
     */
    public function revoke(Request $request, string $deviceId)
    {
        $user = $request->user();

        DeviceToken::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Token revoked'], 200);
    }
}
