@extends('layouts.app')

@section('title', 'Peta Misi')
@section('page-title', 'Peta Misi Orientasi')
@section('page-kicker', $counts['completed'].' / '.$counts['total'].' modul selesai')

@section('content')
@php
    $activeQuest = $activeRow['quest'] ?? null;
@endphp

<div class="flex flex-col gap-4">

    {{-- Ringkasan & filter --}}
    <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-display text-[1.45rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                    Perjalanan Anda, modul demi modul.
                </h1>
                <p class="mt-0.5 max-w-xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                    Modul terbuka berurutan dan mengikuti jadwal orientasi. Selesaikan satu modul untuk membuka yang berikutnya.
                </p>
            </div>

            @if ($activeQuest)
                <a href="{{ route('quests.show', $activeQuest) }}" class="btn btn-primary">
                    <x-icon name="play" class="size-4" :stroke="2.2" />
                    Lanjutkan Modul {{ $activeQuest->step_number }}
                </a>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4" aria-label="Rekapitulasi status modul">
            <div class="rounded-sm border-2 border-ink bg-teal/15 px-2.5 py-2">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Selesai</p>
                <p class="numeric text-[1.2rem] font-bold leading-none">{{ $counts['completed'] }}</p>
            </div>
            <div class="rounded-sm border-2 border-ink bg-amber/30 px-2.5 py-2">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Berjalan</p>
                <p class="numeric text-[1.2rem] font-bold leading-none">{{ $counts['in_progress'] + $counts['available'] }}</p>
            </div>
            <div class="rounded-sm border-2 border-ink bg-paper px-2.5 py-2">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Terkunci</p>
                <p class="numeric text-[1.2rem] font-bold leading-none">{{ $counts['locked'] }}</p>
            </div>
            <div class="rounded-sm border-2 border-ink bg-paper px-2.5 py-2">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Total XP</p>
                <p class="numeric text-[1.2rem] font-bold leading-none">{{ number_format($user->xp, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <button type="button" class="filter-chip stamp bg-ink text-paper" data-filter="all" aria-pressed="true">
                Semua {{ $counts['total'] }}
            </button>
            <button type="button" class="filter-chip stamp bg-paper" data-filter="completed" aria-pressed="false">
                Selesai {{ $counts['completed'] }}
            </button>
            <button type="button" class="filter-chip stamp bg-paper" data-filter="open" aria-pressed="false">
                Terbuka {{ $counts['in_progress'] + $counts['available'] }}
            </button>
            <button type="button" class="filter-chip stamp bg-paper" data-filter="locked" aria-pressed="false">
                Terkunci {{ $counts['locked'] }}
            </button>
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[1.6fr_1fr]">
        {{-- Jalur misi --}}
        <section aria-label="Daftar modul orientasi" class="relative">
            <ol class="relative flex flex-col gap-2.5" id="quest-list">
                @foreach ($board as $index => $row)
                    @php
                        $quest = $row['quest'];
                        $status = $row['status'];
                        $isOpen = $status->isOpen();
                        $filterKey = match ($status) {
                            \App\Enums\QuestStatus::Completed => 'completed',
                            \App\Enums\QuestStatus::Locked => 'locked',
                            default => 'open',
                        };
                    @endphp

                    <li data-status="{{ $filterKey }}" class="quest-row">
                        <a
                            href="{{ route('quests.show', $quest) }}"
                            class="group flex gap-3 rounded-lg border-2 border-ink bg-paper p-3 shadow-[var(--shadow-flat)] transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-flat-lg)]
                                {{ $status === \App\Enums\QuestStatus::Locked ? 'opacity-70' : '' }}
                                {{ $status === \App\Enums\QuestStatus::InProgress ? 'ring-2 ring-amber ring-offset-2 ring-offset-shell' : '' }}"
                        >
                            {{-- Node nomor --}}
                            <span class="relative grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink font-display text-[0.82rem] font-extrabold
                                {{ $status === \App\Enums\QuestStatus::Completed
                                    ? 'bg-teal text-white'
                                    : ($status === \App\Enums\QuestStatus::InProgress
                                        ? 'bg-amber node-ring node-ring-active'
                                        : ($status === \App\Enums\QuestStatus::Available ? 'bg-paper' : 'bg-paper-dim text-ink-faint')) }}">
                                @if ($status === \App\Enums\QuestStatus::Completed)
                                    <x-icon name="check" class="size-5" :stroke="2.6" />
                                @elseif ($status === \App\Enums\QuestStatus::Locked)
                                    <x-icon name="lock" class="size-4" :stroke="2.1" />
                                @else
                                    {{ $quest->step_number }}
                                @endif
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-1.5">
                                    <x-status-pill :status="$status" :reason="$row['locked_reason']" />
                                    <span class="stamp bg-paper-dim text-[0.56rem]">{{ $quest->category }}</span>
                                    <span class="stamp bg-amber/40 text-[0.56rem]">+{{ $quest->xp_reward }} XP</span>
                                    @if ($quest->requiresVerification())
                                        <span class="stamp bg-teal text-white text-[0.56rem]" title="{{ $quest->verification_method->description() }}">
                                            <x-icon name="shield" class="size-2.5" :stroke="2.6" />
                                            {{ $quest->verification_method->label() }}
                                        </span>
                                    @endif
                                </span>

                                <span class="mt-1.5 block font-display text-[0.86rem] font-bold leading-snug">
                                    {{ $quest->title }}
                                </span>
                                <span class="mt-0.5 block text-[0.7rem] font-medium leading-relaxed text-ink-soft text-pretty">
                                    {{ $quest->objective ?: $quest->description }}
                                </span>

                                <span class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.64rem] font-bold text-ink-faint">
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="pin" class="size-3" :stroke="2" />
                                        {{ $quest->location ?: 'Daring' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="clock" class="size-3" :stroke="2" />
                                        ±{{ $quest->estimated_minutes }} menit
                                    </span>
                                    @if ($row['locked_reason'])
                                        <span class="inline-flex items-center gap-1 text-vermilion-deep">
                                            <x-icon name="info" class="size-3" :stroke="2" />
                                            {{ $row['locked_reason'] }}
                                        </span>
                                    @endif
                                </span>
                            </span>

                            <span class="hidden shrink-0 self-center text-ink-faint transition group-hover:translate-x-0.5 group-hover:text-ink sm:block">
                                <x-icon name="chevron-right" class="size-5" :stroke="2" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>

            <p id="filter-empty" class="hidden rounded-lg border-2 border-dashed border-ink/40 bg-paper p-5 text-center text-[0.76rem] font-semibold text-ink-faint">
                Tidak ada modul pada kategori ini.
            </p>
        </section>

        {{-- Kolom samping --}}
        <aside class="flex flex-col gap-4">
            @if ($activeRow)
                <section class="rounded-lg border-2 border-ink bg-amber/25 p-4 shadow-[var(--shadow-flat)]">
                    <span class="stamp bg-ink text-paper">
                        <x-icon name="play" class="size-3" :stroke="2.4" />
                        Modul aktif
                    </span>
                    <h2 class="mt-2 font-display text-[1rem] font-extrabold leading-snug">{{ $activeQuest->title }}</h2>
                    <p class="mt-1 text-[0.72rem] font-medium leading-relaxed text-ink-soft">
                        {{ $activeQuest->objective ?: $activeQuest->description }}
                    </p>

                    <a href="{{ route('quests.show', $activeQuest) }}" class="btn btn-ink mt-3 w-full">
                        <x-icon name="arrow-right" class="size-4" :stroke="2.2" />
                        Buka modul ini
                    </a>
                </section>
            @endif

            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <x-panel-header title="Cara kerja gerbang waktu" icon="clock" />
                <ul class="mt-2.5 flex flex-col gap-2">
                    <li class="flex gap-2 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                        <x-icon name="check-circle" class="mt-px size-4 shrink-0 text-teal-deep" :stroke="2" />
                        <span>Modul hanya terbuka setelah modul sebelumnya selesai.</span>
                    </li>
                    <li class="flex gap-2 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                        <x-icon name="calendar" class="mt-px size-4 shrink-0 text-teal-deep" :stroke="2" />
                        <span>Setiap modul punya hari buka sendiri sesuai jadwal orientasi.</span>
                    </li>
                    <li class="flex gap-2 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                        <x-icon name="shield" class="mt-px size-4 shrink-0 text-teal-deep" :stroke="2" />
                        <span>Status dihitung ulang di server setiap kali halaman dibuka.</span>
                    </li>
                </ul>
                <p class="mt-3 rounded-sm border-[1.5px] border-dashed border-ink/35 bg-paper-dim p-2 text-[0.64rem] font-semibold leading-snug text-ink-faint">
                    Hari orientasi saat ini: <strong class="text-ink">{{ $orientationDay > 0 ? 'ke-'.$orientationDay : 'belum dimulai' }}</strong>.
                </p>
            </section>

            <section class="rounded-lg border-2 border-ink bg-teal/15 p-4 shadow-[var(--shadow-flat)]">
                <div class="flex items-start gap-2.5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-sm border-2 border-ink bg-paper">
                        <x-icon name="gift" class="size-5" :stroke="2" />
                    </span>
                    <div>
                        <p class="font-display text-[0.8rem] font-bold leading-tight">Hadiah akhir perjalanan</p>
                        <p class="mt-0.5 text-[0.68rem] font-medium leading-relaxed text-ink-soft">
                            Sertifikat orientasi digital ber-kode verifikasi, plus lencana
                            <em>Mahasiswa Sejati</em>.
                        </p>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    const chips = document.querySelectorAll('.filter-chip');
    const rows = document.querySelectorAll('.quest-row');
    const empty = document.getElementById('filter-empty');

    const activeChip = 'stamp bg-ink text-paper';
    const idleChip = 'stamp bg-paper hover:bg-paper-dim';

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const filter = chip.dataset.filter;

            chips.forEach((other) => {
                const isActive = other === chip;
                other.className = `filter-chip ${isActive ? activeChip : idleChip}`;
                other.setAttribute('aria-pressed', String(isActive));
            });

            let visible = 0;

            rows.forEach((row) => {
                const show = filter === 'all' || row.dataset.status === filter;
                row.classList.toggle('hidden', !show);

                if (show) {
                    visible += 1;
                }
            });

            empty?.classList.toggle('hidden', visible > 0);
            window.Ospek?.playTone('tap');
        });
    });
</script>
@endpush