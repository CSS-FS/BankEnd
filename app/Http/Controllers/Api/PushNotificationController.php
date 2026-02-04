<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\DeviceToken;
use App\Models\NotificationOutbox;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PushNotificationController extends ApiController
{
    public function __construct(private FcmService $fcmService) {}

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

        $tokens = DeviceToken::where('user_id', $validated['user_id'])
            ->whereNull('revoked_at')
            ->get();

        $successCount = 0;
        $failureCount = 0;

        foreach ($tokens as $token) {
            try {
                $this->fcmService->sendToToken(
                    token: $token->token,
                    title: $validated['title'],
                    body: $validated['body'],
                    data: $validated['data'] ?? [],
                );
                $successCount++;
            } catch (\Throwable $e) {
                $failureCount++;
                $lastError = $e->getMessage();

                logger()->warning('FCM notification failed for token', [
                    'token_id' => $token->id,
                    'user_id' => $validated['user_id'],
                    'error' => $lastError,
                ]);
            }
        }

        $status = $successCount > 0 ? 'sent' : ($failureCount > 0 ? 'failed' : 'sent');

        return response()->json([
            'message' => $successCount > 0 ? 'Notification sent successfully.' : 'Notification delivery failed.',
            'user_id' => $validated['user_id'],
            'status' => $status,
            'devices_success' => $successCount,
            'devices_failed' => $failureCount,
        ], $successCount > 0 ? 201 : 500);
    }
}
