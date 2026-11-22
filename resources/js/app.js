const onReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
};

onReady(() => {
    initMobileMenu();
    initDashboard();
    initThresholdSettings();
});

function initMobileMenu() {
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const closeBtn = document.getElementById('mobile-menu-close');
    const sidebar = document.getElementById('mobile-sidebar');
    const backdrop = document.getElementById('mobile-menu-backdrop');

    if (!toggleBtn || !sidebar || toggleBtn.dataset.initialized === 'true') {
        return;
    }

    toggleBtn.dataset.initialized = 'true';

    const openMenu = () => {
        sidebar.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
        toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');
    };

    const closeMenu = () => {
        sidebar.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
        toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('overflow-hidden');
    };

    toggleBtn.setAttribute('aria-expanded', 'false');
    toggleBtn.addEventListener('click', openMenu);
    closeBtn?.addEventListener('click', closeMenu);
    backdrop?.addEventListener('click', closeMenu);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
}

function setClassName(element, classes) {
    if (element) {
        element.className = classes;
    }
}

function initDashboard() {
    const dashboard = document.querySelector('[data-dashboard-root]');
    if (!dashboard) {
        return;
    }

    const mapHumidityToY = (rh) => {
        const clamped = Math.max(20, Math.min(100, Number(rh)));
        return (200 - ((clamped - 20) / (100 - 20)) * 176).toFixed(1);
    };

    let intervalMs = Math.max(2000, (Number(dashboard.dataset.pollInterval) || 2) * 1000);
    let timer = null;
    let inFlight = false;
    let consecutiveFailures = 0;
    let abortController = null;

    const syncStatus = document.getElementById('live-sync-status');
    const setSyncStatus = (kind, text) => {
        if (!syncStatus) return;
        syncStatus.textContent = text;
        setClassName(syncStatus, kind === 'ok'
            ? 'inline-flex items-center rounded-full bg-[#dcf3e7] px-2.5 py-1 text-xs font-semibold text-[#156d4f]'
            : kind === 'loading'
                ? 'inline-flex items-center rounded-full bg-[#e5e7eb] px-2.5 py-1 text-xs font-semibold text-[#4b5563]'
                : 'inline-flex items-center rounded-full bg-[#fee2e2] px-2.5 py-1 text-xs font-semibold text-[#991b1b]');
    };

    const updateThresholdChart = (station) => {
        if (!station) return;
        const fanOn = Number(station.fan_on_humidity);
        const fanOff = Number(station.fan_off_humidity);
        if (!Number.isFinite(fanOn) || !Number.isFinite(fanOff)) return;

        const yOn = mapHumidityToY(fanOn);
        const yOff = mapHumidityToY(fanOff);

        document.querySelectorAll('[data-fan-on-label]').forEach((element) => {
            element.textContent = `Batas nyala ${fanOn}%`;
        });
        document.querySelectorAll('[data-fan-off-label]').forEach((element) => {
            element.textContent = `Batas kering ${fanOff}%`;
        });
        document.querySelectorAll('[data-fan-on-y-label]').forEach((element) => {
            element.textContent = `${fanOn}%`;
            element.setAttribute('y', String(Number(yOn) + 4));
        });
        document.querySelectorAll('[data-fan-off-y-label]').forEach((element) => {
            element.textContent = `${fanOff}%`;
            element.setAttribute('y', String(Number(yOff) + 4));
        });

        document.querySelectorAll('[data-fan-on-line], [data-fan-on-label]').forEach((element) => {
            if (element.hasAttribute('y1')) {
                element.setAttribute('y1', yOn);
                element.setAttribute('y2', yOn);
            }
        });
        document.querySelectorAll('[data-fan-on-label]').forEach((element) => {
            if (element.hasAttribute('y')) {
                element.setAttribute('y', String(Number(yOn) - 7));
            }
        });
        document.querySelectorAll('[data-fan-off-line]').forEach((element) => {
            element.setAttribute('y1', yOff);
            element.setAttribute('y2', yOff);
        });
        document.querySelectorAll('[data-fan-off-label]').forEach((element) => {
            if (element.hasAttribute('y')) {
                element.setAttribute('y', String(Number(yOff) + 15));
            }
        });
    };

    const pollLiveState = async () => {
        if (inFlight || document.hidden) return;

        inFlight = true;
        setSyncStatus('loading', 'Memperbarui...');
        abortController = new AbortController();

        try {
            const stateUrl = new URL(dashboard.dataset.stateUrl, window.location.origin);
            stateUrl.searchParams.set('_', String(Date.now()));
            const response = await fetch(stateUrl.toString(), {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
                signal: abortController.signal,
            });
            if (!response.ok) throw new Error(`Polling gagal (${response.status})`);

            const data = await response.json();
            const station = data.station;
            if (!station) throw new Error('Respons station tidak tersedia');

            const humidityEl = document.getElementById('live-humidity');
            if (humidityEl) humidityEl.textContent = station.status === 'offline' ? '—' : String(station.current_humidity);
            const temperatureEl = document.getElementById('live-temperature');
            if (temperatureEl) temperatureEl.textContent = station.status === 'offline' ? '—' : String(station.current_temperature);
            const distanceEl = document.getElementById('live-distance');
            if (distanceEl) {
                distanceEl.textContent = station.status === 'offline'
                    ? 'Tidak diketahui'
                    : `${station.current_distance_cm} cm`;
            }

            const fanEl = document.getElementById('live-fan-state');
            if (fanEl) {
                fanEl.textContent = station.status === 'offline'
                    ? 'Tidak diketahui'
                    : (station.fan_is_on ? 'ON' : 'OFF');
                setClassName(fanEl, 'text-4xl font-bold tracking-tight ' +
                    (station.status === 'offline'
                        ? 'text-[#6e7773]'
                        : (station.fan_is_on ? 'text-[#c93b2b]' : 'text-[#156d4f]')));
            }

            const fanButton = document.getElementById('fan-toggle-button');
            if (fanButton) {
                fanButton.disabled = station.status === 'offline';
                fanButton.textContent = station.status === 'offline'
                    ? 'Perangkat Offline'
                    : (station.fan_is_on ? 'Matikan Kipas Manual' : 'Nyalakan Kipas Manual');
            }

            const progressEl = document.getElementById('live-progress-bar');
            if (progressEl) progressEl.style.width = `${station.progress_percent}%`;

            const statusEl = document.getElementById('badge-mcu-status');
            if (statusEl) {
                const mcuName = statusEl.dataset.mcuName || 'Perangkat';
                statusEl.textContent = `${mcuName} ${station.status === 'online' ? 'Online' : 'Offline'}`;
                setClassName(statusEl, 'inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold ' +
                    (station.status === 'online'
                        ? 'bg-[#dcf3e7] text-[#156d4f]'
                        : 'bg-[#fee2e2] text-[#991b1b]'));
            }

            const dryingEl = document.getElementById('badge-drying-state');
            if (dryingEl) {
                dryingEl.textContent = station.drying_state_label;
                setClassName(dryingEl, 'inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold ' +
                    (station.drying_state === 'drying'
                        ? 'bg-[#faeccf] text-[#9a5b13]'
                        : station.drying_state === 'completed'
                            ? 'bg-[#dcf3e7] text-[#156d4f]'
                            : 'bg-[#e5e7eb] text-[#4b5563]'));
            }

            const cycle = data.active_cycle;
            const startEl = document.getElementById('cycle-start-time');
            const durationEl = document.getElementById('cycle-duration');
            const initialEl = document.getElementById('cycle-initial-rh');
            const modeEl = document.getElementById('cycle-mode');
            if (startEl) startEl.textContent = cycle?.started_at || '—';
            if (durationEl) durationEl.textContent = cycle?.duration_minutes ?? 0;
            if (initialEl) initialEl.textContent = cycle ? `${cycle.initial_humidity}%` : '—';
            if (modeEl) modeEl.textContent = cycle?.mode || (station.auto_mode ? 'Otomatis' : 'Manual');

            const ledEl = document.getElementById('cycle-led-label');
            if (ledEl) {
                ledEl.textContent = station.led_label;
                setClassName(ledEl, 'font-bold ' +
                    (station.led_status === 'red_blinking' ? 'text-[#c93b2b]' : 'text-[#156d4f]'));
            }

            const intervalEl = document.getElementById('live-interval');
            if (intervalEl) intervalEl.textContent = station.data_interval_seconds;
            const nextInterval = Math.max(2000, (Number(station.data_interval_seconds) || 2) * 1000);
            if (nextInterval !== intervalMs) {
                intervalMs = nextInterval;
                schedulePoll();
            }

            updateThresholdChart(station);

            if (Array.isArray(data.readings) && data.readings.length > 0) {
                const count = data.readings.length;
                const points = data.readings.map((reading, index) => ({
                    x: count > 1 ? (55 + (index / (count - 1)) * 490).toFixed(1) : '300',
                    y: mapHumidityToY(reading.humidity),
                }));
                document.getElementById('humidity-curve-line')?.setAttribute(
                    'points',
                    points.map((point) => `${point.x},${point.y}`).join(' ')
                );
                const dot = document.getElementById('humidity-curve-dot');
                if (dot && points.length > 0) {
                    const last = points[points.length - 1];
                    dot.setAttribute('cx', last.x);
                    dot.setAttribute('cy', last.y);
                }
            }

            consecutiveFailures = 0;
            setSyncStatus('ok', 'Sinkron');
        } catch (error) {
            if (error.name !== 'AbortError') {
                consecutiveFailures += 1;
                setSyncStatus('error', consecutiveFailures > 1
                    ? `Data terputus (${consecutiveFailures}x gagal)`
                    : 'Data terputus');
            }
        } finally {
            inFlight = false;
        }
    };

    const schedulePoll = () => {
        if (timer) window.clearInterval(timer);
        timer = window.setInterval(() => {
            if (!document.hidden) pollLiveState();
        }, intervalMs);
    };

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            abortController?.abort();
            if (timer) window.clearInterval(timer);
            timer = null;
        } else {
            pollLiveState();
            schedulePoll();
        }
    });

    const fanForm = document.getElementById('fan-toggle-form');
    if (fanForm) {
        fanForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = fanForm.querySelector('button[type="submit"]');
            if (!button || button.disabled) return;

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Memproses...';
            button.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(fanForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': fanForm.querySelector('[name="_token"]')?.value || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Perintah kipas gagal dijalankan');
                if (payload.mqtt_published === false) throw new Error(payload.message || 'Kipas tersimpan, tetapi MQTT gagal');

                button.textContent = payload.fan_is_on ? 'Matikan Kipas Manual' : 'Nyalakan Kipas Manual';
                await pollLiveState();
            } catch (error) {
                setSyncStatus('error', error.message || 'Perintah kipas gagal');
                button.textContent = originalText;
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    }

    pollLiveState();
    schedulePoll();
}

function initThresholdSettings() {
    const form = document.getElementById('threshold-settings-form');
    if (!form || form.dataset.initialized === 'true') return;
    form.dataset.initialized = 'true';

    const submitButton = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', (event) => {
        const fanOn = Number(form.elements.fan_on_humidity.value);
        const fanOff = Number(form.elements.fan_off_humidity.value);
        if (fanOn <= fanOff) {
            form.elements.fan_on_humidity.setCustomValidity('Ambang nyala harus lebih besar daripada ambang mati.');
            form.elements.fan_on_humidity.reportValidity();
            event.preventDefault();
            return;
        }
        form.elements.fan_on_humidity.setCustomValidity('');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
        }
    });

    ['fan_on_humidity', 'fan_off_humidity'].forEach((name) => {
        form.elements[name]?.addEventListener('input', () => {
            form.elements.fan_on_humidity.setCustomValidity('');
        });
    });

    const resetButton = form.querySelector('[form="reset-thresholds-form"]');
    resetButton?.addEventListener('click', (event) => {
        if (!window.confirm('Kembalikan semua pengaturan ambang ke nilai pabrik?')) {
            event.preventDefault();
            return;
        }
        resetButton.disabled = true;
        resetButton.textContent = 'Mereset...';
    });
}
