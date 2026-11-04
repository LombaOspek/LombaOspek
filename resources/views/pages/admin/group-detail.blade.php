@extends('layouts.app')

@section('title', 'Gugus ' . $group->name)
@section('page-title', 'Kelola Anggota Gugus')
@section('page-kicker', $group->study_program)

@section('content')
<div class="flex flex-col gap-4" data-group-detail>

    {{-- Kepala --}}
    <section class="rounded-xl border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('admin.accounts') }}" class="text-[0.64rem] font-bold uppercase tracking-[0.08em] text-ink-faint underline-offset-2 hover:underline">
                    ← Kembali ke akun &amp; gugus
                </a>
                <h1 class="mt-1 font-display text-[1.35rem] font-extrabold leading-tight tracking-tight sm:text-[1.6rem]">
                    {{ $group->name }}
                </h1>
                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-[0.72rem] font-semibold text-ink-soft">
                    <span class="stamp bg-brand-soft text-[0.54rem]">{{ $group->study_program }}</span>
                    <span class="font-mono text-[0.64rem] text-ink-faint">{{ $group->invitation_code }}</span>
                </p>
            </div>

            {{-- Kapasitas: angka + bar, karena "12 / 30" saja tidak menunjukkan
                 seberapa penuh tanpa menghitung sendiri. --}}
            <div class="w-full shrink-0 rounded-md border-2 border-ink bg-paper-dim px-3 py-2 sm:w-56">
                <p class="font-mono text-[0.66rem] font-bold">
                    {{ $members->count() }} / {{ $capacity ?: '∞' }} anggota
                </p>
                @if ($capacity > 0)
                    @php $pct = min(100, (int) round(($members->count() / $capacity) * 100)); @endphp
                    <div class="mt-1 h-2.5 w-full overflow-hidden rounded-xs border-2 border-ink bg-white">
                        <div class="h-full {{ $pct >= 100 ? 'bg-vermilion' : 'bg-gold' }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <p class="mt-1 text-[0.58rem] font-medium text-ink-faint">
                        {{ $available }} slot tersisa
                    </p>
                @endif
            </div>
        </div>

        @if ($officers->isNotEmpty())
            <p class="mt-2 text-[0.66rem] font-medium text-ink-soft">
                <strong class="font-bold text-ink">LO sejurusan:</strong>
                {{ $officers->pluck('name')->implode(', ') }}
            </p>
        @else
            <p class="mt-2 rounded-sm border-2 border-vermilion-deep bg-vermilion/10 px-2 py-1 text-[0.64rem] font-semibold text-vermilion-deep">
                Belum ada LO dari {{ $group->study_program }}. Bukti modul belum bisa diverifikasi untuk gugus ini.
            </p>
        @endif
    </section>

    {{-- minmax(0,...) wajib: grid item default min-width:auto, sehingga tabel
             di dalamnya memaksa kolom lebih lebar dari viewport. --}}
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">

        {{-- Anggota --}}
        <section class="min-w-0 rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
            <div class="p-4 pb-3">
                <x-panel-header title="Anggota" :subtitle="$members->count().' mahasiswa'" icon="users" />
            </div>

            <div class="overflow-x-auto border-t-2 border-ink/10">
                <table class="w-full min-w-[34rem] border-collapse text-left">
                    <thead>
                        <tr class="bg-paper-dim">
                            <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Mahasiswa</th>
                            <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">NIM</th>
                            <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">XP</th>
                            <th class="px-3 py-2 text-right text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-ink/10">
                        @forelse ($members as $member)
                            <tr class="transition hover:bg-paper-dim/60">
                                <td class="px-3 py-2">
                                    <span class="block truncate text-[0.72rem] font-bold leading-tight">{{ $member->name }}</span>
                                    <span class="block truncate text-[0.58rem] font-medium text-ink-faint">{{ $member->email }}</span>
                                </td>
                                <td class="px-3 py-2 font-mono text-[0.66rem] font-bold">{{ $member->nim }}</td>
                                <td class="px-3 py-2 font-mono text-[0.66rem] font-semibold">{{ number_format($member->xp, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">
                                    <form method="POST" action="{{ route('admin.groups.members.destroy', [$group, $member]) }}"
                                          onsubmit="return confirm('Keluarkan {{ $member->name }} dari gugus ini? Akun dan riwayatnya tetap utuh.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm" title="Keluarkan dari gugus">
                                            <x-icon name="logout" class="size-3.5" :stroke="2.2" />
                                            Keluarkan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-[0.74rem] font-semibold text-ink-faint">
                                    Gugus ini belum punya anggota mahasiswa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Tambah anggota --}}
        <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
            <x-panel-header title="Tambah Anggota" subtitle="Hanya mahasiswa sejurusan" icon="plus" />

            <form
                method="POST"
                action="{{ route('admin.groups.members.store', $group) }}"
                class="mt-3 flex flex-col gap-2.5"
                data-group-member-form
                data-candidate-url="{{ route('admin.groups.candidates', $group) }}"
            >
                @csrf

                <div>
                    <label for="member-search" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        Cari mahasiswa
                    </label>
                    <input
                        type="search" id="member-search" data-member-search
                        placeholder="Nama atau NIM…" autocomplete="off"
                        class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-semibold focus:outline-none"
                    >
                    <p class="mt-1 text-[0.58rem] font-medium text-ink-faint">
                        Hanya {{ $group->study_program }} yang muncul. Mahasiswa dari gugus lain bisa dipindahkan ke sini.
                    </p>
                </div>

                <div class="max-h-64 overflow-y-auto rounded-sm border-2 border-ink/30 bg-paper-dim p-1.5" data-member-results>
                    <p class="p-2 text-[0.66rem] font-semibold text-ink-faint">Ketik untuk mencari…</p>
                </div>

                <button type="submit" class="btn btn-primary w-full" data-member-submit disabled>
                    <x-icon name="plus" class="size-4" :stroke="2.4" />
                    Tambahkan ke Gugus
                </button>
            </form>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    /*
     * Pencarian kandidat anggota.
     *
     * Server memfilter sejurusan (bukan klien): daftar seluruh mahasiswa bisa
     * ribuan, dan mengirim semuanya ke browser hanya untuk disaring di sini
     * membocorkan data mahasiswa lain sekaligus memberatkan halaman.
     */
    const form = document.querySelector('[data-group-member-form]');
    if (form) {
        const input = form.querySelector('[data-member-search]');
        const results = form.querySelector('[data-member-results]');
        const submit = form.querySelector('[data-member-submit]');
        const url = form.dataset.candidateUrl;
        let timer = null;

        /*
         * Status memakai textContent, bukan innerHTML: teks status memang
         * statis di sini, tetapi satu pola innerHTML di file ini mengundang
         * penambahan data server ke dalamnya nanti tanpa sanitasi.
         */
        const setStatus = (message, toneClass = 'text-ink-faint') => {
            results.replaceChildren();
            const p = document.createElement('p');
            p.className = `p-2 text-[0.66rem] font-semibold ${toneClass}`;
            p.textContent = message;
            results.appendChild(p);
        };

        const render = (list) => {
            results.replaceChildren();

            if (!list.length) {
                const p = document.createElement('p');
                p.className = 'p-2 text-[0.66rem] font-semibold text-ink-faint';
                p.textContent = 'Tidak ada mahasiswa yang cocok.';
                results.appendChild(p);
                return;
            }

            list.forEach((c) => {
                const label = document.createElement('label');
                label.className = 'flex cursor-pointer items-start gap-2 rounded-xs px-2 py-1.5 hover:bg-white';

                const box = document.createElement('input');
                box.type = 'checkbox';
                box.name = 'user_ids[]';
                box.value = c.id;
                box.className = 'mt-0.5 size-3.5 rounded-xs border-2 border-ink accent-brand';

                const text = document.createElement('span');
                text.className = 'min-w-0';

                const name = document.createElement('span');
                name.className = 'block truncate text-[0.68rem] font-bold leading-tight';
                name.textContent = c.name;

                const meta = document.createElement('span');
                meta.className = 'block font-mono text-[0.56rem] font-medium text-ink-faint';
                meta.textContent = c.nim + (c.group ? ' · ' + c.group : ' · tanpa gugus');

                text.appendChild(name);
                text.appendChild(meta);
                label.appendChild(box);
                label.appendChild(text);
                results.appendChild(label);
            });

            const sync = () => {
                submit.disabled = results.querySelectorAll('input[type="checkbox"]:checked').length === 0;
            };

            results.querySelectorAll('input[type="checkbox"]').forEach((b) => b.addEventListener('change', sync));
            sync();
        };

        const load = async () => {
            const q = encodeURIComponent(input.value.trim());
            setStatus('Mencari…', 'text-ink-faint');

            try {
                const res = await fetch(`${url}?q=${q}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                render(data.candidates ?? []);
            } catch {
                setStatus('Gagal memuat daftar.', 'text-vermilion-deep');
            }
        };

        input.addEventListener('input', () => {
            // Debounce: mengetik cepat tidak boleh membanjiri server.
            clearTimeout(timer);
            timer = setTimeout(load, 250);
        });

        load();
    }
</script>
@endpush