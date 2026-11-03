/**
 * Sinkronisasi dropdown Program Studi → LO Utama.
 *
 * Aturan "satu LO satu jurusan" ditegakkan di server, tetapi kalau form
 * menampilkan semua LO, panitia baru tahu setelah submit gagal. Menyaring
 * pilihan di klien menghilangkan percobaan gagal itu.
 *
 * Data LO per prodi dititipkan sebagai JSON, bukan diambil lewat AJAX:
 * jumlahnya kecil (belasan), dan satu request tambahan hanya menambah
 * kemungkinan gagal di jaringan kampus.
 */
export function initMajorOfficerLink() {
    document.querySelectorAll('[data-major-select]').forEach((majorDd) => {
        const form = majorDd.closest('form');
        const mentorDd = form?.querySelector('[data-mentor-select]');
        if (!mentorDd) return;

        const panes = form.querySelector('[data-officer-map]');
        let map = {};

        try {
            map = JSON.parse(panes?.textContent ?? '{}');
        } catch {
            map = {};
        }

        const rebuild = () => {
            const major = majorDd.querySelector('[data-dd-value]')?.value ?? '';
            const officers = map[major] ?? {};
            const optionsBox = mentorDd.querySelector('[data-dd-options]');
            const hidden = mentorDd.querySelector('[data-dd-value]');
            const labelEl = mentorDd.querySelector('[data-dd-label]');
            const empty = mentorDd.querySelector('[data-dd-empty]');

            if (!optionsBox) return;

            // Buang opsi lama, sisakan pesan "tidak ada".
            optionsBox.querySelectorAll('[data-dd-option]').forEach((o) => o.remove());

            const entries = Object.entries(officers);

            entries.forEach(([value, label]) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dd-option';
                btn.dataset.ddOption = '';
                btn.dataset.value = value;
                btn.dataset.label = label;
                btn.setAttribute('role', 'option');
                btn.setAttribute('aria-selected', 'false');

                const text = document.createElement('span');
                text.className = 'dd-option-text';
                // textContent: nama LO berasal dari database, bukan HTML.
                text.textContent = label;

                btn.appendChild(text);
                optionsBox.insertBefore(btn, empty);
            });

            // LO yang tadinya dipilih mungkin tidak ada di prodi baru:
            // kosongkan supaya tidak mengirim nilai yang tak terlihat.
            const stillValid = entries.some(([v]) => String(v) === String(hidden?.value ?? ''));
            if (!stillValid && hidden) {
                hidden.value = '';
                if (labelEl) {
                    labelEl.textContent = '— Pilih LO —';
                    labelEl.classList.add('is-placeholder');
                }
            }

            if (empty) empty.hidden = entries.length !== 0;
        };

        // Dengarkan perubahan nilai: dropdown custom mengubah hidden input,
        // jadi event 'change' dipancarkan manual oleh dropdown.js.
        majorDd.addEventListener('dd:change', rebuild);
    });
}