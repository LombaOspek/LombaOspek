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
                <div class="mt-2.5 rounded-sm border-[1.5px] border-amber bg-amber/20 p-2.5">
                    <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.1em] text-amber-deep">Baris dilewati</p>
                    <ul class="mt-1 flex flex-col gap-0.5">
                        @foreach (session('import_skipped') as $skip)
                            <li class="text-[0.66rem] font-semibold text-ink-soft">• {{ $skip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.accounts.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-col gap-2.5" data-submit-lock>
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
                        class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-medium file:mr-2 file:rounded-xs file:border-0 file:bg-amber file:px-2 file:py-1 file:font-bold focus:outline-none"
                    >
                    @error('file')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    <p class="mt-1 text-[0.62rem] font-medium text-ink-faint">Maksimal 25 MB. Satu NIM duplikat dilewati, bukan menggagalkan seluruh berkas.</p>
                </div>

                <label class="flex cursor-pointer items-start gap-2 rounded-sm border-[1.5px] border-ink/40 bg-paper-dim p-2">
                    <input type="checkbox" name="auto_generate_password" value="1" class="mt-0.5 size-3.5 rounded-xs border-2 border-ink accent-teal">
                    <span class="text-[0.68rem] font-semibold leading-snug text-ink-soft">
                        Buat kata sandi acak per akun (jika tidak dicentang, sandi awal <code class="font-mono font-bold">password</code>)
                    </span>
                </label>

                <button type="submit" class="btn btn-primary w-full">
                    <x-icon name="plus" class="size-4" :stroke="2.4" />
                    Buat Akun Massal
                </button>
            </form>
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

                <div class="grid gap-2 sm:grid-cols-2">
                    <div>
                        <label for="study_program" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                            Program Studi <span class="text-vermilion">*</span>
                        </label>
                        <input
                            type="text" id="study_program" name="study_program" required maxlength="120"
                            list="major-list" value="{{ old('study_program', 'Sistem Informasi') }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                        >
                        <datalist id="major-list">
                            @foreach ($majors as $major)
                                <option value="{{ $major }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div>
                        <label for="mentor_id" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                            LO Utama <span class="text-vermilion">*</span>
                        </label>
                        <select
                            id="mentor_id" name="mentor_id" required
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                        >
                            <option value="">— Pilih LO —</option>
                            @foreach ($availableOfficers as $officer)
                                <option value="{{ $officer->id }}" @selected(old('mentor_id') == $officer->id)>
                                    {{ $officer->name }} ({{ $officer->nim }})
                                </option>
                            @endforeach
                        </select>
                        @error('mentor_id')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="capacity" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Kapasitas</label>
                        <input
                            type="number" id="capacity" name="capacity" min="1" max="500" value="{{ old('capacity', 75) }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                        >
                    </div>

                    <div>
                        <label for="badge_color" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Warna Lencana</label>
                        <select
                            id="badge_color" name="badge_color"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                        >
                            @foreach (['amber' => 'Kuning Amber', 'teal' => 'Hijau Teal', 'sky' => 'Biru Langit', 'vermilion' => 'Merah Bata'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('badge_color') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    <x-icon name="plus" class="size-4" :stroke="2.4" />
                    Simpan &amp; Daftarkan Gugus
                </button>
            </form>

            {{-- Daftar gugus --}}
            @if ($groups->isNotEmpty())
                <div class="mt-3 border-t-2 border-dashed border-ink/25 pt-2.5">
                    <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Gugus terdaftar</p>
                    <ul class="mt-1.5 flex flex-col gap-1">
                        @foreach ($groups as $group)
                            <li class="flex flex-wrap items-center justify-between gap-1.5 rounded-xs bg-paper-dim px-2 py-1">
                                <span class="min-w-0 truncate text-[0.68rem] font-bold">{{ $group->name }}</span>
                                <span class="flex shrink-0 items-center gap-1.5">
                                    <span class="font-mono text-[0.6rem] font-bold text-ink-faint">{{ $group->invitation_code }}</span>
                                    <span class="stamp bg-paper text-[0.54rem]">{{ $group->members_count }} anggota</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
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
                <select name="role" class="rounded-sm border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none">
                    <option value="">Semua peran</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <select name="major" class="rounded-sm border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none">
                    <option value="">Semua jurusan</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major }}" @selected($filters['major'] === $major)>{{ $major }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-ghost btn-sm">
                    <x-icon name="search" class="size-3.5" :stroke="2.2" />
                    Filter
                </button>
                @if (filled($filters['q']) || filled($filters['role']) || filled($filters['major']))
                    <a href="{{ route('admin.accounts') }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto border-t-2 border-ink/10">
            <table class="w-full min-w-[46rem] border-collapse text-left">
                <thead>
                    <tr class="bg-paper-dim">
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Nama Pengguna</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">NIM</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Peran</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Jurusan</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Gugus</th>
                        <th class="px-3 py-2 text-right text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-ink/10">
                    @forelse ($users as $user)
                        <tr class="transition hover:bg-paper-dim/60">
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-sky text-[0.6rem] font-extrabold">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[0.74rem] font-bold leading-tight">{{ $user->name }}</span>
                                        <span class="block truncate text-[0.62rem] font-medium text-ink-faint">{{ $user->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 font-mono text-[0.68rem] font-bold">{{ $user->nim }}</td>
                            <td class="px-3 py-2">
                                <span class="stamp {{ $user->role->badgeClasses() }} text-[0.54rem]">{{ $user->role->label() }}</span>
                            </td>
                            <td class="px-3 py-2 text-[0.7rem] font-semibold text-ink-soft">{{ $user->major ?: '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($user->group)
                                    <span class="stamp bg-paper-dim text-[0.54rem]">{{ $user->group->name }}</span>
                                @else
                                    <span class="text-[0.66rem] font-semibold text-ink-faint">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-paper transition hover:bg-sky"
                                        data-role-toggle="role-{{ $user->id }}"
                                        aria-label="Ubah peran {{ $user->name }}"
                                        title="Ubah peran & gugus"
                                    >
                                        <x-icon name="settings" class="size-3" :stroke="2" />
                                    </button>

                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.accounts.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-paper transition hover:bg-vermilion hover:text-white"
                                                aria-label="Hapus {{ $user->name }}"
                                                title="Hapus akun"
                                            >
                                                <x-icon name="x" class="size-3" :stroke="2.4" />
                                            </button>
                                        </form>
                                    @endunless
                                </div>

                                {{-- Form ubah peran --}}
                                <form
                                    method="POST"
                                    action="{{ route('admin.accounts.role', $user) }}"
                                    id="role-{{ $user->id }}"
                                    class="mt-1.5 hidden flex-col gap-1 rounded-xs border-[1.5px] border-ink/50 bg-paper-dim p-1.5"
                                    data-submit-lock
                                >
                                    @csrf
                                    <select name="role" class="rounded-xs border-[1.5px] border-ink bg-white px-1.5 py-1 text-[0.66rem] font-semibold focus:outline-none">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    <select name="group_id" class="rounded-xs border-[1.5px] border-ink bg-white px-1.5 py-1 text-[0.66rem] font-semibold focus:outline-none">
                                        <option value="">— Tanpa gugus —</option>
                                        @foreach ($groups as $group)
                                            <option value="{{ $group->id }}" @selected($user->group_id === $group->id)>{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-[0.74rem] font-semibold text-ink-faint">
                                Tidak ada akun yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t-2 border-ink/10 p-3">
                {{ $users->links() }}
            </div>
        @endif
    </section>
</div>
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