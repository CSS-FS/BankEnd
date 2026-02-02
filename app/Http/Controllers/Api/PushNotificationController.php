<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\NotificationOutbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PushNotificationController extends ApiController
{
    /**
     * Send a push notification to a specific user (all devices).
     *
     * @throws ValidationException
     */
    public function sendToUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:191',
            'body' => 'required|string|max:2000',
            'data' => 'nullable|array',
            'scheduled_at' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        // Create an outbox entry targeting the user
        // The queue worker will pick this up and dispatch to all user devices
        $outbox = NotificationOutbox::create([
            'target_type' => 'user',
            'target_id' => $validated['user_id'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'data' => $validated['data'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
        ]);

        return response()->json([
            'message' => 'Notification queued successfully.',
            'outbox_id' => $outbox->id,
            'status' => 'pending',
        ], 201);
    }
}
