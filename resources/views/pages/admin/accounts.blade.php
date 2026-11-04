@extends('layouts.app')

@section('title', 'Manajemen Akun & Gugus')
@section('page-title', 'Manajemen Akun Mahasiswa & LO')
@section('page-kicker', 'Superadmin · Multi-tenant')

@section('content')
<div class="flex flex-col gap-4">

    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-1.5">
                <span class="stamp bg-vermilion text-white">
                    <x-icon name="shield" class="size-3" :stroke="2.2" />
                    Superadmin
                </span>
                <span class="stamp bg-paper">Modul Pentadbiran Mahasiswa</span>
            </p>
            <h1 class="mt-1.5 font-display text-[1.5rem] font-extrabold leading-tight tracking-tight sm:text-[1.8rem]">
                Manajemen Akun &amp; Gugus
            </h1>
            <p class="mt-0.5 max-w-2xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Kelola pendaftaran massal mahasiswa baru, penempatan gugus, dan penugasan Liaison Officer.
            </p>
        </div>

        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('admin.accounts.template') }}" class="btn btn-ghost btn-sm">
                <x-icon name="certificate" class="size-3.5" :stroke="2" />
                Template CSV
            </a>

            {{-- Tambah satu akun tanpa CSV: mahasiswa telat daftar / LO susulan. --}}
            <button type="button" class="btn btn-primary btn-sm" data-account-create>
                <x-icon name="plus" class="size-3.5" :stroke="2.6" />
                Tambah Akun
            </button>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-2.5 lg:grid-cols-4" aria-label="Ringkasan akun">
        <x-stat-tile :value="$stats['students']" label="Total Mahasiswa" icon="users" tone="amber" caption="Akun aktif" />
        <x-stat-tile :value="$stats['groups']" label="Gugus Aktif" icon="layers" tone="paper" caption="Kelompok orientasi" />
        <x-stat-tile :value="$stats['officers']" label="Liaison Officer" icon="user" tone="sky" caption="Kakak tingkat" />
        <x-stat-tile :value="$stats['verified_today']" label="Verifikasi Hari Ini" icon="check-circle" tone="teal" caption="Bukti disetujui" />
    </section>

    <div class="grid gap-4 xl:grid-cols-2">

        {{-- Bulk import --}}
        <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
            <x-panel-header
                title="Import Massal Mahasiswa"
                subtitle="Unggah CSV untuk membuat akun sekaligus"
                icon="layers"
            />

            <div class="mt-3 rounded-sm border-[1.5px] border-dashed border-ink/40 bg-paper-dim p-2.5">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Urutan kolom wajib</p>
                <p class="mt-1 font-mono text-[0.68rem] font-bold text-ink-soft">
                    NIM, Nama Penuh, Jurusan, Email Kampus, Kode Gugus
                </p>
            </div>

            @if (session('import_skipped'))
                <div class="mt-2.5 rounded-sm border-[1.5px] border-gold bg-gold/20 p-2.5">
                    <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.1em] text-gold-deep">Baris dilewati</p>
                    <ul class="mt-1 flex flex-col gap-0.5">
                        @foreach (session('import_skipped') as $skip)
                            <li class="text-[0.66rem] font-semibold text-ink-soft">• {{ $skip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Kredensial hasil import: tampil SEKALI di sini, tidak pernah
                 disimpan plaintext di database. Tanpa ini akun hasil import
                 tidak bisa login dan hanya bisa dipulihkan lewat DB. --}}
            @if (session('import_credentials'))
                @php $credRows = session('import_credentials'); @endphp

                <div class="mt-2.5 rounded-sm border-2 border-vermilion bg-vermilion/10 p-2.5" data-import-credentials>
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.1em] text-vermilion-deep">
                                Simpan sekarang — hanya tampil sekali
                            </p>
                            <p class="mt-0.5 text-[0.64rem] font-semibold leading-snug text-ink-soft">
                                {{ count($credRows) }} akun dibuat. Unduh CSV ini lalu bagikan ke mahasiswa;
                                sandi tidak bisa dilihat lagi setelah halaman ditutup.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-sm border-2 border-ink bg-gold px-2 py-1 font-display text-[0.62rem] font-extrabold uppercase"
                            data-download-credentials
                        >
                            Unduh CSV
                        </button>
                    </div>

                    <div class="mt-2 max-h-48 overflow-auto rounded-xs border-[1.5px] border-ink/30 bg-paper">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-paper-dim">
                                <tr>
                                    <th class="px-2 py-1 font-display text-[0.56rem] font-extrabold uppercase tracking-[0.08em]">NIM</th>
                                    <th class="px-2 py-1 font-display text-[0.56rem] font-extrabold uppercase tracking-[0.08em]">Nama</th>
                                    <th class="px-2 py-1 font-display text-[0.56rem] font-extrabold uppercase tracking-[0.08em]">Sandi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($credRows as $cred)
                                    <tr class="border-t border-ink/10">
                                        <td class="px-2 py-1 font-mono text-[0.62rem] font-bold">{{ $cred['nim'] }}</td>
                                        <td class="px-2 py-1 text-[0.62rem] font-semibold">{{ $cred['name'] }}</td>
                                        <td class="px-2 py-1 font-mono text-[0.62rem] font-bold text-vermilion-deep">{{ $cred['password'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @push('scripts')
                    <script type="application/json" id="import-credentials-data">@json($credRows)</script>
                    <script type="module">
                        // Unduh sebagai CSV supaya panitia bisa mencetak dan membagikan
                        // kredensial tanpa menyalin satu per satu dari tabel.
                        const payload = JSON.parse(document.getElementById('import-credentials-data').textContent);

                        document.querySelector('[data-download-credentials]')?.addEventListener('click', () => {
                            const escape = (v) => `"${String(v).replace(/"/g, '""')}"`;
                            const lines = [['NIM', 'Nama', 'Sandi'].map(escape).join(',')];

                            payload.forEach((row) => {
                                lines.push([row.nim, row.name, row.password].map(escape).join(','));
                            });

                            const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');

                            a.href = url;
                            a.download = 'kredensial-ospekquest-' + new Date().toISOString().slice(0, 10) + '.csv';
                            a.click();

                            URL.revokeObjectURL(url);
                        });
                    </script>
                @endpush
            @endif

            <form
                method="POST"
                action="{{ route('admin.accounts.import') }}"
                enctype="multipart/form-data"
                class="mt-3 flex flex-col gap-2.5"
                data-import-form
                data-init-endpoint="{{ route('admin.accounts.import.init') }}"
                data-batch-endpoint="{{ route('admin.accounts.import.batch', ['importId' => '__ID__']) }}"
            >
                @csrf

                <div>
                    <label for="file" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        Berkas CSV <span class="text-vermilion">*</span>
                    </label>
                    <input
                        type="file"
                        id="file"
                        name="file"
                        accept=".csv,text/csv"
                        required
                        class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-medium file:mr-2 file:rounded-xs file:border-0 file:bg-gold file:px-2 file:py-1 file:font-bold focus:outline-none"
                    >
                    @error('file')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    <p class="mt-1 text-[0.62rem] font-medium text-ink-faint">Maksimal 25 MB. Satu NIM duplikat dilewati, bukan menggagalkan seluruh berkas.</p>
                </div>

                <label class="flex cursor-pointer items-start gap-2 rounded-sm border-[1.5px] border-ink/40 bg-paper-dim p-2">
                    <input type="checkbox" name="auto_generate_password" value="1" class="mt-0.5 size-3.5 rounded-xs border-2 border-ink accent-brand">
                    <span class="text-[0.68rem] font-semibold leading-snug text-ink-soft">
                        Buat kata sandi acak per akun (jika tidak dicentang, sandi awal <code class="font-mono font-bold">password</code>)
                    </span>
                </label>

                <button type="submit" class="btn btn-primary w-full" data-import-submit>
                    <x-icon name="plus" class="size-4" :stroke="2.4" />
                    Mulai Import
                </button>
            </form>

            {{-- Panel progres: tersembunyi sampai import dimulai. --}}
            <div data-import-progress class="mt-3 hidden rounded-sm border-2 border-ink bg-paper-dim p-3">
                <div class="flex items-baseline justify-between gap-2">
                    <span class="font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Progres import</span>
                    <span data-progress-percent class="font-mono text-[0.7rem] font-bold text-brand">0%</span>
                </div>

                <div class="mt-1.5 h-3 w-full overflow-hidden rounded-xs border-2 border-ink bg-white">
                    <div
                        data-progress-bar
                        class="h-full bg-gold transition-[width] duration-200 ease-out"
                        style="width: 0%"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                    ></div>
                </div>

                <p data-progress-count class="mt-1.5 font-mono text-[0.62rem] font-semibold text-ink-soft">0 / 0 baris</p>
                <p data-progress-status class="mt-1 text-[0.62rem] font-medium text-ink-faint" aria-live="polite"></p>

                {{-- Kredensial: hanya tampil sekali, tidak pernah disimpan plaintext. --}}
                <div data-progress-credentials class="mt-2.5 hidden rounded-sm border-2 border-ink bg-white p-2">
                    <p class="text-[0.62rem] font-bold text-vermilion-deep">
                        Sandi sementara — catat atau unduh SEKARANG. Tidak bisa dilihat lagi.
                    </p>
                    <div class="mt-1 max-h-40 overflow-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="text-[0.56rem] font-bold uppercase tracking-[0.06em] text-ink-faint">
                                    <th class="px-2 py-1">NIM</th>
                                    <th class="px-2 py-1">Nama</th>
                                    <th class="px-2 py-1">Sandi</th>
                                </tr>
                            </thead>
                            <tbody data-progress-credential-rows></tbody>
                        </table>
                    </div>
                </div>

                <div data-progress-skipped class="mt-2 hidden rounded-sm border-[1.5px] border-ink/30 bg-white p-2">
                    <p class="text-[0.6rem] font-bold uppercase tracking-[0.06em] text-ink-faint">Baris bermasalah</p>
                    <ul data-progress-skipped-rows class="mt-1 space-y-0.5"></ul>
                </div>

                <button type="button" data-progress-done class="btn btn-secondary mt-2.5 hidden w-full">
                    Muat ulang daftar akun
                </button>
            </div>
        </section>

        {{-- Buat gugus --}}
        <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
            <x-panel-header
                title="Manajemen LO & Gugus"
                subtitle="Setiap gugus otomatis mendapat kode undangan resmi"
                icon="users"
            />

            <form method="POST" action="{{ route('admin.groups.store') }}" class="mt-3 flex flex-col gap-2.5" data-submit-lock>
                @csrf

                <div>
                    <label for="name" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        Nama Gugus <span class="text-vermilion">*</span>
                    </label>
                    <input
                        type="text" id="name" name="name" required maxlength="120"
                        value="{{ old('name') }}"
                        placeholder="Contoh: Kelompok 09 — Rajawali Siber"
                        class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                    >
                    @error('name')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                {{-- Peta LO per prodi, dibaca major-officer.js untuk menyaring. --}}
                <script type="application/json" data-officer-map>@json($officersByMajor)</script>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div>
                        {{--
                            Prodi bergrup per fakultas: panitia melihat konteks
                            "ini prodi fakultas mana" sambil memilih, dan tidak
                            perlu mengetik bebas (yang dulu bisa jadi "sistem
                            informasi" huruf kecil dan tidak dikenali sistem).
                        --}}
                        <x-dropdown
                            name="study_program"
                            label="Program Studi"
                            :options="$majorsByFaculty"
                            :selected="old('study_program', 'Sistem Informasi')"
                            placeholder="— Pilih program studi —"
                            :required="true"
                            :grouped="true"
                            :searchable="true"
                            data-major-select
                        />
                    </div>

                    <div>
                        {{--
                            LO disaring mengikuti prodi yang dipilih: aturan
                            "satu LO satu jurusan" ditegakkan di sini juga,
                            bukan hanya di server, supaya panitia tidak memilih
                            pasangan yang pasti ditolak.
                        --}}
                        <x-dropdown
                            name="mentor_id"
                            label="LO Utama"
                            :options="$officersByMajor[old('study_program', 'Sistem Informasi')] ?? []"
                            :selected="old('mentor_id')"
                            placeholder="— Pilih LO —"
                            :required="true"
                            hint="Hanya LO dari program studi yang sama."
                            data-mentor-select
                        />
                    </div>

                    <div>
                        <label for="capacity" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Kapasitas</label>
                        <input
                            type="number" id="capacity" name="capacity" min="1" max="500" value="{{ old('capacity', 75) }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                        >
                    </div>

                    <div>
                        <x-dropdown
                            name="badge_color"
                            label="Warna Lencana"
                            :options="['amber' => 'Kuning Amber', 'teal' => 'Hijau Teal', 'sky' => 'Biru Langit', 'vermilion' => 'Merah Bata']"
                            :selected="old('badge_color', 'amber')"
                            placeholder="— Pilih warna —"
                        />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    <x-icon name="plus" class="size-4" :stroke="2.4" />
                    Simpan &amp; Daftarkan Gugus
                </button>
            </form>

            {{-- Daftar gugus (dipaginasi, bisa dimuat ulang tanpa reload). --}}
            <div data-group-list-region>
                <x-group-list :groups="$groups" />
            </div>
        </section>
    </div>

    {{-- Tabel pengguna --}}
    <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
        <div class="p-4 pb-3">
            <x-panel-header
                title="Senarai Pengguna Berdaftar"
                :subtitle="$users->total().' akun ditemukan'"
                icon="user"
            />

            <form method="GET" action="{{ route('admin.accounts') }}" class="mt-3 flex flex-wrap items-center gap-1.5">
                <input
                    type="search" name="q" value="{{ $filters['q'] }}"
                    placeholder="Cari nama, NIM, email…"
                    class="w-44 rounded-sm border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none sm:w-56"
                >
                <div class="w-36">
                    <x-dropdown
                        name="role"
                        :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()"
                        :selected="$filters['role']"
                        placeholder="Semua peran"
                    />
                </div>
                <div class="w-44">
                    <x-dropdown
                        name="major"
                        :options="collect($majors)->mapWithKeys(fn ($m) => [$m => $m])->all()"
                        :selected="$filters['major']"
                        placeholder="Semua jurusan"
                        :searchable="true"
                    />
                </div>
                <button type="submit" class="btn btn-ghost btn-sm">
                    <x-icon name="search" class="size-3.5" :stroke="2.2" />
                    Filter
                </button>

                {{-- Toggle arsip: satu-satunya jalan menemukan akun yang sudah
                     dihapus, karena akun terarsip tidak muncul di daftar biasa. --}}
                @if ($filters['arsip'])
                    <a href="{{ route('admin.accounts', array_filter(['q' => $filters['q'], 'role' => $filters['role'], 'major' => $filters['major']])) }}"
                       class="btn btn-sm border-2 border-ink bg-gold font-bold"
                       title="Kembali ke daftar akun aktif">
                        <x-icon name="check" class="size-3.5" :stroke="2.6" />
                        Mode arsip aktif
                    </a>
                @else
                    <a href="{{ route('admin.accounts', array_filter(['q' => $filters['q'], 'role' => $filters['role'], 'major' => $filters['major'], 'arsip' => 1])) }}"
                       class="btn btn-ghost btn-sm"
                       title="Tampilkan akun yang sudah dihapus. Akun dihapus tidak hilang permanen dan bisa dipulihkan dari sini.">
                        <x-icon name="lock" class="size-3.5" :stroke="2.2" />
                        Lihat arsip
                        @if ($archivedCount > 0)
                            <span class="stamp bg-vermilion text-white text-[0.5rem]">{{ $archivedCount }}</span>
                        @endif
                    </a>
                @endif

                @if (filled($filters['q']) || filled($filters['role']) || filled($filters['major']))
                    <a href="{{ route('admin.accounts') }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
            </form>
        </div>

        @include('pages.admin.partials.accounts-table', [
            'users' => $users,
            'filters' => $filters,
            'roles' => $roles,
            'groups' => $allGroups,
        ])
    </section>
</div>

{{-- Modal CRUD akun (tambah / ubah / sandi / hapus). --}}
{{-- Modal memakai daftar gugus LENGKAP, bukan paginator: dropdown gugus tidak
     boleh hanya berisi 8 gugus dari halaman yang sedang dibuka. --}}
<x-account-modal :groups="$allGroups" :majors-by-faculty="$majorsByFaculty" />
@endsection

@push('scripts')
<script type="module">
    document.querySelectorAll('[data-role-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const form = document.getElementById(btn.dataset.roleToggle);
            if (!form) return;
            form.classList.toggle('hidden');
            form.classList.toggle('flex');
            window.Ospek?.playTone('tap');
        });
    });
</script>
@endpush