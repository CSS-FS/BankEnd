# IoT Environmental Parameter Alerts System

## Overview
The IoT Alert System automatically monitors environmental parameters in poultry sheds and sends real-time notifications to all stakeholders (Farm Owners, Managers, and Staff) when values exceed configured thresholds.

## Monitored Parameters

| Parameter | Database Field | Unit | Description |
|-----------|---------------|------|-------------|
| **Temperature** | `temp1`, `temp2`, `temperature` | °C | Shed temperature monitoring |
| **Humidity** | `humidity` | % | Relative humidity levels |
| **CO2 Level** | `co2` | ppm | Carbon dioxide concentration |
| **Ammonia Level** | `ammonia`, `nh3` | ppm | Ammonia gas concentration |

## How It Works

### 1. Threshold Configuration
Farm owners/managers configure alert thresholds for each parameter per shed in the `shed_parameter_limits` table:

```sql
{
  "shed_id": 1,
  "parameter_name": "temperature",
  "min_value": 18.0,
  "max_value": 32.0,
  "unit": "°C"
}
```

### 2. Real-Time Alert Checking

#### A. When Sensor Data is Received
Every time a device sends sensor data to:
- `POST /api/iot/sensor`
- `POST /api/sensor-data`
- `POST /api/sensor-data/multiple`

The system automatically:
1. Stores data in DynamoDB
2. Checks each parameter against configured thresholds
3. Triggers alerts if thresholds are breached

#### B. During Hourly Aggregation
When the `php artisan iot:aggregate-data` command runs (hourly via cron):
1. Aggregates sensor data from DynamoDB
2. Calculates min/max/avg values
3. Checks aggregated values against thresholds
4. Triggers alerts if peak values exceed limits

### 3. Alert Types

#### Low Threshold Breach (WARNING ⚠️)
- **Condition:** Current value < minimum threshold
- **Severity:** `warning`
- **Example:** Temperature drops below 18°C
- **Notification:** In-app notification only

#### High Threshold Breach (CRITICAL 🔴)
- **Condition:** Current value > maximum threshold
- **Severity:** `critical`
- **Example:** Temperature exceeds 32°C
- **Notification:** In-app + Push notification (FCM)

### 4. Alert Notifications

#### Notification Recipients (ALL STAKEHOLDERS)
Alerts are sent to:
1. **Farm Owner** - Primary stakeholder
2. **Farm Managers** - All managers assigned to the farm
3. **Farm Staff** - All staff members assigned to the farm

#### Notification Channels
- **In-App Notifications:** All severity levels
- **Push Notifications (FCM):** Critical alerts only
- **Email:** Future enhancement (not yet implemented)

#### Notification Content
```json
{
  "title": "🔴 🌡️ Temperature Alert - Shed A",
  "message": "🌡️ Temperature in Shed A has exceeded maximum limit of 32.0 °C. Current value: 35.5 °C. Device: DEV-001",
  "data": {
    "shed_id": 1,
    "device_id": 5,
    "parameter": "temperature",
    "current_value": 35.5,
    "min_threshold": 18.0,
    "max_threshold": 32.0,
    "alert_type": "high_threshold",
    "severity": "critical",
    "device_serial": "DEV-001",
    "unit": "°C"
  }
}
```

### 5. Alert Deduplication
To prevent notification spam:
- Similar alerts within 15 minutes are suppressed
- Alert uniqueness checked by: device + parameter + alert_type + severity
- Each unique breach condition triggers only one alert per 15-minute window

### 6. Event Logging
All threshold breaches are logged in `device_events` table:

```json
{
  "device_id": 5,
  "event_type": "threshold_breach",
  "severity": "critical",
  "details": {
    "shed_id": 1,
    "parameter": "temperature",
    "current_value": 35.5,
    "min_threshold": 18.0,
    "max_threshold": 32.0,
    "alert_type": "high_threshold",
    "unit": "°C"
  },
  "occurred_at": "2026-02-16 14:30:00"
}
```

## Implementation Files

### New Files Created
1. **`app/Services/IotAlertService.php`** - Core alert logic service
2. **`app/Events/ParameterThresholdBreached.php`** - Event class
3. **`app/Listeners/SendParameterAlertNotification.php`** - Notification dispatcher

### Modified Files
1. **`app/Providers/EventServiceProvider.php`** - Event listener registration
2. **`app/Http/Controllers/Api/V1/SensorDataController.php`** - Real-time alert checking
3. **`app/Services/IotDataAggregatorService.php`** - Aggregation alert checking

## API Endpoints

### Configure Alert Thresholds

#### Get Parameter Limits
```http
GET /api/v1/sheds/{shedId}/parameter-limits
```

#### Create/Update Parameter Limit
```http
POST /api/v1/sheds/{shedId}/parameter-limits
Content-Type: application/json

{
  "parameter_name": "temperature",
  "min_value": 18.0,
  "max_value": 32.0,
  "unit": "°C"
}
```

#### Get Specific Parameter Limit
```http
GET /api/v1/sheds/{shedId}/parameter-limits/{parameter}
```

#### Delete Parameter Limit
```http
DELETE /api/v1/sheds/{shedId}/parameter-limits/{parameter}
```

### View Notifications

#### Get User Notifications
```http
GET /api/v1/notifications
Authorization: Bearer {token}
```

#### Mark Notification as Read
```http
PATCH /api/v1/notifications/{id}/read
```

#### Get Unread Count
```http
GET /api/v1/notifications/unread-count
```

## Testing the Alert System

### 1. Setup Test Thresholds
```bash
curl -X POST http://localhost:8000/api/v1/sheds/1/parameter-limits \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "parameter_name": "temperature",
    "min_value": 20.0,
    "max_value": 30.0,
    "unit": "°C"
  }'
```

### 2. Send Test Sensor Data (Normal)
```bash
curl -X POST http://localhost:8000/api/iot/sensor \
  -H "Content-Type: application/json" \
  -d '{
    "device_serial": "DEV-001",
    "temperature": 25.0,
    "humidity": 60,
    "co2": 800
  }'
```
**Expected:** No alert (within threshold)

### 3. Send Test Sensor Data (High Alert)
```bash
curl -X POST http://localhost:8000/api/iot/sensor \
  -H "Content-Type: application/json" \
  -d '{
    "device_serial": "DEV-001",
    "temperature": 35.0,
    "humidity": 60,
    "co2": 800
  }'
```
**Expected:**
- Alert triggered
- Device event logged
- Notifications sent to all stakeholders
- Push notification queued (critical)

### 4. Check Notifications
```bash
curl -X GET http://localhost:8000/api/v1/notifications \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Check Device Events
```sql
SELECT * FROM device_events
WHERE event_type = 'threshold_breach'
ORDER BY occurred_at DESC
LIMIT 10;
```

## Scheduled Tasks

### Hourly Data Aggregation + Alert Checking
Add to `routes/console.php` or `app/Console/Kernel.php`:

```php
Schedule::command('iot:aggregate-data')->hourly();
```

### Push Notification Dispatch
```php
Schedule::command('push:notification')->everyFiveMinutes();
```

## Database Schema

### shed_parameter_limits
```sql
CREATE TABLE `shed_parameter_limits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shed_id` bigint unsigned NOT NULL,
  `parameter_name` varchar(50) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `min_value` decimal(10,2) DEFAULT NULL,
  `max_value` decimal(10,2) DEFAULT NULL,
  `avg_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_shed_parameter` (`shed_id`, `parameter_name`),
  FOREIGN KEY (`shed_id`) REFERENCES `sheds`(`id`) ON DELETE CASCADE
);
```

### device_events
```sql
CREATE TABLE `device_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint unsigned NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `details` json DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_device_events` (`device_id`, `event_type`, `occurred_at`),
  FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE
);
```

### notifications
```sql
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `notifiable_id` bigint unsigned DEFAULT NULL,
  `notifiable_type` varchar(255) DEFAULT NULL,
  `farm_id` bigint unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`, `is_read`),
  KEY `idx_user_type` (`user_id`, `type`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`id`) ON DELETE CASCADE
);
```

## Future Enhancements

### 1. Device Health Alerts
- Battery low alerts
- Device offline alerts
- Weak signal alerts

### 2. Appliance Status Alerts
- Fan failure detection
- Heater malfunction
- Critical appliance down alerts

### 3. Alert Escalation
- Auto-escalate if not acknowledged within X minutes
- SMS alerts for critical issues
- WhatsApp notifications

### 4. Alert Analytics
- Alert frequency dashboard
- Most triggered parameters
- Response time tracking
- Alert resolution tracking

### 5. Smart Alerts
- Machine learning for anomaly detection
- Predictive alerts (trend analysis)
- Weather-based threshold adjustment
- Contextual alerts based on flock age

## Troubleshooting

### Alerts Not Triggering

**Check 1:** Verify threshold configuration
```sql
SELECT * FROM shed_parameter_limits WHERE shed_id = 1;
```

**Check 2:** Verify device is linked to shed
```sql
SELECT * FROM shed_devices WHERE device_id = 5 AND is_active = 1;
```

**Check 3:** Check Laravel logs
```bash
tail -f storage/logs/laravel.log | grep "IotAlertService"
```

**Check 4:** Verify event listener is registered
```bash
php artisan event:list | grep ParameterThresholdBreached
```

### Notifications Not Received

**Check 1:** Verify notification created
```sql
SELECT * FROM notifications WHERE type = 'parameter_alert' ORDER BY created_at DESC LIMIT 10;
```

**Check 2:** Check push notification queue
```sql
SELECT * FROM notification_outboxes WHERE status = 'pending' ORDER BY created_at DESC;
```

**Check 3:** Verify device tokens
```sql
SELECT * FROM device_tokens WHERE user_id = 1 AND revoked_at IS NULL;
```

**Check 4:** Run queue worker
```bash
php artisan queue:work --queue=push
```

### Too Many Duplicate Alerts

**Solution:** The 15-minute deduplication window prevents spam. If you're still getting duplicates:
1. Check system time synchronization
2. Verify device is not sending excessive data
3. Adjust deduplication window in `IotAlertService.php` line ~108

---

## Summary

The IoT Alert System provides comprehensive real-time monitoring of environmental parameters with automatic stakeholder notifications. It's designed to be:

- **Automatic:** No manual intervention required
- **Real-time:** Alerts triggered immediately on threshold breach
- **Comprehensive:** All stakeholders notified
- **Intelligent:** Deduplication prevents spam
- **Scalable:** Handles multiple devices and sheds
- **Extensible:** Easy to add new parameters and alert types

For questions or issues, contact the development team.
