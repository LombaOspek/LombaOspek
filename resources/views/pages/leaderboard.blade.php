@extends('layouts.app')

@section('title', 'Peringkat')
@section('page-title', 'Papan Peringkat')
@section('page-kicker', $participants.' mahasiswa baru terdaftar')

@section('content')
<div class="flex flex-col gap-4">

    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-display text-[1.45rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                Peringkat kampus.
            </h1>
            <p class="mt-0.5 max-w-xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Dihitung dari total XP yang diperoleh lewat modul, kuis, dan lencana. Diperbarui setiap kali halaman dibuka.
            </p>
        </div>

        <span class="stamp bg-ink text-paper">
            <x-icon name="podium" class="size-3" :stroke="2.2" />
            Posisi Anda #{{ $myRank }}
        </span>
    </section>

    <div class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">

        {{-- Podium 3 teratas --}}
        <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
            <x-panel-header title="Tiga teratas" subtitle="Peraih XP tertinggi" icon="trophy" />

            @php $podium = $top->take(3); @endphp

            <div class="mt-3 grid grid-cols-3 items-end gap-1.5">
                @foreach ([1, 0, 2] as $slot)
                    @php
                        $entry = $podium->get($slot);
                        $heights = [0 => 'h-24', 1 => 'h-20', 2 => 'h-16'];
                        $fills = [0 => 'bg-amber', 1 => 'bg-sky/70', 2 => 'bg-vermilion/20'];
                    @endphp

                    @if ($entry)
                        <div class="flex flex-col items-center">
                            <span class="grid size-9 place-items-center rounded-full border-2 border-ink bg-sky font-display text-[0.72rem] font-extrabold">
                                {{ mb_strtoupper(mb_substr($entry['user']->name, 0, 1)) }}
                            </span>
                            <p class="mt-1 line-clamp-1 text-center text-[0.62rem] font-bold leading-tight">
                                {{ \Illuminate\Support\Str::before($entry['user']->name, ' ') }}
                            </p>
                            <p class="numeric text-[0.6rem] font-bold text-ink-faint">{{ number_format($entry['user']->xp, 0, ',', '.') }}</p>

                            <div class="mt-1 flex w-full {{ $heights[$slot] }} items-start justify-center rounded-t-sm border-2 border-b-0 border-ink {{ $fills[$slot] }} pt-1">
                                <span class="numeric text-[0.9rem] font-extrabold">{{ $entry['rank'] }}</span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <ol class="mt-3 flex flex-col divide-y-2 divide-ink/10 border-t-2 border-ink/15">
                @foreach ($top as $entry)
                    @php $leader = $entry['user']; @endphp
                    <li class="flex items-center gap-2.5 py-2 {{ $entry['is_me'] ? '' : '' }}">
                        <span class="w-5 shrink-0 text-center font-mono text-[0.7rem] font-bold {{ $entry['rank'] <= 3 ? 'text-vermilion-deep' : 'text-ink-faint' }}">
                            {{ $entry['rank'] }}
                        </span>
                        <span class="grid size-7 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper-dim text-[0.6rem] font-extrabold">
                            {{ mb_strtoupper(mb_substr($leader->name, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-display text-[0.76rem] font-bold leading-tight">
                                {{ $leader->name }}
                                @if ($entry['is_me'])
                                    <span class="stamp ml-1 bg-amber text-[0.5rem]">Anda</span>
                                @endif
                            </span>
                            <span class="block truncate text-[0.6rem] font-semibold text-ink-faint">
                                {{ $leader->group_name ?: 'Tanpa kelompok' }} · Lv {{ $leader->level }}
                            </span>
                        </span>
                        <span class="numeric shrink-0 text-[0.72rem] font-bold">{{ number_format($leader->xp, 0, ',', '.') }}</span>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="flex flex-col gap-4">
            {{-- Klasemen kelompok --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <x-panel-header title="Klasemen kelompok" subtitle="Akumulasi XP per kelompok orientasi" icon="users" />

                @if ($groups->isEmpty())
                    <p class="mt-2.5 text-[0.72rem] font-semibold text-ink-faint">Belum ada data kelompok.</p>
                @else
                    <ol class="mt-2.5 flex flex-col gap-1.5">
                        @foreach ($groups as $group)
                            @php $isMine = $group['group'] === $user->group_name; @endphp
                            <li class="rounded-sm border-[1.5px] p-2 {{ $isMine ? 'border-ink bg-amber/40' : 'border-ink/40' }}">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="min-w-0 truncate font-display text-[0.74rem] font-bold">
                                        {{ $group['rank'] }}. {{ $group['group'] }}
                                        @if ($isMine)<span class="text-[0.6rem] font-extrabold text-vermilion-deep">(Anda)</span>@endif
                                    </span>
                                    <span class="numeric shrink-0 text-[0.7rem] font-bold">{{ number_format($group['total_xp'], 0, ',', '.') }}</span>
                                </div>
                                <p class="text-[0.58rem] font-semibold text-ink-faint">{{ $group['members'] }} anggota</p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            {{-- Posisi saya --}}
            <section class="rounded-lg border-2 border-ink bg-teal/15 p-4 shadow-[var(--shadow-flat)]">
                <p class="text-[0.56rem] font-extrabold uppercase tracking-[0.13em] text-ink/70">Posisi Anda</p>
                <p class="numeric mt-1 text-[2rem] font-extrabold leading-none">#{{ $myRank }}</p>
                <p class="mt-1 text-[0.7rem] font-semibold leading-relaxed text-ink-soft">
                    dari {{ $participants }} mahasiswa baru, dengan
                    <strong class="text-ink">{{ number_format($user->xp, 0, ',', '.') }} XP</strong>
                    dan {{ $completed }} modul selesai.
                </p>
                <a href="{{ route('quests.index') }}" class="btn btn-ghost btn-sm mt-2.5 w-full">
                    <x-icon name="map" class="size-3.5" :stroke="2" />
                    Tambah XP lewat modul
                </a>
            </section>
        </div>
    </div>
</div>
@endsection