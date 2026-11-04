@extends('layouts.app')

@section('title', 'Kelola Bursa Hadiah')
@section('page-title', 'Kelola Bursa Hadiah')
@section('page-kicker', 'Atur merchandise, harga, stok, dan penyerahan')

@section('content')
<div class="flex flex-col gap-4">

    {{-- Kepala --}}
    <section class="rounded-xl border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-[1.4rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                    Kelola Bursa Hadiah
                </h1>
                <p class="mt-1 max-w-2xl text-[0.76rem] font-medium leading-relaxed text-ink-soft">
                    Harga dan stok berlaku untuk <strong class="text-ink">seluruh kampus</strong> — satu harga,
                    bukan per gugus. Hadiah yang dinonaktifkan disembunyikan dari mahasiswa, tetapi klaim lama tetap utuh.
                </p>
            </div>

            <a href="{{ route('rewards.index') }}" class="btn btn-ghost btn-sm shrink-0">
                <x-icon name="gift" class="size-3.5" :stroke="2" />
                Lihat sebagai mahasiswa
            </a>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <x-stat-tile :value="$stats['total']" label="Total Hadiah" icon="gift" tone="paper" caption="Semua item" />
            <x-stat-tile :value="$stats['active']" label="Aktif" icon="check" tone="gold" caption="Tampil di Bursa" />
            <x-stat-tile :value="$stats['claimed']" label="Ditukar" icon="ticket" tone="sky" caption="Total klaim" />
            <x-stat-tile :value="$stats['fulfilled']" label="Diserahkan" icon="check-circle" tone="brand" caption="Sudah diambil" />
        </div>
    </section>

    {{-- minmax(0,...) pada kolom kiri wajib: tanpa itu grid item menolak menyusut
             di bawah lebar kontennya, dan tabel 44rem memaksa halaman melebar. --}}
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]">

        {{-- Daftar hadiah --}}
        <section class="min-w-0 rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
            <div class="p-4 pb-3">
                <x-panel-header title="Daftar Hadiah" :subtitle="$stats['total'].' item'" icon="gift" />
            </div>

            <div class="overflow-x-auto border-t-2 border-ink/10">
                <table class="w-full min-w-[44rem] border-collapse text-left">
                    <thead>
                        <tr class="bg-paper-dim">
                            <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Hadiah</th>
                            <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Harga</th>
                            <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Stok</th>
                            <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Klaim</th>
                            <th class="px-3 py-2 text-right text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-ink/10">
                        @forelse ($rewards as $reward)
                            <tr class="transition hover:bg-paper-dim/60 {{ $reward->is_active ? '' : 'opacity-60' }}">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        @if ($reward->image_path)
                                            <img
                                                src="{{ Storage::disk('public')->url($reward->image_path) }}"
                                                alt=""
                                                class="size-9 shrink-0 rounded-xs border-2 border-ink object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <span class="grid size-9 shrink-0 place-items-center rounded-xs border-2 border-ink bg-gold/40 font-display text-[0.6rem] font-extrabold">
                                                {{ mb_strtoupper(mb_substr($reward->name, 0, 2)) }}
                                            </span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block truncate text-[0.74rem] font-bold leading-tight">{{ $reward->name }}</span>
                                            <span class="block font-mono text-[0.56rem] font-medium text-ink-faint">
                                                {{ $reward->code }} · {{ $reward->category }}
                                            </span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 font-mono text-[0.7rem] font-bold">
                                    {{ number_format($reward->cost_coins, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-[0.68rem] font-semibold">
                                    {{ $reward->stock === null ? '∞' : number_format($reward->stock, 0, ',', '.') }}
                                    @if ($reward->quota_per_user)
                                        <span class="block text-[0.56rem] font-medium text-ink-faint">maks {{ $reward->quota_per_user }}/orang</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-[0.68rem] font-semibold">
                                    {{ $reward->claimed_count }}
                                    <span class="block text-[0.56rem] font-medium text-ink-faint">{{ $reward->fulfilled_count }} diserahkan</span>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($reward->is_active)
                                            <form method="POST" action="{{ route('admin.rewards.deactivate', $reward) }}">
                                                @csrf
                                                <button type="submit" class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-paper transition hover:bg-vermilion hover:text-white" title="Sembunyikan dari Bursa" aria-label="Nonaktifkan {{ $reward->name }}">
                                                    <x-icon name="x" class="size-3" :stroke="2.4" />
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.rewards.activate', $reward) }}">
                                                @csrf
                                                <button type="submit" class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-gold transition hover:bg-brand-soft" title="Tampilkan lagi di Bursa" aria-label="Aktifkan {{ $reward->name }}">
                                                    <x-icon name="check" class="size-3" :stroke="2.6" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-[0.74rem] font-semibold text-ink-faint">
                                    Belum ada hadiah. Tambahkan lewat form di samping.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Form tambah hadiah --}}
        <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
            <x-panel-header title="Tambah Hadiah" subtitle="Muncul langsung di Bursa" icon="plus" />

            <form method="POST" action="{{ route('admin.rewards.store') }}" enctype="multipart/form-data" class="mt-3 flex flex-col gap-2.5" data-submit-lock>
                @csrf

                <div>
                    <label for="rw-name" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        Nama Hadiah <span class="text-vermilion">*</span>
                    </label>
                    <input type="text" id="rw-name" name="name" required maxlength="120" value="{{ old('name') }}"
                           placeholder="Contoh: Lanyard OspekQuest"
                           class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none">
                    @error('name')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="rw-code" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        Kode <span class="text-vermilion">*</span>
                    </label>
                    <input type="text" id="rw-code" name="code" required maxlength="32" value="{{ old('code') }}"
                           placeholder="LANYARD-02"
                           class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 font-mono text-[0.74rem] font-bold uppercase focus:outline-none">
                    <p class="mt-1 text-[0.6rem] font-medium text-ink-faint">Huruf besar, angka, tanda hubung. Harus unik.</p>
                    @error('code')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="rw-cost" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                            Harga (koin) <span class="text-vermilion">*</span>
                        </label>
                        <input type="number" id="rw-cost" name="cost_coins" required min="1" max="100000" value="{{ old('cost_coins', 150) }}"
                               class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 font-mono text-[0.74rem] font-bold focus:outline-none">
                        @error('cost_coins')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="rw-stock" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Stok</label>
                        <input type="number" id="rw-stock" name="stock" min="0" max="100000" value="{{ old('stock') }}"
                               placeholder="kosong = ∞"
                               class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 font-mono text-[0.74rem] font-bold focus:outline-none">
                        <p class="mt-1 text-[0.58rem] font-medium text-ink-faint">Kosongkan = tak terbatas.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="rw-quota" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Kuota/orang</label>
                        <input type="number" id="rw-quota" name="quota_per_user" min="1" max="100" value="{{ old('quota_per_user', 1) }}"
                               class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 font-mono text-[0.74rem] font-bold focus:outline-none">
                    </div>
                    <div>
                        <label for="rw-cat" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Kategori</label>
                        <select id="rw-cat" name="category" class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none">
                            @foreach (['merchandise' => 'Merchandise', 'voucher' => 'Voucher', 'digital' => 'Digital', 'lainnya' => 'Lainnya'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('category', 'merchandise') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="rw-image" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Gambar</label>
                    <input type="file" id="rw-image" name="image" accept="image/jpeg,image/png,image/webp"
                           class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-medium file:mr-2 file:rounded-xs file:border-0 file:bg-gold file:px-2 file:py-1 file:font-bold focus:outline-none">
                    <p class="mt-1 text-[0.6rem] font-medium text-ink-faint">JPG/PNG/WebP, maks 2 MB. Tanpa gambar, kartu memakai inisial.</p>
                    @error('image')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="rw-desc" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Deskripsi</label>
                    <textarea id="rw-desc" name="description" rows="2" maxlength="500"
                              class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-medium focus:outline-none"
                              placeholder="Bahan, ukuran, atau catatan pengambilan.">{{ old('description') }}</textarea>
                </div>

                <label class="flex cursor-pointer items-start gap-2 rounded-sm border-[1.5px] border-ink/40 bg-paper-dim p-2">
                    <input type="checkbox" name="is_active" value="1" checked class="mt-0.5 size-3.5 rounded-xs border-2 border-ink accent-brand">
                    <span class="text-[0.68rem] font-semibold leading-snug text-ink-soft">Langsung tampil di Bursa</span>
                </label>

                <button type="submit" class="btn btn-primary w-full">
                    <x-icon name="plus" class="size-4" :stroke="2.4" />
                    Simpan Hadiah
                </button>
            </form>
        </section>
    </div>

    {{-- Klaim terbaru + penyerahan --}}
    <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
        <div class="p-4 pb-3">
            <x-panel-header title="Klaim Terbaru" subtitle="Tandai hadiah saat sudah diambil" icon="ticket" />
        </div>

        <div class="overflow-x-auto border-t-2 border-ink/10">
            <table class="w-full min-w-[40rem] border-collapse text-left">
                <thead>
                    <tr class="bg-paper-dim">
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Kode</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Mahasiswa</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Hadiah</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Status</th>
                        <th class="px-3 py-2 text-right text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-ink/10">
                    @forelse ($claims as $claim)
                        <tr class="transition hover:bg-paper-dim/60">
                            <td class="px-3 py-2 font-mono text-[0.66rem] font-bold">{{ $claim->claim_code }}</td>
                            <td class="px-3 py-2">
                                <span class="block text-[0.7rem] font-bold leading-tight">{{ $claim->user?->name ?? '—' }}</span>
                                <span class="block text-[0.56rem] font-medium text-ink-faint">{{ $claim->user?->group_name ?? 'tanpa gugus' }}</span>
                            </td>
                            <td class="px-3 py-2 text-[0.68rem] font-semibold">{{ $claim->reward?->name ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="stamp text-[0.5rem] {{ $claim->status === 'fulfilled' ? 'bg-brand text-white' : ($claim->status === 'cancelled' ? 'bg-vermilion text-white' : 'bg-gold text-ink') }}">
                                    {{ $claim->status === 'fulfilled' ? 'Diserahkan' : ($claim->status === 'cancelled' ? 'Dibatalkan' : 'Menunggu') }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                @if ($claim->status === 'pending')
                                    <form method="POST" action="{{ route('admin.rewards.fulfill', $claim) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm">
                                            <x-icon name="check" class="size-3.5" :stroke="2.6" />
                                            Serahkan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[0.62rem] font-semibold text-ink-faint">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-[0.74rem] font-semibold text-ink-faint">
                                Belum ada klaim.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
