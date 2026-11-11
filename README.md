# Helmet Care — Dehumidifier Station (Web & IoT Platform)

A **Laravel 13.x (Blade MVC)** monitoring and control web platform for the **Helmet Care Dehumidifier Station** embedded IoT system powered by an **ESP32** microcontroller, **DHT22** temperature & humidity sensor, **HC-SR04** ultrasonic distance sensor, **Relay Fan** module, and **Status LED** indicator.

---

## Key Features

1. **Dashboard Station (`/`)**
   - Real-time sensor KPI cards: **Current Humidity (% RH)**, **Internal Helmet Temperature (°C)**, **Helmet Proximity Detection (cm)**, and **Fan & LED Status**.
   - **Real-Time Humidity Line Chart** with hysteresis threshold reference lines (`65% ON` & `55% OFF`) and automatic live polling every 2 seconds.
   - **Active Drying Cycle** progress card with a **Manual Fan Override** button via MQTT.
   - **Daily Drying Log** summary table.
2. **Drying History (`/riwayat`)**
   - Filter by date range (`Last 7 days`, `Last 30 days`, `All time`) and cycle status (`All`, `Running`, `Completed`).
   - 3 KPI summary cards: **Total Cycles (7 days)**, **Average Duration (minutes)**, and **Average RH Reduction (points)**.
   - **Daily Drying Duration Bar Chart** (teal bars for completed cycles, orange bar for active cycle).
   - **CSV Export** (`/riwayat/export`) and paginated history table.
3. **Devices & Diagnostics (`/perangkat`)**
   - **ESP32 Station 01** connectivity banner and diagnostics for all 4 hardware components (`HC-SR04`, `DHT22`, `Relay Module`, `Status LED`).
   - Wi-Fi network & MQTT metadata (`SSID`, `RSSI dBm`, `Server Endpoint`, `MQTT Topic`, `Firmware Version`) and chronological **Device Activity Log**.
   - Interactive **Web MQTT Telemetry Simulator** to test website reactions before physical ESP32 hardware is connected.
4. **Threshold Settings (`/pengaturan`)**
   - Configurable hysteresis thresholds (`Fan ON above % RH`, `Fan OFF below % RH`, `Helmet detection distance cm`, `Telemetry interval seconds`).
   - **Operation Mode** controls (`Automatic mode` & completion notification) and **Restore Defaults** button that syncs configuration to the ESP32 over MQTT.

---

## MVC Architecture & IoT Communication (MQTT + REST Bridge)

```text
app/
├── Console/Commands/
│   ├── MqttListenCommand.php          # MQTT subscriber daemon (`php artisan mqtt:listen`)
│   └── MqttSimulateCommand.php        # CLI ESP32 payload simulator (`php artisan mqtt:simulate`)
├── Http/Controllers/
│   ├── Api/TelemetryController.php    # IoT JSON endpoints (`/api/iot/telemetry`, `/api/iot/state`, `/api/iot/config`)
│   ├── DashboardController.php        # Main Dashboard Station & manual fan toggle
│   ├── DryingHistoryController.php    # Drying History & CSV export
│   ├── DeviceController.php           # Device diagnostics & web telemetry simulator
│   └── ThresholdSettingController.php # Hysteresis threshold settings
├── Models/
│   ├── Station.php                    # Station config, live sensor snapshot, & actuator states
│   ├── DryingCycle.php                # Daily drying cycles & RH drop calculations
│   ├── SensorReading.php              # Time-series readings from DHT22 & HC-SR04
│   └── DeviceActivityLog.php          # Chronological hardware & automation event logs
└── Services/
    ├── MqttBrokerService.php          # MQTT client integration (`php-mqtt/client`)
    └── TelemetryIngestionService.php  # Hysteresis state machine & drying cycle lifecycle
```

### MQTT Topic Contract (`config/mqtt.php`)

| Direction | MQTT Topic | Description |
| :--- | :--- | :--- |
| **ESP32 &rarr; Server** | `helmet-care/stations/station-01/telemetry` | Publishes DHT22 & HC-SR04 sensor readings every `N` seconds |
| **Server &rarr; ESP32** | `helmet-care/stations/station-01/commands` | Publishes manual fan relay (`ON`/`OFF`) & LED commands |
| **Server &rarr; ESP32** | `helmet-care/stations/station-01/config` | Syncs updated hysteresis thresholds (% RH, distance cm, interval) |

### Example JSON Payload from ESP32 (MQTT or HTTP `POST /api/iot/telemetry`)

```json
{
  "station_code": "station-01",
  "humidity": 61.0,
  "temperature": 31.4,
  "distance_cm": 8.0,
  "wifi_ssid": "HelmetCare-IoT-Lab",
  "wifi_rssi": -54,
  "firmware_version": "v1.4.2-esp32"
}
```

---

## Installation & Setup

1. **One-command setup (installs Composer packages, creates `.env`, generates app key, runs SQLite migrations, and builds frontend assets):**
   ```bash
   composer run setup
   php artisan db:seed
   ```

2. **Manual installation (alternative to `composer run setup`):**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   touch database/database.sqlite
   php artisan migrate:fresh --seed
   npm install
   npm run build
   ```

---

## Quick Commands to Run & Test

Copy and run the following commands in your terminal to start the web server, simulate ESP32 MQTT telemetry, and run the BDD test suite:

```bash
# Start the local web server (http://127.0.0.1:8000)
php artisan serve

# Simulate an ESP32 sending MQTT telemetry from CLI
php artisan mqtt:simulate --humidity=58.0 --temperature=31.2 --distance=8.0

# Start the background MQTT topic subscriber (when connecting real ESP32 hardware)
php artisan mqtt:listen

# Run the BDD feature test suite
php artisan test
```
