<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\NotificationOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushNotificationController extends Controller
{
    public function enqueue(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|in:topic,user,token',
            'topic' => 'nullable|string|max:191',
            'user_id' => 'nullable|integer|exists:users,id',
            'device_token_id' => 'nullable|integer|exists:device_tokens,id',
            'title' => 'nullable|string|max:191',
            'body' => 'nullable|string|max:2000',
            'payload' => 'nullable|json',
            'scheduled_at' => 'nullable|date',
            'fanout' => 'nullable|boolean',
        ]);

        $data = [];
        if (! empty($validated['payload'])) {
            $decoded = json_decode($validated['payload'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['payload' => 'Payload must be valid JSON.'])->withInput();
            }
            $data = $decoded;
        }

        $scheduledAt = $validated['scheduled_at'] ?? null;

        DB::transaction(function () use ($validated, $data, $scheduledAt) {

            // TOPIC
            if ($validated['target'] === 'topic') {
                NotificationOutbox::create([
                    'target_type' => 'topic',
                    'target_topic' => $validated['topic'],
                    'title' => $validated['title'] ?? null,
                    'body' => $validated['body'] ?? null,
                    'data' => $data ?: null,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                ]);

                return;
            }

            // TOKEN (single device)
            if ($validated['target'] === 'token') {
                NotificationOutbox::create([
                    'target_type' => 'token',
                    'target_id' => (int) $validated['device_token_id'], // device_tokens.id
                    'title' => $validated['title'] ?? null,
                    'body' => $validated['body'] ?? null,
                    'data' => $data ?: null,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                ]);

                return;
            }

            // USER (all devices) - normal or fan-out
            $userId = (int) $validated['user_id'];
            $fanout = ! empty($validated['fanout']);

            if (! $fanout) {
                // single outbox row, dispatcher resolves tokens later
                NotificationOutbox::create([
                    'target_type' => 'user',
                    'target_id' => $userId,
                    'title' => $validated['title'] ?? null,
                    'body' => $validated['body'] ?? null,
                    'data' => $data ?: null,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                ]);

                return;
            }

            // FAN-OUT: create 1 outbox row per token
            $tokens = DeviceToken::query()
                ->where('user_id', $userId)
                ->whereNull('revoked_at')
                ->pluck('id');

            foreach ($tokens as $tokenId) {
                NotificationOutbox::create([
                    'target_type' => 'token',
                    'target_id' => (int) $tokenId,
                    'title' => $validated['title'] ?? null,
                    'body' => $validated['body'] ?? null,
                    'data' => $data ?: null,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                ]);
            }
        });

        return back()->with('success', 'Notification enqueued successfully.');
    }

    public function logs()
    {
        $items = NotificationOutbox::orderByDesc('id')
            ->get();

        return view('admin.push_notifications.logs', compact('items'));
    }
}
