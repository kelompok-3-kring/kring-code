@extends('layouts.app', ['title' => 'Perangkat'])

@section('content')
    {{-- Page Header --}}
    <div class="page-header mb-6 rounded-3xl px-5 py-5 sm:px-6">
        <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">Perangkat</h2>
        <p class="text-sm text-[#6e7773] mt-0.5">Status komponen, konektivitas, dan pengaturan station</p>
    </div>

    {{-- Row 1: 4 Hardware Component Status Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
        {{-- Card 1: Sensor Ultrasonik HC-SR04 --}}
        <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="amber">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-medium text-[#6e7773]">Sensor Ultrasonik HC-SR04</p>
            </div>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">
                {{ $station->status === 'offline' ? 'Tidak diketahui' : ($station->helmet_detected ? 'Helm terdeteksi' : 'Tidak ada helm') }}
            </p>
        </div>

        {{-- Card 2: Sensor DHT22 --}}
        <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="blue">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-medium text-[#6e7773]">Sensor DHT22</p>
            </div>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">
                {{ $station->status === 'offline'
                    ? 'Tidak diketahui'
                    : number_format($station->current_humidity, 1, ',', '.').'% RH' }}
            </p>
        </div>

        {{-- Card 3: Modul Relay (Kipas) --}}
        <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="rose">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-medium text-[#6e7773]">Modul Relay (Kipas)</p>
            </div>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight {{ $station->status === 'offline' ? 'text-[#6e7773]' : ($station->fan_is_on ? 'text-[#c93b2b]' : 'text-[#156d4f]') }}">
                {{ $station->status === 'offline' ? 'Tidak diketahui' : ($station->fan_is_on ? 'Menyala' : 'Mati') }}
            </p>
        </div>

        {{-- Card 4: Lampu LED --}}
        <div class="sensor-card dashboard-card rounded-2xl p-5" data-accent="green">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-medium text-[#6e7773]">Lampu LED</p>
            </div>
            <p class="mt-2 text-2xl sm:text-[26px] font-bold tracking-tight {{ $station->led_status === 'red_blinking'
                ? 'text-[#c93b2b]'
                : ($station->led_status === 'green_solid' ? 'text-[#156d4f]' : 'text-[#6e7773]') }}">
                {{ $station->led_label }}
            </p>
        </div>
    </div>

    {{-- Row 2: Koneksi & Aktivitas Perangkat --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        {{-- Left Card: Koneksi --}}
        <div class="content-card rounded-2xl p-5 sm:p-6">
            <h3 class="text-base font-bold text-[#14231d] mb-4">Koneksi</h3>

            <dl class="space-y-3.5 text-xs sm:text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-[#6e7773]">Jaringan</dt>
                    <dd class="font-bold text-[#14231d] text-right">{{ $station->wifi_ssid ?: 'Tidak diketahui' }}</dd>
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
                    <dd class="font-bold text-[#14231d] text-right">{{ $station->firmware_version ?: 'Belum terhubung' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Right Card: Aktivitas Perangkat --}}
        <div class="content-card rounded-2xl p-5 sm:p-6">
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

    @include('settings._content')
@endsection
