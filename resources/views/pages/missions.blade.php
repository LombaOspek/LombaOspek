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
            <div class="rounded-sm border-2 border-ink bg-brand/15 px-2.5 py-2">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Selesai</p>
                <p class="numeric text-[1.2rem] font-bold leading-none">{{ $counts['completed'] }}</p>
            </div>
            <div class="rounded-sm border-2 border-ink bg-gold/30 px-2.5 py-2">
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
        {{-- Lintasan misi: deretan node berliku, bukan kartu besar. --}}
        <section aria-labelledby="quest-rail-title" class="relative">
            <h2 id="quest-rail-title" class="sr-only">Lintasan modul orientasi</h2>

            <div class="quest-rail">
                {{-- Wadah relatif: acuan tinggi garis lintasan. --}}
                <div class="relative">
                    {{-- Garis lintasan digambar JS melalui pusat tiap node. --}}
                    <svg
                        class="quest-path-line"
                        id="quest-path-svg"
                        aria-hidden="true"
                        focusable="false"
                        preserveAspectRatio="none"
                    >
                        <path class="quest-path-base" />
                        <path class="quest-path-fill" />
                    </svg>

                    <ol class="quest-list" id="quest-list" data-stagger="55">
                        @php
                            // `chapters` selalu dikirim QuestController::index().
                            $globalIndex = 0;

                            // Total node seluruh babak: dipakai menandai node
                            // TERAKHIR sebagai garis finish. Menghitungnya di
                            // sini (bukan menebak dari $loop) membuat penanda
                            // tetap benar walau jumlah modul berubah.
                            $totalNodes = collect($chapters)->sum(fn ($c) => count($c['rows']));
                        @endphp

                        @foreach ($chapters as $chapter)
                            {{-- ====================================================
                                 PALANG GERBANG BABAK
                                 Satu palang menaungi 3-5 node dalam rentang hari
                                 tematik, bukan satu node per hari. Membawa
                                 narasi perjalanan agar peta terasa seperti
                                 game petualangan, bukan daftar tugas.
                                 ==================================================== --}}
                            <li
                                class="quest-chapter-item list-none"
                                data-chapter-key="{{ $chapter['key'] }}"
                                data-day-from="{{ $chapter['day_from'] }}"
                                data-day-to="{{ $chapter['day_to'] }}"
                            >
                                <div @class([
                                    'quest-chapter-gate',
                                    'quest-chapter-gate--current' => $chapter['is_current'],
                                    'quest-chapter-gate--locked' => $chapter['is_future'] && ! $chapter['is_current'],
                                ])>
                                    <div class="quest-chapter-panel">
                                        {{-- Baris atas: nomor babak + rentang hari --}}
                                        <div class="quest-chapter-head">
                                            <span class="quest-chapter-badge">
                                                @if ($chapter['is_done'])
                                                    <x-icon name="check" class="size-3 text-success" :stroke="3" />
                                                @elseif ($chapter['is_future'] && ! $chapter['is_current'])
                                                    <x-icon name="lock" class="size-3" :stroke="2.4" />
                                                @else
                                                    <x-icon :name="$chapter['icon']" class="size-3" :stroke="2.2" />
                                                @endif
                                                <span>{{ $chapter['label'] }}</span>
                                            </span>

                                            <span class="quest-chapter-days">
                                                <x-icon name="calendar" class="size-3" :stroke="2.2" />
                                                {{ $chapter['days'] }}
                                            </span>
                                        </div>

                                        {{-- Narasi perjalanan babak --}}
                                        @if ($chapter['narrative'] !== '')
                                            <p class="quest-chapter-narrative">{{ $chapter['narrative'] }}</p>
                                        @endif

                                        {{-- Progres babak --}}
                                        <div class="quest-chapter-progress">
                                            <div class="quest-chapter-bar" role="presentation">
                                                <span
                                                    class="quest-chapter-bar-fill"
                                                    style="width: {{ $chapter['total'] > 0 ? (int) round(($chapter['completed'] / $chapter['total']) * 100) : 0 }}%"
                                                ></span>
                                            </div>
                                            <span class="numeric quest-chapter-count">
                                                {{ $chapter['completed'] }}/{{ $chapter['total'] }} modul
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            @foreach ($chapter['rows'] as $row)
                                @php
                                    $quest = $row['quest'];
                                    $status = $row['status'];
                                    $completed = $status === \App\Enums\QuestStatus::Completed;
                                    $locked = $status === \App\Enums\QuestStatus::Locked;
                                    // Node hari berikutnya terkunci total: tanpa bayangan, abu-abu redup.
                                    $lockedFuture = $chapter['is_future'] && $locked;

                                    $isActive = ! $completed && ! $locked && $activeRow
                                        && $activeRow['quest']->is($quest);

                                    // Node terakhir = garis finish. Ia tetap
                                    // memakai status selesai/terkunci untuk
                                    // warna, tetapi bentuknya bendera: ujung
                                    // perjalanan harus terlihat seperti ujung.
                                    $isFinish = $globalIndex === $totalNodes - 1;

                                    $nodeKind = $completed
                                        ? 'done'
                                        : ($lockedFuture
                                            ? 'locked-future'
                                            : ($locked ? 'locked' : ($isActive ? 'active' : 'open')));

                                    // Geseran horizontal node: bergantian tegas kiri-kanan
                                    // (+1 / -1) tanpa posisi tengah. Varian ini yang membuat
                                    // kurva benar-benar mengalir sebagai huruf S lebar, dan
                                    // otomatis membuat sisi label ikut selang-seling.
                                    $wave = $globalIndex % 2 === 0 ? 1 : -1;

                                    // Label selalu di sisi berlawanan arah geseran, jadi
                                    // ruang kosong terisi dan kurva tetap terbaca.
                                    $labelSide = $wave > 0 ? 'left' : 'right';

                                    $filterKey = $completed ? 'completed' : ($locked ? 'locked' : 'open');
                                    $globalIndex++;

                                    // Hari nyata modul (bukan nomor babak): dipakai badge
                                    // "Tugas Hari Ke-N" dan label gembok.
                                    $questDay = (int) $quest->unlock_day;
                                @endphp

                                <li
                                    data-status="{{ $filterKey }}"
                                    data-chapter="{{ $chapter['key'] }}"
                                    data-quest-day="{{ $questDay }}"
                                    data-wave="{{ $wave }}"
                                    data-side="{{ $labelSide }}"
                                    class="quest-step animate-rise"
                                    style="--wave: {{ $wave }}"
                                >
                                    <button
                                        type="button"
                                        id="quest-node-{{ $quest->id }}"
                                        class="quest-node quest-node--{{ $nodeKind }}"
                                        data-node-kind="{{ $nodeKind }}"
                                        data-quest-id="{{ $quest->id }}"
                                        data-chapter="{{ $chapter['key'] }}"
                                        data-quest-day="{{ $questDay }}"
                                        data-title="{{ $quest->title }}"
                                        data-objective="{{ \Illuminate\Support\Str::limit($quest->objective ?: $quest->description, 190) }}"
                                        data-xp="{{ $quest->xp_reward }}"
                                        data-location="{{ $quest->location ?: 'Daring' }}"
                                        data-minutes="{{ $quest->estimated_minutes }}"
                                        data-verification="{{ $quest->requiresVerification() ? $quest->verification_method->label() : '' }}"
                                        data-url="{{ route('quests.show', $quest) }}"
                                        data-claim-url="{{ route('quests.start', $quest) }}"
                                        aria-expanded="false"
                                        aria-controls="quest-bubble-{{ $quest->id }}"
                                        @if ($locked)
                                            aria-disabled="true"
                                            title="Terkunci — {{ $lockedFuture ? 'Terbuka pada '.$chapter['days'].' ('.$chapter['label'].')' : 'selesaikan modul sebelumnya' }}"
                                        @else
                                            aria-label="Modul {{ $quest->step_number }}: {{ $quest->title }}"
                                        @endif
                                    >
                                        @if ($isFinish)
                                            {{-- Bendera finish: ujung lintasan. Tetap
                                                 menampilkan centang bila sudah selesai,
                                                 supaya status tidak hilang. --}}
                                            <span class="quest-finish-flag" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M6 21V4" />
                                                    <path d="M6 4h11l-2.5 4L17 12H6" />
                                                </svg>
                                            </span>
                                            @if ($completed)
                                                <span class="quest-finish-check" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="m5 13 4 4 10-10" />
                                                    </svg>
                                                </span>
                                            @endif
                                        @elseif ($completed)
                                            <x-icon name="check" class="size-7" :stroke="3" />
                                        @elseif ($lockedFuture)
                                            <x-icon name="lock" class="size-5" :stroke="2" />
                                        @elseif ($locked)
                                            <x-icon name="lock" class="size-6" :stroke="2.4" />
                                        @elseif ($isActive)
                                            <img
                                                src="{{ asset('images/mascot/wira-run.png') }}"
                                                alt=""
                                                width="80" height="80"
                                                loading="eager"
                                            >
                                        @else
                                            {{ $quest->step_number }}
                                        @endif
                                    </button>

                                    {{-- Label mengambang: judul misi + hari, di sisi
                                         berlawanan lengkungan agar peta tidak kosong
                                         dan kurva tetap terbaca. Murni dekoratif bagi
                                         pembaca layar - informasi yang sama sudah ada
                                         di aria-label tombol node. --}}
                                    <span class="quest-node-label" aria-hidden="true">
                                        <span class="quest-node-label-title">{{ $quest->title }}</span>
                                        <span class="quest-node-label-meta">
                                            Hari {{ $questDay }} &middot; {{ $quest->category }}
                                        </span>
                                    </span>

                                    {{-- Bubble percakapan --}}
                                    <div
                                        class="quest-bubble"
                                        id="quest-bubble-{{ $quest->id }}"
                                        role="dialog"
                                        aria-labelledby="quest-bubble-title-{{ $quest->id }}"
                                        hidden
                                    >
                                        <p class="quest-bubble-title" id="quest-bubble-title-{{ $quest->id }}">
                                            {{ $quest->title }}
                                        </p>
                                        <p class="quest-bubble-text">
                                            {{ \Illuminate\Support\Str::limit($quest->objective ?: $quest->description, 150) }}
                                        </p>

                                        <p class="quest-bubble-text numeric">
                                            <span class="font-extrabold text-ink">+{{ $quest->xp_reward }} XP</span>
                                            &middot; Hari ke-{{ $questDay }}
                                            &middot; {{ $quest->location ?: 'Daring' }}
                                            &middot; ±{{ $quest->estimated_minutes }} menit
                                        </p>

                                        @if ($locked)
                                            <button type="button" class="quest-bubble-cta" disabled>
                                                <x-icon name="lock" class="size-4" :stroke="2.4" />
                                                Terkunci
                                            </button>
                                            <p class="quest-bubble-note">
                                                <x-icon name="info" class="size-3 shrink-0" :stroke="2.4" />
                                                <span>{{ $lockedFuture ? 'Terbuka pada '.$chapter['days'].' orientasi.' : ($row['locked_reason'] ?: 'Selesaikan modul sebelumnya lebih dulu.') }}</span>
                                            </p>
                                        @elseif ($completed)
                                            <a href="{{ route('quests.show', $quest) }}" class="quest-bubble-cta quest-bubble-cta--done">
                                                <x-icon name="check" class="size-4" :stroke="3" />
                                                Sudah selesai
                                            </a>
                                        @else
                                            <a href="{{ route('quests.show', $quest) }}" class="quest-bubble-cta">
                                                <x-icon name="play" class="size-4" :stroke="2.4" />
                                                Klaim XP
                                            </a>
                                            @if ($quest->requiresVerification())
                                                <p class="quest-bubble-note">
                                                    <x-icon name="shield" class="size-3 shrink-0" :stroke="2.4" />
                                                    <span>XP masuk setelah {{ $quest->verification_method->label() }} diverifikasi LO.</span>
                                                </p>
                                            @endif
                                        @endif

                                        <button type="button" class="quest-bubble-dismiss" data-bubble-close>
                                            Tutup
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        @endforeach
                    </ol>

                    {{-- Maskot dekoratif di ruang kosong sisi lintasan.
                         SATU maskot per babak, bukan tiga untuk seluruh peta:
                         tiap babak punya nada sendiri (pengenalan -> eksplorasi
                         -> aksi), dan karakter yang muncul di setiap babak
                         membuat lintasan terasa punya pemandu, bukan hiasan yang
                         hanya menumpuk di awal.

                         Jumlah & posisinya TIDAK dipatok persen: dihitung JS dari
                         jumlah node nyata yang tampil (lihat syncMascots()), lalu
                         digeser ke celah antarbabak bila slotnya kebetulan jatuh
                         di belakang panel babak. Babak terakhir memakai pose
                         trofi: penutup perjalanan. --}}
                    <div class="quest-mascot-zone">
                        @foreach ([
                            ['wira-idea.png', 'WIRA menyapa', 'Selamat datang di kampus. Mulai dari mengenal tempatnya dulu.'],
                            ['wira-star.png', 'WIRA memberi semangat', 'Babak eksplorasi: temui dosen, keliling fakultas, tanya apa saja.'],
                            ['wira-write.png', 'WIRA menemani menulis', 'Saatnya menuangkan yang sudah kamu pelajari jadi aksi nyata.'],
                            ['wira-trophy.png', 'WIRA menunjuk hadiah', 'Sertifikat orientasi menanti di ujung lintasan.'],
                        ] as $index => [$file, $label, $line])
                            <button
                                type="button"
                                class="quest-mascot"
                                data-mascot-slot="{{ $index }}"
                                data-mascot-line="{{ $line }}"
                                aria-label="{{ $label }}"
                            >
                                <img src="{{ asset('images/mascot/' . $file) }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <p id="filter-empty" class="hidden rounded-lg border-2 border-dashed border-ink/40 bg-paper p-5 text-center text-[0.76rem] font-semibold text-ink-faint">
                Tidak ada modul pada kategori ini.
            </p>
        </section>

        {{-- Kolom samping --}}
        <aside class="flex flex-col gap-4">
            @if ($activeRow)
                <section class="rounded-lg border-2 border-ink bg-gold/25 p-4 shadow-[var(--shadow-flat)]">
                    <span class="stamp bg-ink text-paper">
                        <x-icon name="play" class="size-3" :stroke="2.4" />
                        Modul aktif
                    </span>
                    <h2 class="mt-2 font-display text-[1rem] font-extrabold leading-snug">{{ $activeQuest->title }}</h2>

                    {{-- Badge hari spesifik: mahasiswa tahu persis ini tugas hari ke berapa,
                         bukan sekadar "modul aktif". --}}
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <span class="stamp bg-gold text-[0.6rem]">
                            <x-icon name="calendar" class="size-3" :stroke="2.4" />
                            Tugas Hari Ke-{{ $activeQuest->unlock_day }}
                        </span>

                        @if ($activeChapter)
                            <span class="stamp bg-paper text-[0.58rem]">
                                <x-icon :name="$activeChapter['icon']" class="size-2.5" :stroke="2.4" />
                                {{ $activeChapter['label'] }}
                            </span>
                        @endif

                        <span class="stamp bg-paper text-[0.58rem]">
                            <x-icon name="clock" class="size-2.5" :stroke="2.4" />
                            ±{{ $activeQuest->estimated_minutes }} menit
                        </span>
                    </div>

                    <p class="mt-1.5 text-[0.72rem] font-medium leading-relaxed text-ink-soft">
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
                        <x-icon name="check-circle" class="mt-px size-4 shrink-0 text-brand-deep" :stroke="2" />
                        <span>Modul hanya terbuka setelah modul sebelumnya selesai.</span>
                    </li>
                    <li class="flex gap-2 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                        <x-icon name="calendar" class="mt-px size-4 shrink-0 text-brand-deep" :stroke="2" />
                        <span>Setiap modul punya hari buka sendiri sesuai jadwal orientasi.</span>
                    </li>
                    <li class="flex gap-2 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                        <x-icon name="shield" class="mt-px size-4 shrink-0 text-brand-deep" :stroke="2" />
                        <span>Status dihitung ulang di server setiap kali halaman dibuka.</span>
                    </li>
                </ul>
                <p class="mt-3 rounded-sm border-[1.5px] border-dashed border-ink/35 bg-paper-dim p-2 text-[0.64rem] font-semibold leading-snug text-ink-faint">
                    Hari orientasi saat ini: <strong class="text-ink">{{ $orientationDay > 0 ? 'ke-'.$orientationDay : 'belum dimulai' }}</strong>.
                </p>
            </section>

            <section class="rounded-lg border-2 border-ink bg-brand/15 p-4 shadow-[var(--shadow-flat)]">
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
    const list = document.getElementById('quest-list');
    const pathSvg = document.getElementById('quest-path-svg');
    const empty = document.getElementById('filter-empty');
    const chips = document.querySelectorAll('.filter-chip');
    const steps = () => [...list.querySelectorAll('.quest-step')];

    // ------------------------------------------------------------------
    // Filter status: menyembunyikan langkah, lalu lintasan digambar ulang
    // supaya kurvanya menyambung node yang masih tampil.
    // ------------------------------------------------------------------
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

            steps().forEach((step) => {
                const show = filter === 'all' || step.dataset.status === filter;
                step.classList.toggle('hidden', !show);

                if (show) {
                    visible += 1;
                }
            });

            empty?.classList.toggle('hidden', visible > 0);
            closeBubble();
            window.Ospek?.playTone('tap');
            drawPath();
            syncMascots();
        });
    });

    // ------------------------------------------------------------------
    // Garis lintasan
    // ------------------------------------------------------------------
    function visibleNodes() {
        return steps()
            .filter((step) => !step.classList.contains('hidden'))
            .map((step) => step.querySelector('.quest-node'))
            .filter(Boolean);
    }

    function drawPath() {
        if (!pathSvg || !list) return;

        const nodes = visibleNodes();
        const base = pathSvg.querySelector('.quest-path-base');
        const fill = pathSvg.querySelector('.quest-path-fill');

        if (nodes.length < 2) {
            base.removeAttribute('d');
            fill.removeAttribute('d');
            return;
        }

        const svgBox = pathSvg.getBoundingClientRect();
        const width = svgBox.width;
        const height = svgBox.height;
        pathSvg.setAttribute('viewBox', `0 0 ${width} ${height}`);

        // Pusat node diukur dari POSISI DIAM-nya, bukan posisi sesaat.
        // Node aktif memantul naik-turun (translateY), jadi mengukur rect
        // tombolnya langsung akan memaku kurva ke titik acak tergantung kapan
        // drawPath() kebetulan jalan. Karena node dipusatkan di dalam
        // .quest-step yang tidak beranimasi, kita ambil Y dari langkahnya dan
        // X dari komponen translateX matriks (yang hanya berisi --dx).
        const pts = nodes.map((node) => {
            const b = node.getBoundingClientRect();
            const step = node.closest('.quest-step');
            const stepBox = step ? step.getBoundingClientRect() : b;

            // translateX dari matriks computed: "matrix(a,b,c,d,tx,ty)".
            const matrix = window.getComputedStyle(node).transform;
            const tx = matrix && matrix.startsWith('matrix')
                ? parseFloat(matrix.slice(7, -1).split(',')[4]) || 0
                : 0;

            return {
                x: (step ? stepBox.left + stepBox.width / 2 : b.left + b.width / 2) - svgBox.left + tx,
                y: stepBox.top - svgBox.top + stepBox.height / 2,
                kind: node.dataset.nodeKind || 'locked',
            };
        });

        // Catmull-Rom -> bezier kubik: kurva melewati setiap pusat node dengan
        // arah yang mulus, jadi likunya mengikuti geseran node tanpa sudut.
        const TENSION = 0.3;
        let d = `M ${pts[0].x} ${pts[0].y}`;

        for (let i = 0; i < pts.length - 1; i += 1) {
            const p0 = pts[i - 1] || pts[i];
            const p1 = pts[i];
            const p2 = pts[i + 1];
            const p3 = pts[i + 2] || p2;

            const c1x = p1.x + (p2.x - p0.x) * TENSION;
            const c1y = p1.y + (p2.y - p0.y) * TENSION;
            const c2x = p2.x - (p3.x - p1.x) * TENSION;
            const c2y = p2.y - (p3.y - p1.y) * TENSION;

            d += ` C ${c1x} ${c1y} ${c2x} ${c2y} ${p2.x} ${p2.y}`;
        }

        base.setAttribute('d', d);
        fill.setAttribute('d', d);

        // Progres: garis biru sampai node selesai terakhir.
        const done = nodes
            .map((node, index) => (node.dataset.nodeKind === 'done' ? index : -1))
            .filter((index) => index >= 0);
        const allDone = done.length === nodes.length;
        const lastDone = done.length ? pts[done[done.length - 1]].y : pts[0].y;
        const cut = allDone ? height : lastDone;

        fill.style.clipPath = done.length
            ? `inset(0 0 ${Math.max(0, height - cut)}px 0)`
            : 'inset(0 0 100% 0)';
    }

    // ------------------------------------------------------------------
    // Bubble percakapan
    // ------------------------------------------------------------------
    let openBubble = null;

    function closeBubble() {
        if (!openBubble) return;

        const { bubble, node } = openBubble;
        bubble.hidden = true;
        node.setAttribute('aria-expanded', 'false');
        openBubble = null;
    }

    function toggleBubble(node, byHover = false) {
        const bubble = document.getElementById(node.getAttribute('aria-controls'));

        if (!bubble) return;

        if (openBubble && openBubble.node === node) {
            // Bubble yang terbuka karena hover dikunci oleh klik: tetap
            // terbuka, klik berikutnya baru menutup.
            if (openBubble.byHover && !byHover) {
                openBubble.byHover = false;
                return;
            }

            // Sebaliknya, hover TIDAK boleh menutup bubble yang sudah dibuka
            // (atau dikunci) dengan klik. Tanpa ini, timer hover yang menyala
            // sesudah klik akan menutup bubble tepat setelah ia terbuka.
            if (byHover) {
                return;
            }

            closeBubble();
            return;
        }

        closeBubble();
        bubble.hidden = false;
        node.setAttribute('aria-expanded', 'true');
        openBubble = { bubble, node, byHover };
        window.Ospek?.playTone('tap');
    }

    // Hover: bubble dibuka bila kursor berhenti sebentar di atas node.
    // Jeda mencegah bubble berkedip saat kursor sekadar melintas.
    const HOVER_DELAY = 160;
    let hoverTimer = null;

    list?.addEventListener('pointerover', (event) => {
        if (event.pointerType === 'touch') return;

        const node = event.target.closest('.quest-node');

        if (!node || node.classList.contains('quest-node--locked')) return;
        if (openBubble && openBubble.node === node) return;

        window.clearTimeout(hoverTimer);
        hoverTimer = window.setTimeout(() => toggleBubble(node, true), HOVER_DELAY);
    });

    list?.addEventListener('pointerout', (event) => {
        const node = event.target.closest('.quest-node');

        if (!node) return;
        // Kursor pindah ke dalam bubble: jangan tutup, supaya tombolnya
        // masih bisa diklik.
        if (event.relatedTarget && openBubble?.bubble.contains(event.relatedTarget)) return;

        window.clearTimeout(hoverTimer);

        // Bubble yang muncul karena hover ikut hilang saat kursor pergi.
        // Bubble yang dibuka dengan klik tetap terbuka sampai ditutup.
        if (openBubble?.node === node && openBubble.byHover) {
            window.setTimeout(() => {
                if (openBubble?.byHover && !openBubble.bubble.matches(':hover')) closeBubble();
            }, 220);
        }
    });

    // Node terkunci tidak bereaksi terhadap hover sama sekali.

    list?.addEventListener('click', (event) => {
        const close = event.target.closest('[data-bubble-close]');

        if (close) {
            closeBubble();
            return;
        }

        const node = event.target.closest('.quest-node');

        if (node) {
            // Node terkunci tidak membuka apa pun; beri isyarat singkat.
            if (node.classList.contains('quest-node--locked')) {
                window.Ospek?.playTone('warn');
                return;
            }

            toggleBubble(node);
        }
    });

    // Keyboard tidak butuh handler sendiri: node adalah <button>, jadi Enter dan
    // Spasi sudah memicu event click native dan bubble terbuka lewat jalur yang
    // sama. Menambah handler focusin justru membuat satu klik men-toggle dua
    // kali (buka lalu tutup).
    // Escape menutup bubble; klik di luar juga.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeBubble();
    });

    document.addEventListener('click', (event) => {
        if (!openBubble) return;
        if (event.target.closest('.quest-node, .quest-bubble')) return;

        closeBubble();
    });

    // ------------------------------------------------------------------
    // Maskot dekoratif: klik memunculkan sapaan lewat toast bawaan.
    // ------------------------------------------------------------------
    document.querySelectorAll('.quest-mascot').forEach((mascot) => {
        mascot.addEventListener('click', (event) => {
            event.stopPropagation();

            const line = mascot.dataset.mascotLine;

            // Di layar sentuh, tooltip di-toggle oleh tap. Di layar besar
            // tooltip sudah muncul lewat CSS :hover, jadi kelas ini hanya
            // dipakai sebagai penanda state agar tap berikutnya menutup.
            const alreadyOpen = mascot.classList.contains('is-open');

            document.querySelectorAll('.quest-mascot.is-open').forEach((other) => {
                other.classList.remove('is-open');
            });

            if (!alreadyOpen) {
                mascot.classList.add('is-open');
            }

            // Di layar besar tooltip CSS sudah memperlihatkan kalimatnya, jadi
            // toast hanya jadi duplikat. Di ponsel justru lebih parah: toast
            // menutup 1/3 layar padahal tooltip tap sudah tampil di tempat.
            // Suara tap tetap diputar sebagai umpan balik ringan.
            const isTouchViewport = window.matchMedia('(max-width: 767px)').matches;

            if (!isTouchViewport && line && window.Ospek?.toast) {
                window.Ospek.toast({ title: 'WIRA', message: line, tone: 'neutral' });
            }

            // Sinyal haptic ringan di perangkat yang mendukung.
            if (!alreadyOpen && navigator.vibrate) {
                navigator.vibrate(10);
            }

            window.Ospek?.playTone('tap');
        });
    });

    // Tap di luar maskot menutup tooltip. `pointerdown` dipakai agar terasa
    // responsif dan tetap bekerja di perangkat sentuh.
    document.addEventListener('pointerdown', (event) => {
        if (event.target.closest('.quest-mascot')) return;

        document.querySelectorAll('.quest-mascot.is-open').forEach((mascot) => {
            mascot.classList.remove('is-open');
        });
    });

    // Escape menutup tooltip tanpa harus menyentuh layar.
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('.quest-mascot.is-open').forEach((mascot) => {
            mascot.classList.remove('is-open');
        });
    });

    // ------------------------------------------------------------------
    // Scroll otomatis ke node aktif (bila datang dari CTA "Lanjutkan")
    // ------------------------------------------------------------------
    function scrollToActiveNode() {
        if (location.hash !== '#lintasan' && !location.search.includes('scroll=active')) return;

        const target = document.querySelector('.quest-node--active')
            || document.querySelector('.quest-node--done');

        if (!target) return;

        // Beri waktu browser menyelesaikan layout awal
        window.setTimeout(() => {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Beri isyarat visual singkat
            target.focus({ preventScroll: true });
        }, 180);
    }

    scrollToActiveNode();

    // ------------------------------------------------------------------
    // Penempatan maskot yang mengikuti jumlah misi
    // ------------------------------------------------------------------
    // Posisi maskot tidak boleh dipatok persen: jumlah node bisa berapa saja
    // (5, 13, 40) dan tinggi tiap langkah berbeda di tiap breakpoint. Yang
    // dipakai sebagai acuan adalah DAFTAR LANGKAHNYA sendiri - bukan padding
    // berlebih yang membuat maskot terlihat "melayang" saat daftar pendek.
    //
    // Dua aturan penempatan:
    //  1. Maskot disebar merata pada sepertiga posisi daftar (bukan per slot
    //     33% dari kotak pembungkus, yang bisa jatuh di luar daftar).
    //  2. Slot yang jatuh di belakang panel babak DIGESER ke celah antarbabak
    //     terdekat, karena panel babak (z 12) menutupi maskot (z 2) - inilah
    //     yang dulu membuat WIRA pemegang piala "tertelan" panel Bab 3.
    function syncMascots() {
        const zone = document.querySelector('.quest-mascot-zone');
        const mascots = [...document.querySelectorAll('.quest-mascot')];

        if (!zone || mascots.length === 0 || !list) return;

        const zoneBox = zone.getBoundingClientRect();
        const listBox = list.getBoundingClientRect();

        if (listBox.height < 80) return;

        // Tinggi maskot dipakai untuk menjauhkan kepalanya dari panel.
        const mascotH = mascots[0].offsetHeight || 80;

        // Panel babak = area terlarang. Perluas sedikit (zona aman) supaya
        // kepala maskot tidak menempel tepat di garis bawah panel.
        const forbidden = [...document.querySelectorAll('.quest-chapter-item')]
            .map((item) => {
                const b = item.getBoundingClientRect();

                return {
                    top: b.top - zoneBox.top,
                    bottom: b.bottom - zoneBox.top,
                };
            });

        const listTop = listBox.top - zoneBox.top;
        const listHeight = listBox.height;
        const safeTop = mascotH * 0.6;

        // Apakah maskot (tinggi mascotH, di posisi top) akan tertutup panel?
        const collides = (top) => forbidden.some(
            (band) => top + mascotH > band.top && top < band.bottom,
        );

        // Maskot yang sudah ditempatkan: mencegah dua maskot bertumpuk saat
        // daftar sangat pendek (mis. 3 misi) sehingga semua slot berdesakan.
        const placed = [];
        const minGap = mascotH * 0.85;

        const overlapsMascot = (top) => placed.some(
            (other) => Math.abs(other - top) < minGap,
        );

        // Geser ke celah terdekat yang bebas dari panel DAN maskot lain.
        const relocate = (top) => {
            const attempts = [0, -0.12, 0.12, -0.24, 0.24, -0.36, 0.36, -0.5, 0.5];

            for (const delta of attempts) {
                const candidate = top + delta * listHeight;

                if (
                    candidate >= safeTop
                    && candidate + mascotH <= listTop + listHeight
                    && !collides(candidate)
                    && !overlapsMascot(candidate)
                ) {
                    return candidate;
                }
            }

            return top;
        };

        /*
         * Sebar maskot merata sepanjang daftar, satu slot per maskot.
         *
         * Sebelumnya slot-nya DIPATOK tiga: [0.25, 0.5, 0.75]. Padahal view
         * merender EMPAT maskot (satu per babak + penutup). Maskot keempat
         * mengambil `slots[4 % 3]` = slots[0] = 0.25, yaitu posisi yang sudah
         * dipakai maskot pertama, sehingga `overlapsMascot()` menolaknya dan
         * `relocate()` mengembalikan posisi aslinya - dua maskot bertumpuk di
         * satu titik.
         *
         * Akibat yang terlihat pengguna: Bab 3 (Aksi & Penutupan) tidak punya
         * karakter WIRA sama sekali di sepanjang jalannya, karena maskot
         * terakhir (pose trofi) menempel di Bab 1, bukan di ujung lintasan.
         *
         * Rumus (i + 0.5) / n memberi 0.125, 0.375, 0.625, 0.875 untuk empat
         * maskot: satu di tiap babak, dan yang terakhir mendarat di Bab 3.
         */
        const slots = mascots.map((_, i) => (i + 0.5) / mascots.length);

        mascots.forEach((mascot, index) => {
            const isRight = index % 2 === 1;

            mascot.classList.toggle('quest-mascot--right', isRight);
            mascot.classList.toggle('quest-mascot--left', !isRight);

            const ratio = slots[index] ?? (index + 0.5) / mascots.length;
            const base = listTop + listHeight * ratio - mascotH / 2;
            const top = relocate(base);

            placed.push(top);

            mascot.style.setProperty('--mascot-top', `${Math.round(top)}px`);

            // Redupkan bila maskot duduk di area babak yang belum terbuka.
            const chapter = [...document.querySelectorAll('.quest-chapter-item')]
                .filter((item) => {
                    const b = item.getBoundingClientRect();

                    return b.top - zoneBox.top <= top;
                })
                .pop();

            mascot.dataset.future = chapter?.querySelector('.quest-chapter-gate--locked') ? '1' : '0';
        });
    }

    drawPath();
    window.addEventListener('resize', () => {
        drawPath();
        syncMascots();
    }, { passive: true });

    // Kurva harus digambar ulang setiap kali geometri daftar berubah, bukan
    // hanya saat resize. Penyebab paling nyata: bubble yang terbuka menambah
    // tinggi langkah, dan font yang selesai dimuat mengubah tinggi label -
    // keduanya menggeser pusat node sehingga kurva lama jadi meleset.
    if (typeof ResizeObserver === 'function' && list) {
        const observer = new ResizeObserver(() => {
            drawPath();
            syncMascots();
        });
        observer.observe(list);

        // Perubahan tinggi tiap langkah tidak selalu mengubah tinggi total
        // daftar (naik-turun saling meniadakan), jadi amati langkahnya juga.
        steps().forEach((step) => observer.observe(step));

        // Panel babak ikut diamati: narasinya baru reflow setelah font dimuat,
        // dan tinggi barunya menggeser SEMUA node di bawahnya. Tanpa ini, node
        // pertama meleset paling jauh dari kurva.
        document.querySelectorAll('.quest-chapter-item').forEach((item) => observer.observe(item));
    }

    // Jalankan ulang setelah layout benar-benar tenang (dua frame berturut).
    function settle(n = 2) {
        if (n <= 0) return;
        window.requestAnimationFrame(() => settle(n - 1));
        if (n === 1) {
            drawPath();
            syncMascots();
        }
    }

    // Font web mengubah metrik label sehingga tinggi langkah ikut berubah.
    syncMascots();

    if (document.fonts?.ready) {
        document.fonts.ready.then(() => settle());
    }

    // Gambar maskot punya tinggi intrinsik yang baru diketahui setelah dimuat.
    window.addEventListener('load', () => settle());
    window.addEventListener('load', drawPath, { once: true });
    document.fonts?.ready.then(drawPath);
</script>
@endpush

