<?php

namespace App\Events;

use App\Models\Device;
use App\Models\DeviceEvent;
use App\Models\Shed;
use App\Models\ShedParameterLimit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParameterThresholdBreached
{
    use Dispatchable, SerializesModels;

    public DeviceEvent $deviceEvent;
    public Device $device;
    public Shed $shed;
    public string $parameter;
    public float $currentValue;
    public ShedParameterLimit $limit;
    public string $alertType;
    public string $severity;
    public string $message;

    public function __construct(
        DeviceEvent $deviceEvent,
        Device $device,
        Shed $shed,
        string $parameter,
        float $currentValue,
        ShedParameterLimit $limit,
        string $alertType,
        string $severity,
        string $message
    ) {
        $this->deviceEvent = $deviceEvent;
        $this->device = $device;
        $this->shed = $shed;
        $this->parameter = $parameter;
        $this->currentValue = $currentValue;
        $this->limit = $limit;
        $this->alertType = $alertType;
        $this->severity = $severity;
        $this->message = $message;
    }
}
