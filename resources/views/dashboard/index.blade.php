@extends('layouts.app', ['title' => 'Dashboard Station'])

@section('content')
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">Dashboard Station</h2>
            <p class="text-sm text-[#6e7773] mt-0.5">
                Senin, 26 Oktober 2026 &middot; {{ $station->name }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <span
                id="badge-mcu-status"
                class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold {{ $station->status === 'online'
                    ? 'bg-[#dcf3e7] text-[#156d4f]'
                    : 'bg-[#fee2e2] text-[#991b1b]' }}"
            >
                {{ $station->mcu_type }} {{ $station->status === 'online' ? 'Online' : 'Offline' }}
            </span>

            <span
                id="badge-drying-state"
                class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold {{ $station->drying_state === 'drying'
                    ? 'bg-[#faeccf] text-[#9a5b13]'
                    : ($station->drying_state === 'completed'
                        ? 'bg-[#dcf3e7] text-[#156d4f]'
                        : 'bg-[#e5e7eb] text-[#4b5563]') }}"
            >
                {{ $station->drying_state_label }}
            </span>
        </div>
    </div>

    {{-- Row 1: 4 Real-Time Sensor Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
        {{-- Card 1: Kelembapan Saat Ini --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Kelembapan Saat Ini</p>
            <div class="mt-2 flex items-baseline gap-0.5 text-[#14231d]">
                <span id="live-humidity" class="text-4xl font-bold tracking-tight">{{ number_format($station->current_humidity, 0) }}</span>
                <span class="text-2xl font-bold">%</span>
            </div>
            <p id="live-humidity-subtitle" class="mt-2 text-xs text-[#6e7773]">
                @if ($station->current_humidity >= $station->fan_off_humidity)
                    Di atas target &lt; {{ $station->fan_off_humidity }}%
                @else
                    Target tercapai &lt; {{ $station->fan_off_humidity }}%
                @endif
            </p>
        </div>

        {{-- Card 2: Suhu Dalam Helm --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Suhu Dalam Helm</p>
            <div class="mt-2 flex items-baseline gap-1 text-[#14231d]">
                <span id="live-temperature" class="text-4xl font-bold tracking-tight">{{ number_format($station->current_temperature, 1, ',', '.') }}</span>
                <span class="text-xl font-bold">&deg;C</span>
            </div>
            <p class="mt-2 text-xs text-[#6e7773]">Sensor DHT22</p>
        </div>

        {{-- Card 3: Deteksi Helm --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Deteksi Helm</p>
            <div class="mt-2 flex items-baseline gap-1 text-[#14231d]">
                <span id="live-distance" class="text-4xl font-bold tracking-tight">{{ number_format($station->current_distance_cm, 0) }}</span>
                <span class="text-xl font-bold">cm</span>
            </div>
            <p id="live-distance-subtitle" class="mt-2 text-xs text-[#6e7773]">
                {{ $station->helmet_detected ? 'Terdeteksi' : 'Standby' }} (&lt; {{ $station->detection_distance_cm }} cm)
            </p>
        </div>

        {{-- Card 4: Kipas & LED --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Kipas &amp; LED</p>
            <div class="mt-2">
                <span
                    id="live-fan-state"
                    class="text-4xl font-bold tracking-tight {{ $station->fan_is_on ? 'text-[#c93b2b]' : 'text-[#156d4f]' }}"
                >
                    {{ $station->fan_is_on ? 'ON' : 'OFF' }}
                </span>
            </div>
            <p id="live-fan-subtitle" class="mt-2 text-xs text-[#6e7773]">
                {{ $station->fan_is_on ? 'Relay aktif' : 'Relay mati' }} &middot; LED {{ strtolower($station->led_label) }}
            </p>
        </div>
    </div>

    {{-- Row 2: Real-Time Humidity Line Chart & Active Drying Cycle Card --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 mb-5">
        {{-- Left (8 cols): Grafik Kelembapan Real-Time --}}
        <div class="lg:col-span-8 bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)] flex flex-col justify-between">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <h3 class="text-base font-bold text-[#14231d]">Grafik Kelembapan Real-Time</h3>
                <span class="text-xs text-[#6e7773]">
                    Update tiap <span id="live-interval">{{ $station->data_interval_seconds }}</span> detik
                </span>
            </div>

            @php
                // Map humidity (48% to 84%) to SVG Y coordinate (200 down to 24)
                $mapY = function (float $rh): float {
                    $clamped = max(48.0, min(84.0, $rh));
                    return round(200 - (($clamped - 48.0) / (84.0 - 48.0)) * 176, 1);
                };

                $y80 = $mapY(80);
                $yOn = $mapY((float) $station->fan_on_humidity);
                $yOff = $mapY((float) $station->fan_off_humidity);
                $y50 = $mapY(50);

                $points = [];
                $count = max(1, $chartReadings->count());
                foreach ($chartReadings as $idx => $reading) {
                    $x = $count > 1 ? round(55 + ($idx / ($count - 1)) * 490, 1) : 300;
                    $y = $mapY((float) $reading->humidity);
                    $points[] = ['x' => $x, 'y' => $y, 'rh' => $reading->humidity];
                }

                $polylinePoints = collect($points)->map(fn ($p) => "{$p['x']},{$p['y']}")->implode(' ');
                $lastPoint = end($points) ?: ['x' => 545, 'y' => $mapY($station->current_humidity)];
            @endphp

            <div class="w-full overflow-x-auto">
                <svg
                    id="humidity-realtime-svg"
                    viewBox="0 0 600 235"
                    class="w-full h-56 sm:h-64 min-w-[420px]"
                    role="img"
                    aria-label="Grafik Kelembapan Real-Time"
                >
                    {{-- Y-Axis Labels --}}
                    <text x="12" y="{{ $y80 + 4 }}" class="fill-[#6e7773] text-[11px]">80%</text>
                    <text x="12" y="{{ $yOn + 4 }}" class="fill-[#6e7773] text-[11px]">{{ $station->fan_on_humidity }}%</text>
                    <text x="12" y="{{ $yOff + 4 }}" class="fill-[#6e7773] text-[11px]">{{ $station->fan_off_humidity }}%</text>
                    <text x="12" y="{{ $y50 + 4 }}" class="fill-[#6e7773] text-[11px]">50%</text>

                    {{-- Upper Threshold Dashed Line (Batas nyala 65%) --}}
                    <text x="580" y="{{ $yOn - 7 }}" text-anchor="end" class="fill-[#9a5b13] text-[10px] font-medium">
                        Batas nyala {{ $station->fan_on_humidity }}%
                    </text>
                    <line
                        x1="48"
                        y1="{{ $yOn }}"
                        x2="580"
                        y2="{{ $yOn }}"
                        stroke="#cfa063"
                        stroke-width="1.3"
                        stroke-dasharray="4 4"
                    />

                    {{-- Lower Threshold Dashed Line (Batas kering 55%) --}}
                    <line
                        x1="48"
                        y1="{{ $yOff }}"
                        x2="580"
                        y2="{{ $yOff }}"
                        stroke="#5aa388"
                        stroke-width="1.3"
                        stroke-dasharray="4 4"
                    />
                    <text x="580" y="{{ $yOff + 15 }}" text-anchor="end" class="fill-[#187a5b] text-[10px] font-medium">
                        Batas kering {{ $station->fan_off_humidity }}%
                    </text>

                    {{-- Real-Time Humidity Curve --}}
                    @if ($polylinePoints !== '')
                        <polyline
                            id="humidity-curve-line"
                            fill="none"
                            stroke="#166e55"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            points="{{ $polylinePoints }}"
                        />
                        <circle
                            id="humidity-curve-dot"
                            cx="{{ $lastPoint['x'] }}"
                            cy="{{ $lastPoint['y'] }}"
                            r="5.5"
                            fill="#166e55"
                        />
                    @endif

                    {{-- X-Axis Timestamps --}}
                    <text x="55" y="224" class="fill-[#6e7773] text-[11px]">08:00</text>
                    <text x="215" y="224" class="fill-[#6e7773] text-[11px]">08:10</text>
                    <text x="375" y="224" class="fill-[#6e7773] text-[11px]">08:20</text>
                    <text x="545" y="224" text-anchor="middle" class="fill-[#6e7773] text-[11px]">Sekarang</text>
                </svg>
            </div>
        </div>

        {{-- Right (4 cols): Siklus Pengeringan --}}
        <div class="lg:col-span-4 bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)] flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-[#14231d]">Siklus Pengeringan</h3>
                <p class="text-xs text-[#6e7773] mt-1">
                    Target: kelembapan &lt; {{ $station->fan_off_humidity }}%
                </p>

                {{-- Progress Bar --}}
                <div class="mt-4 w-full h-3 rounded-full bg-[#e9e9e4] overflow-hidden">
                    <div
                        id="live-progress-bar"
                        class="h-full rounded-full bg-[#166e55] transition-all duration-300"
                        style="width: {{ $progressPercent }}%"
                    ></div>
                </div>

                <div class="mt-2.5 flex items-center justify-between text-xs text-[#6e7773]">
                    <span>Mulai <span id="cycle-start-time">{{ $activeCycle ? $activeCycle->start_time : '08:04' }}</span></span>
                    <span>Berjalan <span id="cycle-duration">{{ $activeCycle ? $activeCycle->duration_minutes : 18 }}</span> menit</span>
                </div>

                {{-- Cycle Key-Value Details --}}
                <dl class="mt-5 space-y-2.5 text-xs">
                    <div class="flex items-center justify-between">
                        <dt class="text-[#6e7773]">Kelembapan awal</dt>
                        <dd id="cycle-initial-rh" class="font-bold text-[#14231d]">
                            {{ $activeCycle ? number_format($activeCycle->initial_humidity, 0) : 79 }}%
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-[#6e7773]">Status LED</dt>
                        <dd
                            id="cycle-led-label"
                            class="font-bold {{ $station->led_status === 'red_blinking' ? 'text-[#c93b2b]' : 'text-[#156d4f]' }}"
                        >
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
            <form method="POST" action="{{ route('dashboard.toggle-fan') }}" class="mt-6">
                @csrf
                <button
                    type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-[#11261f] hover:bg-[#19382e] text-white text-xs sm:text-sm font-semibold transition-colors cursor-pointer shadow-xs"
                >
                    {{ $station->fan_is_on ? 'Matikan Kipas Manual' : 'Nyalakan Kipas Manual' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Row 3: Log Pengeringan Harian --}}
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-[#14231d]">Log Pengeringan Harian</h3>
            <a
                href="{{ route('history.index') }}"
                class="text-xs font-medium text-[#6e7773] hover:text-[#14231d] transition-colors"
            >
                Lihat semua
            </a>
        </div>

        <div class="overflow-x-auto -mx-5 px-5 sm:mx-0 sm:px-0">
            <table class="w-full text-left border-collapse min-w-[540px]">
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
                                {{ number_format($cycle->initial_humidity, 0) }}% &rarr; {{ number_format((float) ($cycle->final_humidity ?? $station->current_humidity), 0) }}%
                            </td>
                            <td class="py-3.5 pl-4">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $cycle->status === 'running'
                                        ? 'bg-[#faeccf] text-[#9a5b13]'
                                        : 'bg-[#dcf3e7] text-[#156d4f]' }}"
                                >
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
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const intervalSec = Number(@json($station->data_interval_seconds)) || 2;
            const pollIntervalMs = Math.max(2000, intervalSec * 1000);

            const mapHumidityToY = (rh) => {
                const clamped = Math.max(48, Math.min(84, Number(rh)));
                return (200 - ((clamped - 48) / (84 - 48)) * 176).toFixed(1);
            };

            const pollLiveState = async () => {
                try {
                    const response = await fetch('{{ route('api.iot.state') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) return;

                    const data = await response.json();
                    const st = data.station;
                    if (!st) return;

                    const rhEl = document.getElementById('live-humidity');
                    const tempEl = document.getElementById('live-temperature');
                    const distEl = document.getElementById('live-distance');
                    const fanEl = document.getElementById('live-fan-state');
                    const progEl = document.getElementById('live-progress-bar');

                    if (rhEl) rhEl.textContent = st.current_humidity;
                    if (tempEl) tempEl.textContent = st.current_temperature;
                    if (distEl) distEl.textContent = st.current_distance_cm;
                    if (fanEl) {
                        fanEl.textContent = st.fan_is_on ? 'ON' : 'OFF';
                        fanEl.className = 'text-4xl font-bold tracking-tight ' + (st.fan_is_on ? 'text-[#c93b2b]' : 'text-[#156d4f]');
                    }
                    if (progEl) {
                        progEl.style.width = `${st.progress_percent}%`;
                    }

                    // Update SVG polyline if readings are returned
                    if (Array.isArray(data.readings) && data.readings.length > 0) {
                        const count = data.readings.length;
                        const pts = data.readings.map((r, idx) => {
                            const x = count > 1 ? (55 + (idx / (count - 1)) * 490).toFixed(1) : 300;
                            const y = mapHumidityToY(r.humidity);
                            return { x, y };
                        });

                        const lineEl = document.getElementById('humidity-curve-line');
                        const dotEl = document.getElementById('humidity-curve-dot');
                        if (lineEl) {
                            lineEl.setAttribute('points', pts.map(p => `${p.x},${p.y}`).join(' '));
                        }
                        if (dotEl && pts.length > 0) {
                            const last = pts[pts.length - 1];
                            dotEl.setAttribute('cx', last.x);
                            dotEl.setAttribute('cy', last.y);
                        }
                    }
                } catch (err) {
                    // Ignore transient network polling errors
                }
            };

            setInterval(pollLiveState, pollIntervalMs);
        });
    </script>
@endpush
