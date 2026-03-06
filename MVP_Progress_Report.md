# FlockSense MVP — Sprint Progress Report
**Date:** 06 March 2026
**Prepared by:** Development Team
**Purpose:** Meeting Summary — Tasks Accomplished, Pending Work & Blockers

---

## 1. Farm View — New Fields & UI Changes

### Requirement
Add 5 new fields (Country, Phone Number, Contact Person, Alerts, Notifications) and remove 2 fields (Latitude, Longitude) from the Add Farm and Edit Farm modals.

### Flow
```
Admin opens Farm page
  → Clicks "Add Farm" or "Edit Farm"
  → Modal opens with updated form fields
  → Form submits to FarmController (store / update)
  → Validated and saved to farms table (MySQL)
  → Farm table listing reflects new columns
```

### Status: COMPLETED

#### Tasks Accomplished
- **Database Migration** (`2026_02_18_221925_add_new_fields_to_farms_table.php`)
  - Dropped `latitude` and `longitude` columns
  - Added `country` (string, default: Pakistan)
  - Added `phone_number` (string max 11, nullable)
  - Added `contact_person` (string max 50, nullable)
  - Added `alerts` (boolean, default: false)
  - Added `notifications` (boolean, default: false)

- **Farm Model** (`app/Models/Farm.php`)
  - All 5 new fields added to `$fillable`
  - `alerts` and `notifications` cast to `boolean`

- **FarmController** (`app/Http/Controllers/Web/FarmController.php`)
  - `store()`: Validates all 5 new fields with correct rules (country required, phone nullable digits max 11, contact_person max 50, alerts/notifications boolean)
  - `update()`: Same validation rules applied
  - Both methods handle checkbox boolean values via `$request->boolean()`

- **Add Farm Modal** (`resources/views/admin/farms/index.blade.php`)
  - Country: Dropdown with Pakistan selected by default
  - Phone Number: Text input, numbers only (oninput filter), maxlength=11
  - Contact Person: Text input, maxlength=50
  - Alerts: Toggle switch (form-check form-switch), hidden input for false/0 fallback
  - Notifications: Toggle switch, same pattern
  - Latitude/Longitude: Removed — no longer present

- **Edit Farm Modal** (same file)
  - All 5 fields present with same UI controls
  - AJAX pre-fill: `edit_country`, `edit_phone_number`, `edit_contact_person`, `edit_alerts`, `edit_notifications` all populated from API response
  - Country defaults to Pakistan if value is null

- **Farm Listing Table**
  - 5 new columns added: Country, Phone Number, Contact Person, Alerts (On/Off badge), Notifications (On/Off badge)

#### Blockers / Notes
- None. Fully functional end-to-end.

---

## 2. Daily Report Notification (In-App)

### Requirement
When a daily production report is submitted, notify the farm owner that the Daily Report is ready to review. Type of notification should be `report`. In-app only.

### Flow
```
Staff/Manager submits Production Log
  → ProductionLogController::store()
  → ProductionLog saved to database
  → event(DailyReportSubmitted) fired
  → SendDailyReportNotification listener handles event
  → Collects stakeholders: Owner + Managers + Staff of the farm
  → Creates Notification record per user (type = 'report', in-app)
  → Notification visible in /admin/notifications page
```

### Status: COMPLETED (with one deviation noted)

#### Tasks Accomplished
- **Event** (`app/Events/DailyReportSubmitted.php`)
  - Carries: `ProductionLog`, `Shed`, `Flock`

- **Listener** (`app/Listeners/SendDailyReportNotification.php`)
  - Creates `Notification` record per stakeholder with `type = 'report'`
  - Notification data includes: `shed_id`, `flock_id`, `production_log_id`, `age`, `submitter_name`
  - Notifiable linked to the ProductionLog (polymorphic)

- **Event Registration** (`app/Providers/EventServiceProvider.php`)
  - `DailyReportSubmitted → SendDailyReportNotification` registered

- **Controller Trigger** (`app/Http/Controllers/Web/ProductionLogController.php`, line 162)
  - `event(new DailyReportSubmitted($productionLog, $productionLog->shed, $flock))` fired after save

- **Notification Log View** (`resources/views/admin/notifications/index.blade.php`)
  - Type column shows "Report" badge for `type = 'report_submitted'`

#### Deviation / Bug to Fix
- The listener stores `type = 'report'` in the database, but the notification log view checks for `type === 'report_submitted'` to show the Report badge. **These do not match — the Report badge will not display correctly.** Fix: align either the stored value or the view condition.

- The listener also creates a `NotificationOutbox` (push notification) in addition to in-app. The requirement stated **in-app only**. The push queue entry should be removed from `SendDailyReportNotification`.

---

## 3. Climate Alert (Indoor Conditions Threshold Breach)

### Requirement
Send an alert to all stakeholders (Owner, Managers, Staff) when indoor climate conditions go beyond a set range.

### Flow
```
IoT Device sends sensor data
  → POST /api/v1/iot/sync (or /sensor)
  → IoTDeviceDataController resolves device by serial_no
  → Sensor data stored in DynamoDB (raw time-series)
  → checkSensorThresholds() called for: temperature, humidity, co2, ammonia
  → IotAlertService::checkParameterThreshold() per parameter
      → Loads ShedParameterLimit for this shed+parameter
      → If value > max_value: severity = 'critical', alertType = 'high_threshold'
      → If value < min_value: severity = 'warning',  alertType = 'low_threshold'
      → Checks DeviceEvent: if same breach already logged within last 15 minutes → SKIP (dedup)
      → Creates DeviceEvent record
      → Fires event(ParameterThresholdBreached)
  → SendParameterAlertNotification listener handles event
      → Sends to: Farm Owner + all Managers + all Staff
      → Creates in-app Notification per user
      → Creates NotificationOutbox (push) for critical/warning
```

### Status: COMPLETED

#### Tasks Accomplished
- **Service** (`app/Services/IotAlertService.php`)
  - `checkParameterThreshold()`: real-time check per parameter
  - `checkAggregatedThreshold()`: hourly aggregated check (peak/low in window)
  - 15-minute deduplication via JSON query on `device_events` table
  - Severity mapping: high_threshold = critical, low_threshold = warning

- **Event** (`app/Events/ParameterThresholdBreached.php`)
  - Carries: DeviceEvent, Device, Shed, parameter, currentValue, limit, alertType, severity, message

- **Listener** (`app/Listeners/SendParameterAlertNotification.php`)
  - Distributes to: Owner + Managers + Staff
  - In-app `Notification` always created
  - `NotificationOutbox` (push) created for critical/warning only

- **Controller** (`app/Http/Controllers/Api/V1/IoTDeviceDataController.php`)
  - All 6 IoT endpoints trigger `checkSensorThresholds()` after storing data
  - Tracked parameters: `temperature`, `humidity`, `co2`, `ammonia`

- **Parameter Limits** (`app/Models/ShedParameterLimit.php` + `ShedParameterLimitController`)
  - Per-shed configurable min/max thresholds
  - API: `GET/PUT /api/v1/sheds/{shedId}/parameter-limits/{parameter}`

#### Blockers / Notes
- None. End-to-end working.

---

## 4. Vaccination Alert

### Requirement
Send an alert to all stakeholders when vaccination is administered.

### Flow (Expected)
```
Staff records Production Log with is_vaccinated = true
  → ProductionLogController::store()
  → Check if is_vaccinated = true AND medicine recorded
  → Fire VaccinationRecorded event
  → Listener sends in-app Notification + push to all stakeholders
```

### Status: NOT IMPLEMENTED

#### What Is Missing
- No `VaccinationRecorded` event exists
- No listener for vaccination notification exists
- `ProductionLogController::store()` does NOT check `is_vaccinated` flag to trigger any notification
- `EventServiceProvider` has no vaccination event registered

#### Work Required
1. Create `app/Events/VaccinationRecorded.php`
2. Create `app/Listeners/SendVaccinationNotification.php`
3. Register event → listener in `EventServiceProvider`
4. Add trigger in `ProductionLogController::store()` when `is_vaccinated === true`

---

## 5. KPI Alert (Abnormal KPIs)

### Requirement
Send an alert to all stakeholders when KPIs are abnormal (mortality rate, livability, FCR, CV/uniformity).

### Flow
```
Staff submits Production Log
  → ProductionLogController::store()
  → KPIAlertService::check($productionLog) evaluates:
      - Mortality Rate > 0.5% (warning) or > 1.0% (critical)
      - Livability < 95% (warning) or < 90% (critical)
      - FCR > 2.0 (warning) or > 2.5 (critical)
      - CV > 10% (warning) or > 15% (critical)
  → If any breach found: event(AbnormalKPIDetected) fired
  → SendKPIAlertNotification listener handles event
      → Collects Owner + Managers + Staff
      → Creates in-app Notification (type = 'kpi_alert') per user
      → Creates NotificationOutbox (push) per user
```

### Status: COMPLETED

#### Tasks Accomplished
- **Service** (`app/Services/KPIAlertService.php`)
  - Checks 4 KPIs: mortality_rate, livability, FCR, CV
  - Industry-standard thresholds (configurable in constants)
  - Returns list of breaches with severity, value, threshold, unit
  - `highestSeverity()` and `buildBreachSummary()` helpers

- **Event** (`app/Events/AbnormalKPIDetected.php`)
  - Carries: ProductionLog, Shed, Flock, breaches[]

- **Listener** (`app/Listeners/SendKPIAlertNotification.php`)
  - In-app Notification + push per stakeholder
  - Severity emoji in title (critical vs warning)
  - Full breach summary in notification message

- **Controller Trigger** (`app/Http/Controllers/Web/ProductionLogController.php`, lines 178-181)
  - Checks KPIs immediately after production log creation
  - Fires event only if breaches found

#### Blockers / Notes
- None. Fully functional.

---

## 6. Device Tokens — Farm Field & Last Updated

### Requirement
- Add `Farm` field to device tokens — to know which farm a device/user belongs to (empty for Admin)
- Rename `last_seen` to `last_updated`

### Flow
```
User registers FCM token via POST /api/v1/device-tokens
  → DeviceToken created with user_id + farm_id (auto-resolved from user's farm)
  → Admin users: farm_id = null
  → Manager/Staff: farm_id = assigned farm

Farm Manager re-assigned
  → FarmController::assignManager()
  → Old manager's tokens: farm_id = null
  → New manager's tokens: farm_id = this farm

Device Tokens page (/admin/device-tokens)
  → Shows Farm column (farm name + ID, or dash for admin)
  → Shows "Last Updated" column (renamed from Last Seen)
```

### Status: COMPLETED

#### Tasks Accomplished
- **Migration** (`2026_02_26_000001_update_device_tokens_add_farm_and_rename_last_seen.php`)
  - Added `farm_id` (nullable foreign key to farms, nullOnDelete)
  - Renamed `last_seen_at` → `last_updated_at`

- **DeviceToken Model** (`app/Models/DeviceToken.php`)
  - `farm_id` in `$fillable`
  - `farm()` BelongsTo relation added
  - `syncFarmForUser(userId, farmId)` static helper for bulk farm assignment/unassignment
  - `last_updated_at` in casts and fillable

- **Device Tokens View** (`resources/views/admin/push_notifications/device_tokens.blade.php`)
  - Farm column added: shows farm name + ID, dash for null (admin)
  - Column header: "Last Updated" (renamed)
  - Filter column indices updated accordingly

#### Blockers / Notes
- None. Fully working.

---

## 7. Notification Logs — Farm, User & Type Columns

### Requirement (Change 1 + Change 2)
- Add Farm and User columns to the notification log (push outbox) — with clickable links
- Add Type column: Report vs Notification

### Flow (Push Notification Logs)
```
Admin opens /admin/notifications/logs
  → PushNotificationController::logs() loads NotificationOutbox records
  → Bulk loads User models (where target_type = 'user')
  → Bulk loads Shed+Farm models (via shed_id in data JSON)
  → View renders:
      - Farm column: links to /admin/farms/{id}/detail
      - User column: links to /admin/clients/{id}
      - Type column: checks data['production_log_id'] to classify Report vs Notification
      - Type filter dropdown to filter by Report / Notification
```

### Flow (In-App Notification Log)
```
User/Admin opens /admin/notifications
  → NotificationController::index() loads Notification records with farm + user eager loaded
  → View renders:
      - Farm column: links to farm show page
      - User column: links to client show page
      - Type column: report_submitted → Report badge, else Notification badge
      - Type filter dropdown
```

### Status: COMPLETED (with one bug noted)

#### Tasks Accomplished
- **Push Notification Logs View** (`resources/views/admin/push_notifications/logs.blade.php`)
  - Farm column: resolved from `data['shed_id']` → Shed → Farm, with link
  - User column: resolved from target_id when target_type = 'user', with link
  - Type column: `data['production_log_id']` present = Report, else = Notification
  - Type filter (column index 2) + Status filter added

- **In-App Notification Log View** (`resources/views/admin/notifications/index.blade.php`)
  - Farm column with link to farm show page
  - User column with link to client show page
  - Type column (Report / Notification badge)
  - Type filter dropdown

- **NotificationController** (`app/Http/Controllers/Web/NotificationController.php`)
  - Admin: loads all notifications; non-admin: loads own notifications
  - Eager loads `farm`, `notifiable`, `user` relations

#### Bug / Mismatch to Fix
- In-App Notification Log view checks `type === 'report_submitted'` to show Report badge
- `SendDailyReportNotification` stores `type = 'report'`
- **These do not match.** Report badge never appears. Fix: change view condition to `type === 'report'` OR update listener to store `type = 'report_submitted'`

---

## Summary Table

| # | Requirement | Status | Notes |
|---|---|---|---|
| 1 | Farm — Country dropdown (default Pakistan) | DONE | Add + Edit modals |
| 1 | Farm — Phone Number (optional, max 11 digits) | DONE | Add + Edit modals |
| 1 | Farm — Contact Person (optional, max 50) | DONE | Add + Edit modals |
| 1 | Farm — Alerts toggle (default off) | DONE | Add + Edit modals |
| 1 | Farm — Notifications toggle (default off) | DONE | Add + Edit modals |
| 1 | Farm — Remove Latitude & Longitude | DONE | Dropped from DB + UI |
| 2 | Daily Report notification (in-app, type=report) | DONE | Minor bug: type mismatch + extra push entry |
| 3 | Climate alert on threshold breach | DONE | Full flow working |
| 4 | Vaccination alert to all stakeholders | NOT DONE | Event/listener/trigger missing |
| 5 | KPI alert on abnormal KPIs | DONE | Full flow working |
| 6 | Device Tokens — Farm field | DONE | Nullable for admin |
| 6 | Device Tokens — Last Seen → Last Updated | DONE | Renamed in DB + UI |
| 7 | Notification Logs — Farm column (linked) | DONE | Push outbox + in-app log |
| 7 | Notification Logs — User column (linked) | DONE | Push outbox + in-app log |
| 7 | Notification Logs — Type column (Report/Notification) | DONE | Bug: type mismatch in in-app view |

---

## Pending Items & Fixes Required

### P1 — Vaccination Alert (Not Started)
**What:** Fire a notification when `is_vaccinated = true` in a production log.
**Files to create:**
- `app/Events/VaccinationRecorded.php`
- `app/Listeners/SendVaccinationNotification.php`

**Files to modify:**
- `app/Providers/EventServiceProvider.php` — register new event
- `app/Http/Controllers/Web/ProductionLogController.php` — add trigger after store

### P2 — Type Mismatch: Report Notification Badge
**What:** In-app notification view checks `type === 'report_submitted'` but listener stores `type = 'report'`.
**Fix:** In `resources/views/admin/notifications/index.blade.php`, change:
```
$notification->type === 'report_submitted'
```
to:
```
$notification->type === 'report'
```

### P3 — Daily Report Push Notification (spec says in-app only)
**What:** `SendDailyReportNotification` listener creates both in-app notification AND push outbox entry. Spec requires in-app only.
**Fix:** Remove the `NotificationOutbox::create()` block from `SendDailyReportNotification`.

---

## Architecture Overview (For New Team Member)

### Notification System — Two Tracks

```
Track A: In-App Notification
  notifications table (user_id, farm_id, type, title, message, is_read)
  Visible at: /admin/notifications
  Created by: All listeners (SendDailyReportNotification, SendKPIAlertNotification, SendParameterAlertNotification)

Track B: Push Notification (FCM)
  notification_outboxes table (target_type, target_id, title, body, data, status)
  Sent by: DispatchPendingPushNotifications scheduled command
  Visible at: /admin/notifications/logs
  Created by: All listeners (for critical/warning alerts)
```

### Event → Listener Map

| Event | Trigger | Listener | Sends |
|---|---|---|---|
| `ParameterThresholdBreached` | IoT data exceeds shed threshold | `SendParameterAlertNotification` | In-app + Push (critical/warning) |
| `DailyReportSubmitted` | ProductionLog saved | `SendDailyReportNotification` | In-app + Push (should be in-app only) |
| `AbnormalKPIDetected` | KPI threshold breach on log save | `SendKPIAlertNotification` | In-app + Push |
| `VaccinationRecorded` | NOT CREATED YET | NOT CREATED YET | — |

### IoT Data Pipeline

```
Device → POST /api/v1/iot/sync
  → DynamoDB (raw sensor + appliance history)
  → MySQL DeviceAppliance (latest appliance snapshot)
  → checkSensorThresholds() → IotAlertService
  → [Scheduled] iot:aggregate-data → iot_data_logs (hourly aggregates, MySQL)
  → ShedAnalyticsService uses iot_data_logs for dashboard
```
