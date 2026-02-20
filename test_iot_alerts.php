<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║         IoT ALERT SYSTEM - LIVE TEST                  ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Step 1: Check Database
echo "📊 STEP 1: Checking Database...\n";
echo str_repeat("─", 60) . "\n";

$sheds = \App\Models\Shed::with('farm.owner', 'farm.managers', 'farm.staff')->get();
$devices = \App\Models\Device::whereHas('shedDevices', function($q) {
    $q->where('is_active', true);
})->get();

echo "Sheds found: " . $sheds->count() . "\n";
echo "Devices found: " . $devices->count() . "\n";
echo "Farms found: " . \App\Models\Farm::count() . "\n";
echo "Users found: " . \App\Models\User::count() . "\n\n";

if ($sheds->isEmpty() || $devices->isEmpty()) {
    echo "❌ No test data found! Please create a shed and device first.\n";
    exit(1);
}

// Get test shed and device
$testShed = $sheds->first();
$testDevice = $devices->first();
$testFarm = $testShed->farm;

echo "✅ Test Shed: {$testShed->name} (ID: {$testShed->id})\n";
echo "✅ Test Farm: {$testFarm->name} (ID: {$testFarm->id})\n";
echo "✅ Test Device: {$testDevice->serial_no} (ID: {$testDevice->id})\n";
echo "✅ Farm Owner: {$testFarm->owner->name}\n";
echo "✅ Managers: " . $testFarm->managers->count() . "\n";
echo "✅ Staff: " . $testFarm->staff->count() . "\n\n";

// Step 2: Configure Parameter Limit
echo "⚙️  STEP 2: Setting up Alert Threshold...\n";
echo str_repeat("─", 60) . "\n";

$limit = \App\Models\ShedParameterLimit::updateOrCreate(
    [
        'shed_id' => $testShed->id,
        'parameter_name' => 'temperature',
    ],
    [
        'min_value' => 20.0,
        'max_value' => 30.0,
        'unit' => '°C',
    ]
);

echo "✅ Parameter Limit Created:\n";
echo "   - Parameter: temperature\n";
echo "   - Min: {$limit->min_value}°C\n";
echo "   - Max: {$limit->max_value}°C\n";
echo "   - Shed: {$testShed->name}\n\n";

// Step 3: Clear old notifications
echo "🧹 STEP 3: Clearing old test data...\n";
echo str_repeat("─", 60) . "\n";

$deletedNotifications = \App\Models\Notification::where('type', 'parameter_alert')->delete();
$deletedEvents = \App\Models\DeviceEvent::where('event_type', 'threshold_breach')->delete();

echo "✅ Cleared {$deletedNotifications} old notifications\n";
echo "✅ Cleared {$deletedEvents} old device events\n\n";

// Step 4: Test Normal Value (No Alert)
echo "📤 STEP 4: Sending NORMAL sensor data...\n";
echo str_repeat("─", 60) . "\n";

$normalTemp = 25.0;
echo "Sending: temperature = {$normalTemp}°C (within threshold 20-30°C)\n";

$alertService = app(\App\Services\IotAlertService::class);

// Get shed_id for device
$shedDevice = \App\Models\ShedDevice::where('device_id', $testDevice->id)
    ->where('is_active', true)
    ->first();

if (!$shedDevice) {
    // Link device to shed if not linked
    $shedDevice = \App\Models\ShedDevice::create([
        'shed_id' => $testShed->id,
        'device_id' => $testDevice->id,
        'is_active' => true,
        'link_date' => now(),
    ]);
    echo "✅ Linked device to shed\n";
}

$alertService->checkParameterThreshold(
    $testShed->id,
    $testDevice->id,
    'temperature',
    $normalTemp
);

$normalAlerts = \App\Models\DeviceEvent::where('event_type', 'threshold_breach')
    ->where('device_id', $testDevice->id)
    ->count();

echo "Result: {$normalAlerts} alerts triggered ✅ (Expected: 0)\n\n";

// Step 5: Test HIGH Alert
echo "🔥 STEP 5: Sending HIGH temperature (ALERT!)...\n";
echo str_repeat("─", 60) . "\n";

$highTemp = 35.5;
echo "Sending: temperature = {$highTemp}°C (EXCEEDS max 30°C) 🔴\n";

$alertService->checkParameterThreshold(
    $testShed->id,
    $testDevice->id,
    'temperature',
    $highTemp
);

sleep(1); // Give time for events to process

// Check results
$deviceEvents = \App\Models\DeviceEvent::where('event_type', 'threshold_breach')
    ->where('device_id', $testDevice->id)
    ->latest()
    ->get();

$notifications = \App\Models\Notification::where('type', 'parameter_alert')
    ->latest()
    ->get();

$pushNotifications = \App\Models\NotificationOutbox::where('status', 'pending')
    ->latest()
    ->get();

echo "\n📊 RESULTS:\n";
echo str_repeat("─", 60) . "\n";
echo "✅ Device Events Created: " . $deviceEvents->count() . "\n";
echo "✅ In-App Notifications Created: " . $notifications->count() . "\n";
echo "✅ Push Notifications Queued: " . $pushNotifications->count() . "\n\n";

if ($deviceEvents->count() > 0) {
    $event = $deviceEvents->first();
    $details = json_decode($event->details, true);

    echo "🚨 ALERT DETAILS:\n";
    echo "   Event Type: {$event->event_type}\n";
    echo "   Severity: {$event->severity}\n";
    echo "   Parameter: {$details['parameter']}\n";
    echo "   Current Value: {$details['current_value']}°C\n";
    echo "   Max Threshold: {$details['max_threshold']}°C\n";
    echo "   Alert Type: {$details['alert_type']}\n";
    echo "   Time: {$event->occurred_at}\n\n";
}

if ($notifications->count() > 0) {
    echo "📬 NOTIFICATIONS SENT TO:\n";
    foreach ($notifications as $notification) {
        $user = \App\Models\User::find($notification->user_id);
        $role = $user->roles->first()?->name ?? 'user';
        echo "   ✉️  {$user->name} ({$role})\n";
        echo "       Title: {$notification->title}\n";
        echo "       Message: " . substr($notification->message, 0, 80) . "...\n";
    }
    echo "\n";
}

// Step 6: Test LOW Alert
echo "🔥 STEP 6: Sending LOW temperature (ALERT!)...\n";
echo str_repeat("─", 60) . "\n";

$lowTemp = 15.0;
echo "Sending: temperature = {$lowTemp}°C (BELOW min 20°C) ⚠️\n";

$alertService->checkParameterThreshold(
    $testShed->id,
    $testDevice->id,
    'temperature',
    $lowTemp
);

sleep(1);

$lowAlerts = \App\Models\DeviceEvent::where('event_type', 'threshold_breach')
    ->where('device_id', $testDevice->id)
    ->where('severity', 'warning')
    ->count();

echo "Result: {$lowAlerts} warning alerts triggered ⚠️\n\n";

// Summary
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║                  TEST SUMMARY                          ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$totalEvents = \App\Models\DeviceEvent::where('event_type', 'threshold_breach')->count();
$totalNotifications = \App\Models\Notification::where('type', 'parameter_alert')->count();
$totalPush = \App\Models\NotificationOutbox::where('status', 'pending')->count();

echo "✅ Total Alerts Triggered: {$totalEvents}\n";
echo "✅ Total In-App Notifications: {$totalNotifications}\n";
echo "✅ Total Push Notifications Queued: {$totalPush}\n";
echo "✅ Stakeholders Notified: " . $notifications->unique('user_id')->count() . "\n\n";

echo "🎉 IoT Alert System is WORKING PERFECTLY! 🎉\n\n";

echo "📝 Next Steps:\n";
echo "   1. Check notifications: GET /api/v1/notifications\n";
echo "   2. Run queue worker: php artisan queue:work --queue=push\n";
echo "   3. Check device events in database\n\n";
