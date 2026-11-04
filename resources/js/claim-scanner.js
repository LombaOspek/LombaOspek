/**
 * Pemindai QR klaim untuk meja panitia.
 *
 * Kenapa ada pemindai di sini, padahal HP panitia sudah punya kamera:
 * di meja penyerahan panitia memegang laptop yang menampilkan daftar klaim.
 * Mengeluarkan HP, membuka kamera, memindai, lalu mencari barisnya lagi di
 * laptop adalah tiga langkah yang membentuk antrean. Pemindai ini memangkasnya
 * jadi satu: arahkan QR ke kamera laptop, halaman klaim terbuka sendiri.
 *
 * Memakai `BarcodeDetector` bawaan browser bila tersedia (Chrome/Edge di
 * Windows sudah punya). Kalau tidak tersedia, panel pemindai TIDAK
 * ditampilkan sama sekali — lebih baik tidak ada tombol daripada tombol yang
 * ditekan lalu diam saja. Input kode manual tetap bekerja sebagai jalan
 * terakhir.
 */

const PANEL = '[data-claim-scanner]';

export function initClaimScanner() {
    const panel = document.querySelector(PANEL);
    if (!panel) return;

    const video = panel.querySelector('[data-scanner-video]');
    const openBtn = panel.querySelector('[data-scanner-open]');
    const closeBtn = panel.querySelector('[data-scanner-close]');
    const status = panel.querySelector('[data-scanner-status]');
    const manualForm = panel.querySelector('[data-scanner-manual]');
    const manualInput = panel.querySelector('[data-scanner-code]');

    if (!video || !openBtn) return;

    const say = (text, tone = 'neutral') => {
        if (!status) return;
        status.textContent = text;
        status.dataset.tone = tone;
    };

    // Tanpa BarcodeDetector, jangan tawarkan tombol yang tidak bisa bekerja.
    if (!('BarcodeDetector' in window)) {
        openBtn.hidden = true;
        say('Pemindai kamera tidak didukung browser ini. Masukkan kode secara manual.', 'warn');
        return;
    }

    let stream = null;
    let raf = 0;
    let detector = null;

    const stop = () => {
        cancelAnimationFrame(raf);
        raf = 0;

        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            stream = null;
        }

        video.srcObject = null;
        video.hidden = true;
        if (closeBtn) closeBtn.hidden = true;
        if (openBtn) openBtn.hidden = false;
    };

    /**
     * Payload QR adalah URL verifikasi. Yang dibutuhkan halaman ini hanya kode
     * di segmen terakhir, tetapi kode manual juga diterima apa adanya supaya
     * satu fungsi bisa menangani kedua bentuk.
     */
    const extractCode = (raw) => {
        const text = String(raw || '').trim();
        if (!text) return null;

        // OQ-XXXX-XXXX (boleh dengan spasi/huruf kecil)
        const direct = text.toUpperCase().match(/OQ-[A-Z0-9]{4}-[A-Z0-9]{4}/);
        if (direct) return direct[0];

        try {
            const url = new URL(text);
            const last = url.pathname.split('/').filter(Boolean).pop();
            if (last && /^[A-Za-z0-9-]{4,}$/.test(last)) return last.toUpperCase();
        } catch {
            // bukan URL, biarkan gagal di bawah
        }

        return null;
    };

    const onDetected = (raw) => {
        const code = extractCode(raw);
        if (!code) {
            say('QR terbaca tetapi bukan kode klaim OspekQuest.', 'warn');
            return;
        }

        say(`Klaim ${code} ditemukan, membuka…`, 'ok');
        stop();
        window.location.href = `/klaim/${encodeURIComponent(code)}`;
    };

    const scanLoop = async () => {
        if (!stream) return;

        try {
            const found = await detector.detect(video);
            if (found.length > 0) {
                onDetected(found[0].rawValue);
                return;
            }
        } catch {
            // Bingkai gagal sesekali itu normal saat kamera menyesuaikan fokus.
        }

        raf = requestAnimationFrame(scanLoop);
    };

    const start = async () => {
        try {
            detector = detector ?? new window.BarcodeDetector({ formats: ['qr_code'] });

            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 } },
                audio: false,
            });

            video.srcObject = stream;
            video.hidden = false;
            await video.play();

            openBtn.hidden = true;
            if (closeBtn) closeBtn.hidden = false;
            say('Arahkan QR pada layar mahasiswa ke kamera.', 'neutral');

            raf = requestAnimationFrame(scanLoop);
        } catch (error) {
            stop();
            say(
                'Kamera tidak bisa dibuka. Beri izin kamera di browser, atau masukkan kode manual.',
                'warn',
            );
        }
    };

    openBtn.addEventListener('click', start);
    closeBtn?.addEventListener('click', stop);

    // Kamera harus mati saat meninggalkan halaman — kalau tidak, indikator
    // kamera menyala terus dan panitia mengira sedang direkam.
    window.addEventListener('pagehide', stop);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stop();
    });

    if (manualForm) {
        manualForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const code = extractCode(manualInput?.value ?? '');
            if (!code) {
                say('Format kode tidak dikenal. Contoh: OQ-QRRF-Z6UT', 'warn');
                return;
            }
            window.location.href = `/klaim/${encodeURIComponent(code)}`;
        });
    }
}