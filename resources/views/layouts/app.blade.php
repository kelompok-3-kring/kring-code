<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard Station' }} — Helmet Care Dehumidifier Station</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#f4f4f0] text-[#14231d] antialiased selection:bg-[#187a5b] selection:text-white">
    <div class="min-h-screen flex flex-col lg:flex-row">
        {{-- Mobile Top Navigation Bar (< lg) --}}
        <header class="lg:hidden sticky top-0 z-40 bg-[#0d231c] text-white px-4 py-3.5 flex items-center justify-between shadow-md">
            <div>
                <a href="{{ route('dashboard') }}" class="block">
                    <span class="text-lg font-bold tracking-tight text-white block leading-tight">Helmet Care</span>
                    <span class="text-xs text-[#8ca89e] block">Dehumidifier Station</span>
                </a>
            </div>
            <button
                type="button"
                id="mobile-menu-toggle"
                aria-label="Buka menu navigasi"
                class="inline-flex items-center justify-center rounded-lg p-2 text-[#a7c2b8] hover:bg-[#16382d] hover:text-white focus:outline-none"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </header>

        {{-- Mobile Slide-Over Navigation Drawer --}}
        <div id="mobile-menu-backdrop" class="fixed inset-0 z-50 bg-black/50 hidden lg:hidden transition-opacity"></div>
        <aside
            id="mobile-sidebar"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-[#0d231c] text-white transform -translate-x-full transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto lg:w-60 xl:w-64 lg:shrink-0 flex flex-col justify-between"
        >
            <div class="p-5 lg:p-6">
                {{-- Brand Header --}}
                <div class="flex items-center justify-between mb-8">
                    <a href="{{ route('dashboard') }}" class="block">
                        <h1 class="text-xl font-bold tracking-tight text-white">Helmet Care</h1>
                        <p class="text-xs text-[#8aa399] mt-0.5">Dehumidifier Station</p>
                    </a>
                    <button
                        type="button"
                        id="mobile-menu-close"
                        aria-label="Tutup menu"
                        class="lg:hidden rounded-lg p-1.5 text-[#8aa399] hover:text-white hover:bg-[#16382d]"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Primary Navigation Links --}}
                @php
                    $navItems = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
                        ['label' => 'Riwayat Pengeringan', 'route' => 'history.index', 'active' => request()->routeIs('history.*')],
                        ['label' => 'Perangkat', 'route' => 'devices.index', 'active' => request()->routeIs('devices.*')],
                        ['label' => 'Pengaturan Ambang', 'route' => 'settings.index', 'active' => request()->routeIs('settings.*')],
                    ];
                @endphp

                <nav class="space-y-2" aria-label="Menu Utama">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            class="block px-4 py-3 rounded-xl text-sm font-medium transition-colors duration-150 {{ $item['active']
                                ? 'bg-[#187a5b] text-white font-semibold shadow-sm'
                                : 'text-[#b5c9c0] hover:bg-[#15352b] hover:text-white' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Footer MQTT & Hardware Info inside Sidebar --}}
            <div class="p-5 lg:p-6 border-t border-[#193b30] text-xs text-[#7c998e] space-y-1">
                <div class="flex items-center gap-2 text-[#a9c4b9] font-medium">
                    <span class="h-2 w-2 rounded-full bg-[#22c55e] animate-pulse"></span>
                    <span>MQTT Telemetry Ready</span>
                </div>
                <p class="truncate" title="{{ config('mqtt.topic_prefix') }}/{{ config('mqtt.default_station') }}">
                    Topic: {{ config('mqtt.topic_prefix') }}/station-01
                </p>
            </div>
        </aside>

        {{-- Main Content Column --}}
        <main class="flex-1 min-w-0 px-4 py-6 sm:px-6 lg:px-9 lg:py-8 pb-24 lg:pb-10">
            <div class="max-w-6xl mx-auto">
                {{-- Flash Notification Banner --}}
                @if (session('status'))
                    <div
                        role="alert"
                        class="mb-6 rounded-xl bg-[#ddf3e8] border border-[#b4e2cc] px-4 py-3 text-sm font-medium text-[#135e43] flex items-center justify-between shadow-xs"
                    >
                        <div class="flex items-center gap-2.5">
                            <svg class="h-5 w-5 text-[#187a5b] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        role="alert"
                        class="mb-6 rounded-xl bg-[#fee2e2] border border-[#fecaca] px-4 py-3 text-sm text-[#991b1b]"
                    >
                        <p class="font-semibold mb-1">Periksa kembali input pengaturan:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        {{-- Mobile Bottom Quick Navigation Bar (< lg) --}}
        <nav
            aria-label="Navigasi Bawah Mobile"
            class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-[#0d231c] border-t border-[#1c3f33] px-2 py-1.5 grid grid-cols-4 gap-1 text-center"
        >
            @foreach ($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="py-2 px-1 rounded-lg text-[11px] font-medium truncate transition-colors {{ $item['active']
                        ? 'bg-[#187a5b] text-white font-semibold'
                        : 'text-[#a3bcb2] hover:text-white' }}"
                >
                    {{ $item['label'] === 'Riwayat Pengeringan' ? 'Riwayat' : ($item['label'] === 'Pengaturan Ambang' ? 'Ambang' : $item['label']) }}
                </a>
            @endforeach
        </nav>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('mobile-menu-toggle');
            const closeBtn = document.getElementById('mobile-menu-close');
            const sidebar = document.getElementById('mobile-sidebar');
            const backdrop = document.getElementById('mobile-menu-backdrop');

            const openMenu = () => {
                sidebar?.classList.remove('-translate-x-full');
                backdrop?.classList.remove('hidden');
            };

            const closeMenu = () => {
                sidebar?.classList.add('-translate-x-full');
                backdrop?.classList.add('hidden');
            };

            toggleBtn?.addEventListener('click', openMenu);
            closeBtn?.addEventListener('click', closeMenu);
            backdrop?.addEventListener('click', closeMenu);
        });
    </script>
    @stack('scripts')
</body>
</html>
