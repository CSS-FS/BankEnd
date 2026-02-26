<?php

namespace App\Listeners;

use App\Events\ParameterThresholdBreached;
use App\Models\Notification;
use App\Models\NotificationOutbox;
use App\Services\IotAlertService;
use Illuminate\Support\Facades\Log;

class SendParameterAlertNotification
{
    /**
     * Handle the event - Send notifications to all stakeholders
     */
    public function handle(ParameterThresholdBreached $event): void
    {
        $shed = $event->shed;
        $farm = $shed->farm;
        $device = $event->device;
        $parameter = $event->parameter;
        $currentValue = $event->currentValue;
        $limit = $event->limit;
        $alertType = $event->alertType;
        $severity = $event->severity;

        if (!$farm) {
            Log::warning("[SendParameterAlert] Shed {$shed->id} has no farm associated.");
            return;
        }

        // Prepare notification content
        $severityEmoji = IotAlertService::getSeverityEmoji($severity);
        $parameterName = IotAlertService::getParameterDisplayName($parameter);

        $title = "{$severityEmoji} {$parameterName} Alert - {$shed->name}";

        $thresholdText = $alertType === 'high_threshold'
            ? "exceeded maximum limit of {$limit->max_value} {$limit->unit}"
            : "dropped below minimum limit of {$limit->min_value} {$limit->unit}";

        $message = "{$parameterName} in {$shed->name} has {$thresholdText}. Current value: {$currentValue} {$limit->unit}. Device: {$device->serial_no}";

        $notificationData = [
            'shed_id' => $shed->id,
            'device_id' => $device->id,
            'parameter' => $parameter,
            'current_value' => $currentValue,
            'min_threshold' => $limit->min_value,
            'max_threshold' => $limit->max_value,
            'alert_type' => $alertType,
            'severity' => $severity,
            'device_serial' => $device->serial_no,
            'unit' => $limit->unit,
        ];

        // Collect all stakeholders (Owner, Managers, Staff)
        $stakeholders = collect();

        // 1. Farm Owner
        if ($farm->owner) {
            $stakeholders->push([
                'user' => $farm->owner,
                'role' => 'owner',
            ]);
        }

        // 2. Farm Managers
        $managers = $farm->managers()->get();
        foreach ($managers as $manager) {
            $stakeholders->push([
                'user' => $manager,
                'role' => 'manager',
            ]);
        }

        // 3. Farm Staff
        $staff = $farm->staff()->get();
        foreach ($staff as $staffMember) {
            $stakeholders->push([
                'user' => $staffMember,
                'role' => 'staff',
            ]);
        }

        Log::info("[SendParameterAlert] Sending alert to {$stakeholders->count()} stakeholders for shed {$shed->id}");

        // Send notifications to all stakeholders
        foreach ($stakeholders as $stakeholder) {
            $user = $stakeholder['user'];

            // Create in-app notification
            Notification::create([
                'user_id' => $user->id,
                'notifiable_id' => $event->deviceEvent->id,
                'notifiable_type' => get_class($event->deviceEvent),
                'farm_id' => $farm->id,
                'type' => 'parameter_alert',
                'title' => $title,
                'message' => $message,
                'data' => $notificationData,
                'is_read' => false,
            ]);

            // Queue push notification (only for critical alerts)
        if (in_array($severity, ['critical', 'warning'])) {
                NotificationOutbox::create([
                    'target_type' => 'user',
                    'target_id' => $user->id,
                    'title' => $title,
                    'body' => $message,
                    'data' => $notificationData,
                    'scheduled_at' => now(),
                    'status' => 'pending',
                ]);

                Log::info("[SendParameterAlert] Queued push notification for user {$user->id} (critical alert)");
            }
        }

        Log::info("[SendParameterAlert] Alert notifications sent successfully for {$parameter} breach in shed {$shed->id}");
    }
}
