<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DryingCycle;
use App\Services\TelemetryIngestionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DryingHistoryController
 *
 * Handles the "Riwayat Pengeringan" page (`/riwayat`), providing:
 * - Date range and cycle status filtering
 * - 7-day KPI summary statistics (Total Siklus, Rata-rata Durasi, Rata-rata Penurunan RH)
 * - Daily drying duration bar chart data
 * - Paginated cycle table (4 rows per page matching UI specification)
 * - CSV export (`Ekspor CSV`)
 */
class DryingHistoryController extends Controller
{
    public function __construct(
        private readonly TelemetryIngestionService $telemetryService
    ) {}

    /**
     * Display the Drying History page.
     */
    public function index(Request $request): View
    {
        $station = $this->telemetryService->getOrSeedDefaultStation();
        $range = (string) $request->query('range', '7d');
        $status = (string) $request->query('status', 'all');

        $filteredQuery = $this->buildFilteredQuery($station->id, $range, $status);

        // Calculate KPI summary metrics across the filtered dataset
        $allMatching = (clone $filteredQuery)->get();
        $totalCycles = $allMatching->count();
        $avgDuration = $totalCycles > 0
            ? (int) round((float) $allMatching->avg('duration_minutes'))
            : 0;
        $avgRhDrop = $totalCycles > 0
            ? (int) round((float) $allMatching->avg(fn (DryingCycle $c) => $c->humidity_drop))
            : 0;

        // Bar chart: chronological 7 most recent daily cycles
        $chartCycles = $station->dryingCycles()
            ->orderByDesc('started_at')
            ->limit(7)
            ->get()
            ->reverse()
            ->values();

        // Paginated table (4 items per page to match "Menampilkan 1–4 dari 7")
        $cycles = $filteredQuery
            ->orderByDesc('started_at')
            ->paginate(4)
            ->withQueryString();

        return view('history.index', [
            'station' => $station,
            'cycles' => $cycles,
            'chartCycles' => $chartCycles,
            'totalCycles' => $totalCycles,
            'avgDuration' => $avgDuration,
            'avgRhDrop' => $avgRhDrop,
            'selectedRange' => $range,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Export the filtered drying cycles as a downloadable CSV file (`Ekspor CSV`).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $station = $this->telemetryService->getOrSeedDefaultStation();
        $range = (string) $request->query('range', '7d');
        $status = (string) $request->query('status', 'all');

        $cycles = $this->buildFilteredQuery($station->id, $range, $status)
            ->orderByDesc('started_at')
            ->get();

        $filename = sprintf('riwayat-pengeringan-%s.csv', $station->code);

        return response()->streamDownload(function () use ($cycles): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Tanggal',
                'Mulai',
                'Selesai',
                'Durasi (menit)',
                'RH Awal (%)',
                'RH Akhir (%)',
                'Suhu Rata-rata (C)',
                'Mode',
                'Status',
            ]);

            foreach ($cycles as $cycle) {
                fputcsv($handle, [
                    $cycle->formatted_date,
                    $cycle->start_time,
                    $cycle->end_time,
                    $cycle->duration_minutes,
                    number_format((float) $cycle->initial_humidity, 0),
                    number_format((float) ($cycle->final_humidity ?? 0), 0),
                    number_format((float) $cycle->avg_temperature, 1, '.', ''),
                    $cycle->mode,
                    $cycle->status_label,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the Eloquent query filtered by date range and cycle status.
     *
     * @return Builder<DryingCycle>
     */
    private function buildFilteredQuery(int $stationId, string $range, string $status): Builder
    {
        $query = DryingCycle::query()->where('station_id', $stationId);

        if ($status !== 'all' && in_array($status, ['running', 'completed', 'stopped'], true)) {
            $query->where('status', $status);
        }

        // Anchor relative date filters to the latest recorded cycle date so seeded historical dates work seamlessly
        $latestCycleDate = DryingCycle::where('station_id', $stationId)->max('started_at');
        if ($latestCycleDate && in_array($range, ['7d', '30d'], true)) {
            $days = $range === '7d' ? 7 : 30;
            $cutoff = Carbon::parse($latestCycleDate)->subDays($days)->startOfDay();
            $query->where('started_at', '>=', $cutoff);
        }

        return $query;
    }
}
