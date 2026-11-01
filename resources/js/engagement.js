/**
 * OspekQuest — engagement hook.
 *
 * Gugus Wars : gerak tali murni CSS (custom property dari server).
 * Kotak Misteri : modal + confetti, hadiah tetap dari server.
 * Live Feed  : rotasi kartu aktivitas XP nyata mahasiswa lain.
 * Misi Rahasia : 7 ketukan cepat pada WIRA -> shake + hadiah sekali seumur akun.
 *
 * Tidak ada angka hadiah yang dipakai dari klien: semua nilai yang dibayarkan
 * diambil dari respons server, dan endpoint menolak klaim kedua.
 */

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

async function postJSON(url, payload = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
    });

    let data = null;
    try {
        data = await response.json();
    } catch {
        data = null;
    }

    return { ok: response.ok, status: response.status, data };
}

/* ------------------------------------------------------------------ *
 * Confetti: memakai ulang burstConfetti dari app.js bila tersedia,
 * dengan cadangan CSS-only agar tetap jalan tanpa dependensi.
 * ------------------------------------------------------------------ */

function confettiAt(x, y) {
    if (reduceMotion()) return;

    if (window.Ospek?.burstConfetti) {
        window.Ospek.burstConfetti(x, y);
        return;
    }

    const colors = ['#2758a3', '#f7bc29', '#14653f', '#c4451f'];
    const host = document.createElement('div');
    host.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:9999;overflow:hidden';
    document.body.appendChild(host);

    for (let i = 0; i < 18; i += 1) {
        const piece = document.createElement('span');
        const angle = (i / 18) * Math.PI * 2;
        const distance = 70 + Math.random() * 120;

        piece.className = 'confetti-piece';
        piece.style.cssText = `
            position:absolute;left:${x}px;top:${y}px;
            width:9px;height:6px;background:${colors[i % colors.length]};
            border:1.5px solid #17150f;border-radius:2px;
            --burst-x:${Math.cos(angle) * distance}px;
            --burst-y:${Math.sin(angle) * distance - 40}px;
            --burst-rot:${Math.random() * 360}deg;
        `;
        host.appendChild(piece);
    }

    window.setTimeout(() => host.remove(), 1000);
}

/* ------------------------------------------------------------------ *
 * Sinkronisasi XP setelah klaim.
 *
 * Klaim tidak memuat ulang halaman, jadi angka di topbar akan tetap
 * menunjukkan saldo lama padahal server sudah menambah XP. Nilai yang
 * ditampilkan diambil dari respons server (balance_after), bukan dari
 * hitungan klien.
 * ------------------------------------------------------------------ */

function syncXpBalance(balanceAfter) {
    if (typeof balanceAfter !== 'number') return;

    document.documentElement.dataset.xp = String(balanceAfter);

    document.querySelectorAll('[data-xp-value]').forEach((el) => {
        el.textContent = new Intl.NumberFormat('id-ID').format(balanceAfter);
    });

    // Stat tile "Total XP" di dashboard memakai penanda yang sama.
    document.querySelectorAll('[data-counter-value]').forEach((el) => {
        el.textContent = new Intl.NumberFormat('id-ID').format(balanceAfter);
    });
}

/* ------------------------------------------------------------------ *
 * 1. Kotak Misteri Harian
 * ------------------------------------------------------------------ */

function initDailyBox() {
    const modal = document.querySelector('[data-daily-box]');
    if (!modal) return;

    const openBtn = modal.querySelector('[data-daily-box-open]');
    const closeBtn = modal.querySelector('[data-daily-box-close]');
    const lid = modal.querySelector('[data-box-lid]');
    const label = modal.querySelector('[data-box-label]');
    const result = modal.querySelector('[data-box-result]');
    const xpText = modal.querySelector('[data-box-xp]');

    // Kunci penyimpanan HARUS memuat id user. Dengan tanggal saja, mahasiswa
    // yang login di perangkat bersama (lab kampus) tidak akan pernah melihat
    // kotaknya sendiri karena penanda milik akun sebelumnya masih tersimpan.
    const boxKey = modal.dataset.boxKey;
    const storageKey = `ospek.dailybox.${modal.dataset.user}.${boxKey}`;
    let claimed = modal.dataset.claimed === '1';
    let busy = false;

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const open = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Fokus wajib dipindah ke dalam dialog sebelum pengguna menekan apa
        // pun. Tanpa ini, pengguna keyboard/screen reader tetap berada di
        // halaman belakang dan Tab menjelajah konten yang tertutup modal.
        const card = modal.querySelector('[data-box-card]') ?? modal.firstElementChild;
        const target = openBtn?.hasAttribute('disabled') ? card : openBtn;

        if (target && typeof target.focus === 'function') {
            target.focus({ preventScroll: true });
        }
    };

    const markClaimed = (xp) => {
        claimed = true;

        if (lid) {
            lid.classList.remove('bg-gold');
            lid.classList.add('bg-success', 'animate-lid-shake');
            lid.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-10 text-white" aria-hidden="true"><path d="M4.8 12.6l4.6 4.6L19.2 7"/></svg>';
        }

        if (label) label.textContent = 'Sudah dibuka hari ini';

        if (result) result.classList.remove('hidden');
        if (xpText) xpText.textContent = `+${xp} XP`;

        openBtn?.setAttribute('disabled', 'disabled');
    };

    openBtn?.addEventListener('click', async () => {
        if (busy) return;

        if (claimed) {
            window.Ospek?.toast({
                title: 'Kotak hari ini sudah dibuka',
                message: 'Kembali besok untuk kotak misteri berikutnya.',
                tone: 'neutral',
            });
            return;
        }

        busy = true;

        const box = openBtn.querySelector('span[data-box-lid]');
        const rect = box?.getBoundingClientRect();
        const cx = rect ? rect.left + rect.width / 2 : window.innerWidth / 2;
        const cy = rect ? rect.top + rect.height / 2 : window.innerHeight / 2;

        // Efek "meledak" langsung, tanpa menunggu server: kalau klaim ditolak,
        // UI dipulihkan dan pengguna diberi tahu alasannya.
        confettiAt(cx, cy);
        window.Ospek?.playTone('reward');

        const { ok, data } = await postJSON(modal.dataset.endpoint);

        busy = false;

        if (!ok) {
            claimed = data?.reason === 'already_claimed';

            window.Ospek?.toast({
                title: claimed ? 'Kotak sudah dibuka' : 'Gagal membuka kotak',
                message: data?.message ?? 'Coba lagi sebentar lagi.',
                tone: claimed ? 'neutral' : 'warning',
            });

            if (claimed) markClaimed(data?.box?.value ?? 0);

            return;
        }

        window.localStorage.setItem(storageKey, '1');
        markClaimed(data.xp);

        syncXpBalance(data.balance_after);

        window.Ospek?.toast({
            title: `Kotak Misteri: +${data.xp} XP`,
            message: 'Hadiah sudah masuk ke saldo Anda.',
            tone: 'success',
        });
    });

    closeBtn?.addEventListener('click', close);

    // Klik latar menutup modal: kotak boleh dilewati.
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    // Perangkap fokus: modal `aria-modal="true"` berjanji bahwa konten di
    // belakang tidak bisa dijangkau. Tanpa ini, Tab dari tombol terakhir
    // melompat ke sidebar di belakang modal dan janji itu bohong.
    modal.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') return;

        const focusables = [...modal.querySelectorAll(
            'button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
        )].filter((el) => el.offsetParent !== null);

        if (focusables.length === 0) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus({ preventScroll: true });
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus({ preventScroll: true });
        }
    });

    if (claimed) {
        markClaimed(0);
        if (xpText) xpText.textContent = 'Sudah diklaim';
        return;
    }

    // Muncul otomatis sekali per hari per kotak. localStorage hanya penanda
    // tampilan; klaim ganda tetap ditolak server.
    if (!window.localStorage.getItem(storageKey)) {
        window.setTimeout(open, 900);
    }
}

/* ------------------------------------------------------------------ *
 * 2. FOMO Live Feed
 * ------------------------------------------------------------------ */

function initLiveFeed() {
    const host = document.querySelector('[data-live-feed]');
    const dataEl = document.getElementById('live-feed-data');
    if (!host || !dataEl) return;

    let items = [];
    try {
        items = JSON.parse(dataEl.textContent || '[]');
    } catch {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) return;

    host.classList.remove('hidden');
    host.classList.add('flex');

    const rotateMs = Number(host.dataset.rotateMs || 9000);
    let index = 0;

    const buildCard = (item) => {
        const card = document.createElement('div');
        card.className = 'pointer-events-auto flex w-full items-start gap-2 rounded-md border-2 border-ink bg-paper-dim p-2 shadow-[var(--shadow-flat-sm)] animate-feed-in';

        const badge = document.createElement('span');
        badge.className = 'grid size-6 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-brand-soft text-[0.5rem] font-extrabold';
        badge.textContent = (item.name || 'M').slice(0, 1).toUpperCase();

        const body = document.createElement('div');
        body.className = 'min-w-0 flex-1';

        const line = document.createElement('p');
        line.className = 'text-[0.64rem] font-semibold leading-snug';
        // textContent, bukan innerHTML: nama berasal dari database.
        line.textContent = `${item.name} ${item.action}`;

        const meta = document.createElement('p');
        meta.className = 'mt-0.5 flex items-center gap-1 text-[0.55rem] font-bold text-ink-faint';
        meta.textContent = `+${item.xp} XP · ${item.ago}`;

        body.append(line, meta);
        card.append(badge, body);

        return card;
    };

    const showNext = () => {
        const item = items[index % items.length];
        index += 1;

        host.replaceChildren();
        const card = buildCard(item);
        host.appendChild(card);

        window.setTimeout(() => {
            card.classList.remove('animate-feed-in');
            card.classList.add('animate-feed-out');
            window.setTimeout(() => card.remove(), 320);
        }, rotateMs - 400);
    };

    // Jangan bersaing dengan toast sesi: beri jeda awal yang sopan.
    window.setTimeout(showNext, 2600);
    window.setInterval(showNext, rotateMs);
}

/* ------------------------------------------------------------------ *
 * 3. Misi Rahasia (easter egg WIRA)
 * ------------------------------------------------------------------ */

function initSecretMission() {
    const trigger = document.querySelector('[data-secret-trigger]');
    if (!trigger) return;

    const required = Number(trigger.dataset.secretClicks || 7);
    const windowMs = Number(trigger.dataset.secretWindow || 2600);
    const endpoint = trigger.dataset.secretEndpoint;
    const storageKey = 'ospek.secret.found';

    let stamps = [];
    let busy = false;

    const shake = () => {
        if (reduceMotion()) return;

        const target = document.querySelector('main') ?? document.body;
        target.classList.add('animate-secret-shake');
        window.setTimeout(() => target.classList.remove('animate-secret-shake'), 520);
    };

    trigger.addEventListener('click', async () => {
        const now = Date.now();

        // Jendela geser: hanya ketukan dalam rentang terakhir yang dihitung,
        // jadi klik lambat tidak pernah mencapai ambang.
        stamps = stamps.filter((stamp) => now - stamp <= windowMs);
        stamps.push(now);

        // Umpan balik progres: makin dekat ambang, makin kencang getarnya.
        if (stamps.length >= Math.ceil(required / 2) && !reduceMotion()) {
            trigger.style.transform = `scale(${1 + stamps.length * 0.01})`;
            window.setTimeout(() => {
                trigger.style.transform = '';
            }, 140);
        }

        if (stamps.length < required || busy) return;

        busy = true;
        stamps = [];
        shake();

        const { ok, data } = await postJSON(endpoint, { clicks: required });

        busy = false;

        if (ok) {
            window.localStorage.setItem(storageKey, '1');
            confettiAt(window.innerWidth / 2, window.innerHeight / 2);
            window.Ospek?.playTone('levelup');

            window.Ospek?.toast({
                title: `Misi Rahasia terbuka: +${data.xp} XP`,
                message: 'WIRA membocorkan jalan pintas. Jangan beri tahu yang lain.',
                tone: 'success',
            });

            if (typeof data.balance_after === 'number') {
                syncXpBalance(data.balance_after);
            }

            return;
        }

        if (data?.reason === 'already_claimed') {
            window.Ospek?.toast({
                title: 'Misi Rahasia sudah ditemukan',
                message: 'Hadiahnya hanya sekali. Coba cari kejutan lain di peta.',
                tone: 'neutral',
            });
        }
    });
}

/* ------------------------------------------------------------------ *
 * Bootstrap
 * ------------------------------------------------------------------ */

export function initEngagement() {
    initDailyBox();
    initLiveFeed();
    initSecretMission();
}
