# Flock Sense API Documentation

## 📚 Available Documentation

### Parameter Monitoring APIs
Complete documentation for the Parameter Monitoring Dashboard feature.

**File:** [API_PARAMETER_MONITORING.md](./API_PARAMETER_MONITORING.md)

**Covers:**
- Temperature monitoring
- CO2 level monitoring
- Ammonia level monitoring
- Humidity monitoring
- Alert threshold management
- Data export (Excel & PDF)

**Quick Start:**
```bash
# Get temperature data
curl "http://127.0.0.1:8000/api/v1/sheds/5/parameters/temperature/data?time_range=24hour"

# Update alert thresholds
curl -X PUT "http://127.0.0.1:8000/api/v1/sheds/5/parameter-limits/temperature" \
  -H "Content-Type: application/json" \
  -d '{"min_value": 20.0, "max_value": 32.0}'
```

---

## 🌐 Interactive API Documentation

### Swagger UI
Access interactive API documentation with "Try it out" feature:

**Development:**
- [Parameter Monitoring APIs](http://localhost/External-projects/flock-sense/swagger-ui.html)
- [Temperature & Sensor APIs](http://localhost/External-projects/flock-sense/swagger-temperature-ui.html)

**Note:** Access via web server (http://localhost) not file:// protocol

---

## 🚀 Quick Links

| Resource | Description | Link |
|----------|-------------|------|
| Parameter Monitoring | Complete API docs | [View](./API_PARAMETER_MONITORING.md) |
| Swagger UI | Interactive docs | [Open](http://localhost/External-projects/flock-sense/swagger-ui.html) |
| Base URL (Dev) | API endpoint | http://127.0.0.1:8000/api/v1 |

---

## 📖 API Categories

### 1. Parameter Data APIs
- Get real-time parameter data
- Statistics and charts
- Time-range filtering

### 2. Alert Management APIs
- Get alert thresholds
- Update alert settings
- Threshold validation

### 3. Data Export APIs
- Excel export with formatting
- PDF export with statistics
- Custom date ranges

---

## 🔧 Development Setup

### Prerequisites
```bash
php artisan serve        # Start Laravel server
```

### Testing
```bash
# Using Postman
Import endpoints from documentation

# Using cURL
See examples in API_PARAMETER_MONITORING.md

# Using Browser
Open Swagger UI links above
```

---

## 📞 Support

**Email:** support@flocksense.com

**Issues:** Report bugs or request features via the project repository

---

## 📝 Documentation Standards

All API documentation follows these standards:
- ✅ Complete request/response examples
- ✅ Error handling documented
- ✅ Testing examples included
- ✅ Copy-paste ready code snippets
- ✅ Status codes explained

---

**Last Updated:** 2026-02-09
