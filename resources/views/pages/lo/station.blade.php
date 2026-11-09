@extends('layouts.app')

@section('title', 'Station Hadiah — Portal LO')
@section('page-title', 'Station Penyerahan Hadiah')
@section('page-kicker', $group?->name ?? 'Belum ada gugus')

@section('content')
<div class="flex flex-col gap-4">

    {{-- Kepala: identitas station + ringkasan --}}
    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-1.5">
                <span class="stamp bg-vermilion text-white">
                    <x-icon name="gift" class="size-3" :stroke="2.2" />
                    Station Hadiah
                </span>
                <span class="stamp bg-brand text-white">
                    <span class="size-1.5 rounded-full bg-white"></span>
                    {{ $memberCount }} mahasiswa
                </span>
            </p>
            <h1 class="mt-1.5 font-display text-[1.5rem] font-extrabold leading-tight tracking-tight sm:text-[1.8rem]">
                Serahkan hadiah gugus Anda.
            </h1>
            <p class="mt-0.5 max-w-2xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Pindai atau ketik kode klaim mahasiswa, lalu tekan <strong>Serahkan</strong>.
                Koin sudah terpotong saat penukaran — di sini Anda hanya menandai barang sudah diambil.
            </p>
        </div>
    </section>

    {{-- Kotak cari kode klaim: dipakai di meja station --}}
    <section class="rounded-lg border-2 border-ink bg-gold/20 p-3.5 shadow-[var(--shadow-flat)]">
        <form method="GET" action="{{ route('lo.station') }}" class="flex flex-wrap items-end gap-2">
            <div class="min-w-0 flex-1">
                <label for="q" class="block font-display text-[0.62rem] font-bold uppercase tracking-[0.08em]">
                    Cari kode klaim / nama / NIM
                </label>
                <input
                    type="text" id="q" name="q" value="{{ $cari }}"
                    placeholder="OQ-XXXX-XXXX"
                    autocomplete="off" autocapitalize="characters"
                    class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-1.5 font-mono text-[0.8rem] font-bold uppercase tracking-wider focus:outline-none"
                >
            </div>

            <input type="hidden" name="status" value="{{ $status }}">

            <button type="submit" class="btn btn-primary btn-sm">
                <x-icon name="search" class="size-3.5" :stroke="2.6" />
                Cari
            </button>

            @if ($cari !== '')
                <a href="{{ route('lo.station', ['status' => $status]) }}" class="btn btn-ghost btn-sm">
                    Bersihkan
                </a>
            @endif
        </form>
    </section>

    {{-- Ringkasan status --}}
    <section class="grid grid-cols-3 gap-2.5">
        @foreach ([
            ['pending', 'Menunggu', 'bg-gold', 'text-ink', $counts['pending']],
            ['fulfilled', 'Diserahkan', 'bg-brand', 'text-white', $counts['fulfilled']],
            ['cancelled', 'Dibatalkan', 'bg-vermilion', 'text-white', $counts['cancelled']],
        ] as [$key, $label, $bg, $fg, $jumlah])
            <a href="{{ route('lo.station', ['status' => $key]) }}"
               class="rounded-lg border-2 border-ink p-3 shadow-[var(--shadow-flat-sm)] transition
                      {{ $status === $key ? $bg.' '.$fg : 'bg-paper hover:bg-paper-dim' }}">
                <span class="block font-display text-[0.58rem] font-bold uppercase tracking-[0.08em] opacity-80">{{ $label }}</span>
                <span class="mt-0.5 block font-display text-[1.35rem] font-extrabold leading-none">{{ $jumlah }}</span>
            </a>
        @endforeach
    </section>

    {{-- Galat --}}
    @if ($errors->any())
        <div class="rounded-lg border-2 border-vermilion bg-vermilion/10 p-3">
            @foreach ($errors->all() as $pesan)
                <p class="text-[0.74rem] font-bold text-vermilion">{{ $pesan }}</p>
            @endforeach
        </div>
    @endif

    {{-- Daftar klaim --}}
    <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b-2 border-ink px-3.5 py-2.5">
            <h2 class="font-display text-[0.86rem] font-extrabold">
                @if ($status === 'pending') Menunggu diambil
                @elseif ($status === 'fulfilled') Sudah diserahkan
                @elseif ($status === 'cancelled') Dibatalkan
                @else Semua klaim
                @endif
            </h2>
            <span class="text-[0.62rem] font-semibold text-ink-faint">
                {{ $claims->total() }} klaim
            </span>
        </div>

        @if ($claims->isEmpty())
            <div class="px-4 py-10 text-center">
                <p class="font-display text-[0.86rem] font-bold text-ink-soft">
                    @if ($cari !== '')
                        Tidak ada klaim yang cocok dengan "{{ $cari }}".
                    @elseif ($status === 'pending')
                        Tidak ada hadiah yang menunggu diambil. Gugus Anda sudah bersih.
                    @else
                        Belum ada klaim pada status ini.
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[38rem] border-collapse text-left">
                    <thead>
                        <tr class="border-b-2 border-ink/20 bg-paper-dim">
                            <th class="px-3 py-2 font-display text-[0.58rem] font-bold uppercase tracking-[0.08em]">Kode Klaim</th>
                            <th class="px-3 py-2 font-display text-[0.58rem] font-bold uppercase tracking-[0.08em]">Mahasiswa</th>
                            <th class="px-3 py-2 font-display text-[0.58rem] font-bold uppercase tracking-[0.08em]">Hadiah</th>
                            <th class="px-3 py-2 font-display text-[0.58rem] font-bold uppercase tracking-[0.08em]">Status</th>
                            <th class="px-3 py-2 text-right font-display text-[0.58rem] font-bold uppercase tracking-[0.08em]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($claims as $claim)
                            <tr class="border-b border-ink/10 last:border-0">
                                <td class="px-3 py-2">
                                    <span class="block font-mono text-[0.72rem] font-bold tracking-wider">{{ $claim->claim_code }}</span>
                                    <span class="block text-[0.56rem] font-medium text-ink-faint">
                                        {{ $claim->created_at?->format('d M Y · H:i') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block text-[0.7rem] font-bold leading-tight">{{ $claim->user?->name ?? '—' }}</span>
                                    <span class="block text-[0.56rem] font-medium text-ink-faint">{{ $claim->user?->nim ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block text-[0.68rem] font-semibold">{{ $claim->reward?->name ?? '—' }}</span>
                                    <span class="block text-[0.56rem] font-medium text-ink-faint">{{ $claim->reward?->code ?? '' }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="stamp text-[0.5rem] {{ $claim->status === 'fulfilled' ? 'bg-brand text-white' : ($claim->status === 'cancelled' ? 'bg-vermilion text-white' : 'bg-gold text-ink') }}">
                                        {{ $claim->status === 'fulfilled' ? 'Diserahkan' : ($claim->status === 'cancelled' ? 'Dibatalkan' : 'Menunggu') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @if ($claim->status === 'pending')
                                        <form method="POST" action="{{ route('lo.station.fulfill', $claim) }}" data-submit-lock>
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <x-icon name="check" class="size-3.5" :stroke="2.6" />
                                                Serahkan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[0.62rem] font-semibold text-ink-faint">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($claims->hasPages())
                <div class="border-t-2 border-ink/20 px-3.5 py-2.5">
                    {{ $claims->links() }}
                </div>
            @endif
        @endif
    </section>

    {{-- Catatan aturan --}}
    <section class="rounded-lg border-2 border-dashed border-ink/40 bg-paper-dim p-3.5">
        <p class="font-display text-[0.66rem] font-bold uppercase tracking-[0.08em] text-ink-soft">Aturan station</p>
        <ul class="mt-1.5 flex flex-col gap-1 text-[0.72rem] font-medium leading-relaxed text-ink-soft">
            <li>• Hanya klaim mahasiswa <strong>gugus Anda</strong> yang muncul di sini.</li>
            <li>• Satu kode klaim hanya bisa diserahkan <strong>sekali</strong>; percobaan kedua ditolak.</li>
            <li>• Harga dan stok hadiah adalah wewenang admin — hubungi admin bila stok habis.</li>
            <li>• Koin sudah terpotong saat penukaran; menyerahkan barang tidak mengubah saldo.</li>
        </ul>
    </section>

</div>
@endsection