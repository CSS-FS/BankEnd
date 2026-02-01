<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\NotificationOutbox;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $outboxId) {}

    public function handle(FcmService $sender): void
    {
        $row = NotificationOutbox::find($this->outboxId);
        if (! $row) {
            return;
        }

        // Don't send before scheduled time
        if ($row->scheduled_at && now()->lt($row->scheduled_at)) {
            return;
        }

        // Skip if already done
        if (in_array($row->status, ['sent', 'failed'])) {
            return;
        }

        $row->update(['status' => 'processing']);

        try {
            if ($row->target_type === 'topic') {
                $sender->sendToTopic($row->target_topic, $row->title, $row->body, $row->data ?? []);
                $row->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);

                return;
            }

            if ($row->target_type === 'user') {
                // resolve all tokens and send (sequentially) - or you can enqueue per token inside here
                $tokenIds = DeviceToken::where('user_id', $row->target_id)
                    ->whereNull('revoked_at')
                    ->pluck('id');

                foreach ($tokenIds as $tokenId) {
                    // enqueue per token for parallel execution if desired
                    DispatchPushNotificationJob::dispatch(
                        NotificationOutbox::create([
                            'target_type' => 'token',
                            'target_id' => $tokenId,
                            'title' => $row->title,
                            'body' => $row->body,
                            'data' => $row->data,
                            'scheduled_at' => $row->scheduled_at,
                            'status' => 'pending',
                        ])->id
                    )->onQueue('push');
                }

                $row->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);

                return;
            }

            // token target
            $token = DeviceToken::whereKey($row->target_id)->first();
            if (! $token || $token->revoked_at) {
                $row->update(['status' => 'failed', 'sent_at' => now(), 'last_error' => 'Token revoked or missing.']);

                return;
            }

            $sender->sendToToken($token->token, $row->title, $row->body, $row->data ?? []);

            $token->update(['last_seen_at' => now(), 'last_error' => null]);
            $row->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);

        } catch (\Throwable $e) {
            $attempts = $row->attempts + 1;
            $row->attempts = $attempts;
            $row->last_error = $e->getMessage();

            // If exception indicates invalid/unregistered token -> revoke it
            if (str_contains(strtolower($e->getMessage()), 'unregistered')
                || str_contains(strtolower($e->getMessage()), 'invalid registration')
                || str_contains(strtolower($e->getMessage()), 'not a valid fcm registration token')) {
                DeviceToken::whereKey($row->target_id)->update([
                    'revoked_at' => now(),
                    'last_error' => $e->getMessage(),
                ]);
            }

            if ($attempts >= $row->max_attempts) {
                $row->status = 'failed';
                $row->sent_at = now();
                $row->next_retry_at = null;
            } else {
                // exponential backoff
                $delaySeconds = min(60 * pow(2, $attempts), 3600); // cap 1 hour
                $row->status = 'pending';
                $row->next_retry_at = now()->addSeconds($delaySeconds);
            }

            $row->save();
            throw $e; // let queue system record failure too if needed
        }
    }
}
