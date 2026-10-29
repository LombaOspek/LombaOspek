@php
    /** @var \App\Models\User|null $barUser */
    $barUser = auth()->user();
    $program = config('ospek.program');
@endphp

<header class="sticky top-0 z-20 border-b-2 border-ink bg-shell/95 backdrop-blur-sm lg:static lg:rounded-t-xl lg:border-2 lg:border-b-2 lg:shadow-[var(--shadow-flat)]">
    <div class="flex items-center justify-between gap-3 px-4 py-2.5 sm:px-5">
        <div class="flex min-w-0 items-center gap-2.5">
            {{-- Tombol drawer hanya muncul di layar kecil --}}
            <button
                type="button"
                class="grid size-9 shrink-0 place-items-center rounded-sm border-2 border-ink bg-paper shadow-[var(--shadow-flat-sm)] transition active:translate-x-0.5 active:translate-y-0.5 lg:hidden"
                data-sidebar-open
                aria-controls="sidebar"
                aria-expanded="false"
                aria-label="Buka menu navigasi"
            >
                <x-icon name="menu" class="size-4" :stroke="2.2" />
            </button>

            <div class="min-w-0">
                <p class="truncate font-display text-[0.9rem] font-bold leading-tight sm:text-[0.98rem]">
                    @yield('page-title', 'Portal Orientasi')
                </p>
                <p class="truncate text-[0.62rem] font-semibold uppercase tracking-[0.1em] text-ink-faint">
                    @yield('page-kicker', $program['study_program'].' · '.$program['academic_year'])
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            {{-- Rentetan: data nyata dari StreakService --}}
            <span
                class="hidden items-center gap-1.5 rounded-sm border-2 border-ink bg-amber px-2 py-1 font-display text-[0.7rem] font-bold shadow-[var(--shadow-flat-sm)] sm:inline-flex"
                title="Hari berturut-turut Anda membuka portal"
            >
                <x-icon name="flame" class="size-3.5 text-vermilion-deep" :stroke="2" />
                {{ $barUser?->streak_days ?? 0 }} hari
            </span>

            {{-- XP ringkas --}}
            <span
                class="inline-flex items-center gap-1.5 rounded-sm border-2 border-ink bg-paper px-2 py-1 font-display text-[0.7rem] font-bold shadow-[var(--shadow-flat-sm)]"
                title="Total pengalaman terkumpul"
            >
                <x-icon name="bolt" class="size-3.5 text-amber-deep" :stroke="2" />
                <span class="numeric">{{ number_format($barUser?->xp ?? 0, 0, ',', '.') }}</span>
                <span class="hidden text-[0.6rem] font-extrabold text-ink-faint sm:inline">XP</span>
            </span>

            {{-- Toggle suara: sinkron ke preferensi akun --}}
            <button
                type="button"
                data-sound-toggle
                aria-pressed="{{ $barUser?->sound_effects ? 'true' : 'false' }}"
                class="grid size-9 place-items-center rounded-sm border-2 border-ink bg-paper shadow-[var(--shadow-flat-sm)] transition hover:bg-white active:translate-x-0.5 active:translate-y-0.5"
                aria-label="Aktifkan atau matikan efek suara"
                title="Efek suara"
            >
                <x-icon name="{{ $barUser?->sound_effects ? 'bell' : 'bell' }}" class="size-4" :stroke="1.9" />
            </button>

            <a
                href="{{ route('profile') }}"
                class="grid size-9 shrink-0 place-items-center rounded-full border-2 border-ink bg-sky font-display text-[0.78rem] font-extrabold shadow-[var(--shadow-flat-sm)] transition hover:bg-teal hover:text-white"
                aria-label="Buka profil saya"
            >
                {{ mb_strtoupper(mb_substr($barUser?->name ?? 'M', 0, 1)) }}
            </a>
        </div>
    </div>
</header>

@push('scripts')
<script type="module">
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const openBtn = document.querySelector('[data-sidebar-open]');

    function open() {
        sidebar?.classList.remove('hidden');
        sidebar?.classList.add('flex');
        backdrop?.classList.remove('hidden');
        openBtn?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        sidebar?.classList.add('hidden');
        sidebar?.classList.remove('flex');
        backdrop?.classList.add('hidden');
        openBtn?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    openBtn?.addEventListener('click', open);
    backdrop?.addEventListener('click', close);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
</script>
@endpush