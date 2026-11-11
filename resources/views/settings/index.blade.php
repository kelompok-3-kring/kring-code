@extends('layouts.app', ['title' => 'Pengaturan Ambang'])

@section('content')
    {{-- Page Header --}}
    <div class="mb-6">
        <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">Pengaturan Ambang</h2>
        <p class="text-sm text-[#6e7773] mt-0.5">
            Atur kapan kipas menyala dan berhenti
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
        {{-- Left Card: Ambang Sensor Form --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <h3 class="text-base font-bold text-[#14231d] mb-5">Ambang Sensor</h3>

            <form id="threshold-settings-form" method="POST" action="{{ route('settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Input 1: Kipas menyala di atas (% RH) --}}
                <div>
                    <label for="fan_on_humidity" class="block text-xs sm:text-sm font-medium text-[#14231d] mb-1.5">
                        Kipas menyala di atas (% RH)
                    </label>
                    <input
                        id="fan_on_humidity"
                        type="number"
                        name="fan_on_humidity"
                        min="30"
                        max="95"
                        required
                        value="{{ old('fan_on_humidity', $station->fan_on_humidity) }}"
                        class="w-full rounded-xl border border-[#dcdcd6] px-3.5 py-2.5 text-sm font-medium text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">
                        Relay aktif saat kelembapan melewati nilai ini.
                    </p>
                </div>

                {{-- Input 2: Kipas mati di bawah (% RH) --}}
                <div>
                    <label for="fan_off_humidity" class="block text-xs sm:text-sm font-medium text-[#14231d] mb-1.5">
                        Kipas mati di bawah (% RH)
                    </label>
                    <input
                        id="fan_off_humidity"
                        type="number"
                        name="fan_off_humidity"
                        min="20"
                        max="90"
                        required
                        value="{{ old('fan_off_humidity', $station->fan_off_humidity) }}"
                        class="w-full rounded-xl border border-[#dcdcd6] px-3.5 py-2.5 text-sm font-medium text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">
                        Siklus dianggap selesai dan dicatat ke log.
                    </p>
                </div>

                {{-- Input 3: Jarak deteksi helm (cm) --}}
                <div>
                    <label for="detection_distance_cm" class="block text-xs sm:text-sm font-medium text-[#14231d] mb-1.5">
                        Jarak deteksi helm (cm)
                    </label>
                    <input
                        id="detection_distance_cm"
                        type="number"
                        name="detection_distance_cm"
                        min="2"
                        max="100"
                        required
                        value="{{ old('detection_distance_cm', $station->detection_distance_cm) }}"
                        class="w-full rounded-xl border border-[#dcdcd6] px-3.5 py-2.5 text-sm font-medium text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">
                        Sistem aktif jika jarak ultrasonik lebih kecil.
                    </p>
                </div>

                {{-- Input 4: Interval kirim data (detik) --}}
                <div>
                    <label for="data_interval_seconds" class="block text-xs sm:text-sm font-medium text-[#14231d] mb-1.5">
                        Interval kirim data (detik)
                    </label>
                    <input
                        id="data_interval_seconds"
                        type="number"
                        name="data_interval_seconds"
                        min="1"
                        max="300"
                        required
                        value="{{ old('data_interval_seconds', $station->data_interval_seconds) }}"
                        class="w-full rounded-xl border border-[#dcdcd6] px-3.5 py-2.5 text-sm font-medium text-[#14231d] focus:outline-none focus:border-[#187a5b]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">
                        Seberapa sering ESP32 mengirim data ke server.
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-2 flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#187a5b] hover:bg-[#14664c] text-white text-xs sm:text-sm font-semibold transition-colors cursor-pointer shadow-xs"
                    >
                        Simpan Perubahan
                    </button>

                    <button
                        type="submit"
                        form="reset-thresholds-form"
                        class="px-5 py-2.5 rounded-xl bg-[#ededeb] hover:bg-[#e2e2de] text-[#14231d] text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
                    >
                        Kembalikan Default
                    </button>
                </div>
            </form>

            {{-- Separate POST form for Kembalikan Default --}}
            <form id="reset-thresholds-form" method="POST" action="{{ route('settings.reset') }}" class="hidden">
                @csrf
            </form>
        </div>

        {{-- Right Card: Perilaku LED & Mode Operasi --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#e9e9e4] shadow-[0_1px_2px_rgba(0,0,0,0.02)] space-y-7">
            {{-- Perilaku LED Section --}}
            <div>
                <h3 class="text-base font-bold text-[#14231d] mb-4">Perilaku LED</h3>

                <ul class="space-y-3 text-xs sm:text-sm">
                    <li class="flex items-center gap-3">
                        <span class="h-3.5 w-3.5 rounded-full bg-[#c93b2b] shrink-0"></span>
                        <span>
                            <strong class="font-bold text-[#14231d]">Merah berkedip</strong>
                            <span class="text-[#6e7773]">&middot; sedang mengeringkan</span>
                        </span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="h-3.5 w-3.5 rounded-full bg-[#187a5b] shrink-0"></span>
                        <span>
                            <strong class="font-bold text-[#14231d]">Hijau menyala</strong>
                            <span class="text-[#6e7773]">&middot; helm kering, siap dipakai</span>
                        </span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="h-3.5 w-3.5 rounded-full bg-[#8e9592] shrink-0"></span>
                        <span>
                            <strong class="font-bold text-[#14231d]">Mati</strong>
                            <span class="text-[#6e7773]">&middot; standby, tidak ada helm</span>
                        </span>
                    </li>
                </ul>
            </div>

            {{-- Mode Operasi Section --}}
            <div>
                <h3 class="text-base font-bold text-[#14231d] mb-4">Mode Operasi</h3>

                <div class="space-y-3.5 text-xs sm:text-sm">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            name="auto_mode"
                            value="1"
                            form="threshold-settings-form"
                            @checked(old('auto_mode', $station->auto_mode))
                            class="h-4 w-4 rounded border-[#dcdcd6] text-[#2563eb] focus:ring-[#2563eb]"
                        />
                        <span class="text-[#14231d]">Otomatis (kipas mengikuti ambang)</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            name="notify_on_complete"
                            value="1"
                            form="threshold-settings-form"
                            @checked(old('notify_on_complete', $station->notify_on_complete))
                            class="h-4 w-4 rounded border-[#dcdcd6] text-[#2563eb] focus:ring-[#2563eb]"
                        />
                        <span class="text-[#14231d]">Kirim notifikasi saat helm selesai kering</span>
                    </label>
                </div>

                {{-- Hysteresis Callout Box --}}
                <div class="mt-5 rounded-xl bg-[#f4f4f0] p-4 text-xs leading-relaxed text-[#6e7773]">
                    Jarak antara ambang nyala dan mati mencegah kipas hidup-mati berulang di sekitar satu nilai.
                </div>
            </div>
        </div>
    </div>
@endsection
