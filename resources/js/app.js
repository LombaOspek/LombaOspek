/**
 * OspekQuest — perilaku sisi klien.
 *
 * Modul ES tanpa dependensi supaya bundel Vite tetap kecil; seluruh logika
 * gamifikasi (XP, gating, penilaian) tetap di server.
 */

import { initEngagement } from './engagement.js';
import { initRewardStore } from './rewards.js';
import { initImportProgress } from './import-progress.js';
import { initDropdowns } from './dropdown.js';
import { initMajorOfficerLink } from './major-officer.js';
import { initAccountActions } from './account-actions.js';
import { initAjaxPagination } from './ajax-pagination.js';
import { initClaimScanner } from './claim-scanner.js';

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------ *
 * Efek suara prosedural (Web Audio, tanpa file audio)
 * ------------------------------------------------------------------ */

let audioContext = null;
let soundEnabled = document.documentElement.dataset.sound === '1';

function context() {
    if (audioContext === null) {
        const Ctor = window.AudioContext || window.webkitAudioContext;
        audioContext = Ctor ? new Ctor() : null;
    }

    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume();
    }

    return audioContext;
}

export function playTone(kind = 'tap') {
    if (!soundEnabled || prefersReducedMotion()) {
        return;
    }

    const ctx = context();

    if (!ctx) {
        return;
    }

    const now = ctx.currentTime;
    const master = ctx.createGain();
    master.connect(ctx.destination);

    const notes = {
        tap: [420, 300],
        reward: [523.25, 659.25, 783.99],
        unlock: [392, 523.25, 659.25, 880],
        levelup: [392, 523.25, 659.25, 783.99, 1046.5],
        complete: [587.33, 880],
        warn: [240, 190],
    }[kind] ?? [400];

    master.gain.setValueAtTime(kind === 'tap' ? 0.06 : 0.09, now);
    master.gain.exponentialRampToValueAtTime(0.0001, now + notes.length * 0.13 + 0.24);

    notes.forEach((frequency, index) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        const start = now + index * 0.1;

        osc.type = kind === 'warn' ? 'square' : 'triangle';
        osc.frequency.setValueAtTime(frequency, start);
        gain.gain.setValueAtTime(0.9, start);
        gain.gain.exponentialRampToValueAtTime(0.001, start + 0.22);

        osc.connect(gain);
        gain.connect(master);
        osc.start(start);
        osc.stop(start + 0.24);
    });
}

/* ------------------------------------------------------------------ *
 * Toast
 * ------------------------------------------------------------------ */

// Ikon dibuat lewat createElementNS (bukan innerHTML) dan seluruh teks masuk
// melalui textContent, sehingga isi pesan tidak pernah diinterpretasi sebagai
// HTML — aman dari XSS meski pesan berasal dari respons server.
const SVG_NS = 'http://www.w3.org/2000/svg';

const TONE_GLYPH = {
    success: 'M4.8 12.6l4.6 4.6L19.2 7',
    warning: 'M12 7.5v5M12 16.2h.01M12 3.6 3.4 19.8h17.2L12 3.6Z',
    danger: 'M6.5 6.5l11 11M17.5 6.5l-11 11',
    neutral: 'M12 11v5.5M12 8h.01',
};

const TONE_CLASS = {
    success: 'bg-success text-white',
    warning: 'bg-gold text-ink',
    danger: 'bg-vermilion text-white',
    neutral: 'bg-ink text-paper',
};

function glyph(path) {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2.2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('class', 'size-4');
    svg.setAttribute('aria-hidden', 'true');

    const shape = document.createElementNS(SVG_NS, 'path');
    shape.setAttribute('d', path);
    svg.appendChild(shape);

    return svg;
}

function dismiss(element) {
    element.style.opacity = '0';
    element.style.transform = 'translateY(8px)';
    window.setTimeout(() => element.remove(), 220);
}

export function toast({ title, message = '', tone = 'success' } = {}) {
    const host = document.getElementById('toast-host');

    if (!host) {
        return;
    }

    const item = document.createElement('div');
    item.className =
        'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border-2 border-ink bg-paper p-3 opacity-0 transition-all duration-200';
    item.style.boxShadow = 'var(--shadow-flat-lg)';
    item.style.transform = 'translateY(-8px)';
    item.setAttribute('role', 'status');

    const badge = document.createElement('span');
    badge.className = `grid size-8 shrink-0 place-items-center rounded-sm border-2 border-ink ${TONE_CLASS[tone] ?? TONE_CLASS.neutral}`;
    badge.appendChild(glyph(TONE_GLYPH[tone] ?? TONE_GLYPH.neutral));

    const body = document.createElement('div');
    body.className = 'min-w-0 flex-1';

    const heading = document.createElement('p');
    heading.className = 'font-display text-[0.82rem] font-bold leading-tight';
    heading.textContent = title;
    body.appendChild(heading);

    if (message) {
        const text = document.createElement('p');
        text.className = 'mt-0.5 text-[0.72rem] font-medium leading-snug text-ink-soft';
        text.textContent = message;
        body.appendChild(text);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'shrink-0 rounded p-0.5 text-ink-faint transition hover:text-ink';
    close.setAttribute('aria-label', 'Tutup notifikasi');
    close.appendChild(glyph('M6 6l12 12M18 6L6 18'));
    close.addEventListener('click', () => dismiss(item));

    item.append(badge, body, close);
    host.appendChild(item);

    requestAnimationFrame(() => {
        item.style.opacity = '1';
        item.style.transform = 'translateY(0)';
    });

    window.setTimeout(() => dismiss(item), 5200);
}

/* ------------------------------------------------------------------ *
 * Bootstrap
 * ------------------------------------------------------------------ */

export function boot() {
    document.documentElement.dataset.sound = soundEnabled ? '1' : '0';

    // Lima fitur engagement (Kotak Misteri, FOMO feed, Misi Rahasia).
    // Gugus Wars dan gelar leaderboard dirender server; tidak butuh JS.
    initEngagement();
    initRewardStore();
    initImportProgress();
    initDropdowns();
    initMajorOfficerLink();
    initAccountActions();
    initAjaxPagination();
    initClaimScanner();

    // Level halaman sebelumnya: dipakai untuk mendeteksi kenaikan level.
    const root = document.documentElement;
    const currentLevel = Number(root.dataset.level || 0);
    const storedLevel = Number(window.sessionStorage.getItem('ospek.level') || 0);

    if (storedLevel > 0) {
        root.dataset.levelBefore = String(storedLevel);
    }

    if (currentLevel > 0) {
        window.sessionStorage.setItem('ospek.level', String(currentLevel));
    }

    document.querySelectorAll('[data-sound-toggle]').forEach((el) => {
        el.addEventListener('click', () => {
            soundEnabled = !soundEnabled;
            document.documentElement.dataset.sound = soundEnabled ? '1' : '0';
            el.setAttribute('aria-pressed', String(soundEnabled));
            playTone(soundEnabled ? 'reward' : 'tap');

            const form = document.querySelector('[data-pref-form="sound"]');

            if (form) {
                const input = form.querySelector('input[name="sound_effects"]');

                if (input) {
                    input.checked = soundEnabled;
                    form.requestSubmit();
                }
            }
        });
    });

    document.querySelectorAll('[data-submit-lock]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
            });
        });
    });

    // Stagger animasi otomatis untuk kontainer bertanda data-stagger
    document.querySelectorAll('[data-stagger]').forEach((container) => {
        if (prefersReducedMotion()) return;
        const delay = Number(container.dataset.stagger || 40);
        Array.from(container.children).forEach((child, index) => {
            child.style.animationDelay = `${index * delay}ms`;
        });
    });

    // Angka berhitung (counter animation) untuk elemen [data-counter]
    document.querySelectorAll('[data-counter]').forEach((el) => {
        if (prefersReducedMotion()) return;
        const target = Number(el.dataset.counter || 0);
        const duration = 600;
        const start = performance.now();
        const initial = 0;

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            // easeOutQuad
            const eased = 1 - (1 - progress) * (1 - progress);
            const current = Math.round(initial + (target - initial) * eased);
            el.textContent = current.toLocaleString('id-ID');
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
}

/**
 * Konfeti geometris murni DOM (tanpa canvas, tanpa library external).
 * Ringan: 18 keping SVG flat yang meledak lalu hilang dalam 900ms.
 */
export function burstConfetti(originX = window.innerWidth / 2, originY = window.innerHeight / 2) {
    if (prefersReducedMotion()) return;

    const colors = ['#2758a3', '#f7bc29', '#14653f', '#c4451f', '#ffffff'];
    const container = document.createElement('div');
    container.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:9999;overflow:hidden;';
    document.body.appendChild(container);

    const count = 22;
    for (let i = 0; i < count; i++) {
        const piece = document.createElement('div');
        const color = colors[i % colors.length];
        const angle = (i / count) * 2 * Math.PI + (Math.random() - 0.5) * 0.4;
        const dist = 80 + Math.random() * 140;
        const dx = Math.cos(angle) * dist;
        const dy = Math.sin(angle) * dist - 30; // sedikit dorongan ke atas

        const isCircle = i % 3 === 0;
        piece.style.cssText = `
            position:absolute;
            left:${originX}px; top:${originY}px;
            width:${isCircle ? 8 : 10}px; height:${isCircle ? 8 : 6}px;
            background:${color};
            border:1.5px solid #17150f;
            border-radius:${isCircle ? '50%' : '2px'};
            transform:translate(-50%, -50%);
            transition:transform 700ms cubic-bezier(0.2, 0.9, 0.3, 1.2), opacity 500ms ease 300ms;
        `;
        container.appendChild(piece);

        requestAnimationFrame(() => {
            piece.style.transform = `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) rotate(${Math.random() * 360}deg)`;
            piece.style.opacity = '0';
        });
    }

    setTimeout(() => container.remove(), 950);
}

/**
 * Overlay "Level Up" — muncul saat level pengguna naik setelah aksi.
 * Seluruh teks dimasukkan lewat textContent (bukan innerHTML) karena
 * `title` berasal dari config/data server; ini menutup jalur XSS.
 */
export function levelUp({ level, title, sound = true } = {}) {
    if (document.getElementById('levelup-overlay')) return;

    const overlay = document.createElement('div');
    overlay.id = 'levelup-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Level naik');
    /*
     * Latar disiapkan TANPA warna lebih dulu.
     *
     * Sebelumnya overlay langsung dipasang dengan bg-ink/70, sementara kartunya
     * beranimasi `pop-in` yang mulai dari opacity 0. Selama ~100ms pertama yang
     * terlihat hanya layar gelap tanpa isi — persis gejala "halaman redup tanpa
     * alasan". Gelapnya baru dinyalakan setelah kartu benar-benar tampil.
     */
    overlay.className = 'fixed inset-0 z-[10000] flex items-center justify-center p-4';

    const card = document.createElement('div');
    card.className = 'relative w-full max-w-sm overflow-hidden rounded-xl border-[3px] border-ink bg-paper p-6 text-center';
    card.style.boxShadow = '10px 10px 0 0 var(--color-ink)';

    const stripe = document.createElement('div');
    stripe.className = 'absolute inset-x-0 top-0 h-2 bg-gold';

    const kicker = document.createElement('p');
    kicker.className = 'mt-2 text-[0.6rem] font-extrabold uppercase tracking-[0.2em] text-gold-deep';
    kicker.textContent = 'Level Naik';

    const ring = document.createElement('div');
    ring.className = 'mx-auto mt-3 grid size-24 place-items-center rounded-full border-[3px] border-ink bg-gold';
    ring.style.boxShadow = 'var(--shadow-flat)';
    const badge = document.createElement('span');
    badge.className = 'numeric font-display text-[2.4rem] font-extrabold leading-none';
    badge.textContent = String(level ?? '');
    ring.appendChild(badge);

    const heading = document.createElement('p');
    heading.className = 'mt-3 font-display text-[1.15rem] font-extrabold leading-tight';
    heading.textContent = title ?? 'Pangkat baru terbuka';

    const body = document.createElement('p');
    body.className = 'mt-1 text-[0.74rem] font-medium leading-relaxed text-ink-soft';
    body.textContent = 'Perjalanan Anda naik satu tingkat. Teruskan sampai sertifikat!';

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn btn-primary mt-4 w-full';
    closeBtn.setAttribute('data-levelup-close', '');
    closeBtn.textContent = 'Lanjutkan Perjalanan';

    card.append(stripe, kicker, ring, heading, body, closeBtn);
    overlay.appendChild(card);
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    /*
     * Gelapkan latar SETELAH kartu terpasang, dengan transisi.
     *
     * Kartu memakai `pop-in` yang mulai dari opacity 0; kalau latar sudah gelap
     * sejak awal, ada ~100ms layar gelap kosong. Transisi 160ms juga membuat
     * kemunculannya terasa disengaja, bukan kedipan.
     */
    overlay.style.backgroundColor = 'transparent';
    overlay.style.backdropFilter = 'blur(0px)';
    overlay.style.transition = 'background-color 160ms ease, backdrop-filter 160ms ease';
    requestAnimationFrame(() => {
        overlay.style.backgroundColor = 'color-mix(in srgb, var(--color-ink) 70%, transparent)';
        overlay.style.backdropFilter = 'blur(4px)';
    });

    if (sound) {
        playTone('levelup');
    }

    const close = () => {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 180ms ease';
        window.setTimeout(() => {
            overlay.remove();
            document.body.style.overflow = '';
        }, 190);
    };

    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); }
    });
}

/**
 * XP burst: angka "+N XP" melayang dari elemen sumber ke bilah XP di sidebar.
 */
export function xpBurst(amount, sourceEl = null) {
    if (prefersReducedMotion() || !amount) return;

    const from = sourceEl?.getBoundingClientRect();
    const target = document.querySelector('[data-xp-bar]')?.getBoundingClientRect();

    const startX = from ? from.left + from.width / 2 : window.innerWidth / 2;
    const startY = from ? from.top + from.height / 2 : window.innerHeight / 2;

    const chip = document.createElement('div');
    chip.textContent = `+${amount} XP`;
    chip.style.cssText = `
        position:fixed; left:${startX}px; top:${startY}px;
        transform:translate(-50%,-50%) scale(0.8);
        padding:4px 10px; border:2px solid #17150f; border-radius:999px;
        background:#f7bc29; color:#17150f;
        font:800 12px/1 "Plus Jakarta Sans",sans-serif; letter-spacing:.04em;
        box-shadow:3px 3px 0 #17150f; z-index:9999; pointer-events:none;
        transition:transform 760ms cubic-bezier(.2,.9,.3,1.1), left 760ms cubic-bezier(.4,0,.3,1),
                   top 760ms cubic-bezier(.4,0,.3,1), opacity 320ms ease 560ms;
    `;
    document.body.appendChild(chip);

    requestAnimationFrame(() => {
        chip.style.transform = 'translate(-50%,-50%) scale(1.15)';
        if (target) {
            chip.style.left = `${target.left + target.width / 2}px`;
            chip.style.top = `${target.top + 12}px`;
            chip.style.opacity = '0.2';
        } else {
            chip.style.top = `${startY - 60}px`;
            chip.style.opacity = '0';
        }
    });

    window.setTimeout(() => chip.remove(), 1100);
}


/**
 * Peta gelar level, disuntik dari config/ospek.php lewat meta tag
 * supaya tidak ada daftar gelar yang di-hardcode di dua tempat.
 */
function levelTitles() {
    const el = document.querySelector('meta[name="level-titles"]');
    if (!el) return {};

    // <meta> tidak punya textContent — nilainya ada di atribut content.
    try {
        return JSON.parse(el.getAttribute('content') || '{}');
    } catch {
        return {};
    }
}

/**
 * Deteksi kenaikan level: bandingkan dataset.level sebelum dan sesudah.
 * Mengembalikan detail level baru bila naik, atau null.
 */
export function detectLevelUp() {
    const root = document.documentElement;
    const before = Number(root.dataset.levelBefore || 0);
    const after = Number(root.dataset.level || 0);

    if (!before || !after || after <= before) return null;

    const titles = levelTitles();

    return { level: after, title: titles[after] || 'Pangkat baru terbuka' };
}

window.Ospek = {
    toast,
    playTone,
    burstConfetti,
    levelUp,
    xpBurst,
    detectLevelUp,
    setSound(value) {
        soundEnabled = Boolean(value);
        document.documentElement.dataset.sound = soundEnabled ? '1' : '0';
    },

    /**
     * Pasang ulang komponen interaktif pada DOM yang baru diganti.
     *
     * Dipakai paginasi AJAX: setelah tabel ditukar, dropdown dan tombol aksi
     * di dalamnya belum punya listener, jadi tanpa ini halaman terlihat benar
     * tetapi tidak bisa dipakai.
     */
    rebind() {
        /*
         * Pasang ulang HANYA komponen yang elemennya ikut ditukar.
         *
         * initDropdowns() dan initAccountActions() sudah memasang listener di
         * `document` (delegasi), jadi elemen baru otomatis tertangani dan
         * TIDAK perlu dipasang ulang. Memanggil ulang initDropdowns() justru
         * mendaftarkan listener `document` kedua - dan listener lama menutup
         * panel yang baru dibuka, sehingga dropdown mati setelah paginasi AJAX.
         *
         * initMajorOfficerLink() aman: ia memasang listener per elemen dan
         * melewati elemen yang sudah ditandai.
         */
        initMajorOfficerLink();
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}