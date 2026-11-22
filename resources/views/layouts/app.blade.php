<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="500x500" href="{{ asset('images/logo-rounded.png') }}">
    <link rel="apple-touch-icon" sizes="500x500" href="{{ asset('images/logo-rounded.png') }}">
    <title>KRING: {{ $title ?? 'Dashboard Station' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full text-[#14231d] antialiased selection:bg-[#187a5b] selection:text-white">
    <div class="min-h-screen flex flex-col lg:flex-row">
        {{-- Mobile Top Navigation Bar (< lg) --}}
        <header class="lg:hidden sticky top-0 z-40 border-b border-white/5 bg-[#10261f]/95 px-4 py-3.5 flex items-center justify-between shadow-lg backdrop-blur-xl">
            <div>
                <a href="{{ route('dashboard') }}" class="block">
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo KRING" class="h-9 w-9 rounded-xl object-contain">
                        <span>
                            <span class="text-base font-bold tracking-tight text-white block leading-tight">Helmet Care</span>
                            <span class="text-[11px] text-[#91aca1] block">Dehumidifier Station</span>
                        </span>
                    </div>
                </a>
            </div>
            <button
                type="button"
                id="mobile-menu-toggle"
                aria-label="Buka menu navigasi"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-[#b2c6be] hover:bg-white/10 hover:text-white focus:outline-none"
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
            class="app-sidebar fixed inset-y-0 left-0 z-50 w-64 text-white transform -translate-x-full transition-transform duration-200 ease-in-out lg:translate-x-0 lg:w-60 xl:w-64 lg:h-screen lg:overflow-y-auto lg:shrink-0 flex flex-col justify-between"
        >
            <div class="p-5 lg:p-6">
                {{-- Brand Header --}}
                <div class="sidebar-brand mb-7 rounded-2xl px-3.5 py-3 flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo KRING" class="h-11 w-11 rounded-[14px] object-contain">
                        <span>
                            <h1 class="text-lg font-bold tracking-[0.08em] text-white">KRING</h1>
                            <p class="text-[11px] text-[#91aca1] mt-0.5">Dehumidifier Station</p>
                        </span>
                    </a>
                    <button
                        type="button"
                        id="mobile-menu-close"
                        aria-label="Tutup menu"
                        class="lg:hidden rounded-lg p-1.5 text-[#8aa399] hover:text-white hover:bg-white/10"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Primary Navigation Links --}}
                @php
                    $navItems = [
                        [
                            'label' => 'Dashboard',
                            'route' => 'dashboard',
                            'active' => request()->routeIs('dashboard'),
                            'icon' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>',
                        ],
                        [
                            'label' => 'Riwayat Pengeringan',
                            'route' => 'history.index',
                            'active' => request()->routeIs('history.*'),
                            'icon' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 109-9"/><path d="M3 4v5h5M12 7v5l3 2"/></svg>',
                        ],
                        [
                            'label' => 'Perangkat',
                            'route' => 'devices.index',
                            'active' => request()->routeIs('devices.*'),
                            'icon' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="5" width="14" height="14" rx="3"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/></svg>',
                        ],
                    ];
                @endphp

                <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-[0.18em] text-[#78978b]">Menu Utama</p>
                <nav class="space-y-2" aria-label="Menu Utama">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            class="sidebar-nav-link flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium {{ $item['active']
                                ? 'active text-white font-semibold'
                                : 'text-[#b5c9c0] hover:bg-[#15352b] hover:text-white' }}"
                        >
                            <span class="sidebar-nav-icon">{!! $item['icon'] !!}</span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- Main Content Column --}}
        <main class="flex-1 min-w-0 px-4 py-6 sm:px-6 lg:ml-60 lg:px-9 lg:py-8 xl:ml-64 pb-24 lg:pb-10">
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

                @if (session('warning'))
                    <div
                        role="alert"
                        class="mb-6 rounded-xl bg-[#fff7ed] border border-[#fed7aa] px-4 py-3 text-sm font-medium text-[#9a3412]"
                    >
                        {{ session('warning') }}
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
            class="mobile-bottom-nav lg:hidden fixed bottom-0 inset-x-0 z-30 border-t border-white/8 px-2 py-1.5 grid grid-cols-3 gap-1 text-center"
        >
            @foreach ($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="mobile-bottom-link py-2 px-1 rounded-xl text-[11px] font-medium truncate {{ $item['active']
                        ? 'active text-white font-semibold'
                        : 'text-[#a3bcb2] hover:text-white' }}"
                >
                    {{ $item['label'] === 'Riwayat Pengeringan' ? 'Riwayat' : $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    @stack('scripts')
</body>
</html>
