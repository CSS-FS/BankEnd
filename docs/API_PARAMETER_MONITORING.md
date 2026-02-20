# Parameter Monitoring Dashboard - API Documentation

## 📋 Overview

This document provides complete API documentation for the Parameter Monitoring Dashboard feature. The dashboard displays real-time monitoring cards for environmental parameters (Temperature, CO2, Ammonia, Humidity) with statistics, charts, alert management, and export capabilities.

## 🎯 Features

- ✅ Real-time parameter data display
- ✅ Statistics (Min, Average, Max)
- ✅ Time-series chart visualization
- ✅ Alert threshold management (High/Low)
- ✅ Excel export
- ✅ PDF export
- ✅ Multiple time ranges (24 hour, Last week, Current month, Custom)

## 🔗 Base URL

```
Development: http://127.0.0.1:8000/api/v1
Production:  https://api.flocksense.com/api/v1
```

## 📊 Supported Parameters

| Parameter | Description | Unit | Example Value |
|-----------|-------------|------|---------------|
| `temperature` | Temperature reading | °C | 27.9 |
| `co2` | Carbon dioxide level | ppm | 850 |
| `ammonia` | Ammonia concentration | ppm | 12 |
| `humidity` | Relative humidity | % | 65 |

---

## 🚀 API Endpoints

### 1. Get Parameter Data

Fetch parameter-specific data including current value, statistics, chart data, and alert thresholds.

**Endpoint:**
```
GET /sheds/{shedId}/parameters/{parameter}/data
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `shedId` | integer | Yes | Shed identifier |
| `parameter` | string | Yes | Parameter name: `temperature`, `co2`, `ammonia`, `humidity` |

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `time_range` | string | No | `24hour` | Time range: `24hour`, `last_week`, `current_month`, `custom` |
| `from` | date | Conditional | - | Start date (required if `time_range=custom`) Format: YYYY-MM-DD |
| `to` | date | Conditional | - | End date (required if `time_range=custom`) Format: YYYY-MM-DD |

**Example Requests:**

```http
# 24 hour data for temperature
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/data?time_range=24hour

# Last week data for CO2
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/co2/data?time_range=last_week

# Current month data for ammonia
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/ammonia/data?time_range=current_month

# Custom date range for humidity
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/humidity/data?time_range=custom&from=2026-02-01&to=2026-02-09
```

**Success Response (200 OK):**

```json
{
  "parameter": "temperature",
  "current_value": 27.9,
  "unit": "°C",
  "statistics": {
    "min": 23.5,
    "average": 25.8,
    "max": 28.2
  },
  "chart_data": [
    {
      "timestamp": "2026-02-09 10:00:00",
      "value": 24.5,
      "min": 23.5,
      "max": 25.2
    },
    {
      "timestamp": "2026-02-09 11:00:00",
      "value": 26.0,
      "min": 25.0,
      "max": 27.0
    },
    {
      "timestamp": "2026-02-09 12:00:00",
      "value": 27.9,
      "min": 26.5,
      "max": 28.2
    }
  ],
  "alert_thresholds": {
    "high": 28.0,
    "low": 24.0
  }
}
```

**Response Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `parameter` | string | Parameter name |
| `current_value` | float/null | Latest parameter value |
| `unit` | string/null | Unit of measurement |
| `statistics.min` | float/null | Minimum value in time range |
| `statistics.average` | float/null | Average value in time range |
| `statistics.max` | float/null | Maximum value in time range |
| `chart_data` | array | Array of data points for chart |
| `chart_data[].timestamp` | string | Timestamp in Y-m-d H:i:s format |
| `chart_data[].value` | float | Average value at timestamp |
| `chart_data[].min` | float | Minimum value at timestamp |
| `chart_data[].max` | float | Maximum value at timestamp |
| `alert_thresholds` | object/null | Alert threshold settings |
| `alert_thresholds.high` | float | Maximum threshold |
| `alert_thresholds.low` | float | Minimum threshold |

**Error Responses:**

```json
// Shed not found (404 Not Found)
{
  "message": "No query results for model [App\\Models\\Shed] 5"
}

// Validation error (422 Unprocessable Entity)
{
  "message": "The given data was invalid.",
  "errors": {
    "time_range": ["The selected time range is invalid."],
    "from": ["The from field is required when time range is custom."]
  }
}
```

---

### 2. Get Alert Settings

Retrieve alert threshold settings for a specific parameter. Used when opening the "Set Alert" modal.

**Endpoint:**
```
GET /sheds/{shedId}/parameter-limits/{parameter}
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `shedId` | integer | Yes | Shed identifier |
| `parameter` | string | Yes | Parameter name |

**Example Requests:**

```http
GET http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature
GET http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/co2
GET http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/ammonia
GET http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/humidity
```

**Success Response (200 OK):**

```json
{
  "data": {
    "id": 1,
    "shed_id": 5,
    "parameter_name": "temperature",
    "unit": "°C",
    "min_value": 24.0,
    "max_value": 28.0,
    "avg_value": null,
    "created_at": "2026-02-09T10:00:00.000000Z",
    "updated_at": "2026-02-09T15:30:00.000000Z"
  }
}
```

**Response Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Parameter limit ID |
| `shed_id` | integer | Shed ID |
| `parameter_name` | string | Parameter name |
| `unit` | string | Unit of measurement |
| `min_value` | float | Low threshold (displayed as "Low" in UI) |
| `max_value` | float | High threshold (displayed as "High" in UI) |
| `avg_value` | float/null | Average value (not used in UI) |
| `created_at` | string | Creation timestamp |
| `updated_at` | string | Last update timestamp |

**Error Responses:**

```json
// Parameter limit not found (404 Not Found)
{
  "message": "No query results for model [App\\Models\\ShedParameterLimit]"
}

// Shed not found (404 Not Found)
{
  "message": "No query results for model [App\\Models\\Shed] 5"
}
```

---

### 3. Update Alert Settings

Create or update alert threshold settings. Used when user saves changes from the "Set Alert" modal.

**Endpoint:**
```
PUT /sheds/{shedId}/parameter-limits/{parameter}
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `shedId` | integer | Yes | Shed identifier |
| `parameter` | string | Yes | Parameter name |

**Request Headers:**
```
Content-Type: application/json
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `unit` | string | Optional | Unit of measurement (e.g., "°C", "ppm", "%") |
| `min_value` | float | Optional | Low threshold value |
| `max_value` | float | Optional | High threshold value (must be >= min_value) |
| `avg_value` | float | Optional | Average reference value (not used in UI) |

> **Note:** All fields are optional for updates. If the parameter limit doesn't exist, it will be created automatically.

**Example Requests:**

```http
# Temperature alert
PUT http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature
Content-Type: application/json

{
  "unit": "°C",
  "min_value": 24.0,
  "max_value": 28.0
}

# CO2 alert
PUT http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/co2
Content-Type: application/json

{
  "unit": "ppm",
  "min_value": 600,
  "max_value": 1000
}

# Ammonia alert
PUT http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/ammonia
Content-Type: application/json

{
  "unit": "ppm",
  "min_value": 5,
  "max_value": 15
}

# Humidity alert
PUT http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/humidity
Content-Type: application/json

{
  "unit": "%",
  "min_value": 50,
  "max_value": 70
}

# Partial update (only max value)
PUT http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature
Content-Type: application/json

{
  "max_value": 30.0
}
```

**Success Response - Updated (200 OK):**

```json
{
  "message": "Parameter limit updated successfully.",
  "data": {
    "id": 1,
    "shed_id": 5,
    "parameter_name": "temperature",
    "unit": "°C",
    "min_value": 24.0,
    "max_value": 28.0,
    "avg_value": null,
    "created_at": "2026-02-09T10:00:00.000000Z",
    "updated_at": "2026-02-09T15:30:00.000000Z"
  }
}
```

**Success Response - Created (201 Created):**

```json
{
  "message": "Parameter limit created successfully.",
  "data": {
    "id": 2,
    "shed_id": 5,
    "parameter_name": "co2",
    "unit": "ppm",
    "min_value": 600,
    "max_value": 1000,
    "avg_value": null,
    "created_at": "2026-02-09T15:30:00.000000Z",
    "updated_at": "2026-02-09T15:30:00.000000Z"
  }
}
```

**Error Responses:**

```json
// Validation error: max < min (422 Unprocessable Entity)
{
  "message": "Max value must be greater than or equal to min value."
}

// Validation error: invalid data (422 Unprocessable Entity)
{
  "message": "The given data was invalid.",
  "errors": {
    "min_value": ["The min value must be a number."],
    "max_value": ["The max value must be a number."]
  }
}

// Shed not found (404 Not Found)
{
  "message": "No query results for model [App\\Models\\Shed] 5"
}
```

---

### 4. Export Parameter Data to Excel

Export parameter data to Excel (.xlsx) file with professional formatting.

**Endpoint:**
```
GET /sheds/{shedId}/parameters/{parameter}/export/excel
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `shedId` | integer | Yes | Shed identifier |
| `parameter` | string | Yes | Parameter name |

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `time_range` | string | No | `24hour` | Time range: `24hour`, `last_week`, `current_month`, `custom` |
| `from` | date | Conditional | - | Start date (required if `time_range=custom`) |
| `to` | date | Conditional | - | End date (required if `time_range=custom`) |

**Example Requests:**

```http
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/excel?time_range=24hour
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/co2/export/excel?time_range=last_week
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/ammonia/export/excel?time_range=current_month
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/humidity/export/excel?time_range=custom&from=2026-02-01&to=2026-02-09
```

**Success Response (200 OK):**
- Content-Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Binary file download
- Filename format: `{ShedName}_{parameter}_data_{timestamp}.xlsx`
  - Example: `Shed_A_temperature_data_2026-02-09_143000.xlsx`

**Excel File Contents:**
- **Sheet Name:** Parameter name with unit (e.g., "Temperature (°C)")
- **Columns:**
  1. Timestamp
  2. Value (Avg)
  3. Min
  4. Max
- **Formatting:** Bold headers, professional styling

**Error Responses:**

```json
// No devices found (404 Not Found)
{
  "message": "No devices found for this shed"
}

// Shed not found (404 Not Found)
{
  "message": "No query results for model [App\\Models\\Shed] 5"
}
```

---

### 5. Export Parameter Data to PDF

Export parameter data to PDF file with professional layout including statistics and alert information.

**Endpoint:**
```
GET /sheds/{shedId}/parameters/{parameter}/export/pdf
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `shedId` | integer | Yes | Shed identifier |
| `parameter` | string | Yes | Parameter name |

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `time_range` | string | No | `24hour` | Time range: `24hour`, `last_week`, `current_month`, `custom` |
| `from` | date | Conditional | - | Start date (required if `time_range=custom`) |
| `to` | date | Conditional | - | End date (required if `time_range=custom`) |

**Example Requests:**

```http
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/pdf?time_range=24hour
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/co2/export/pdf?time_range=last_week
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/ammonia/export/pdf?time_range=current_month
GET http://127.0.0.1:8000/api/v1/sheds/5/parameters/humidity/export/pdf?time_range=custom&from=2026-02-01&to=2026-02-09
```

**Success Response (200 OK):**
- Content-Type: `application/pdf`
- Binary file download
- Filename format: `{ShedName}_{parameter}_data_{timestamp}.pdf`
  - Example: `Shed_A_temperature_data_2026-02-09_143000.pdf`

**PDF Contents:**
1. **Header Section:**
   - Shed name
   - Parameter name
   - Date range
   - Generated timestamp

2. **Info Section:**
   - Parameter name
   - Unit of measurement
   - Time range

3. **Statistics Summary:**
   - Minimum value
   - Average value
   - Maximum value

4. **Alert Thresholds Section (if configured):**
   - High threshold
   - Low threshold

5. **Data Table:**
   - All data points with timestamps
   - Value (Avg), Min, Max columns
   - Professional table formatting

6. **Footer:**
   - Page numbers
   - Report generation info

**Error Responses:**

```json
// No devices found (404 Not Found)
{
  "message": "No devices found for this shed"
}

// Shed not found (404 Not Found)
{
  "message": "No query results for model [App\\Models\\Shed] 5"
}
```

---

## 🎬 User Flow & API Call Sequence

### Scenario 1: Page Load
```
1. User opens Parameter Monitoring Dashboard
2. Frontend calls:
   → GET /sheds/5/parameters/temperature/data?time_range=24hour
   → GET /sheds/5/parameters/co2/data?time_range=24hour
   → GET /sheds/5/parameters/ammonia/data?time_range=24hour
   → GET /sheds/5/parameters/humidity/data?time_range=24hour
3. Cards display with current data, statistics, charts, and alert thresholds
```

### Scenario 2: Change Time Range
```
1. User clicks "Last week" button on Temperature card
2. Frontend calls:
   → GET /sheds/5/parameters/temperature/data?time_range=last_week
3. Card updates with last week's data
```

### Scenario 3: Edit Alert Thresholds
```
1. User clicks "Edit" button on Set Alert section
2. Frontend calls:
   → GET /sheds/5/parameter-limits/temperature
3. Modal opens with current values (High: 28.0°C, Low: 24.0°C)
4. User changes values to High: 30.0°C, Low: 20.0°C
5. User clicks "Save" button
6. Frontend calls:
   → PUT /sheds/5/parameter-limits/temperature
   Body: {"min_value": 20.0, "max_value": 30.0}
7. Modal closes
8. Frontend refreshes card data:
   → GET /sheds/5/parameters/temperature/data?time_range=24hour
9. Card displays updated alert thresholds
```

### Scenario 4: Export Data
```
1. User clicks "Export Excel" button
2. Frontend calls:
   → GET /sheds/5/parameters/temperature/export/excel?time_range=24hour
3. Excel file downloads automatically
```

---

## 🧪 Testing Guide

### Prerequisites
- Laravel development server running: `php artisan serve`
- Database seeded with test data
- At least one shed with devices configured

### Using Postman

**1. Get Parameter Data:**
```
Method: GET
URL: http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/data
Params:
  - time_range: 24hour
```

**2. Get Alert Settings:**
```
Method: GET
URL: http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature
```

**3. Update Alert Settings:**
```
Method: PUT
URL: http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature
Headers:
  - Content-Type: application/json
Body (raw JSON):
{
  "unit": "°C",
  "min_value": 20.0,
  "max_value": 32.0
}
```

**4. Export to Excel:**
```
Method: GET
URL: http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/excel
Params:
  - time_range: 24hour
Send and Download: Enabled
```

**5. Export to PDF:**
```
Method: GET
URL: http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/pdf
Params:
  - time_range: 24hour
Send and Download: Enabled
```

### Using cURL

```bash
# Get parameter data
curl "http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/data?time_range=24hour"

# Get alert settings
curl "http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature"

# Update alert settings
curl -X PUT "http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature" \
  -H "Content-Type: application/json" \
  -d '{"min_value": 20.0, "max_value": 32.0}'

# Export to Excel
curl "http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/excel?time_range=24hour" \
  -o temperature_data.xlsx

# Export to PDF
curl "http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/pdf?time_range=24hour" \
  -o temperature_data.pdf
```

### Browser Testing

Direct browser access for GET endpoints:
```
http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/data?time_range=24hour
http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature
http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/excel?time_range=24hour
http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/export/pdf?time_range=24hour
```

---

## 📋 Quick Reference

### All Endpoints Summary

| # | Method | Endpoint | Purpose |
|---|--------|----------|---------|
| 1 | GET | `/sheds/{id}/parameters/{param}/data` | Get parameter data with statistics and chart |
| 2 | GET | `/sheds/{id}/parameter-limits/{param}` | Get alert threshold settings |
| 3 | PUT | `/sheds/{id}/parameter-limits/{param}` | Update/create alert thresholds |
| 4 | GET | `/sheds/{id}/parameters/{param}/export/excel` | Export data to Excel |
| 5 | GET | `/sheds/{id}/parameters/{param}/export/pdf` | Export data to PDF |

### Parameter Names

- `temperature` - Temperature (°C)
- `co2` - Carbon Dioxide (ppm)
- `ammonia` - Ammonia (ppm)
- `humidity` - Humidity (%)

### Time Range Options

- `24hour` - Last 24 hours (default)
- `last_week` - Last 7 days
- `current_month` - From start of current month to now
- `custom` - Custom date range (requires `from` and `to` parameters)

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 404 | Not Found | Shed or parameter limit not found |
| 422 | Unprocessable Entity | Validation error |
| 500 | Internal Server Error | Server error |

---

## 🔧 Implementation Notes

### Database Tables Used

1. **sheds** - Shed information
2. **shed_devices** - Device-shed relationships
3. **iot_data_logs** - Aggregated sensor data (hourly)
4. **shed_parameter_limits** - Alert threshold settings

### Parameter Mapping

Some parameters use database aliases:
- `temperature` → mapped to `temp1` in database
- `ammonia` → mapped to `nh3` in database

### Data Aggregation

- Data is stored in `iot_data_logs` table with hourly aggregation
- Each record contains `min_value`, `max_value`, and `avg_value`
- Latest value uses records with `time_window = 'latest'`

### Alert Thresholds

- Stored in `shed_parameter_limits` table
- Independent per shed and parameter
- `min_value` corresponds to "Low" threshold in UI
- `max_value` corresponds to "High" threshold in UI
- Updates create new record if doesn't exist (upsert behavior)

---

## 🐛 Common Issues & Solutions

### Issue 1: Empty chart data returned
**Cause:** No devices associated with shed or no data in time range
**Solution:** Verify shed has active devices and data exists in `iot_data_logs`

### Issue 2: Alert thresholds not showing
**Cause:** No parameter limits configured for the parameter
**Solution:** Create parameter limit using PUT endpoint (API #3)

### Issue 3: Export fails with 404
**Cause:** No devices found for shed
**Solution:** Verify shed has devices in `shed_devices` table

### Issue 4: Validation error on alert update
**Cause:** max_value < min_value
**Solution:** Ensure max_value >= min_value in request

---

## 📞 Support

For issues or questions, contact:
- Email: support@flocksense.com
- Documentation: [Swagger UI](http://127.0.0.1:8000/swagger-ui.html)

---

## 📝 Changelog

### Version 1.0.0 (2026-02-09)
- Initial API documentation
- All 5 endpoints documented
- Testing guide added
- Error handling documented

---

**Last Updated:** 2026-02-09
**API Version:** v1
**Author:** Flock Sense Development Team
