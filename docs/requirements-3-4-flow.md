# Requirements 3 & 4 — Implementation Flow

---

## Requirement 3 — Vaccination Alert

### Kya Karta Hai?
Jab bhi staff daily report mein vaccination `true` kare — farm ke **Owner, Managers, aur Staff** sabko **in-app + push** notification jati hai.

---

### Trigger
```
POST /api/v1/production       (Mobile App)
POST /admin/productions       (Web Portal)
```

---

### Files Involved

| File | Role |
|---|---|
| `app/Http/Controllers/Api/V1/ProductionLogController.php` | API se event fire karta hai |
| `app/Http/Controllers/Web/ProductionLogController.php` | Web se event fire karta hai |
| `app/Events/VaccinationRecorded.php` | Event class (signal) |
| `app/Listeners/SendVaccinationNotification.php` | Notification bhejta hai |
| `app/Providers/EventServiceProvider.php` | Event aur Listener ko jodta hai |

---

### Flow Diagram

```
Staff Daily Report Submit karta hai
(is_vaccinated: true)
            |
            v
+---------------------------+
|  ProductionLogController  |
|  store()                  |
|                           |
|  ProductionLog::create()  |
|                           |
|  if (is_vaccinated) {     |
|    event(Vaccination      |
|    Recorded)              |
|  }                        |
+---------------------------+
            |
            v
+---------------------------+
|  VaccinationRecorded      |
|  Event                    |
|                           |
|  carries:                 |
|  - productionLog          |
|  - shed                   |
|  - flock                  |
+---------------------------+
            |
            v
+---------------------------+
|  EventServiceProvider     |
|  routes to listener       |
+---------------------------+
            |
            v
+---------------------------+
|  SendVaccinationNotifi-   |
|  cation Listener          |
|                           |
|  1. Farm nikalo           |
|  2. Stakeholders collect  |
|     - Owner               |
|     - Managers            |
|     - Staff               |
+---------------------------+
            |
     +------+------+
     |             |
     v             v
+---------+   +------------------+
| IN-APP  |   | PUSH             |
|         |   |                  |
| notifi- |   | notification_    |
| cations |   | outboxes table   |
| table   |   | (pending)        |
+---------+   +------------------+
```

---

### Notification Content

```
Type    : vaccination
Title   : "Vaccination Recorded — Shed A"
Message : "Vaccination has been recorded for Flock 'Flock 1'
           in Shed 'Shed A' on Day 15. Recorded by: Ali."
```

---

### Condition

```
is_vaccinated = false  -->  Koi notification NAHI
is_vaccinated = true   -->  Sabko notification JAYEGI
```

---

### Real Life Scenario

```
Ali (Worker)
  Raat ko mobile app kholta hai
  Daily report fill karta hai:
    - 3 murghiyan mari
    - 50 kg feed dia
    - Vaccine: YES  <-- tick karta hai
  Submit daba deta hai
        |
        v
  Ahmed (Owner)    -- notification mili
  Bilal (Manager)  -- notification mili
  Sara  (Staff)    -- notification mili
```

---
---

## Requirement 4 — Abnormal KPI Alert

### Kya Karta Hai?
Jab bhi daily report submit ho — system **4 KPIs check** karta hai. Agar koi bhi KPI abnormal ho — **Owner, Managers, aur Staff** sabko **in-app + push** notification jati hai.

---

### Trigger
```
POST /api/v1/production       (Mobile App)
POST /admin/productions       (Web Portal)
```

---

### Files Involved

| File | Role |
|---|---|
| `app/Http/Controllers/Api/V1/ProductionLogController.php` | API se KPI check aur event fire |
| `app/Http/Controllers/Web/ProductionLogController.php` | Web se KPI check aur event fire |
| `app/Services/KPIAlertService.php` | 4 KPIs calculate aur check karta hai |
| `app/Events/AbnormalKPIDetected.php` | Event class (signal) |
| `app/Listeners/SendKPIAlertNotification.php` | Notification bhejta hai |
| `app/Providers/EventServiceProvider.php` | Event aur Listener ko jodta hai |

---

### KPI Thresholds

| KPI | Formula | Warning | Critical |
|---|---|---|---|
| Mortality Rate | (aaj ki maut / kal ki count) x 100 | > 0.5% | > 1.0% |
| Livability | (zinda / total) x 100 | < 95% | < 90% |
| FCR | feed consumed / weight gained | > 2.0 | > 2.5 |
| CV (Uniformity) | weight variation % among birds | > 10% | > 15% |

> FCR aur CV sirf tab check hote hain jab weight log bhi submit hua ho.

---

### Flow Diagram

```
Staff Daily Report Submit karta hai
            |
            v
+---------------------------+
|  ProductionLogController  |
|  store()                  |
|                           |
|  ProductionLog::create()  |
|                           |
|  KPIAlertService          |
|  ::check($log)            |
+---------------------------+
            |
            v
+----------------------------------+
|  KPIAlertService::check()        |
|                                  |
|  CHECK 1: Mortality Rate         |
|  = (deaths / prev_count) x 100  |
|  > 0.5% = warning               |
|  > 1.0% = critical              |
|                                  |
|  CHECK 2: Livability             |
|  = log->livability               |
|  < 95% = warning                 |
|  < 90% = critical                |
|                                  |
|  CHECK 3: FCR (if weightLog)     |
|  = weightLog->fcr                |
|  > 2.0 = warning                 |
|  > 2.5 = critical                |
|                                  |
|  CHECK 4: CV (if weightLog)      |
|  = weightLog->cv                 |
|  > 10% = warning                 |
|  > 15% = critical                |
+----------------------------------+
            |
    +-------+-------+
    |               |
    v               v
[Breach mila]   [Breach nahi]
    |               |
    v               v
Event fire      Kuch nahi
                (normal)
    |
    v
+---------------------------+
|  AbnormalKPIDetected      |
|  Event                    |
|                           |
|  carries:                 |
|  - productionLog          |
|  - shed                   |
|  - flock                  |
|  - breaches (array)       |
|    [{kpi, value,          |
|      threshold,           |
|      severity}]           |
+---------------------------+
            |
            v
+---------------------------+
|  EventServiceProvider     |
|  routes to listener       |
+---------------------------+
            |
            v
+---------------------------+
|  SendKPIAlertNotification |
|  Listener                 |
|                           |
|  1. Highest severity lao  |
|     (critical > warning)  |
|  2. Summary text banao    |
|  3. Stakeholders collect  |
|     - Owner               |
|     - Managers            |
|     - Staff               |
+---------------------------+
            |
     +------+------+
     |             |
     v             v
+---------+   +------------------+
| IN-APP  |   | PUSH             |
|         |   |                  |
| notifi- |   | notification_    |
| cations |   | outboxes table   |
| table   |   | (pending)        |
+---------+   +------------------+
```

---

### Notification Content

```
Type    : kpi_alert
Title   : "Abnormal KPI — Shed A"
Message : "Abnormal KPIs detected for Flock 'Flock 1'
           in Shed 'Shed A' on Day 15:
           • Mortality Rate: 1.2% (above 1%) — CRITICAL
           • FCR: 2.6 (above 2.5) — CRITICAL"
```

---

### Real Life Scenario

```
Ali (Worker)
  Raat ko daily report submit karta hai:
    day_mortality_count  : 15
    night_mortality_count: 10
    (total 25 maut, kal 1000 theen)
        |
        v
  Mortality Rate = 25/1000 x 100 = 2.5%
  2.5% > 1% threshold  -->  CRITICAL breach mila!
        |
        v
  Ahmed (Owner)    -- "Mortality Rate 2.5% -- CRITICAL"
  Bilal (Manager)  -- "Mortality Rate 2.5% -- CRITICAL"
  Sara  (Staff)    -- "Mortality Rate 2.5% -- CRITICAL"
```

---

### Ek Baar Mein Multiple Breaches

```
Agar ek report mein 2 KPI kharab hon:

  Mortality Rate: 1.5%  CRITICAL
  FCR           : 2.8   CRITICAL

  Sirf EK notification jati hai:

  Title: "Abnormal KPI Alert -- Shed A"
  Body:
    Mortality Rate: 1.5% (above 1%) -- CRITICAL
    FCR: 2.8 (above 2.5) -- CRITICAL
```

---
---

## Dono Requirements Ka Comparison

| Cheez | Req 3 (Vaccination) | Req 4 (KPI Alert) |
|---|---|---|
| Trigger | `is_vaccinated = true` | Koi bhi KPI abnormal |
| Event | `VaccinationRecorded` | `AbnormalKPIDetected` |
| Listener | `SendVaccinationNotification` | `SendKPIAlertNotification` |
| Service | Nahi (direct) | `KPIAlertService` |
| In-app | Haan | Haan |
| Push | Haan | Haan |
| Owner ko | Haan | Haan |
| Managers ko | Haan | Haan |
| Staff ko | Haan | Haan |
| Notification Type | `vaccination` | `kpi_alert` |
| Har roz? | Sirf vaccination wale din | Sirf jab KPI kharab ho |

---

## Overall Event System Map

```
EventServiceProvider
  |
  |-- NotificationTriggered       --> CreateGenericNotification
  |   (Daily Report -- owner only)
  |
  |-- ParameterThresholdBreached  --> SendParameterAlertNotification
  |   (IoT sensor alert)
  |
  |-- VaccinationRecorded         --> SendVaccinationNotification
  |   (Req 3)
  |
  |-- AbnormalKPIDetected         --> SendKPIAlertNotification
      (Req 4)
```
