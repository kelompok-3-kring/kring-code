@extends('layouts.app', ['title' => 'Perangkat'])

@section('content')
    {{-- Page Header --}}
    <div class="mb-6">
        <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">Perangkat</h2>
        <p class="text-sm text-[#6e7773] mt-0.5">
            Status station dan komponen yang terhubung
        </p>
    </div>

    {{-- Top Station Identity & Status Banner --}}
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
        <div>
            <h3 class="text-lg font-bold text-[#14231d]">{{ $station->name }}</h3>
            <p class="text-xs sm:text-sm text-[#6e7773] mt-0.5">
                {{ $station->mcu_type }} &middot; Data terakhir diterima {{ $station->data_interval_seconds }} detik lalu
            </p>
        </div>

        <div>
            <span
                class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold {{ $station->status === 'online'
                    ? 'bg-[#dcf3e7] text-[#156d4f]'
                    : 'bg-[#fee2e2] text-[#991b1b]' }}"
            >
                {{ $station->status === 'online' ? 'Online' : 'Offline' }}
            </span>
        </div>
    </div>

    {{-- Row 1: 4 Hardware Component Status Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
        {{-- Card 1: Sensor Ultrasonik HC-SR04 --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Sensor Ultrasonik HC-SR04</p>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">
                {{ $station->ultrasonic_status }}
            </p>
            <p class="mt-2 text-xs text-[#6e7773]">
                Jarak terbaca {{ number_format($station->current_distance_cm, 0) }} cm
            </p>
        </div>

        {{-- Card 2: Sensor DHT22 --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Sensor DHT22</p>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">
                {{ $station->dht_status }}
            </p>
            <p class="mt-2 text-xs text-[#6e7773]">
                {{ number_format($station->current_humidity, 0) }}% RH &middot; {{ number_format($station->current_temperature, 1, ',', '.') }} &deg;C
            </p>
        </div>

        {{-- Card 3: Modul Relay (Kipas) --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Modul Relay (Kipas)</p>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight {{ $station->fan_is_on ? 'text-[#c93b2b]' : 'text-[#156d4f]' }}">
                {{ $station->fan_is_on ? 'Menyala' : 'Mati' }}
            </p>
            <p class="mt-2 text-xs text-[#6e7773]">
                @if ($station->fan_is_on)
                    Sejak {{ $station->fan_turned_on_at ? $station->fan_turned_on_at->format('H:i') : '08:04' }}
                @else
                    Siaga (Standby)
                @endif
            </p>
        </div>

        {{-- Card 4: Lampu LED --}}
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Lampu LED</p>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight {{ $station->led_status === 'red_blinking'
                ? 'text-[#c93b2b]'
                : ($station->led_status === 'green_solid' ? 'text-[#156d4f]' : 'text-[#6e7773]') }}">
                {{ $station->led_label }}
            </p>
            <p class="mt-2 text-xs text-[#6e7773]">
                {{ $station->led_mode_description }}
            </p>
        </div>
    </div>

    {{-- Row 2: Koneksi & Aktivitas Perangkat --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        {{-- Left Card: Koneksi --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <h3 class="text-base font-bold text-[#14231d] mb-4">Koneksi</h3>

            <dl class="space-y-3.5 text-xs sm:text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-[#6e7773]">Jaringan</dt>
                    <dd class="font-bold text-[#14231d] text-right">{{ $station->wifi_ssid }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-[#6e7773]">Kekuatan sinyal</dt>
                    <dd class="font-bold text-[#14231d] text-right">{{ $station->wifi_rssi }} dBm</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-[#6e7773]">Endpoint server</dt>
                    <dd class="font-bold text-[#14231d] text-right truncate max-w-[260px]" title="{{ $station->server_endpoint }}">
                        {{ $station->server_endpoint }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-[#6e7773]">Topic MQTT</dt>
                    <dd class="font-bold text-[#187a5b] text-right font-mono text-xs">{{ $mqttTopic }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-[#6e7773]">Versi firmware</dt>
                    <dd class="font-bold text-[#14231d] text-right">{{ $station->firmware_version }}</dd>
                </div>
            </dl>
        </div>

        {{-- Right Card: Aktivitas Perangkat --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <h3 class="text-base font-bold text-[#14231d] mb-4">Aktivitas Perangkat</h3>

            <ul class="space-y-3.5 text-xs sm:text-sm">
                @forelse ($activities as $activity)
                    <li class="flex items-center justify-between gap-4">
                        <span class="text-[#14231d]">{{ $activity->description }}</span>
                        <span class="text-[#6e7773] shrink-0 font-medium">
                            {{ $activity->occurred_at->format('H:i') }}
                        </span>
                    </li>
                @empty
                    <li class="text-[#6e7773]">Belum ada aktivitas perangkat tercatat.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Row 3: Interactive MQTT / IoT Sensor Simulator Panel --}}
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h3 class="text-base font-bold text-[#14231d]">Simulator Telemetry MQTT (Uji Coba Sensor ESP32)</h3>
                <p class="text-xs text-[#6e7773] mt-0.5">
                    Simulasikan pengiriman data sensor DHT22 &amp; HC-SR04 melalui channel MQTT sebelum perangkat fisik ESP32 terhubung.
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-lg bg-[#f3f4f1] text-[11px] font-mono text-[#4e5955]">
                POST /api/iot/telemetry &bull; {{ $mqttTopic }}
            </span>
        </div>

        <form method="POST" action="{{ route('devices.simulate') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            @csrf
            <div>
                <label for="sim-humidity" class="block text-xs font-medium text-[#14231d] mb-1.5">
                    Kelembapan DHT22 (% RH)
                </label>
                <input
                    id="sim-humidity"
                    type="number"
                    step="0.1"
                    min="0"
                    max="100"
                    name="humidity"
                    value="{{ old('humidity', $station->current_humidity) }}"
                    class="w-full rounded-xl border border-[#dcdcd6] px-3.5 py-2 text-sm text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                />
            </div>

            <div>
                <label for="sim-temperature" class="block text-xs font-medium text-[#14231d] mb-1.5">
                    Suhu Dalam Helm (&deg;C)
                </label>
                <input
                    id="sim-temperature"
                    type="number"
                    step="0.1"
                    min="-10"
                    max="80"
                    name="temperature"
                    value="{{ old('temperature', $station->current_temperature) }}"
                    class="w-full rounded-xl border border-[#dcdcd6] px-3.5 py-2 text-sm text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                />
            </div>

            <div>
                <label for="sim-distance" class="block text-xs font-medium text-[#14231d] mb-1.5">
                    Jarak Ultrasonik (cm)
                </label>
                <input
                    id="sim-distance"
                    type="number"
                    step="0.5"
                    min="0"
                    max="400"
                    name="distance_cm"
                    value="{{ old('distance_cm', $station->current_distance_cm) }}"
                    class="w-full rounded-xl border border-[#dcdcd6] px-3.5 py-2 text-sm text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                />
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full py-2.5 px-4 rounded-xl bg-[#187a5b] hover:bg-[#14664c] text-white text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
                >
                    Kirim Data Telemetry
                </button>
            </div>
        </form>
    </div>
@endsection
