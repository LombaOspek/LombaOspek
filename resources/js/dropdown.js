/**
 * Dropdown custom: keyboard, pencarian, dan penempatan panel.
 *
 * Memakai tombol + listbox ARIA, bukan <select>, karena tampilan select
 * digambar sistem operasi sehingga tidak bisa dibuat neo-brutalist.
 * Konsekuensinya SEMUA perilaku bawaan select harus dipasang ulang di sini:
 * panah, Home/End, Enter/Escape, type-ahead, dan fokus yang dikembalikan.
 */

const OPEN_FLAG = 'data-open';

/** Tutup semua dropdown lain saat satu dibuka. */
function closeAll(except = null) {
    document.querySelectorAll(`.dd[${OPEN_FLAG}]`).forEach((dd) => {
        if (dd === except) return;
        dd.removeAttribute(OPEN_FLAG);
        dd.querySelector('[data-dd-trigger]')?.setAttribute('aria-expanded', 'false');
        const panel = dd.querySelector('[data-dd-panel]');
        if (panel) panel.hidden = true;
    });
}

/** Pilih opsi: perbarui label, hidden input, status terpilih, lalu tutup. */
function selectOption(dd, option) {
    if (!option) return;

    const value = option.dataset.value ?? '';
    const label = option.dataset.label ?? '';

    dd.querySelector('[data-dd-value]').value = value;

    const labelEl = dd.querySelector('[data-dd-label]');
    if (labelEl) {
        labelEl.textContent = label;
        labelEl.classList.remove('is-placeholder');
    }

    dd.querySelectorAll('[data-dd-option]').forEach((opt) => {
        const match = opt === option;
        opt.classList.toggle('is-selected', match);
        opt.setAttribute('aria-selected', match ? 'true' : 'false');
    });

    // Buang penanda error begitu pengguna memilih, supaya pesan lama tidak
    // menggantung di bawah field yang sudah benar.
    dd.querySelector('[data-dd-required]')?.removeAttribute('data-invalid');

    // Komponen lain (mis. penyaring LO per prodi) perlu tahu nilai berubah.
    // Hidden input tidak memancarkan 'change' sendiri saat diisi lewat JS.
    dd.dispatchEvent(new CustomEvent('dd:change', { detail: { value, label }, bubbles: true }));

    close(dd);
    dd.querySelector('[data-dd-trigger]')?.focus();
}

function open(dd) {
    closeAll(dd);

    const panel = dd.querySelector('[data-dd-panel]');
    const trigger = dd.querySelector('[data-dd-trigger]');
    if (!panel || !trigger) return;

    panel.hidden = false;
    dd.setAttribute(OPEN_FLAG, '');
    trigger.setAttribute('aria-expanded', 'true');

    // Buka ke atas bila ruang di bawah kurang dari tinggi panel: tanpa ini,
    // dropdown di baris tabel paling bawah akan keluar dari layar.
    const rect = trigger.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const needed = Math.min(panel.scrollHeight || 260, 260);
    panel.dataset.drop = spaceBelow < needed && rect.top > spaceBelow ? 'up' : 'down';

    const search = dd.querySelector('[data-dd-search]');
    if (search) {
        search.value = '';
        filter(dd, '');
        // Focus dengan preventScroll: focus() biasa menggulir halaman ke
        // elemen, dan guliran itu ditangkap listener scroll global yang lalu
        // menutup panel yang baru saja dibuka.
        requestAnimationFrame(() => search.focus({ preventScroll: true }));
    } else {
        const selected = dd.querySelector('[data-dd-option].is-selected')
            ?? dd.querySelector('[data-dd-option]');
        setActive(dd, selected);
    }
}

function close(dd) {
    dd.removeAttribute(OPEN_FLAG);
    dd.querySelector('[data-dd-trigger]')?.setAttribute('aria-expanded', 'false');
    const panel = dd.querySelector('[data-dd-panel]');
    if (panel) panel.hidden = true;
}

function visibleOptions(dd) {
    return Array.from(dd.querySelectorAll('[data-dd-option]')).filter((o) => !o.hidden);
}

function setActive(dd, option) {
    dd.querySelectorAll('[data-dd-option]').forEach((o) => o.classList.remove('is-active'));
    if (!option) return;

    option.classList.add('is-active');

    /*
     * Gulir HANYA daftar opsinya, jangan pakai scrollIntoView.
     *
     * scrollIntoView menggulir seluruh halaman bila daftar sudah muat, dan
     * guliran halaman itu ditangkap listener scroll global yang lalu menutup
     * panel - gejalanya: dropdown terbuka lalu menutup sendiri dalam 7ms.
     */
    const box = option.closest('.dd-options');
    if (!box) return;

    const top = option.offsetTop;
    const bottom = top + option.offsetHeight;

    if (top < box.scrollTop) {
        box.scrollTop = top;
    } else if (bottom > box.scrollTop + box.clientHeight) {
        box.scrollTop = bottom - box.clientHeight;
    }
}

function filter(dd, term) {
    const needle = term.trim().toLowerCase();
    let shown = 0;

    dd.querySelectorAll('[data-dd-option]').forEach((opt) => {
        const hit = needle === '' || (opt.dataset.label ?? '').toLowerCase().includes(needle);
        opt.hidden = !hit;
        if (hit) shown++;
    });

    // Grup yang seluruh isinya tersaring ikut disembunyikan.
    dd.querySelectorAll('.dd-group').forEach((group) => {
        let sibling = group.nextElementSibling;
        let anyVisible = false;

        while (sibling && !sibling.classList.contains('dd-group')) {
            if (sibling.matches('[data-dd-option]') && !sibling.hidden) anyVisible = true;
            sibling = sibling.nextElementSibling;
        }

        group.hidden = !anyVisible;
    });

    const empty = dd.querySelector('[data-dd-empty]');
    if (empty) empty.hidden = shown !== 0;

    if (shown > 0) {
        setActive(dd, visibleOptions(dd)[0]);
    }
}

function bind(dd) {
    if (dd.dataset.ddBound) return;
    dd.dataset.ddBound = '1';

    const trigger = dd.querySelector('[data-dd-trigger]');
    const panel = dd.querySelector('[data-dd-panel]');
    const hidden = dd.querySelector('[data-dd-value]');
    const search = dd.querySelector('[data-dd-search]');

    trigger?.addEventListener('click', () => {
        if (dd.hasAttribute(OPEN_FLAG)) close(dd);
        else open(dd);
    });

    // Klik opsi: pakai mousedown agar tidak kalah oleh blur/click di luar.
    panel?.addEventListener('click', (event) => {
        const option = event.target.closest('[data-dd-option]');
        if (option) selectOption(dd, option);
    });

    search?.addEventListener('input', () => filter(dd, search.value));

    // Type-ahead tanpa kotak pencarian: mengetik huruf melompat ke opsi.
    trigger?.addEventListener('keydown', (event) => {
        if (!dd.hasAttribute(OPEN_FLAG) && (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            open(dd);
            return;
        }

        if (!dd.hasAttribute(OPEN_FLAG)) return;

        const options = visibleOptions(dd);
        const active = dd.querySelector('[data-dd-option].is-active');
        const index = options.indexOf(active);

        const move = (next) => {
            event.preventDefault();
            const target = options[Math.max(0, Math.min(options.length - 1, next))];
            setActive(dd, target);
        };

        switch (event.key) {
            case 'ArrowDown': move(index + 1); break;
            case 'ArrowUp': move(index - 1); break;
            case 'Home': move(0); break;
            case 'End': move(options.length - 1); break;
            case 'Enter':
                event.preventDefault();
                if (active) selectOption(dd, active);
                break;
            case 'Escape':
                event.preventDefault();
                close(dd);
                trigger.focus();
                break;
            case 'Tab':
                close(dd);
                break;
            default:
                // Type-ahead: huruf/angka melompat ke opsi berawalan itu.
                if (event.key.length === 1 && /\S/.test(event.key)) {
                    const char = event.key.toLowerCase();
                    const hit = visibleOptions(dd).find((o) => (o.dataset.label ?? '').toLowerCase().startsWith(char));
                    if (hit) { event.preventDefault(); setActive(dd, hit); }
                }
        }
    });

    // Keyboard di dalam kotak pencarian.
    search?.addEventListener('keydown', (event) => {
        const options = visibleOptions(dd);
        const active = dd.querySelector('[data-dd-option].is-active');
        const index = options.indexOf(active);

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive(dd, options[Math.min(options.length - 1, index + 1)]);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(dd, options[Math.max(0, index - 1)]);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            // Enter di kotak cari memilih opsi yang sedang disorot.
            if (active) selectOption(dd, active);
            else if (options.length) selectOption(dd, options[0]);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            close(dd);
            trigger?.focus();
        }
    });

    // Nilai terkirim dari server tapi opsinya tidak ada (mis. data lama):
    // kosongkan supaya tidak diam-diam mengirim nilai yang tak terlihat.
    if (hidden && hidden.value && !dd.querySelector('[data-dd-option].is-selected')) {
        hidden.value = '';
        const labelEl = dd.querySelector('[data-dd-label]');
        if (labelEl) labelEl.classList.add('is-placeholder');
    }
}

export function initDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach(bind);

    // Satu listener global, bukan per komponen: lebih murah dan tidak bocor
    // saat komponen ditambahkan dinamis.
    if (!window.__ddGlobalBound) {
        window.__ddGlobalBound = true;

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-dropdown]')) closeAll();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAll();
        });

        /*
         * Tutup saat HALAMAN digulir, tetapi jangan reaktif terhadap scroll
         * di dalam panel (daftar opsi panjang) maupun scroll yang dipicu
         * fokus ke kotak pencarian - keduanya menutup dropdown tepat setelah
         * ia dibuka, sehingga dropdown tampak tidak bisa dibuka sama sekali.
         */
        document.addEventListener('scroll', (event) => {
            const target = event.target;

            if (target instanceof Element
                && (target.closest('[data-dd-panel]') || target.closest('[data-dropdown]'))) {
                return;
            }

            closeAll();
        }, { passive: true, capture: true });
    }
}