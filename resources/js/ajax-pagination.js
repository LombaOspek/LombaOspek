/**
 * Paginasi tanpa reload untuk tabel akun dan daftar gugus.
 *
 * Kenapa: setiap klik halaman memuat ulang seluruh halaman admin — header,
 * sidebar, form import, form gugus, dan modal ikut dikirim ulang padahal tidak
 * berubah. Dengan 3.000+ akun itu terasa lambat di jaringan kampus.
 *
 * Cara kerja:
 *  1. Intersep klik pada tautan paginasi di dalam region yang dikenal.
 *  2. Minta fragmen (header X-Partial) — server membalas markup tabel saja.
 *  3. Tukar isi region, lalu replaceState agar URL dan tombol kembali browser
 *     tetap benar.
 *
 * Tanpa JavaScript tautan <a href> tetap berfungsi seperti biasa. Karena itu
 * href TIDAK dihapus: memperbaikinya sebagai progressive enhancement, bukan
 * menggantinya.
 */

const REGION_SELECTORS = [
    '[data-accounts-region]',
    '[data-group-list-region]',
    '[data-fragment-accounts]',
    '[data-fragment-group-list]',
];

/** Apakah tautan ini milik paginasi yang kita tangani? */
function isPaginationLink(link) {
    return link instanceof HTMLAnchorElement
        && link.closest('nav[aria-label="Navigasi halaman"]')
        && REGION_SELECTORS.some((sel) => link.closest(sel));
}

async function loadPage(url, { push = true } = {}) {
    const region = document.querySelector('[data-accounts-region]');
    if (!region) {
        window.location.href = url;
        return;
    }

    // Penanda sibuk: tanpa ini, klik beruntun membuat dua respons datang
    // tidak berurutan dan isi tabel bisa berakhir di halaman yang salah.
    document.body.dataset.paginationBusy = '1';

    try {
        const res = await fetch(url, {
            headers: {
                'X-Partial': '1',
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) throw new Error('HTTP ' + res.status);

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        // Tukar tiap region yang ada di fragmen.
        const pairs = [
            ['[data-fragment-accounts]', '[data-accounts-region]'],
            ['[data-fragment-group-list]', '[data-group-list-region]'],
        ];

        pairs.forEach(([fromSel, toSel]) => {
            const incoming = doc.querySelector(fromSel);
            const current = document.querySelector(toSel);

            if (incoming && current) {
                current.replaceChildren(...incoming.childNodes);
            }
        });

        // Modal & dropdown perlu dipasang ulang: elemennya baru.
        window.Ospek?.rebind?.();

        if (push) {
            window.history.replaceState({ pagination: true }, '', url);
        }

        // Gulir ke tabel, bukan ke atas halaman: pengguna sedang melihat daftar.
        document.querySelector('[data-accounts-region]')
            ?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    } catch (error) {
        // Gagal jaringan: jatuh ke navigasi biasa, jangan biarkan halaman diam.
        window.location.href = url;
    } finally {
        delete document.body.dataset.paginationBusy;
    }
}

export function initAjaxPagination() {
    if (!document.querySelector('[data-accounts-region]')) return;

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link || !isPaginationLink(link)) return;

        // Biarkan browser menangani Ctrl/Cmd+klik (buka tab baru).
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

        if (document.body.dataset.paginationBusy === '1') {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        loadPage(link.href);
    });

    // Tombol kembali/maju browser: muat ulang fragmen sesuai URL.
    window.addEventListener('popstate', () => {
        loadPage(window.location.href, { push: false });
    });
}