/**
 * Modal CRUD akun: tambah, edit, reset sandi, hapus/pulihkan.
 *
 * Satu modal dipakai untuk semua mode. Isinya diisi dari atribut data tombol
 * pemicu, bukan dirender sekali per baris tabel - tabel berisi 10 baris dan
 * merender satu form tersembunyi per aksi membuat DOM membengkak.
 */

const MODES = {
    create: {
        title: 'Tambah Akun',
        subtitle: 'Akun baru mendapat sandi sementara yang ditampilkan sekali.',
        submit: 'Buat Akun',
        method: 'POST',
        url: (row) => row.dataset.urlCreate,
        fields: ['name', 'nim', 'email', 'role', 'major', 'group'],
    },
    edit: {
        title: 'Ubah Akun',
        subtitle: 'NIM tidak bisa diubah karena dipakai untuk login.',
        submit: 'Simpan Perubahan',
        method: 'PUT',
        url: (row) => row.dataset.urlUpdate,
        fields: ['name', 'email', 'major', 'group'],
    },
    password: {
        title: 'Setel Ulang Sandi',
        subtitle: 'Sandi baru ditampilkan sekali setelah disimpan.',
        submit: 'Setel Ulang',
        method: 'POST',
        url: (row) => row.dataset.urlPassword,
        fields: ['password'],
    },
    delete: {
        title: 'Hapus Akun',
        subtitle: 'Akun dipindahkan ke arsip, bukan dihapus permanen.',
        submit: 'Hapus Akun',
        method: 'DELETE',
        url: (row) => row.dataset.urlDelete,
        fields: ['confirm'],
    },
    restore: {
        title: 'Pulihkan Akun',
        subtitle: 'Akun kembali aktif dengan seluruh riwayatnya.',
        submit: 'Pulihkan',
        method: 'POST',
        url: (row) => row.dataset.urlRestore,
        fields: [],
    },
};

const ALL_FIELDS = ['name', 'nim', 'email', 'role', 'major', 'group', 'confirm', 'password'];

function setValue(dd, value, label) {
    if (!dd) return;
    const hidden = dd.querySelector('[data-dd-value]');
    const labelEl = dd.querySelector('[data-dd-label]');
    const placeholder = labelEl?.dataset.placeholder ?? '— Pilih —';

    if (hidden) hidden.value = value ?? '';

    if (labelEl) {
        const found = Array.from(dd.querySelectorAll('[data-dd-option]'))
            .find((o) => String(o.dataset.value) === String(value ?? ''));

        if (found) {
            labelEl.textContent = found.dataset.label;
            labelEl.classList.remove('is-placeholder');
        } else {
            labelEl.textContent = label || placeholder;
            labelEl.classList.add('is-placeholder');
        }
    }

    dd.querySelectorAll('[data-dd-option]').forEach((o) => {
        const match = String(o.dataset.value) === String(value ?? '');
        o.classList.toggle('is-selected', match);
        o.setAttribute('aria-selected', match ? 'true' : 'false');
    });
}

export function initAccountActions() {
    const modal = document.querySelector('[data-account-modal]');
    if (!modal) return;

    const form = modal.querySelector('[data-modal-form]');
    const titleEl = modal.querySelector('[data-modal-title]');
    const subtitleEl = modal.querySelector('[data-modal-subtitle]');
    const submitEl = modal.querySelector('[data-modal-submit]');
    const methodEl = modal.querySelector('[data-modal-method]');
    const confirmTarget = modal.querySelector('[data-confirm-target]');
    const modeEl = modal.querySelector('[name="_modal_mode"]');
    const idEl = modal.querySelector('[name="_modal_id"]');

    const field = (name) => modal.querySelector(`[data-field-${name}]`);
    // Nama field di server "group_id", bukan "group": kalau salah, hidden
    // input gugus tidak pernah diisi dan akun tersimpan tanpa gugus.
    const dd = (name) => modal.querySelector(`[data-dropdown]:has([name="${name}"])`);

    let lastFocus = null;

    const showFields = (mode) => {
        ALL_FIELDS.forEach((name) => {
            const el = field(name);
            if (!el) return;
            const visible = MODES[mode].fields.includes(name);
            el.classList.toggle('hidden', !visible);

            // Field yang disembunyikan WAJIB dinonaktifkan: input yang disabled
            // tidak ikut terkirim, dan input hidden yang tetap aktif bisa
            // mengirim nilai lama yang tidak terlihat (mis. nim saat edit).
            el.querySelectorAll('input, button, select').forEach((input) => {
                if (input.dataset.modalCancel) return;
                input.disabled = !visible;
            });
        });
    };

    const openModal = (mode, row) => {
        const cfg = MODES[mode];
        if (!cfg) return;

        lastFocus = document.activeElement;

        // Dicatat agar server bisa membuka kembali modal ini saat validasi
        // gagal, bukan menyembunyikan pesan error di dalam modal tertutup.
        modeEl.value = mode;
        idEl.value = row.dataset.accountId ?? '';

        titleEl.textContent = cfg.title;
        subtitleEl.textContent = cfg.subtitle;
        submitEl.textContent = cfg.submit;
        methodEl.value = cfg.method;
        form.setAttribute('action', cfg.url(row) ?? form.getAttribute('action'));

        // Bersihkan nilai sebelumnya supaya data akun sebelumnya tidak bocor
        // ke akun berikutnya.
        form.querySelectorAll('input[type="text"], input[type="email"]').forEach((i) => { i.value = ''; });
        setValue(dd('role'), row.dataset.role ?? '', row.dataset.roleLabel);
        setValue(dd('major'), row.dataset.major ?? '', row.dataset.major);
        setValue(dd('group_id'), row.dataset.groupId ?? '', row.dataset.groupLabel ?? row.dataset.groupName);

        showFields(mode);

        if (mode === 'create') {
            setValue(dd('role'), row.dataset.role ?? 'student');
        }

        // Nama & email: untuk edit diisi dari baris, untuk create dibiarkan
        // kosong agar panitia mengetik sendiri (bukan mewarisi akun sebelumnya).
        if (mode === 'edit') {
            const nameInput = modal.querySelector('#am-name');
            const emailInput = modal.querySelector('#am-email');

            if (nameInput) nameInput.value = row.dataset.name ?? '';
            if (emailInput) emailInput.value = row.dataset.email ?? '';
        }

        if (mode === 'delete') {
            // Konfirmasi memakai NIM kalau ada, kalau tidak namanya.
            const target = row.dataset.nim || row.dataset.name || '';
            if (confirmTarget) confirmTarget.textContent = target;
            const input = field('confirm')?.querySelector('input[name="confirm_identity"]');
            if (input) {
                input.value = '';
                input.placeholder = `Ketik: ${target}`;
            }
            submitEl.classList.remove('btn-primary');
            submitEl.classList.add('btn-danger');
        } else {
            submitEl.classList.remove('btn-danger');
            submitEl.classList.add('btn-primary');
        }

        // NIM hanya relevan saat membuat akun.
        const nimHint = modal.querySelector('[data-nim-hint]');
        if (nimHint) nimHint.classList.toggle('hidden', mode !== 'create');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Fokus ke field pertama yang terlihat, bukan ke body: dialog dengan
        // aria-modal harus memindahkan fokus ke dalamnya.
        requestAnimationFrame(() => {
            const first = Array.from(form.querySelectorAll('input:not([disabled]):not([type="hidden"]), [data-dd-trigger]:not([disabled])'))
                .find((el) => el.offsetParent !== null);
            first?.focus({ preventScroll: true });
        });
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        lastFocus?.focus({ preventScroll: true });
    };

    // Pemicu per baris tabel: satu listener untuk seluruh tabel.
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-account-action]');
        if (!trigger) return;

        event.preventDefault();
        openModal(trigger.dataset.accountAction, trigger.closest('[data-account-row]') ?? trigger);
    });

    // Tombol "Tambah Akun" di kepala halaman: tidak punya baris tabel, jadi
    // memakai modal itu sendiri sebagai sumber data kosong.
    document.querySelectorAll('[data-account-create]').forEach((btn) => {
        btn.addEventListener('click', () => {
            btn.dataset.accountAction = 'create';
            openModal('create', btn);
        });
    });

    modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', closeModal);
    });

    // Klik latar gelap menutup, klik di dalam kartu tidak.
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', (event) => {
        if (modal.classList.contains('hidden')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        // Focus trap: Tab tidak boleh keluar dari dialog.
        if (event.key === 'Tab') {
            const focusables = Array.from(
                modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), [tabindex]:not([tabindex="-1"])'),
            ).filter((el) => el.offsetParent !== null);

            if (focusables.length === 0) return;

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    // Buka otomatis bila server mengembalikan error validasi: tanpa ini,
    // pengguna melihat halaman termuat ulang tanpa tahu apa yang salah,
    // karena pesan error berada di dalam modal yang tertutup.
    if (modal.dataset.reopen) {
        const mode = modal.dataset.reopen;
        const rowId = modal.dataset.reopenId;

        // Mode create tidak punya baris tabel: pakai tombol "Tambah Akun"
        // sebagai sumber data kosong, sama seperti saat diklik manual.
        const source = rowId
            ? document.querySelector(`[data-account-row][data-account-id="${rowId}"]`)
            : document.querySelector('[data-account-create]');

        if (source) {
            if (!source.dataset.accountAction) source.dataset.accountAction = mode;
            openModal(mode, source);
        }
    }
}