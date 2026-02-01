<?php

// app/Console/Commands/DispatchPendingPushNotifications.php

namespace App\Console\Commands;

use App\Jobs\DispatchPushNotificationJob;
use App\Models\NotificationOutbox;
use Illuminate\Console\Command;

class DispatchPendingPushNotifications extends Command
{
    protected $signature = 'push:notification {--limit=100}';

    protected $description = 'Dispatch pending push notifications from outbox';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $rows = NotificationOutbox::query()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get(['id']);

        foreach ($rows as $row) {
            DispatchPushNotificationJob::dispatch($row->id)->onQueue('push');
        }

        $this->info("Dispatched {$rows->count()} notifications.");

        return self::SUCCESS;
    }
}
