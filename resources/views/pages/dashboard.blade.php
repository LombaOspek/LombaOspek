@extends('layouts.app')

@section('title', 'Dasbor')
@section('page-title', 'Dasbor Perjalanan')
@section('page-kicker', '')

@php
    $activeQuest = $activeRow['quest'] ?? null;
@endphp

@section('content')
<div class="flex flex-col gap-4">

    {{-- Sambutan + ringkasan hari orientasi --}}
    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-1.5">
                <span class="stamp bg-teal text-white">
                    <span class="size-1.5 rounded-full bg-white"></span>
                    {{ $orientationEnded ? 'Orientasi Selesai' : 'Orientasi Berlangsung' }}
                </span>
                @if ($orientationDay > 0)
                    <span class="stamp bg-paper">Hari ke-{{ $orientationDay }} dari {{ $totalDays }}</span>
                @else
                    <span class="stamp bg-paper">Pra-orientasi</span>
                @endif
            </p>
            <h1 class="mt-1.5 font-display text-[1.5rem] font-extrabold leading-tight tracking-tight sm:text-[1.8rem]">
                Halo, {{ \Illuminate\Support\Str::before($user->name, ' ') }}.
            </h1>
            <p class="mt-0.5 max-w-xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                {{ $activeQuest
                    ? 'Lanjutkan perjalanan Anda — modul berikutnya sudah terbuka.'
                    : 'Semua modul terbuka sudah selesai. Cek lencana dan sertifikat Anda.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <span class="stamp bg-paper" title="Peringkat Anda di antara seluruh mahasiswa baru">
                <x-icon name="podium" class="size-3" :stroke="2.2" />
                Peringkat {{ $userRank }}/{{ $participantCount }}
            </span>
            <span class="stamp bg-paper" title="Kelompok orientasi Anda">
                <x-icon name="users" class="size-3" :stroke="2.2" />
                {{ $user->group_name ?: 'Tanpa kelompok' }}
            </span>
        </div>
    </section>

    {{-- Empat kartu statistik utama --}}
    <section class="grid grid-cols-2 gap-2.5 lg:grid-cols-4" aria-label="Ringkasan progres">
        <x-stat-tile
            :value="number_format($user->xp, 0, ',', '.')"
            label="Total XP"
            icon="bolt"
            tone="amber"
            :caption="'Level '.$user->level.' · '.$user->level_title"
        />
        <x-stat-tile
            :value="$completedQuests.'/'.$totalQuests"
            label="Modul Selesai"
            icon="map"
            tone="paper"
            :caption="$completionPercentage.'% dari seluruh modul'"
        />
        <x-stat-tile
            :value="$user->streak_days.' hari'"
            label="Rentetan Aktif"
            icon="flame"
            tone="paper"
            :caption="$user->last_active_on?->diffForHumans(short: true) ?? 'Belum ada aktivitas'"
        />
        <x-stat-tile
            :value="$unlockedAchievements.'/'.$achievementRows->count()"
            label="Lencana"
            icon="medal"
            tone="sky"
            :caption="'Sertifikat: '.$completedQuests.'/'.$totalQuests.' modul'"
        />
    </section>

    <div class="grid gap-4 xl:grid-cols-[1.65fr_1fr]">
        {{-- Kolom kiri: modul aktif + daftar modul --}}
        <div class="flex flex-col gap-4">

            @if ($activeQuest && $activeRow)
                <section class="relative overflow-hidden rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat-lg)]">
                    <div class="stripe-amber h-2 w-full border-b-2 border-ink"></div>

                    <div class="p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <span class="stamp bg-ink text-paper">
                                <x-icon name="play" class="size-3" :stroke="2.4" />
                                Modul {{ $activeQuest->step_number }} · Sedang Berjalan
                            </span>
                            <span class="stamp bg-amber">
                                +{{ $activeQuest->xp_reward }} XP
                            </span>
                        </div>

                        <h2 class="mt-2.5 font-display text-[1.15rem] font-extrabold leading-snug tracking-tight sm:text-[1.3rem]">
                            {{ $activeQuest->title }}
                        </h2>
                        <p class="mt-1.5 max-w-2xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                            {{ $activeQuest->objective ?: $activeQuest->description }}
                        </p>

                        <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 text-[0.68rem] font-bold text-ink-faint">
                            <div class="flex items-center gap-1">
                                <dt class="sr-only">Lokasi</dt>
                                <x-icon name="pin" class="size-3.5 text-ink-soft" :stroke="2" />
                                <dd>{{ $activeQuest->location ?: 'Daring' }}</dd>
                            </div>
                            <div class="flex items-center gap-1">
                                <dt class="sr-only">Estimasi</dt>
                                <x-icon name="clock" class="size-3.5 text-ink-soft" :stroke="2" />
                                <dd>±{{ $activeQuest->estimated_minutes }} menit</dd>
                            </div>
                            <div class="flex items-center gap-1">
                                <dt class="sr-only">Kategori</dt>
                                <x-icon name="layers" class="size-3.5 text-ink-soft" :stroke="2" />
                                <dd>{{ $activeQuest->category }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            @if ($activeQuest->action_type === \App\Enums\QuestActionType::Quiz)
                                <a href="{{ route('quiz', ['module' => $activeQuest->quiz_module_code]) }}" class="btn btn-accent">
                                    <x-icon name="quiz" class="size-4" :stroke="2" />
                                    Kerjakan Kuis
                                </a>
                            @else
                                <a href="{{ route('quests.show', $activeQuest) }}" class="btn btn-primary">
                                    <x-icon name="arrow-right" class="size-4" :stroke="2.2" />
                                    Buka Modul
                                </a>
                            @endif

                            <a href="{{ route('quests.index') }}" class="btn btn-ghost">
                                Lihat Peta Misi
                            </a>
                        </div>
                    </div>
                </section>
            @else
                <section class="rounded-lg border-2 border-ink bg-teal/15 p-5 shadow-[var(--shadow-flat)]">
                    <h2 class="font-display text-[1.1rem] font-extrabold">Semua modul terbuka sudah selesai.</h2>
                    <p class="mt-1 text-[0.78rem] font-medium text-ink-soft">
                        Modul berikutnya akan terbuka sesuai jadwal orientasi. Sementara itu, sempurnakan lencana Anda.
                    </p>
                    <a href="{{ route('achievements') }}" class="btn btn-primary mt-3">Lihat pencapaian</a>
                </section>
            @endif

            {{-- Daftar modul ringkas --}}
            <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
                <div class="p-4 pb-3">
                    <x-panel-header
                        title="Peta Misi"
                        :subtitle="$completedQuests.' dari '.$totalQuests.' modul selesai'"
                        icon="map"
                        :action="route('quests.index')"
                        action-label="Buka peta"
                    />
                    <div class="mt-3 flex items-center gap-2">
                        <x-progress :value="$completionPercentage" tone="teal" class="flex-1" label="Progres modul orientasi" />
                        <span class="numeric shrink-0 text-[0.72rem] font-bold">{{ $completionPercentage }}%</span>
                    </div>
                </div>

                <ol class="divide-y-2 divide-ink/10 border-t-2 border-ink/10">
                    @foreach ($board as $row)
                        @php $quest = $row['quest']; @endphp
                        <li>
                            <a
                                href="{{ route('quests.show', $quest) }}"
                                class="flex items-center gap-2.5 px-4 py-2 transition hover:bg-paper-dim"
                            >
                                <span class="grid size-7 shrink-0 place-items-center rounded-full border-[1.5px] border-ink font-mono text-[0.62rem] font-bold
                                    {{ $row['status'] === \App\Enums\QuestStatus::Completed ? 'bg-teal text-white' : ($row['status']->isOpen() ? 'bg-amber' : 'bg-paper-dim text-ink-faint') }}">
                                    @if ($row['status'] === \App\Enums\QuestStatus::Completed)
                                        <x-icon name="check" class="size-3.5" :stroke="2.6" />
                                    @else
                                        {{ $quest->step_number }}
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-display text-[0.78rem] font-bold leading-tight">{{ $quest->title }}</span>
                                    <span class="block truncate text-[0.64rem] font-semibold text-ink-faint">
                                        {{ $quest->category }} · {{ $quest->location ?: 'Daring' }}
                                    </span>
                                </span>

                                <span class="shrink-0">
                                    <x-status-pill :status="$row['status']" :reason="$row['locked_reason']" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Info gerbang waktu: hanya relevan bila masih ada modul yang terkunci --}}
            @if ($activeRow === null && $completedQuests < $totalQuests)
                <section class="rounded-lg border-2 border-ink bg-teal/15 p-4 shadow-[var(--shadow-flat)]">
                    <div class="flex items-start gap-2.5">
                        <span class="grid size-8 shrink-0 place-items-center rounded-sm border-[1.5px] border-ink bg-paper">
                            <x-icon name="lock" class="size-4" :stroke="2" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-display text-[0.78rem] font-bold leading-tight">
                                Modul berikutnya terbuka bertahap
                            </p>
                            <p class="mt-1 text-[0.68rem] font-medium leading-relaxed text-ink-soft">
                                Setiap modul punya hari buka sendiri sesuai jadwal orientasi.
                                Selesaikan modul hari ini untuk membuka modul berikutnya.
                            </p>
                            <a href="{{ route('quests.index') }}" class="mt-2 inline-flex items-center gap-1 font-display text-[0.68rem] font-bold underline decoration-dotted underline-offset-2 hover:text-vermilion-deep">
                                Lihat jadwal modul
                                <x-icon name="arrow-right" class="size-3" :stroke="2.4" />
                            </a>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        {{-- Kolom kanan: peringkat, lencana, riwayat XP --}}
        <div class="flex flex-col gap-4">

            <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
                <div class="p-4 pb-2">
                    <x-panel-header
                        title="Papan Peringkat"
                        subtitle="5 teratas + posisi Anda"
                        icon="podium"
                        :action="route('leaderboard')"
                        action-label="Semua"
                    />
                </div>

                <ol class="px-3 pb-3">
                    @foreach ($leaderboard as $entry)
                        @php
                            $leader = $entry['user'];
                            $isMe = $entry['is_me'];
                            $medalTone = match ($entry['rank']) {
                                1 => 'bg-amber',
                                2 => 'bg-sky/70',
                                3 => 'bg-vermilion/20',
                                default => 'bg-paper-dim',
                            };
                        @endphp

                        {{-- Pemisah saat baris "Anda" melompati peringkat di luar 5 besar. --}}
                        @if ($isMe && $entry['rank'] > $leaderboard->count())
                            <li class="flex items-center gap-1.5 py-1 pl-2 text-[0.6rem] font-bold text-ink-faint" aria-hidden="true">
                                <span class="font-mono">⋮</span>
                                <span>peringkat {{ $entry['rank'] - 1 }} tidak ditampilkan</span>
                            </li>
                        @endif

                        <li @class([
                            'flex items-center gap-2 rounded-sm px-2 py-1.5',
                            'mt-1.5 border-[1.5px] border-ink bg-amber/50' => $isMe,
                        ])>
                            <span class="grid size-5 shrink-0 place-items-center rounded-xs border-[1.5px] border-ink font-mono text-[0.6rem] font-bold {{ $medalTone }}">
                                {{ $entry['rank'] }}
                            </span>
                            <span class="grid size-6 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-sky text-[0.58rem] font-extrabold">
                                {{ mb_strtoupper(mb_substr($leader->name, 0, 1)) }}
                            </span>
                            <span class="min-w-0 flex-1 truncate font-display text-[0.72rem] font-bold">
                                {{ $leader->name }}
                                @if ($isMe)
                                    <span class="text-[0.6rem] font-extrabold text-vermilion-deep">(Anda)</span>
                                @endif
                            </span>
                            <span class="numeric shrink-0 text-[0.68rem] font-bold">{{ number_format($leader->xp, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ol>

                @if ($groupStandings->isNotEmpty())
                    <div class="border-t-2 border-ink/10 px-4 py-2.5">
                        <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.13em] text-ink-faint">Klasemen Kelompok</p>
                        <ul class="mt-1.5 flex flex-col gap-1">
                            @foreach ($groupStandings as $group)
                                <li class="flex items-center justify-between gap-2 text-[0.68rem] font-bold">
                                    <span class="truncate {{ $group['group'] === $user->group_name ? 'text-vermilion-deep' : 'text-ink-soft' }}">
                                        {{ $group['rank'] }}. {{ $group['group'] }}
                                    </span>
                                    <span class="numeric shrink-0 text-ink-faint">{{ number_format($group['total_xp'], 0, ',', '.') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
                <div class="p-4 pb-2">
                    <x-panel-header
                        title="Lencana Terdekat"
                        subtitle="Pencapaian dengan progres tertinggi"
                        icon="medal"
                        :action="route('achievements')"
                        action-label="Semua"
                    />
                </div>

                @php
                    $closest = $achievementRows
                        ->where('is_unlocked', false)
                        ->sortByDesc('percentage')
                        ->take(3);
                @endphp

                <div class="flex flex-col gap-2.5 px-4 pb-4">
                    @forelse ($closest as $row)
                        @php
                            $ach = $row['achievement'];
                        @endphp
                        <div class="flex items-center gap-2.5">
                            {{-- Lencana perisai: satu SVG utuh, ikon tidak terpotong --}}
                            <x-badge
                                :icon="$ach->icon"
                                :tone="$ach->badge_color"
                                class="size-9 shrink-0"
                                :label="$ach->title"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="truncate font-display text-[0.72rem] font-bold">{{ $ach->title }}</span>
                                    <span class="numeric shrink-0 text-[0.62rem] font-bold text-ink-faint">
                                        {{ $row['current_steps'] }}/{{ $row['total_steps'] }}
                                    </span>
                                </div>
                                <x-progress :value="$row['percentage']" tone="amber" height="h-1.5" class="mt-1" />
                            </div>
                        </div>
                    @empty
                        <p class="py-2 text-[0.72rem] font-semibold text-ink-faint">
                            Semua lencana otomatis sudah terbuka. Luar biasa.
                        </p>
                    @endforelse
                </div>
            </section>

            @if ($recentXp->isNotEmpty())
                <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
                    <div class="p-4 pb-2">
                        <x-panel-header title="Riwayat XP" subtitle="Audit poin terakhir" icon="trending" />
                    </div>
                    <ul class="divide-y-2 divide-ink/10 border-t-2 border-ink/10">
                        @foreach ($recentXp as $tx)
                            <li class="flex items-center justify-between gap-2 px-4 py-1.5">
                                <span class="min-w-0 truncate text-[0.68rem] font-semibold text-ink-soft">{{ $tx->description }}</span>
                                <span class="numeric shrink-0 text-[0.68rem] font-bold {{ $tx->amount >= 0 ? 'text-teal-deep' : 'text-vermilion-deep' }}">
                                    {{ $tx->amount >= 0 ? '+' : '' }}{{ $tx->amount }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection