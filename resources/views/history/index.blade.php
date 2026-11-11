@extends('layouts.app', ['title' => 'Riwayat Pengeringan'])

@section('content')
    {{-- Page Header --}}
    <div class="mb-5">
        <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">Riwayat Pengeringan</h2>
        <p class="text-sm text-[#6e7773] mt-0.5">
            Semua siklus pengeringan yang tercatat oleh station
        </p>
    </div>

    {{-- Filter Controls & Export CSV Button --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
        <form
            method="GET"
            action="{{ route('history.index') }}"
            class="flex flex-wrap items-end gap-3"
        >
            <div>
                <label for="filter-range" class="block text-xs font-medium text-[#6e7773] mb-1.5">
                    Rentang tanggal
                </label>
                <select
                    id="filter-range"
                    name="range"
                    onchange="this.form.submit()"
                    class="rounded-xl bg-white border border-[#dcdcd6] px-3.5 py-2 text-xs sm:text-sm font-medium text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                >
                    <option value="7d" @selected($selectedRange === '7d')>7 hari terakhir</option>
                    <option value="30d" @selected($selectedRange === '30d')>30 hari terakhir</option>
                    <option value="all" @selected($selectedRange === 'all')>Semua waktu</option>
                </select>
            </div>

            <div>
                <label for="filter-status" class="block text-xs font-medium text-[#6e7773] mb-1.5">
                    Status
                </label>
                <select
                    id="filter-status"
                    name="status"
                    onchange="this.form.submit()"
                    class="rounded-xl bg-white border border-[#dcdcd6] px-3.5 py-2 text-xs sm:text-sm font-medium text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                >
                    <option value="all" @selected($selectedStatus === 'all')>Semua</option>
                    <option value="running" @selected($selectedStatus === 'running')>Berjalan</option>
                    <option value="completed" @selected($selectedStatus === 'completed')>Selesai</option>
                </select>
            </div>
        </form>

        <div>
            <a
                href="{{ route('history.export', ['range' => $selectedRange, 'status' => $selectedStatus]) }}"
                class="inline-flex items-center justify-center rounded-xl bg-[#11261f] hover:bg-[#19382e] text-white text-xs sm:text-sm font-semibold px-4 py-2.5 transition-colors shadow-xs"
            >
                Ekspor CSV
            </a>
        </div>
    </div>

    {{-- Row 1: 3 KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Total Siklus (7 hari)</p>
            <p class="mt-2 text-4xl font-bold tracking-tight text-[#14231d]">{{ $totalCycles }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Rata-rata Durasi</p>
            <div class="mt-2 flex items-baseline gap-1.5 text-[#14231d]">
                <span class="text-4xl font-bold tracking-tight">{{ $avgDuration }}</span>
                <span class="text-lg font-bold">mnt</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <p class="text-xs font-medium text-[#6e7773]">Rata-rata Penurunan RH</p>
            <div class="mt-2 flex items-baseline gap-1.5 text-[#14231d]">
                <span class="text-4xl font-bold tracking-tight">{{ $avgRhDrop }}</span>
                <span class="text-lg font-bold">poin</span>
            </div>
        </div>
    </div>

    {{-- Row 2: Durasi Pengeringan per Hari (Bar Chart) --}}
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)] mb-5">
        <h3 class="text-base font-bold text-[#14231d] mb-6">Durasi Pengeringan per Hari</h3>

        @php
            $maxDuration = max(45, (int) $chartCycles->max('duration_minutes'));
        @endphp

        <div class="overflow-x-auto">
            <div class="min-w-[480px] pt-4 pb-2 px-2 sm:px-8">
                <div class="h-52 flex items-end justify-between gap-4 sm:gap-8 border-b border-[#dcdcd6] px-4">
                    @foreach ($chartCycles as $barCycle)
                        @php
                            $heightPercent = max(12, min(100, (int) round(($barCycle->duration_minutes / $maxDuration) * 100)));
                            $isRunning = $barCycle->status === 'running';
                        @endphp
                        <div class="flex-1 flex flex-col items-center justify-end h-full">
                            <span class="text-xs sm:text-sm font-medium text-[#2c3a35] mb-2">
                                {{ $barCycle->duration_minutes }}
                            </span>
                            <div
                                class="w-full max-w-[54px] rounded-t-md transition-all duration-300 {{ $isRunning ? 'bg-[#b45309]' : 'bg-[#166e55]' }}"
                                style="height: {{ $heightPercent }}%"
                                title="{{ $barCycle->short_date }}: {{ $barCycle->duration_minutes }} menit ({{ $barCycle->status_label }})"
                            ></div>
                        </div>
                    @endforeach
                </div>

                {{-- X-Axis Day Labels --}}
                <div class="flex items-center justify-between gap-4 sm:gap-8 px-4 pt-3">
                    @foreach ($chartCycles as $barCycle)
                        <div class="flex-1 text-center text-xs sm:text-sm text-[#4e5955]">
                            {{ $barCycle->short_date }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="mt-5 text-xs text-[#6e7773]">
            Menit per hari. Batang oranye = siklus yang masih berjalan.
        </p>
    </div>

    {{-- Row 3: Paginated History Table --}}
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
        <div class="overflow-x-auto -mx-5 px-5 sm:mx-0 sm:px-0">
            <table class="w-full text-left border-collapse min-w-[640px]">
                <thead>
                    <tr class="border-b border-[#ecece7] text-xs font-semibold text-[#6e7773]">
                        <th class="py-3 pr-4">Tanggal</th>
                        <th class="py-3 px-4">Mulai</th>
                        <th class="py-3 px-4">Selesai</th>
                        <th class="py-3 px-4">Durasi</th>
                        <th class="py-3 px-4">RH Awal &rarr; Akhir</th>
                        <th class="py-3 px-4">Suhu Rata-rata</th>
                        <th class="py-3 pl-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f1f1ec] text-xs sm:text-sm text-[#14231d]">
                    @forelse ($cycles as $cycle)
                        <tr class="hover:bg-[#fafaf7] transition-colors">
                            <td class="py-3.5 pr-4 font-medium">{{ $cycle->short_date }}</td>
                            <td class="py-3.5 px-4">{{ $cycle->start_time }}</td>
                            <td class="py-3.5 px-4">{{ $cycle->end_time }}</td>
                            <td class="py-3.5 px-4">{{ $cycle->duration_minutes }} mnt</td>
                            <td class="py-3.5 px-4">
                                {{ number_format($cycle->initial_humidity, 0) }}% &rarr; {{ number_format((float) ($cycle->final_humidity ?? $station->current_humidity), 0) }}%
                            </td>
                            <td class="py-3.5 px-4">{{ number_format($cycle->avg_temperature, 1, ',', '.') }} &deg;C</td>
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
                            <td colspan="7" class="py-8 text-center text-sm text-[#6e7773]">
                                Tidak ada data siklus pengeringan yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Pagination Footer --}}
        <div class="mt-5 pt-3 border-t border-[#f1f1ec] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-[#6e7773]">
                Menampilkan {{ $cycles->firstItem() ?? 0 }}–{{ $cycles->lastItem() ?? 0 }} dari {{ $cycles->total() }}
            </p>

            <div class="flex items-center gap-2">
                @if ($cycles->onFirstPage())
                    <span class="px-4 py-2 rounded-xl bg-[#ededeb] text-[#8a918e] text-xs font-semibold cursor-not-allowed">
                        Sebelumnya
                    </span>
                @else
                    <a
                        href="{{ $cycles->previousPageUrl() }}"
                        class="px-4 py-2 rounded-xl bg-[#ededeb] hover:bg-[#e2e2de] text-[#14231d] text-xs font-semibold transition-colors"
                    >
                        Sebelumnya
                    </a>
                @endif

                @if ($cycles->hasMorePages())
                    <a
                        href="{{ $cycles->nextPageUrl() }}"
                        class="px-4 py-2 rounded-xl bg-[#187a5b] hover:bg-[#14664c] text-white text-xs font-semibold transition-colors"
                    >
                        Berikutnya
                    </a>
                @else
                    <span class="px-4 py-2 rounded-xl bg-[#ededeb] text-[#8a918e] text-xs font-semibold cursor-not-allowed">
                        Berikutnya
                    </span>
                @endif
            </div>
        </div>
    </div>
@endsection
