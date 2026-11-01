@extends('layouts.app')

@section('title', 'Bursa Hadiah')
@section('page-title', 'Bursa Hadiah')
@section('page-kicker', 'Tukarkan koin dengan hadiah orientasi')

@section('content')
<div class="flex flex-col gap-4">

    {{-- Kepala: saldo koin + aturan singkat --}}
    <section class="rounded-xl border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-[1.4rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                    Bursa Hadiah
                </h1>
                <p class="mt-1 max-w-xl text-[0.76rem] font-medium leading-relaxed text-ink-soft">
                    Koin didapat dari modul, kuis, dan lencana. Menukar hadiah hanya mengurangi koin —
                    <strong class="text-ink">XP dan peringkat Anda tidak berubah</strong>.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2 rounded-md border-2 border-ink bg-gold px-3 py-2 shadow-[var(--shadow-flat-sm)]">
                <x-icon name="bolt" class="size-4" :stroke="2.4" />
                <div>
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.1em] opacity-70">Saldo Koin</p>
                    <p class="numeric text-[1.15rem] font-extrabold leading-none" data-coin-balance>
                        {{ number_format($user->coins, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[1.6fr_1fr]">

        {{-- Daftar hadiah --}}
        <section aria-label="Daftar hadiah">
            @if ($rows->isEmpty())
                <div class="rounded-lg border-2 border-dashed border-ink/30 bg-paper-dim p-8 text-center">
                    <p class="font-display text-[0.9rem] font-bold">Belum ada hadiah</p>
                    <p class="mt-1 text-[0.72rem] font-medium text-ink-soft">
                        Panitia belum membuka bursa. Coba lagi nanti.
                    </p>
                </div>
            @else
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($rows as $row)
                        @php
                            $reward = $row['reward'];
                            $sisaKuota = $reward->hasUnlimitedQuota()
                                ? null
                                : max(0, $reward->quota_per_user - $row['claimed']);
                            $bisaDitukar = $row['affordable'] && ! $row['sold_out'] && ($sisaKuota === null || $sisaKuota > 0);
                        @endphp

                        <article
                            class="flex flex-col rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)] {{ $bisaDitukar ? '' : 'opacity-70' }}"
                            data-reward-card
                            data-reward-id="{{ $reward->getKey() }}"
                            data-reward-name="{{ $reward->name }}"
                            data-reward-cost="{{ $reward->cost_coins }}"
                            data-endpoint="{{ route('rewards.redeem', $reward) }}"
                        >
                            {{-- Gambar hadiah: kalau tidak ada, tampilkan blok warna
                                 dengan inisial, bukan gambar rusak. --}}
                            <div class="relative flex h-24 items-center justify-center overflow-hidden rounded-t-[6px] border-b-2 border-ink bg-brand-soft">
                                @if ($reward->image_path)
                                    <img
                                        src="{{ asset($reward->image_path) }}"
                                        alt=""
                                        class="size-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <span class="font-display text-[2rem] font-extrabold text-brand-deep/60">
                                        {{ mb_strtoupper(mb_substr($reward->name, 0, 2)) }}
                                    </span>
                                @endif

                                @if ($row['sold_out'])
                                    <span class="stamp absolute left-2 top-2 bg-vermilion text-white">Habis</span>
                                @elseif ($reward->stock !== null && $reward->stock <= 5)
                                    <span class="stamp absolute left-2 top-2 bg-gold text-ink">Sisa {{ $reward->stock }}</span>
                                @endif
                            </div>

                            <div class="flex flex-1 flex-col gap-1.5 p-3">
                                <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">
                                    {{ $reward->category }}
                                </p>
                                <h2 class="font-display text-[0.86rem] font-extrabold leading-tight">
                                    {{ $reward->name }}
                                </h2>
                                <p class="line-clamp-2 text-[0.68rem] font-medium leading-snug text-ink-soft">
                                    {{ $reward->description ?: 'Hadiah resmi orientasi OspekQuest.' }}
                                </p>

                                <div class="mt-auto flex flex-wrap items-center gap-1.5 pt-1">
                                    <span class="inline-flex items-center gap-1 rounded-sm border-2 border-ink bg-gold px-1.5 py-0.5 font-display text-[0.68rem] font-extrabold">
                                        <x-icon name="bolt" class="size-3" :stroke="2.6" />
                                        {{ number_format($reward->cost_coins, 0, ',', '.') }}
                                    </span>

                                    @if ($sisaKuota !== null)
                                        <span class="rounded-sm border-[1.5px] border-ink/40 px-1.5 py-0.5 text-[0.58rem] font-bold text-ink-soft">
                                            Kuota {{ $row['claimed'] }}/{{ $reward->quota_per_user }}
                                        </span>
                                    @endif
                                </div>

                                <button
                                    type="button"
                                    class="mt-2 w-full rounded-md border-2 border-ink px-3 py-2 font-display text-[0.72rem] font-extrabold uppercase tracking-[0.04em] transition
                                        {{ $bisaDitukar
                                            ? 'bg-brand text-white shadow-[0_3px_0_0_var(--color-brand-deep)] hover:-translate-y-0.5 active:translate-y-0.5 active:shadow-none'
                                            : 'cursor-not-allowed bg-paper-dim text-ink-faint' }}"
                                    data-redeem-btn
                                    @disabled(! $bisaDitukar)
                                    aria-describedby="reward-status-{{ $reward->getKey() }}"
                                >
                                    @if ($row['sold_out'])
                                        Stok Habis
                                    @elseif ($sisaKuota !== null && $sisaKuota <= 0)
                                        Kuota Penuh
                                    @elseif (! $row['affordable'])
                                        Koin Kurang
                                    @else
                                        Tukar Sekarang
                                    @endif
                                </button>

                                <p id="reward-status-{{ $reward->getKey() }}" class="sr-only">
                                    @if ($row['sold_out'])
                                        Hadiah ini sudah habis.
                                    @elseif (! $row['affordable'])
                                        Anda butuh {{ $reward->cost_coins - $user->coins }} koin lagi.
                                    @else
                                        Hadiah tersedia untuk ditukar.
                                    @endif
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Kolom kanan: klaim saya + riwayat koin --}}
        <div class="flex min-w-0 flex-col gap-4">

            <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
                <div class="p-4 pb-2">
                    <x-panel-header title="Klaim Saya" subtitle="Tunjukkan kode ke panitia" icon="gift" />
                </div>

                @if ($redemptions->isEmpty())
                    <p class="px-4 pb-4 text-[0.7rem] font-semibold text-ink-faint">
                        Belum ada klaim. Tukar hadiah pertama Anda.
                    </p>
                @else
                    <ul class="flex flex-col gap-2 px-3 pb-3">
                        @foreach ($redemptions as $redemption)
                            @php
                                $tone = match ($redemption->status) {
                                    'fulfilled' => 'bg-success text-white',
                                    'cancelled' => 'bg-paper-dim text-ink-faint',
                                    default => 'bg-gold text-ink',
                                };
                                $label = match ($redemption->status) {
                                    'fulfilled' => 'Sudah diambil',
                                    'cancelled' => 'Dibatalkan',
                                    default => 'Menunggu',
                                };
                            @endphp

                            <li class="rounded-sm border-2 border-ink bg-paper-dim p-2">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="min-w-0 truncate font-display text-[0.72rem] font-bold leading-tight">
                                        {{ $redemption->reward?->name ?? 'Hadiah' }}
                                    </p>
                                    <span class="stamp shrink-0 {{ $tone }} text-[0.5rem]">{{ $label }}</span>
                                </div>
                                <p class="mt-1 font-mono text-[0.68rem] font-bold tracking-wider text-ink">
                                    {{ $redemption->claim_code }}
                                </p>
                                <p class="mt-0.5 text-[0.58rem] font-semibold text-ink-faint">
                                    {{ number_format($redemption->coins_spent, 0, ',', '.') }} koin ·
                                    {{ $redemption->created_at?->diffForHumans(short: true) }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
                <div class="p-4 pb-2">
                    <x-panel-header title="Riwayat Koin" subtitle="Mutasi terakhir" icon="trending" />
                </div>

                @if ($coinHistory->isEmpty())
                    <p class="px-4 pb-4 text-[0.7rem] font-semibold text-ink-faint">
                        Belum ada mutasi koin.
                    </p>
                @else
                    <ul class="divide-y-2 divide-ink/10 border-t-2 border-ink/10">
                        @foreach ($coinHistory as $tx)
                            <li class="flex items-center justify-between gap-2 px-4 py-1.5">
                                <span class="min-w-0 truncate text-[0.66rem] font-semibold text-ink-soft">
                                    {{ $tx->description }}
                                </span>
                                <span class="numeric shrink-0 text-[0.66rem] font-bold {{ $tx->amount >= 0 ? 'text-success-deep' : 'text-vermilion-deep' }}">
                                    {{ $tx->amount >= 0 ? '+' : '' }}{{ number_format($tx->amount, 0, ',', '.') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div>

{{-- Modal konfirmasi. Penukaran tidak boleh terjadi karena salah ketuk. --}}
<div
    id="redeem-modal"
    class="fixed inset-0 z-[70] hidden items-center justify-center bg-ink/70 p-4"
    data-redeem-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="redeem-modal-title"
>
    <div class="w-full max-w-sm rounded-xl border-2 border-ink bg-paper p-5 shadow-[var(--shadow-flat-xl)]" tabindex="-1" data-redeem-card>
        <h2 id="redeem-modal-title" class="font-display text-[1.05rem] font-extrabold leading-tight">
            Tukar hadiah ini?
        </h2>
        <p class="mt-1 text-[0.75rem] font-medium leading-relaxed text-ink-soft">
            <strong class="text-ink" data-confirm-name>Hadiah</strong> akan ditukar dengan
            <strong class="text-ink"><span data-confirm-cost>0</span> koin</strong>.
            Koin tidak bisa dikembalikan kecuali klaim dibatalkan panitia.
        </p>

        <div class="mt-3 flex gap-2">
            <button
                type="button"
                class="flex-1 rounded-md border-2 border-ink bg-paper-dim px-3 py-2 font-display text-[0.74rem] font-extrabold uppercase"
                data-redeem-cancel
            >
                Batal
            </button>
            <button
                type="button"
                class="flex-1 rounded-md border-2 border-ink bg-brand px-3 py-2 font-display text-[0.74rem] font-extrabold uppercase text-white shadow-[0_3px_0_0_var(--color-brand-deep)]"
                data-redeem-confirm
            >
                Ya, Tukar
            </button>
        </div>
    </div>
</div>
@endsection
