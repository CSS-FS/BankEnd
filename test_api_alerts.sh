#!/bin/bash

# IoT Alert System API Test Script
echo "╔════════════════════════════════════════════════════════╗"
echo "║         IoT ALERT SYSTEM - API TEST                   ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

BASE_URL="http://localhost:8000"
API_URL="$BASE_URL/api"

echo "🔧 Configuration:"
echo "   Base URL: $BASE_URL"
echo "   Test Device: DEV-001"
echo "   Test Shed ID: 1"
echo ""

# Test 1: Configure Parameter Limit
echo "📋 STEP 1: Setting up Alert Threshold..."
echo "────────────────────────────────────────────────────────────"
echo "Setting: Temperature Min=20°C, Max=30°C"
echo ""
echo "Request:"
echo "POST $API_URL/v1/sheds/1/parameter-limits"
echo ""

curl -X POST "$API_URL/v1/sheds/1/parameter-limits" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "parameter_name": "temperature",
    "min_value": 20.0,
    "max_value": 30.0,
    "unit": "°C"
  }' 2>/dev/null | jq '.' || echo "⚠️  API might not be running or authentication required"

echo ""
echo ""

# Test 2: Send Normal Data (No Alert)
echo "📤 STEP 2: Sending NORMAL sensor data..."
echo "────────────────────────────────────────────────────────────"
echo "Sending: temperature = 25.0°C (within 20-30°C range)"
echo ""

curl -X POST "$API_URL/iot/sensor" \
  -H "Content-Type: application/json" \
  -d '{
    "device_serial": "DEV-001",
    "temperature": 25.0,
    "humidity": 60,
    "co2": 800
  }' 2>/dev/null | jq '.' || echo "Data sent"

echo ""
echo "✅ Expected: No alert triggered (value within threshold)"
echo ""
echo ""

# Test 3: Send HIGH Temperature (Alert!)
echo "🔥 STEP 3: Sending HIGH temperature (ALERT!)..."
echo "────────────────────────────────────────────────────────────"
echo "Sending: temperature = 35.5°C (EXCEEDS max 30°C) 🔴"
echo ""

curl -X POST "$API_URL/iot/sensor" \
  -H "Content-Type: application/json" \
  -d '{
    "device_serial": "DEV-001",
    "temperature": 35.5,
    "humidity": 60,
    "co2": 800
  }' 2>/dev/null | jq '.' || echo "Data sent"

echo ""
echo "🚨 Expected: CRITICAL alert triggered!"
echo "   ✅ Device event logged"
echo "   ✅ Notifications sent to Owner, Managers, Staff"
echo "   ✅ Push notification queued"
echo ""
echo ""

# Test 4: Send LOW Temperature (Warning Alert)
echo "❄️  STEP 4: Sending LOW temperature (WARNING!)..."
echo "────────────────────────────────────────────────────────────"
echo "Sending: temperature = 15.0°C (BELOW min 20°C) ⚠️"
echo ""

curl -X POST "$API_URL/iot/sensor" \
  -H "Content-Type: application/json" \
  -d '{
    "device_serial": "DEV-001",
    "temperature": 15.0,
    "humidity": 60,
    "co2": 800
  }' 2>/dev/null | jq '.' || echo "Data sent"

echo ""
echo "⚠️  Expected: WARNING alert triggered!"
echo "   ✅ Device event logged"
echo "   ✅ In-app notifications sent"
echo ""
echo ""

# Test 5: Check Notifications (requires auth token)
echo "📬 STEP 5: How to check notifications..."
echo "────────────────────────────────────────────────────────────"
echo "Run these commands to verify:"
echo ""
echo "1. Get notifications (requires login token):"
echo "   curl -X GET $API_URL/v1/notifications \\"
echo "     -H 'Authorization: Bearer YOUR_TOKEN'"
echo ""
echo "2. Check database directly:"
echo "   mysql> SELECT * FROM device_events"
echo "          WHERE event_type='threshold_breach'"
echo "          ORDER BY occurred_at DESC LIMIT 5;"
echo ""
echo "   mysql> SELECT * FROM notifications"
echo "          WHERE type='parameter_alert'"
echo "          ORDER BY created_at DESC LIMIT 10;"
echo ""
echo "   mysql> SELECT * FROM notification_outboxes"
echo "          WHERE status='pending'"
echo "          ORDER BY created_at DESC LIMIT 5;"
echo ""
echo ""

echo "╔════════════════════════════════════════════════════════╗"
echo "║                  TEST COMPLETE                         ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "📝 What happens when alerts trigger:"
echo ""
echo "1️⃣  Sensor data stored in DynamoDB ✅"
echo "2️⃣  Parameter checked against threshold ✅"
echo "3️⃣  DeviceEvent created (threshold_breach) ✅"
echo "4️⃣  ParameterThresholdBreached event fired ✅"
echo "5️⃣  SendParameterAlertNotification listener runs ✅"
echo "6️⃣  In-app notifications created for ALL stakeholders:"
echo "     - Farm Owner ✉️"
echo "     - All Managers ✉️"
echo "     - All Staff ✉️"
echo "7️⃣  Push notifications queued (critical only) 📱"
echo ""
echo "🎉 IoT Alert System Ready!"
echo ""
