@extends('layouts.app', ['title' => 'Dashboard Station'])

@section('content')
    <div data-dashboard-root data-state-url="{{ route('api.iot.state') }}"
        data-poll-interval="{{ $station->data_interval_seconds }}">
        {{-- Page Header --}}
        <div
            class="dashboard-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 rounded-3xl px-5 py-5 sm:px-6">
            <div>
                <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">Dashboard Station</h2>
                <p class="text-sm text-[#6e7773] mt-0.5">
                    {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <span id="badge-mcu-status" data-mcu-name="{{ $station->mcu_type }}"
                    class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold {{ $station->status === 'online' ? 'bg-[#dcf3e7] text-[#156d4f]' : 'bg-[#fee2e2] text-[#991b1b]' }}">
                    {{ $station->mcu_type }} {{ $station->status === 'online' ? 'Online' : 'Offline' }}
                </span>
                <span id="live-sync-status"
                    class="inline-flex items-center rounded-full bg-[#e5e7eb] px-2.5 py-1 text-xs font-semibold text-[#4b5563]">
                    Menyiapkan...
                </span>

                <span id="badge-drying-state"
                    class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold {{ $station->drying_state === 'drying'
                        ? 'bg-[#faeccf] text-[#9a5b13]'
                        : ($station->drying_state === 'completed'
                            ? 'bg-[#dcf3e7] text-[#156d4f]'
                            : 'bg-[#e5e7eb] text-[#4b5563]') }}">
                    {{ $station->drying_state_label }}
                </span>
            </div>
        </div>

        {{-- Row 1: 4 Real-Time Sensor Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
            {{-- Card 1: Kelembapan Saat Ini --}}
            <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="green">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-medium text-[#6e7773]">Kelembapan Saat Ini</p>
                    <span class="sensor-icon" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3.5S6.5 9.6 6.5 14a5.5 5.5 0 0011 0C17.5 9.6 12 3.5 12 3.5z" />
                            <path d="M9.5 14.5a2.5 2.5 0 005 0" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-0.5 text-[#14231d]">
                    <span id="live-humidity" class="text-4xl font-bold tracking-tight">
                        {{ $station->status === 'offline' ? '—' : number_format($station->current_humidity, 0) }}
                    </span>
                    @if ($station->status !== 'offline')
                        <span class="text-2xl font-bold">%</span>
                    @endif
                </div>
            </div>

            {{-- Card 2: Suhu Dalam Helm --}}
            <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="blue">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-medium text-[#6e7773]">Suhu Dalam Helm</p>
                    <span class="sensor-icon" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M9 14.5V5a3 3 0 016 0v9.5a5 5 0 11-6 0z" />
                            <path d="M12 8v8" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-1 text-[#14231d]">
                    <span id="live-temperature" class="text-4xl font-bold tracking-tight">
                        {{ $station->status === 'offline' ? '—' : number_format($station->current_temperature, 1, ',', '.') }}
                    </span>
                    @if ($station->status !== 'offline')
                        <span class="text-xl font-bold">&deg;C</span>
                    @endif
                </div>
            </div>

            {{-- Card 3: Deteksi Helm --}}
            <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="amber">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-medium text-[#6e7773]">Deteksi Helm</p>
                    <span class="sensor-icon" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 16.5h16M6 16.5V11a6 6 0 0112 0v5.5M9 16.5v-3h6v3" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-1 text-[#14231d]">
                    <span id="live-distance" class="text-4xl font-bold tracking-tight">
                        {{ $station->status === 'offline' ? 'Tidak diketahui' : number_format($station->current_distance_cm, 0) . ' cm' }}
                    </span>
                </div>
            </div>

            {{-- Card 4: Kipas & LED --}}
            <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="rose">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-medium text-[#6e7773]">Kipas &amp; LED</p>
                    <span class="sensor-icon" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="2" />
                            <path
                                d="M12 10c-1-4 1-6 3-6 2 0 2 3 1 5M14 13c4 1 5 4 4 6-1 2-4 1-5-1M10 13c-3 3-6 2-7 0-1-2 2-4 5-4" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <span id="live-fan-state"
                        class="text-4xl font-bold tracking-tight {{ $station->status === 'offline' ? 'text-[#6e7773]' : ($station->fan_is_on ? 'text-[#c93b2b]' : 'text-[#156d4f]') }}">
                        {{ $station->status === 'offline' ? 'Tidak diketahui' : ($station->fan_is_on ? 'ON' : 'OFF') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Row 2: Real-Time Humidity Line Chart & Active Drying Cycle Card --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 mb-5">
            {{-- Left (8 cols): Grafik Kelembapan Real-Time --}}
            <div class="dashboard-card lg:col-span-8 rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="text-base font-bold text-[#14231d]">Grafik Kelembapan Real-Time</h3>
                    <span class="text-xs text-[#6e7773]">
                        Update tiap <span id="live-interval">{{ $station->data_interval_seconds }}</span> detik
                    </span>
                </div>

                @php
                    // Map the full allowed humidity range (20% to 100%) to SVG Y coordinates.
                    $mapY = function (?float $rh): float {
                        $clamped = max(20.0, min(100.0, (float) ($rh ?? 61.0)));
                        return round(200 - (($clamped - 20.0) / (100.0 - 20.0)) * 176, 1);
                    };

                    $y100 = $mapY(100);
                    $y80 = $mapY(80);
                    $yOn = $mapY((float) $station->fan_on_humidity);
                    $yOff = $mapY((float) $station->fan_off_humidity);
                    $y50 = $mapY(50);
                    $y20 = $mapY(20);

                    $points = [];
                    $count = max(1, $chartReadings->count());
                    foreach ($chartReadings as $idx => $reading) {
                        $x = $count > 1 ? round(55 + ($idx / ($count - 1)) * 490, 1) : 300;
                        $y = $mapY((float) $reading->humidity);
                        $points[] = ['x' => $x, 'y' => $y, 'rh' => $reading->humidity];
                    }

                    $polylinePoints = collect($points)->map(fn($p) => "{$p['x']},{$p['y']}")->implode(' ');
                    $lastPoint = end($points) ?: ['x' => 545, 'y' => $mapY($station->current_humidity)];
                    $chartTimeLabels = [
                        $chartReadings->first()?->recorded_at?->format('H:i') ?? now()->subMinutes(20)->format('H:i'),
                        $chartReadings
                            ->get((int) floor(max(0, $chartReadings->count() - 1) / 2))
                            ?->recorded_at?->format('H:i') ?? now()->subMinutes(10)->format('H:i'),
                        $chartReadings->last()?->recorded_at?->format('H:i') ?? now()->format('H:i'),
                    ];
                @endphp

                <div class="w-full overflow-x-auto">
                    <svg id="humidity-realtime-svg" viewBox="0 0 600 235" class="w-full h-56 sm:h-64 min-w-[420px]"
                        role="img" aria-label="Grafik Kelembapan Real-Time">
                        {{-- Y-Axis Labels --}}
                        <text x="12" y="{{ $y100 + 4 }}" class="fill-[#6e7773] text-[11px]">100%</text>
                        <text x="12" y="{{ $y80 + 4 }}" class="fill-[#6e7773] text-[11px]">80%</text>
                        <text data-fan-on-y-label x="12" y="{{ $yOn + 4 }}"
                            class="fill-[#6e7773] text-[11px]">{{ $station->fan_on_humidity }}%</text>
                        <text data-fan-off-y-label x="12" y="{{ $yOff + 4 }}"
                            class="fill-[#6e7773] text-[11px]">{{ $station->fan_off_humidity }}%</text>
                        <text x="12" y="{{ $y50 + 4 }}" class="fill-[#6e7773] text-[11px]">50%</text>
                        <text x="12" y="{{ $y20 + 4 }}" class="fill-[#6e7773] text-[11px]">20%</text>

                        {{-- Upper Threshold Dashed Line (Batas nyala 65%) --}}
                        <text data-fan-on-label x="580" y="{{ $yOn - 7 }}" text-anchor="end"
                            class="fill-[#9a5b13] text-[10px] font-medium">
                            Batas nyala {{ $station->fan_on_humidity }}%
                        </text>
                        <line data-fan-on-line x1="48" y1="{{ $yOn }}" x2="580"
                            y2="{{ $yOn }}" stroke="#cfa063" stroke-width="1.3" stroke-dasharray="4 4" />

                        {{-- Lower Threshold Dashed Line (Batas kering 55%) --}}
                        <line data-fan-off-line x1="48" y1="{{ $yOff }}" x2="580"
                            y2="{{ $yOff }}" stroke="#5aa388" stroke-width="1.3" stroke-dasharray="4 4" />
                        <text data-fan-off-label x="580" y="{{ $yOff + 15 }}" text-anchor="end"
                            class="fill-[#187a5b] text-[10px] font-medium">
                            Batas kering {{ $station->fan_off_humidity }}%
                        </text>

                        {{-- Real-Time Humidity Curve --}}
                        @if ($polylinePoints !== '')
                            <polyline id="humidity-curve-line" fill="none" stroke="#166e55" stroke-width="3"
                                stroke-linecap="round" stroke-linejoin="round" points="{{ $polylinePoints }}" />
                            <circle id="humidity-curve-dot" cx="{{ $lastPoint['x'] }}" cy="{{ $lastPoint['y'] }}"
                                r="5.5" fill="#166e55" />
                        @endif

                        {{-- X-Axis Timestamps --}}
                        <text x="55" y="224" class="fill-[#6e7773] text-[11px]">{{ $chartTimeLabels[0] }}</text>
                        <text x="215" y="224" class="fill-[#6e7773] text-[11px]">{{ $chartTimeLabels[1] }}</text>
                        <text x="375" y="224" class="fill-[#6e7773] text-[11px]">{{ $chartTimeLabels[2] }}</text>
                        <text x="545" y="224" text-anchor="middle" class="fill-[#6e7773] text-[11px]">Sekarang</text>
                    </svg>
                </div>
            </div>

            {{-- Right (4 cols): Siklus Pengeringan --}}
            <div class="dashboard-card lg:col-span-4 rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-base font-bold text-[#14231d]">Siklus Pengeringan</h3>

                    {{-- Progress Bar --}}
                    <div class="mt-4 w-full h-3 rounded-full bg-[#e9e9e4] overflow-hidden">
                        <div id="live-progress-bar" class="h-full rounded-full bg-[#166e55] transition-all duration-300"
                            style="width: {{ $progressPercent }}%"></div>
                    </div>

                    <div class="mt-2.5 flex items-center justify-between text-xs text-[#6e7773]">
                        <span>Mulai <span
                                id="cycle-start-time">{{ $activeCycle ? $activeCycle->start_time : '—' }}</span></span>
                        <span>Berjalan <span
                                id="cycle-duration">{{ $activeCycle ? $activeCycle->duration_minutes : 0 }}</span>
                            menit</span>
                    </div>

                    {{-- Cycle Key-Value Details --}}
                    <dl class="mt-5 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <dt class="text-[#6e7773]">Kelembapan awal</dt>
                            <dd id="cycle-initial-rh" class="font-bold text-[#14231d]">
                                {{ $activeCycle ? number_format($activeCycle->initial_humidity, 0) . '%' : '—' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-[#6e7773]">Status LED</dt>
                            <dd id="cycle-led-label"
                                class="font-bold {{ $station->led_status === 'red_blinking' ? 'text-[#c93b2b]' : 'text-[#156d4f]' }}">
                                {{ $station->led_label }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-[#6e7773]">Mode</dt>
                            <dd id="cycle-mode" class="font-bold text-[#14231d]">
                                {{ $activeCycle ? $activeCycle->mode : ($station->auto_mode ? 'Otomatis' : 'Manual') }}
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Manual Fan Toggle Action Form --}}
                <form id="fan-toggle-form" method="POST" action="{{ route('dashboard.toggle-fan') }}" class="mt-6">
                    @csrf
                    <button type="submit" id="fan-toggle-button" @disabled($station->status === 'offline')
                        class="dashboard-action w-full py-3 px-4 rounded-xl text-white text-xs sm:text-sm font-semibold transition-all duration-200 cursor-pointer disabled:cursor-not-allowed disabled:opacity-60">
                        {{ $station->status === 'offline'
                            ? 'Perangkat Offline'
                            : ($station->fan_is_on
                                ? 'Matikan Kipas Manual'
                                : 'Nyalakan Kipas Manual') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Row 3: Log Pengeringan Harian --}}
        <div class="dashboard-card rounded-2xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-[#14231d]">Log Pengeringan Harian</h3>
                <a href="{{ route('history.index') }}"
                    class="text-xs font-medium text-[#6e7773] hover:text-[#14231d] transition-colors">
                    Lihat semua
                </a>
            </div>

            <div class="overflow-x-auto -mx-5 px-5 sm:mx-0 sm:px-0">
                <table class="dashboard-table w-full text-left border-collapse min-w-[540px]">
                    <thead>
                        <tr class="border-b border-[#ecece7] text-xs font-semibold text-[#6e7773]">
                            <th class="py-3 pr-4">Tanggal</th>
                            <th class="py-3 px-4">Mulai</th>
                            <th class="py-3 px-4">Selesai</th>
                            <th class="py-3 px-4">Durasi</th>
                            <th class="py-3 px-4">RH Awal &rarr; Akhir</th>
                            <th class="py-3 pl-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f1f1ec] text-xs sm:text-sm text-[#14231d]">
                        @forelse ($dailyLogs as $cycle)
                            <tr class="hover:bg-[#fafaf7] transition-colors">
                                <td class="py-3.5 pr-4 font-medium">{{ $cycle->formatted_date }}</td>
                                <td class="py-3.5 px-4">{{ $cycle->start_time }}</td>
                                <td class="py-3.5 px-4">{{ $cycle->end_time }}</td>
                                <td class="py-3.5 px-4">{{ $cycle->duration_minutes }} mnt</td>
                                <td class="py-3.5 px-4">
                                    {{ number_format($cycle->initial_humidity, 0) }}% &rarr;
                                    {{ number_format((float) ($cycle->final_humidity ?? $station->current_humidity), 0) }}%
                                </td>
                                <td class="py-3.5 pl-4">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $cycle->status === 'running' ? 'bg-[#faeccf] text-[#9a5b13]' : 'bg-[#dcf3e7] text-[#156d4f]' }}">
                                        {{ $cycle->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-[#6e7773]">
                                    Belum ada log siklus pengeringan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
