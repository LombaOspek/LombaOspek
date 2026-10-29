/**
 * OspekQuest — perilaku sisi klien.
 *
 * Modul ES tanpa dependensi supaya bundel Vite tetap kecil; seluruh logika
 * gamifikasi (XP, gating, penilaian) tetap di server.
 */

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
    success: 'bg-teal text-white',
    warning: 'bg-amber text-ink',
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
}

window.Ospek = {
    toast,
    playTone,
    setSound(value) {
        soundEnabled = Boolean(value);
        document.documentElement.dataset.sound = soundEnabled ? '1' : '0';
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}