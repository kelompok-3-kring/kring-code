<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TelemetryIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DashboardController
 *
 * Handles the primary "Dashboard Station" screen (`/`), displaying:
 * - Real-time sensor KPI cards (Kelembapan Saat Ini, Suhu Dalam Helm, Deteksi Helm, Kipas & LED)
 * - Real-time Humidity Line Chart with hysteresis threshold reference lines (65% & 55%)
 * - Active Drying Cycle status card & manual fan override control
 * - Daily Drying Log summary table
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly TelemetryIngestionService $telemetryService
    ) {}

    /**
     * Display the main Dashboard Station view.
     */
    public function index(): View
    {
        $station = $this->telemetryService->getOrSeedDefaultStation();
        $activeCycle = $station->activeCycle()->first();

        // Latest 3 cycles for "Log Pengeringan Harian" table on Dashboard
        $dailyLogs = $station->dryingCycles()
            ->orderByDesc('started_at')
            ->limit(3)
            ->get();

        // Time-series readings for "Grafik Kelembapan Real-Time"
        $chartReadings = $station->sensorReadings()
            ->orderByDesc('recorded_at')
            ->limit(8)
            ->get()
            ->reverse()
            ->values();

        return view('dashboard.index', [
            'station' => $station,
            'activeCycle' => $activeCycle,
            'dailyLogs' => $dailyLogs,
            'chartReadings' => $chartReadings,
            'progressPercent' => $station->calculateDryingProgress($activeCycle),
        ]);
    }

    /**
     * Handle the "Matikan Kipas Manual" / "Nyalakan Kipas Manual" action button.
     */
    public function toggleFan(Request $request): RedirectResponse|JsonResponse
    {
        $station = $this->telemetryService->getOrSeedDefaultStation();
        $updatedStation = $this->telemetryService->toggleManualFan($station);

        $message = $updatedStation->fan_is_on
            ? 'Kipas berhasil dinyalakan secara manual (perintah dikirim via MQTT).'
            : 'Kipas berhasil dimatikan secara manual (perintah dikirim via MQTT).';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => $message,
                'fan_is_on' => $updatedStation->fan_is_on,
                'led_status' => $updatedStation->led_status,
                'led_label' => $updatedStation->led_label,
                'drying_state_label' => $updatedStation->drying_state_label,
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', $message);
    }
}
