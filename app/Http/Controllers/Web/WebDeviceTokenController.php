<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class WebDeviceTokenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tokens = DeviceToken::with(['user', 'farm'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->orderByDesc('last_updated_at')
            ->paginate(30);

        return view('admin.push_notifications.device_tokens', compact('tokens'));
    }

    public function revoke(DeviceToken $token)
    {
        $token->update(['revoked_at' => now()]);

        return back()->with('success', 'Token revoked successfully.');
    }
}
