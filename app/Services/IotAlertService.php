<?php

namespace App\Services;

use App\Events\ParameterThresholdBreached;
use App\Models\Device;
use App\Models\DeviceEvent;
use App\Models\IotDataLog;
use App\Models\Shed;
use App\Models\ShedParameterLimit;
use Illuminate\Support\Facades\Log;

class IotAlertService
{
    /**
     * Check if a parameter value breaches the threshold and trigger alert
     *
     * @param int $shedId
     * @param int $deviceId
     * @param string $parameter
     * @param float $currentValue
     * @return void
     */
    public function checkParameterThreshold(
        int $shedId,
        int $deviceId,
        string $parameter,
        float $currentValue
    ): void {
        // Get parameter limit for this shed
        $limit = ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->first();

        if (!$limit) {
            // No limit set for this parameter, skip alert check
            return;
        }

        $alertType = null;
        $severity = null;
        $message = null;

        // Check MIN threshold breach
        if ($limit->min_value !== null && $currentValue < $limit->min_value) {
            $alertType = 'low_threshold';
            $severity = 'warning';
            $message = "{$parameter} is below minimum threshold. Current: {$currentValue}, Min: {$limit->min_value}";
        }

        // Check MAX threshold breach (more critical)
        if ($limit->max_value !== null && $currentValue > $limit->max_value) {
            $alertType = 'high_threshold';
            $severity = 'critical';
            $message = "{$parameter} has exceeded maximum threshold. Current: {$currentValue}, Max: {$limit->max_value}";
        }

        // If threshold breached, log event and trigger notification
        if ($alertType) {
            $this->logThresholdBreach(
                $deviceId,
                $shedId,
                $parameter,
                $currentValue,
                $limit,
                $alertType,
                $severity,
                $message
            );
        }
    }

    /**
     * Check aggregated data for threshold breaches
     *
     * @param int $deviceId
     * @param int $shedId
     * @param string $parameter
     * @param float $minValue
     * @param float $maxValue
     * @param float $avgValue
     * @return void
     */
    public function checkAggregatedThreshold(
        int $deviceId,
        int $shedId,
        string $parameter,
        float $minValue,
        float $maxValue,
        float $avgValue
    ): void {
        // Get parameter limit for this shed
        $limit = ShedParameterLimit::where('shed_id', $shedId)
            ->where('parameter_name', $parameter)
            ->first();

        if (!$limit) {
            return;
        }

        // Check if MAX value in the hour exceeded threshold
        if ($limit->max_value !== null && $maxValue > $limit->max_value) {
            $this->logThresholdBreach(
                $deviceId,
                $shedId,
                $parameter,
                $maxValue,
                $limit,
                'high_threshold',
                'critical',
                "{$parameter} peak value exceeded maximum threshold in last hour. Peak: {$maxValue}, Max: {$limit->max_value}"
            );
        }

        // Check if MIN value in the hour went below threshold
        if ($limit->min_value !== null && $minValue < $limit->min_value) {
            $this->logThresholdBreach(
                $deviceId,
                $shedId,
                $parameter,
                $minValue,
                $limit,
                'low_threshold',
                'warning',
                "{$parameter} dropped below minimum threshold in last hour. Low: {$minValue}, Min: {$limit->min_value}"
            );
        }
    }

    /**
     * Log the threshold breach event and trigger notification
     */
    protected function logThresholdBreach(
        int $deviceId,
        int $shedId,
        string $parameter,
        float $currentValue,
        ShedParameterLimit $limit,
        string $alertType,
        string $severity,
        string $message
    ): void {
        $device = Device::find($deviceId);
        $shed = Shed::with('farm')->find($shedId);

        if (!$device || !$shed) {
            Log::error("[IotAlertService] Device or Shed not found. Device: {$deviceId}, Shed: {$shedId}");
            return;
        }

        // Check if similar alert was triggered recently (avoid spam)
        $recentAlert = DeviceEvent::where('device_id', $deviceId)
            ->where('event_type', 'threshold_breach')
            ->where('severity', $severity)
            ->where('created_at', '>', now()->subMinutes(15)) // Within last 15 minutes
            ->whereRaw("details->>'parameter' = ?", [$parameter])
            ->whereRaw("details->>'alert_type' = ?", [$alertType])
            ->first();

        if ($recentAlert) {
            Log::info("[IotAlertService] Similar alert recently triggered, skipping duplicate.");
            return;
        }

        // Create Device Event
        $eventDetails = [
            'shed_id' => $shedId,
            'parameter' => $parameter,
            'current_value' => $currentValue,
            'min_threshold' => $limit->min_value,
            'max_threshold' => $limit->max_value,
            'alert_type' => $alertType,
            'unit' => $limit->unit,
        ];

        $deviceEvent = DeviceEvent::create([
            'device_id' => $deviceId,
            'event_type' => 'threshold_breach',
            'severity' => $severity,
            'details' => json_encode($eventDetails),
            'occurred_at' => now(),
        ]);

        Log::info("[IotAlertService] Threshold breach logged: {$parameter} at {$currentValue} for device {$deviceId}");

        // Trigger notification event to all stakeholders
        event(new ParameterThresholdBreached(
            $deviceEvent,
            $device,
            $shed,
            $parameter,
            $currentValue,
            $limit,
            $alertType,
            $severity,
            $message
        ));
    }

    /**
     * Get parameter display name with emoji
     */
    public static function getParameterDisplayName(string $parameter): string
    {
        return match (strtolower($parameter)) {
            'temperature' => '🌡️ Temperature',
            'humidity' => '💧 Humidity',
            'co2' => '💨 CO2 Level',
            'ammonia' => '☁️ Ammonia Level',
            default => ucfirst($parameter),
        };
    }

    /**
     * Get severity emoji
     */
    public static function getSeverityEmoji(string $severity): string
    {
        return match ($severity) {
            'critical' => '🔴',
            'warning' => '⚠️',
            'info' => '🔵',
            default => '⚪',
        };
    }
}
