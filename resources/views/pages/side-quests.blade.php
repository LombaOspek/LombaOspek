@extends('layouts.app')

@section('title', 'Side Quest')
@section('page-title', 'Side Quest')
@section('page-kicker', 'Misi sosial opsional di luar jalur utama')

@section('content')
<div class="flex flex-col gap-4">

    {{-- Kepala: penjelasan singkat + ringkasan bonus --}}
    <section class="rounded-xl border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 max-w-xl">
                <h1 class="font-display text-[1.4rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                    Side Quest
                </h1>
                <p class="mt-1 text-[0.76rem] font-medium leading-relaxed text-ink-soft">
                    Misi sosial di luar jalur utama. <strong class="text-ink">Tidak wajib</strong>, tidak
                    memblokir modul berikutnya, dan XP-nya bonus — bukan syarat sertifikat.
                </p>
                <p class="mt-1.5 text-[0.66rem] font-semibold text-ink-faint">
                    Modul utama selesai: {{ $completedModules }}
                </p>
            </div>

            <div class="grid shrink-0 grid-cols-3 gap-1.5">
                <div class="rounded-sm border-2 border-ink bg-gold/40 px-2.5 py-1.5 text-center">
                    <p class="text-[0.5rem] font-extrabold uppercase tracking-[0.08em] text-ink-faint">Selesai</p>
                    <p class="numeric text-[1rem] font-extrabold leading-none">{{ $summary['completed'] }}/{{ $summary['total'] }}</p>
                </div>
                <div class="rounded-sm border-2 border-ink bg-brand-soft/60 px-2.5 py-1.5 text-center">
                    <p class="text-[0.5rem] font-extrabold uppercase tracking-[0.08em] text-ink-faint">Terbuka</p>
                    <p class="numeric text-[1rem] font-extrabold leading-none">{{ $summary['available'] }}</p>
                </div>
                <div class="rounded-sm border-2 border-ink bg-paper-dim px-2.5 py-1.5 text-center">
                    <p class="text-[0.5rem] font-extrabold uppercase tracking-[0.08em] text-ink-faint">Bonus XP</p>
                    <p class="numeric text-[1rem] font-extrabold leading-none">{{ number_format($summary['bonus_xp'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </section>

    @if ($rows->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-ink/30 bg-paper-dim p-8 text-center">
            <p class="font-display text-[0.9rem] font-bold">Belum ada Side Quest</p>
            <p class="mt-1 text-[0.72rem] font-medium text-ink-soft">Panitia belum menambahkan misi sosial.</p>
        </div>
    @else
        <section aria-label="Daftar Side Quest">
            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach ($rows as $row)
                    @php
                        $quest = $row['quest'];
                        $status = $row['status'];
                        $isLocked = $status === \App\Enums\QuestStatus::Locked;
                        $isDone = $status === \App\Enums\QuestStatus::Completed;
                        $isOpen = $status->isOpen();

                        // Jumlah ganjil: kartu terakhir membentang penuh supaya
                        // tidak menyisakan lubang kosong di baris bawah grid.
                        $spanFull = $loop->last && $rows->count() % 2 === 1;
                    @endphp

                    <li
                        class="flex flex-col rounded-lg border-2 border-ink shadow-[var(--shadow-flat)]
                            {{ $spanFull ? 'sm:col-span-2' : '' }}
                            {{ $isDone ? 'bg-success/10' : ($isLocked ? 'bg-paper-dim opacity-75' : 'bg-paper') }}"
                        data-side-quest
                        data-quest-id="{{ $quest->getKey() }}"
                        data-status="{{ $status->value }}"
                    >
                        <div class="flex items-start gap-2.5 p-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-sm border-2 border-ink
                                {{ $isDone ? 'bg-success text-white' : ($isLocked ? 'bg-paper text-ink-faint' : 'bg-gold') }}">
                                @if ($isDone)
                                    <x-icon name="check" class="size-4" :stroke="3" />
                                @elseif ($isLocked)
                                    <x-icon name="lock" class="size-4" :stroke="2.4" />
                                @else
                                    <x-icon :name="$quest->icon ?: 'users'" class="size-4" :stroke="2.2" />
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="text-[0.55rem] font-extrabold uppercase tracking-[0.08em] text-ink-faint">
                                        {{ $quest->category }}
                                    </span>
                                    @if ($isDone)
                                        <span class="stamp bg-success text-white text-[0.45rem]">Selesai</span>
                                    @endif
                                </div>

                                <h2 class="mt-0.5 font-display text-[0.82rem] font-extrabold leading-tight">
                                    {{ $quest->title }}
                                </h2>
                                <p class="mt-0.5 line-clamp-2 text-[0.66rem] font-medium leading-snug text-ink-soft">
                                    {{ $quest->description }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-2 border-t-2 border-ink/10 px-3 py-2">
                            <span class="inline-flex items-center gap-1 rounded-sm border-2 border-ink bg-gold/60 px-1.5 py-0.5 font-display text-[0.62rem] font-extrabold">
                                <x-icon name="bolt" class="size-2.5" :stroke="2.8" />
                                +{{ $quest->xp_reward }} XP
                            </span>

                            @if ($isDone)
                                <span class="text-[0.6rem] font-bold text-success-deep">Sudah dikerjakan</span>
                            @elseif ($isLocked)
                                <span class="text-right text-[0.58rem] font-semibold leading-tight text-ink-faint">
                                    {{ $row['locked_reason'] }}
                                </span>
                            @else
                                <form method="POST" action="{{ route('side-quests.start', $quest) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="rounded-md border-2 border-ink bg-brand px-2.5 py-1 font-display text-[0.64rem] font-extrabold uppercase tracking-[0.03em] text-white shadow-[0_2px_0_0_var(--color-brand-deep)] transition active:translate-y-0.5 active:shadow-none"
                                    >
                                        {{ $isOpen && $status === \App\Enums\QuestStatus::InProgress ? 'Lanjutkan' : 'Mulai' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
