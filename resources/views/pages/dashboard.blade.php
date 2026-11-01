@extends('layouts.app')

@section('title', 'Dasbor')
@section('page-title', 'Dasbor Perjalanan')
@section('page-kicker', '')

@php
    $activeQuest = $activeRow['quest'] ?? null;
@endphp

@section('content')
<div class="flex flex-col gap-4">

    {{-- Sambutan + maskot WIRA --}}
    <section class="relative overflow-hidden rounded-xl border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div class="min-w-0 w-full max-w-xl">
                <p class="flex flex-wrap items-center gap-1.5">
                    <span class="stamp bg-brand text-white">
                        <span class="size-1.5 rounded-full bg-white"></span>
                        {{ $orientationEnded ? 'Orientasi Selesai' : 'Orientasi Berlangsung' }}
                    </span>
                    @if ($orientationDay > 0)
                        <span class="stamp bg-paper-dim">Hari ke-{{ $orientationDay }} dari {{ $totalDays }}</span>
                    @else
                        <span class="stamp bg-paper-dim">Pra-orientasi</span>
                    @endif
                    @if ($user->group_name)
                        <span class="stamp bg-gold/40">
                            <x-icon name="users" class="size-3" :stroke="2.2" />
                            {{ $user->group_name }}
                        </span>
                    @endif
                </p>

                <h1 class="mt-2 font-display text-[1.5rem] font-extrabold leading-tight tracking-tight sm:text-[1.85rem]">
                    Halo, {{ \Illuminate\Support\Str::before($user->name, ' ') }}!
                </h1>

                <p class="mt-1 text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                    {{ $activeQuest
                        ? 'Wira siap memandu Anda! Modul "'.$activeQuest->title.'" sudah siap dikerjakan.'
                        : 'Luar biasa! Seluruh modul yang terbuka sudah Anda selesaikan. Cek lencana dan sertifikat Anda.' }}
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($activeQuest)
                        <a href="{{ route('quests.show', $activeQuest) }}" class="btn btn-primary btn-sm">
                            <x-icon name="play" class="size-3.5" :stroke="2.4" />
                            Lanjut Modul {{ $activeQuest->step_number }}
                        </a>
                    @endif
                    <a href="{{ route('quests.index') }}" class="btn btn-ghost btn-sm">
                        <x-icon name="map" class="size-3.5" :stroke="2" />
                        Peta Misi
                    </a>
                    <span class="stamp bg-paper text-[0.66rem]" title="Peringkat Anda">
                        <x-icon name="podium" class="size-3" :stroke="2.2" />
                        Peringkat {{ $userRank }}/{{ $participantCount }}
                    </span>
                </div>
            </div>

            {{-- Ilustrasi maskot WIRA UMK. Balon ucapan diberi ruang sendiri
                 di atas maskot (bukan ditumpuk di atas kepala) agar tidak
                 menutupi wajah dan tidak menabrak teks di sebelahnya. --}}
            <div class="flex shrink-0 flex-col items-center gap-1.5 self-center sm:self-auto sm:pr-2">
                {{-- Balon ucapan mini WIRA --}}
                <div class="relative hidden rounded-md border-2 border-ink bg-gold px-2.5 py-1 text-[0.62rem] font-extrabold uppercase tracking-[0.06em] shadow-[var(--shadow-flat-sm)] sm:block">
                    Terbang Tinggi!
                    {{-- Ekor balon mengarah ke maskot di bawahnya. --}}
                    <span class="absolute -bottom-[6px] left-1/2 size-2 -translate-x-1/2 rotate-45 border-b-2 border-r-2 border-ink bg-gold" aria-hidden="true"></span>
                </div>

                {{-- Maskot WIRA sekaligus pemicu Misi Rahasia: 7 ketukan cepat
                     membuat peta bergetar dan membuka hadiah tersembunyi.
                     Dibiarkan sebagai <button> agar bisa difokus keyboard. --}}
                <button
                    type="button"
                    id="wira-secret-trigger"
                    class="animate-float w-28 cursor-pointer border-0 bg-transparent p-0 sm:w-36"
                    data-secret-trigger
                    data-secret-endpoint="{{ route('engagement.secret-mission') }}"
                    data-secret-clicks="{{ $engagement['secret']['clicks'] }}"
                    data-secret-window="{{ $engagement['secret']['windowMs'] }}"
                    data-secret-xp="{{ $engagement['secret']['xp'] }}"
                    aria-label="Maskot WIRA UMK"
                >
                    <img
                        src="{{ asset('images/mascot/wira-wave.png') }}"
                        alt=""
                        width="144"
                        height="144"
                        class="pointer-events-none h-auto w-full drop-shadow-[4px_6px_0_rgba(23,21,15,0.85)]"
                        loading="eager"
                    >
                </button>
            </div>
        </div>
    </section>

    {{-- Empat kartu statistik utama --}}
        <section class="grid grid-cols-2 gap-2.5 lg:grid-cols-4" aria-label="Ringkasan progres" data-stagger="50">
            <x-stat-tile
                :value="number_format($user->xp, 0, ',', '.')"
                :counter="$user->xp"
                label="Total XP"
                icon="bolt"
                tone="gold"
                :caption="'Level '.$user->level.' · '.$user->level_title"
            />
            <x-stat-tile
                :value="$completedQuests.'/'.$totalQuests"
                label="Modul Selesai"
                icon="check-circle"
                tone="brand"
                :caption="$completionPercentage.'% dari seluruh modul'"
            />
            <x-stat-tile
                :value="$user->streak_days.' hari'"
                label="Rentetan Aktif"
                icon="flame"
                tone="paper"
                :caption="$user->last_active_on ? $user->last_active_on->diffForHumans() : 'Mulai hari ini'"
            />
            <x-stat-tile
                :value="$unlockedAchievements.'/'.count($achievementRows)"
                label="Lencana"
                icon="medal"
                tone="brand-soft"
                :caption="'Sertifikat: '.$completedQuests.'/'.$totalQuests.' modul'"
            />
        </section>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.65fr_1fr]">
        {{-- Kolom kiri: modul aktif + daftar modul --}}
        <div class="flex min-w-0 flex-col gap-4">

            @if ($activeQuest && $activeRow)
                <section class="relative overflow-hidden rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat-lg)]">
                    <div class="stripe-gold h-2 w-full border-b-2 border-ink"></div>

                    <div class="p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <span class="stamp bg-ink text-paper">
                                <x-icon name="play" class="size-3" :stroke="2.4" />
                                Modul {{ $activeQuest->step_number }} · Sedang Berjalan
                            </span>
                            <span class="stamp bg-gold">
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
                <section class="rounded-lg border-2 border-ink bg-brand/15 p-5 shadow-[var(--shadow-flat)]">
                    <h2 class="font-display text-[1.1rem] font-extrabold">Semua modul terbuka sudah selesai.</h2>
                    <p class="mt-1 text-[0.78rem] font-medium text-ink-soft">
                        Modul berikutnya akan terbuka sesuai jadwal orientasi. Sementara itu, sempurnakan lencana Anda.
                    </p>
                    <a href="{{ route('achievements') }}" class="btn btn-primary mt-3">Lihat pencapaian</a>
                </section>
            @endif

            {{-- ============================================================
                 FOKUS HARIAN
                 Daftar panjang seluruh misi dihapus dari dasbor: ia memaksa
                 mata memindai delapan baris sekaligus. Diganti tiga widget
                 yang menjawab tiga pertanyaan saja - apa yang saya kerjakan
                 hari ini, apa langkah berikutnya, dan apa tantangannya.
                 Daftar lengkap tetap ada di Peta Misi.
                 ============================================================ --}}

            {{-- Widget 1: Fokus Hari Ini --}}
            <section
                id="fokus-hari-ini"
                class="relative overflow-hidden rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat-lg)]"
                aria-labelledby="fokus-hari-ini-title"
            >
                <div class="stripe-gold h-2 w-full border-b-2 border-ink"></div>

                <div class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="stamp bg-ink text-paper">
                                <x-icon name="target" class="size-3" :stroke="2.4" />
                                Fokus Hari Ini
                            </span>
                            <h2 id="fokus-hari-ini-title" class="mt-2 font-display text-[1.1rem] font-extrabold leading-snug tracking-tight sm:text-[1.25rem]">
                                @if ($orientationDay > 0)
                                    Hari ke-{{ $focus['day'] }} dari {{ $totalDays }}
                                @else
                                    Persiapan sebelum orientasi
                                @endif
                            </h2>
                        </div>

                        <span class="stamp bg-gold text-[0.66rem]">
                            {{ $focus['completed'] }}/{{ $focus['total'] }} modul
                        </span>
                    </div>

                    {{-- Progress bar harian --}}
                    <div class="mt-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-[0.66rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">
                                Progres hari ini
                            </span>
                            <span class="numeric text-[0.8rem] font-extrabold">{{ $focus['percentage'] }}%</span>
                        </div>

                        <div
                            class="mt-1.5 h-4 w-full overflow-hidden rounded-full border-2 border-ink bg-paper-dim"
                            role="progressbar"
                            aria-valuenow="{{ $focus['percentage'] }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Progres modul hari ini"
                        >
                            <div
                                class="h-full border-r-2 border-ink bg-gold transition-[width] duration-700 ease-out"
                                style="width: {{ $focus['percentage'] }}%"
                            ></div>
                        </div>
                    </div>

                    {{-- Modul hari ini, maksimal tiga baris --}}
                    <ul class="mt-3 flex flex-col gap-1.5">
                        @forelse ($focus['rows']->take(3) as $row)
                            @php
                                $focusQuest = $row['quest'];
                                $focusDone = $row['status'] === \App\Enums\QuestStatus::Completed;
                            @endphp

                            <li class="flex items-center gap-2 rounded-sm border-[1.5px] border-ink/25 bg-paper-dim/60 px-2 py-1.5">
                                <span @class([
                                    'grid size-5 shrink-0 place-items-center rounded-full border-[1.5px] border-ink',
                                    'bg-success text-white' => $focusDone,
                                    'bg-paper text-ink-faint' => ! $focusDone,
                                ])>
                                    @if ($focusDone)
                                        <x-icon name="check" class="size-3" :stroke="3" />
                                    @else
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                    @endif
                                </span>

                                <span @class([
                                    'min-w-0 flex-1 truncate font-display text-[0.74rem] font-bold',
                                    'text-ink-faint line-through' => $focusDone,
                                ])>
                                    {{ $focusQuest->title }}
                                </span>

                                <span class="numeric shrink-0 text-[0.62rem] font-bold text-ink-faint">
                                    +{{ $focusQuest->xp_reward }}
                                </span>
                            </li>
                        @empty
                            <li class="rounded-sm border-[1.5px] border-dashed border-ink/30 px-2 py-1.5 text-[0.7rem] font-semibold text-ink-faint">
                                Tidak ada modul terjadwal hari ini.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </section>

            {{-- Widget 2: Misi Selanjutnya + CTA besar --}}
            <section
                id="widget-misi-selanjutnya"
                class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]"
                aria-label="Misi Selanjutnya"
            >
                <div class="p-4 pb-2">
                    <x-panel-header
                        title="Misi Selanjutnya"
                        :subtitle="$nextUp->isEmpty() ? 'Semua modul terjadwal sudah tuntas' : 'Langkah terdekat Anda'"
                        icon="flag"
                    />
                </div>

                <ol class="flex flex-col gap-2 px-4 pb-3">
                    @forelse ($nextUp as $index => $row)
                        @php
                            $nextQuest = $row['quest'];
                            $nextOpen = $row['status']->isOpen();
                        @endphp

                        <li>
                            <a
                                href="{{ $nextOpen ? route('quests.show', $nextQuest) : route('quests.index') }}"
                                class="flex items-start gap-2.5 rounded-sm border-2 border-ink p-2 shadow-[var(--shadow-flat-sm)] transition
                                    {{ $index === 0 ? 'bg-gold/35' : 'bg-paper-dim/70' }} hover:-translate-y-0.5"
                            >
                                <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 border-ink font-display text-[0.66rem] font-extrabold
                                    {{ $nextOpen ? 'bg-gold' : 'bg-paper text-ink-faint' }}">
                                    @if ($nextOpen)
                                        {{ $nextQuest->step_number }}
                                    @else
                                        <x-icon name="lock" class="size-3" :stroke="2.4" />
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-display text-[0.78rem] font-bold leading-tight">
                                        {{ $nextQuest->title }}
                                    </span>
                                    <span class="mt-0.5 block truncate text-[0.63rem] font-semibold text-ink-faint">
                                        Bab {{ $nextQuest->unlock_day }} · {{ $nextQuest->category }} · ±{{ $nextQuest->estimated_minutes }} menit
                                    </span>
                                    @if (! $nextOpen && $row['locked_reason'])
                                        <span class="mt-0.5 block text-[0.62rem] font-bold text-vermilion-deep">
                                            {{ $row['locked_reason'] }}
                                        </span>
                                    @endif
                                </span>

                                <span class="stamp shrink-0 bg-gold/60 text-[0.58rem]">+{{ $nextQuest->xp_reward }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-sm border-[1.5px] border-dashed border-ink/30 px-2 py-3 text-center text-[0.7rem] font-semibold text-ink-faint">
                            Seluruh modul sudah selesai. Cek sertifikat Anda.
                        </li>
                    @endforelse
                </ol>

                {{-- CTA besar: melompat ke Peta Misi lalu menggulir ke node aktif. --}}
                <div class="px-4 pb-4">
                    <a
                        href="{{ route('quests.index') }}#lintasan"
                        id="cta-lanjutkan"
                        data-scroll-to-map
                        class="flex w-full items-center justify-center gap-2 rounded-md border-2 border-ink bg-brand px-4 py-3 font-display text-[0.86rem] font-extrabold uppercase tracking-[0.04em] text-white shadow-[0_5px_0_0_var(--color-brand-deep)] transition hover:-translate-y-0.5 active:translate-y-1 active:shadow-[0_1px_0_0_var(--color-brand-deep)]"
                    >
                        <x-icon name="play" class="size-4" :stroke="2.6" />
                        Lanjutkan Perjalanan
                    </a>
                    <p class="mt-1.5 text-center text-[0.6rem] font-semibold text-ink-faint">
                        Membuka Peta Misi dan menggulir ke posisi Anda.
                    </p>
                </div>
            </section>

            {{-- Widget 3: Tantangan Harian --}}
            <section
                id="widget-tantangan-harian"
                class="rounded-lg border-2 border-ink bg-brand/12 shadow-[var(--shadow-flat)]"
                aria-label="Tantangan Harian"
            >
                <div class="flex items-start justify-between gap-2 p-4 pb-2">
                    <x-panel-header
                        title="Tantangan Harian"
                        :subtitle="$challengeDone.' dari '.count($dailyChallenges).' selesai hari ini'"
                        icon="target"
                    />
                    @if ($challengeDone === count($dailyChallenges))
                        <span class="stamp shrink-0 bg-success text-white text-[0.56rem]">
                            <x-icon name="check" class="size-2.5" :stroke="3" />
                            Tuntas
                        </span>
                    @endif
                </div>

                <ul class="flex flex-col gap-2 px-4 pb-4">
                    @foreach ($dailyChallenges as $challenge)
                        @php
                            $challengePct = (int) round(($challenge['current'] / max(1, $challenge['target'])) * 100);
                            $toneClass = match ($challenge['tone']) {
                                'gold' => 'bg-gold',
                                'vermilion' => 'bg-vermilion/25',
                                default => 'bg-brand/25',
                            };
                        @endphp

                        <li class="rounded-sm border-2 border-ink bg-paper p-2 shadow-[var(--shadow-flat-sm)]">
                            <div class="flex items-start gap-2">
                                <span class="grid size-6 shrink-0 place-items-center rounded-sm border-[1.5px] border-ink {{ $toneClass }}">
                                    <x-icon :name="$challenge['icon']" class="size-3.5" :stroke="2.2" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-1 font-display text-[0.72rem] font-bold leading-tight">
                                        <span @class(['truncate', 'text-ink-faint line-through' => $challenge['done']])>
                                            {{ $challenge['title'] }}
                                        </span>
                                        @if ($challenge['done'])
                                            <x-icon name="check" class="size-3 shrink-0 text-success" :stroke="3" />
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-[0.6rem] font-semibold leading-snug text-ink-faint">
                                        {{ $challenge['hint'] }}
                                    </p>

                                    <div class="mt-1 flex items-center gap-1.5">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full border-[1.5px] border-ink bg-paper-dim">
                                            <div
                                                class="h-full {{ $challenge['done'] ? 'bg-success' : 'bg-gold' }}"
                                                style="width: {{ $challengePct }}%"
                                            ></div>
                                        </div>
                                        <span class="numeric shrink-0 text-[0.6rem] font-bold text-ink-faint">
                                            {{ $challenge['current'] }}/{{ $challenge['target'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="border-t-2 border-ink/10 px-4 py-2 text-[0.58rem] font-semibold leading-snug text-ink-faint">
                    Tantangan dihitung dari aktivitas Anda hari ini, bukan diklaim sepihak.
                </p>
            </section>

            {{-- Widget 4: Side Quest — satu ajakan sosial, bukan daftar panjang.
                 Daftar lengkap ada di halaman /misi-samping. --}}
            @if ($sideQuest)
                @php
                    $sq = $sideQuest['quest'];
                    $sqDone = $sideQuest['status'] === \App\Enums\QuestStatus::Completed;
                @endphp

                <section
                    id="widget-side-quest"
                    class="rounded-lg border-2 border-ink bg-vermilion/10 shadow-[var(--shadow-flat)]"
                    aria-label="Side Quest"
                >
                    <div class="flex items-start justify-between gap-2 p-4 pb-2">
                        <x-panel-header
                            title="Side Quest"
                            subtitle="Misi sosial opsional · bonus XP"
                            icon="users"
                        />
                        <span class="stamp shrink-0 bg-paper text-[0.5rem]">
                            {{ $sideQuestSummary['completed'] }}/{{ $sideQuestSummary['total'] }}
                        </span>
                    </div>

                    <div class="px-4 pb-4">
                        <a
                            href="{{ $sqDone ? route('side-quests.index') : route('quests.show', $sq) }}"
                            class="flex items-start gap-2.5 rounded-sm border-2 border-ink bg-paper p-2.5 shadow-[var(--shadow-flat-sm)] transition hover:-translate-y-0.5"
                        >
                            <span class="grid size-8 shrink-0 place-items-center rounded-sm border-2 border-ink bg-gold">
                                <x-icon :name="$sq->icon ?: 'users'" class="size-4" :stroke="2.2" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-display text-[0.78rem] font-bold leading-tight">
                                    {{ $sq->title }}
                                </span>
                                <span class="mt-0.5 block truncate text-[0.62rem] font-semibold text-ink-faint">
                                    {{ $sq->category }} · ±{{ $sq->estimated_minutes }} menit
                                </span>
                            </span>
                            <span class="stamp shrink-0 bg-gold/60 text-[0.55rem]">+{{ $sq->xp_reward }}</span>
                        </a>

                        <a
                            href="{{ route('side-quests.index') }}"
                            class="mt-2 inline-flex items-center gap-1 font-display text-[0.66rem] font-bold underline decoration-dotted underline-offset-2 hover:text-vermilion-deep"
                        >
                            Lihat semua Side Quest
                            <x-icon name="arrow-right" class="size-3" :stroke="2.4" />
                        </a>
                    </div>
                </section>
            @endif

            {{-- Widget 5: Gugus Wars — adu tarik tambang XP antargugus --}}
            <x-engagement.gugus-wars :wars="$engagement['gugusWars']" />

            {{-- Info gerbang waktu: hanya relevan bila masih ada modul yang terkunci --}}
            @if ($activeRow === null && $completedQuests < $totalQuests)
                <section class="rounded-lg border-2 border-ink bg-brand/15 p-4 shadow-[var(--shadow-flat)]">
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
        <div class="flex min-w-0 flex-col gap-4">

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
                                1 => 'bg-gold',
                                2 => 'bg-brand-soft/70',
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
                            'mt-1.5 border-[1.5px] border-ink bg-gold/50' => $isMe,
                        ])>
                            <span class="grid size-5 shrink-0 place-items-center rounded-xs border-[1.5px] border-ink font-mono text-[0.6rem] font-bold {{ $medalTone }}">
                                {{ $entry['rank'] }}
                            </span>
                            <span class="grid size-6 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-brand-soft text-[0.58rem] font-extrabold">
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
                                <span class="numeric shrink-0 text-[0.68rem] font-bold {{ $tx->amount >= 0 ? 'text-brand-deep' : 'text-vermilion-deep' }}">
                                    {{ $tx->amount >= 0 ? '+' : '' }}{{ $tx->amount }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    {{-- Kotak Misteri Harian: modal terpusat, melompat saat halaman dibuka.
         Diletakkan di luar grid tapi di dalam wadah halaman agar posisi
         fixed-nya tidak terpengaruh stacking context kolom. --}}
    <x-engagement.daily-box :box="$engagement['dailyBox']" />
</div>
@endsection

@push('scripts')
    @include('partials.live-feed', ['liveFeed' => $engagement['liveFeed']])
    <script type="module">
        // Data engagement dikirim sebagai JSON, bukan variabel global, supaya
        // tidak ada nilai hadiah yang bisa diubah lewat konsol sebelum klaim.
        // Handler-nya ada di resources/js/app.js dan hanya membaca atribut data.
        document.documentElement.dataset.engagementReady = '1';
    </script>
@endpush