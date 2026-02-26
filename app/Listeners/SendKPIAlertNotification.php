<?php

namespace App\Listeners;

use App\Events\AbnormalKPIDetected;
use App\Models\Notification;
use App\Models\NotificationOutbox;
use App\Services\KPIAlertService;
use Illuminate\Support\Facades\Log;

class SendKPIAlertNotification
{
    /**
     * Handle the event — send KPI alert notifications to all stakeholders.
     */
    public function handle(AbnormalKPIDetected $event): void
    {
        $productionLog = $event->productionLog;
        $shed          = $event->shed;
        $flock         = $event->flock;
        $breaches      = $event->breaches;
        $farm          = $shed->farm;

        if (! $farm) {
            Log::warning("[SendKPIAlertNotification] Shed {$shed->id} has no farm associated.");
            return;
        }

        $service  = new KPIAlertService();
        $severity = $service->highestSeverity($breaches);
        $summary  = $service->buildBreachSummary($breaches);

        $title   = ($severity === 'critical' ? '🚨' : '⚠️')
                 . " KPI Alert — {$shed->name}";

        $message = "Abnormal KPIs detected for Flock '{$flock->name}' in Shed '{$shed->name}' "
                 . "on Day {$productionLog->age}:\n{$summary}";

        $notificationData = [
            'shed_id'           => $shed->id,
            'flock_id'          => $flock->id,
            'production_log_id' => $productionLog->id,
            'age'               => $productionLog->age,
            'severity'          => $severity,
            'breaches'          => $breaches,
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

        Log::info("[SendKPIAlertNotification] Sending to {$stakeholders->count()} stakeholders for shed {$shed->id}, severity: {$severity}");

        foreach ($stakeholders as $user) {
            // In-app notification
            Notification::create([
                'user_id'         => $user->id,
                'notifiable_id'   => $productionLog->id,
                'notifiable_type' => get_class($productionLog),
                'farm_id'         => $farm->id,
                'type'            => 'kpi_alert',
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
                'body'         => "Abnormal KPIs in Shed '{$shed->name}' — Day {$productionLog->age}. Tap to view details.",
                'data'         => $notificationData,
                'scheduled_at' => now(),
                'status'       => 'pending',
            ]);
        }

        Log::info("[SendKPIAlertNotification] KPI alert sent for flock {$flock->id} in shed {$shed->id}");
    }
}
