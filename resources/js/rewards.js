/**
 * Bursa Hadiah — konfirmasi dan penukaran dari sisi klien.
 *
 * Prinsip: klien HANYA memberi tahu "tukar hadiah id X". Harga, stok, dan
 * kelayakan diputuskan server. Nilai di layar hanya untuk konfirmasi.
 */

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const formatCoins = (value) => new Intl.NumberFormat('id-ID').format(value);

function closeModal(modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openModal(modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const card = modal.querySelector('[data-redeem-card]');
    card?.focus({ preventScroll: true });
}

export function initRewardStore() {
    const modal = document.querySelector('[data-redeem-modal]');
    if (!modal) return;

    const nameEl = modal.querySelector('[data-confirm-name]');
    const costEl = modal.querySelector('[data-confirm-cost]');
    const confirmBtn = modal.querySelector('[data-redeem-confirm]');
    const cancelBtn = modal.querySelector('[data-redeem-cancel]');

    let pending = null; // { endpoint, name, cost, card }
    let busy = false;

    const cards = [...document.querySelectorAll('[data-reward-card]')];
    if (cards.length === 0) return;

    cards.forEach((card) => {
        const button = card.querySelector('[data-redeem-btn]');
        if (!button || button.disabled) return;

        button.addEventListener('click', () => {
            pending = {
                endpoint: card.dataset.endpoint,
                name: card.dataset.rewardName,
                cost: Number(card.dataset.rewardCost),
                card,
                button,
            };

            if (nameEl) nameEl.textContent = pending.name;
            if (costEl) costEl.textContent = formatCoins(pending.cost);

            openModal(modal);
        });
    });

    cancelBtn?.addEventListener('click', () => closeModal(modal));

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal(modal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal(modal);
        }
    });

    confirmBtn?.addEventListener('click', async () => {
        if (!pending || busy) return;

        busy = true;
        confirmBtn.disabled = true;
        const original = confirmBtn.textContent;
        confirmBtn.textContent = 'Memproses…';

        try {
            const response = await fetch(pending.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data?.ok) {
                window.Ospek?.toast({
                    title: 'Penukaran gagal',
                    message: data?.message ?? 'Coba lagi sebentar lagi.',
                    tone: 'warning',
                });

                // Saldo bisa saja sudah berubah di server; pakai angka server.
                if (typeof data?.coins === 'number') syncBalance(data.coins);

                closeModal(modal);
                return;
            }

            syncBalance(data.coins);

            if (pending.card) markCardUsed(pending.card, data.claim_code);

            window.Ospek?.playTone('reward');
            window.Ospek?.burstConfetti?.(
                window.innerWidth / 2,
                window.innerHeight / 2,
            );

            window.Ospek?.toast({
                title: `Berhasil: ${data.reward?.name ?? pending.name}`,
                message: `Kode klaim ${data.claim_code}. Tunjukkan ke panitia.`,
                tone: 'success',
            });

            closeModal(modal);

            // Muat ulang setelah jeda singkat supaya kode klaim muncul di
            // daftar "Klaim Saya" tanpa perlu refresh manual.
            window.setTimeout(() => window.location.reload(), 1400);
        } catch {
            window.Ospek?.toast({
                title: 'Koneksi terganggu',
                message: 'Penukaran tidak terkirim. Muat ulang halaman sebelum mencoba lagi.',
                tone: 'danger',
            });

            closeModal(modal);
        } finally {
            busy = false;
            confirmBtn.disabled = false;
            confirmBtn.textContent = original;
        }
    });
}

/**
 * Sinkronkan semua tempat saldo koin ditampilkan.
 */
function syncBalance(coins) {
    if (typeof coins !== 'number') return;

    document.querySelectorAll('[data-coin-balance]').forEach((el) => {
        el.textContent = formatCoins(coins);
    });

    document.querySelectorAll('[data-coin-value]').forEach((el) => {
        el.textContent = formatCoins(coins);
    });
}

/**
 * Tandai kartu sebagai sudah ditukar supaya tidak bisa diklik dua kali
 * sebelum halaman dimuat ulang.
 */
function markCardUsed(card, claimCode) {
    const button = card.querySelector('[data-redeem-btn]');

    if (button) {
        button.disabled = true;
        button.textContent = 'Ditukar';
        button.className =
            'mt-2 w-full cursor-not-allowed rounded-md border-2 border-ink bg-success px-3 py-2 font-display text-[0.72rem] font-extrabold uppercase tracking-[0.04em] text-white';
    }

    const badge = document.createElement('p');
    badge.className = 'mt-1.5 rounded-sm border-2 border-ink bg-success/15 px-1.5 py-1 font-mono text-[0.6rem] font-bold text-center';
    badge.textContent = claimCode;

    card.querySelector('.flex.flex-1')?.appendChild(badge);
}
