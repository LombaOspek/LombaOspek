/**
 * Pemindai QR klaim untuk meja panitia.
 *
 * Kenapa ada pemindai di sini, padahal HP panitia sudah punya kamera:
 * di meja penyerahan panitia memegang laptop yang menampilkan daftar klaim.
 * Mengeluarkan HP, membuka kamera, memindai, lalu mencari barisnya lagi di
 * laptop adalah tiga langkah yang membentuk antrean. Pemindai ini memangkasnya
 * jadi satu.
 *
 * DUA MESIN PEMINDAI, BUKAN SATU
 *  1. `BarcodeDetector` — API bawaan Chrome/Edge. Cepat, tanpa unduhan.
 *  2. `jsQR` — cadangan untuk browser yang tidak punya API itu.
 *
 * Kenapa cadangan wajib, bukan opsional: pada pemeriksaan 9 November 2026 di
 * Chrome, `BarcodeDetector` TIDAK ADA, dan versi lama langsung menyembunyikan
 * tombol kamera. Di iPhone/Safari API ini juga tidak pernah ada. Artinya
 * pemindai mati total di HP yang paling mungkin dipakai panitia di lapangan.
 * Memeriksa `'BarcodeDetector' in window` lalu menyerah adalah asumsi
 * desktop-Chrome yang tidak berlaku saat hari-H.
 *
 * URUTAN PENDAFTARAN PENTING
 * Input kode manual didaftarkan PALING AWAL, sebelum pemeriksaan kemampuan
 * pemindai. Versi lama melakukan `return` lebih dulu ketika BarcodeDetector
 * tidak ada, sehingga form manual — satu-satunya jalan yang masih bekerja —
 * ikut mati.
 */

import jsQR from 'jsqr';

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

    if (!openBtn) return;

    const say = (text, tone = 'neutral') => {
        if (!status) return;
        status.textContent = text;
        status.dataset.tone = tone;
    };

    /**
     * Payload QR adalah URL verifikasi. Yang dibutuhkan halaman ini hanya kode
     * di segmen terakhir, tetapi kode manual juga diterima apa adanya supaya
     * satu fungsi bisa menangani kedua bentuk.
     */
    const extractCode = (raw) => {
        const text = String(raw || '').trim();
        if (!text) return null;

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

    /**
     * `data-scanner-target` berisi pola URL dengan penanda {code}.
     *
     * Di `/admin/bursa` hasil pindai membuka halaman verifikasi publik.
     * Di station LO hasil pindai harus membuka baris klaim di station itu
     * sendiri, supaya LO bisa langsung menekan "Serahkan".
     */
    const goToCode = (code) => {
        const target = panel.dataset.scannerTarget || '/klaim/{code}';
        window.location.href = target.replace('{code}', encodeURIComponent(code));
    };

    let stream = null;
    let raf = 0;

    const stop = () => {
        cancelAnimationFrame(raf);
        raf = 0;

        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            stream = null;
        }

        if (video) {
            video.srcObject = null;
            video.hidden = true;
        }

        if (closeBtn) closeBtn.hidden = true;
        openBtn.hidden = false;
    };

    const onDetected = (raw) => {
        const code = extractCode(raw);
        if (!code) {
            say('QR terbaca tetapi bukan kode klaim OspekQuest.', 'warn');
            return;
        }

        say(`Klaim ${code} ditemukan, membuka…`, 'ok');
        stop();
        goToCode(code);
    };

    /*
     * ---------- INPUT MANUAL: didaftarkan lebih dulu ----------
     *
     * Ini jalan terakhir yang harus SELALU bekerja: ponsel mati, kamera rusak,
     * izin ditolak, atau browser tanpa pemindai apa pun. Karena itu
     * pendaftarannya tidak boleh berada di belakang pemeriksaan kemampuan.
     */
    if (manualForm) {
        manualForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const code = extractCode(manualInput?.value ?? '');
            if (!code) {
                say('Format kode tidak dikenal. Contoh: OQ-QRRF-Z6UT', 'warn');
                return;
            }
            goToCode(code);
        });
    }

    /** Ada akses kamera sama sekali? Tanpa ini tombol tidak ada gunanya. */
    const hasCamera = Boolean(
        navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function',
    );

    if (!hasCamera) {
        openBtn.hidden = true;
        say('Browser ini tidak memberi akses kamera. Masukkan kode secara manual.', 'warn');
        return;
    }

    const useNative = 'BarcodeDetector' in window;
    let nativeDetector = null;

    if (!useNative) {
        /*
         * Bukan kegagalan — kita turun ke jsQR. Jangan sembunyikan tombol:
         * justru di sinilah pemindai paling dibutuhkan.
         */
        say('Kamera siap. Pemindai memakai mode kompatibilitas.', 'neutral');
    }

    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    /** Jalur 1: BarcodeDetector bawaan browser. */
    const scanNative = async () => {
        if (!stream) return;

        try {
            const found = await nativeDetector.detect(video);
            if (found.length > 0) {
                onDetected(found[0].rawValue);
                return;
            }
        } catch {
            // Bingkai gagal sesekali itu normal saat kamera menyesuaikan fokus.
        }

        raf = requestAnimationFrame(scanNative);
    };

    /**
     * Jalur 2: jsQR di atas <canvas>.
     *
     * Video diperkecil ke maksimal 640px sisi terpanjang sebelum diproses.
     * Bukan soal kualitas: jsQR menerima ImageData dan memeriksa setiap piksel.
     * Pada 1280x720 itu ~920 ribu piksel per bingkai, dan menjalankannya
     * 60x/detik membuat laptop panitia panas serta baterai habis dalam satu
     * jam penyerahan. Di 640px jumlahnya turun 4x dan QR di layar ponsel tetap
     * lebih dari cukup besar untuk terbaca.
     */
    const scanFallback = () => {
        if (!stream || !video || !ctx) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            const maxSide = 640;
            const vw = video.videoWidth || 0;
            const vh = video.videoHeight || 0;

            if (vw > 0 && vh > 0) {
                const scale = Math.min(1, maxSide / Math.max(vw, vh));
                const w = Math.round(vw * scale);
                const h = Math.round(vh * scale);

                if (canvas.width !== w || canvas.height !== h) {
                    canvas.width = w;
                    canvas.height = h;
                }

                ctx.drawImage(video, 0, 0, w, h);

                try {
                    const data = ctx.getImageData(0, 0, w, h);
                    const found = jsQR(data.data, w, h, { inversionAttempts: 'dontInvert' });
                    if (found && found.data) {
                        onDetected(found.data);
                        return;
                    }
                } catch {
                    // Kanvas kosong pada bingkai pertama; lanjut saja.
                }
            }
        }

        raf = requestAnimationFrame(scanFallback);
    };

    const start = async () => {
        try {
            if (useNative) {
                nativeDetector = nativeDetector ?? new window.BarcodeDetector({ formats: ['qr_code'] });
            }

            stream = await navigator.mediaDevices.getUserMedia({
                // `environment` = kamera belakang di ponsel. Di laptop diabaikan.
                video: { facingMode: 'environment', width: { ideal: 1280 } },
                audio: false,
            });

            if (!video) return;

            video.srcObject = stream;
            video.hidden = false;
            await video.play();

            openBtn.hidden = true;
            if (closeBtn) closeBtn.hidden = false;
            say('Arahkan QR pada layar mahasiswa ke kamera.', 'neutral');

            raf = requestAnimationFrame(useNative ? scanNative : scanFallback);
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
}