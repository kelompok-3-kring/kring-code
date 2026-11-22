<div class="mt-8">
    <div class="page-header mb-6 rounded-3xl px-5 py-5 sm:px-6">
        <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#14231d]">Pengaturan Ambang</h2>
        <p class="text-sm text-[#6e7773] mt-0.5">
            Atur kapan kipas menyala dan berhenti
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
        <div class="settings-card rounded-2xl p-5 sm:p-6">
            <h3 class="text-base font-bold text-[#14231d] mb-5">Ambang Sensor</h3>

            <form id="threshold-settings-form" method="POST" action="{{ route('settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

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
                        class="settings-input w-full rounded-xl px-3.5 py-2.5 text-sm font-medium text-[#14231d]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">Relay aktif saat kelembapan melewati nilai ini.</p>
                </div>

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
                        class="settings-input w-full rounded-xl px-3.5 py-2.5 text-sm font-medium text-[#14231d]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">Siklus dianggap selesai dan dicatat ke log.</p>
                </div>

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
                        class="settings-input w-full rounded-xl px-3.5 py-2.5 text-sm font-medium text-[#14231d]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">Sistem aktif jika jarak ultrasonik lebih kecil.</p>
                </div>

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
                        class="settings-input w-full rounded-xl px-3.5 py-2.5 text-sm font-medium text-[#14231d]"
                    />
                    <p class="mt-1.5 text-xs text-[#6e7773]">Seberapa sering ESP8266 mengirim data ke server.</p>
                </div>

                <div class="pt-2 flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        class="primary-action px-5 py-2.5 rounded-xl text-white text-xs sm:text-sm font-semibold cursor-pointer"
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

            <form id="reset-thresholds-form" method="POST" action="{{ route('settings.reset') }}" class="hidden">
                @csrf
            </form>
        </div>

        <div class="settings-card rounded-2xl p-5 sm:p-6 space-y-7">
            <div>
                <h3 class="text-base font-bold text-[#14231d] mb-4">Perilaku LED</h3>
                <ul class="space-y-3 text-xs sm:text-sm">
                    <li class="flex items-center gap-3">
                        <span class="h-3.5 w-3.5 rounded-full bg-[#c93b2b] shrink-0"></span>
                        <span><strong class="font-bold text-[#14231d]">Merah</strong> <span class="text-[#6e7773]">&middot; sedang mengeringkan</span></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="h-3.5 w-3.5 rounded-full bg-[#187a5b] shrink-0"></span>
                        <span><strong class="font-bold text-[#14231d]">Hijau</strong> <span class="text-[#6e7773]">&middot; helm kering, siap dipakai</span></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="h-3.5 w-3.5 rounded-full bg-[#2563eb] shrink-0"></span>
                        <span><strong class="font-bold text-[#14231d]">Biru</strong> <span class="text-[#6e7773]">&middot; standby, tidak ada helm</span></span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>
