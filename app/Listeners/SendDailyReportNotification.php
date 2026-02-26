<?php

namespace App\Listeners;

use App\Events\DailyReportSubmitted;
use App\Models\Notification;
use App\Models\NotificationOutbox;
use Illuminate\Support\Facades\Log;

class SendDailyReportNotification
{
    /**
     * Handle the event — send report submitted alert to all stakeholders (in-app + push).
     */
    public function handle(DailyReportSubmitted $event): void
    {
        $productionLog = $event->productionLog;
        $shed          = $event->shed;
        $flock         = $event->flock;
        $farm          = $shed->farm;

        if (! $farm) {
            Log::warning("[SendDailyReportNotification] Shed {$shed->id} has no farm associated.");
            return;
        }

        $submitterName = $productionLog->user?->name ?? 'Staff';

        $title   = "Daily Report Submitted — {$shed->name}";
        $message = "Daily report for Flock '{$flock->name}' in Shed '{$shed->name}' "
                 . "on Day {$productionLog->age} has been submitted by {$submitterName}.";

        $notificationData = [
            'shed_id'           => $shed->id,
            'flock_id'          => $flock->id,
            'production_log_id' => $productionLog->id,
            'age'               => $productionLog->age,
            'submitter_name'    => $submitterName,
        ];

        // Collect all stakeholders (Owner, Managers, Staff)
        $stakeholders = collect();

        if ($farm->owner) {
            $stakeholders->push($farm->owner);
        }

        foreach ($farm->managers()->get() as $manager) {
            $stakeholders->push($manager);
        }

        foreach ($farm->staff()->get() as $staffMember) {
            $stakeholders->push($staffMember);
        }

        Log::info("[SendDailyReportNotification] Sending to {$stakeholders->count()} stakeholders for shed {$shed->id}");

        foreach ($stakeholders as $user) {
            // In-app notification
            Notification::create([
                'user_id'         => $user->id,
                'notifiable_id'   => $productionLog->id,
                'notifiable_type' => get_class($productionLog),
                'farm_id'         => $farm->id,
                'type'            => 'report',
                'title'           => $title,
                'message'         => $message,
                'data'            => $notificationData,
                'is_read'         => false,
            ]);

            // Push notification
            NotificationOutbox::create([
                'target_type'  => 'user',
                'target_id'    => $user->id,
                'title'        => $title,
                'body'         => $message,
                'data'         => $notificationData,
                'scheduled_at' => now(),
                'status'       => 'pending',
            ]);
        }

        Log::info("[SendDailyReportNotification] Notifications sent for flock {$flock->id} in shed {$shed->id}");
    }
}
