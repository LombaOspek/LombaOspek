@php
    /** @var \App\Models\User|null $navUser */
    $navUser = auth()->user();
    $program = config('ospek.program');

    $items = [
        ['route' => 'dashboard', 'active' => ['dashboard', 'home'], 'label' => 'Dasbor', 'icon' => 'home'],
        ['route' => 'quests.index', 'active' => ['quests.*'], 'label' => 'Peta Misi', 'icon' => 'map'],
        ['route' => 'quiz', 'active' => ['quiz*'], 'label' => 'Kuis Interaktif', 'icon' => 'quiz'],
        ['route' => 'achievements', 'active' => ['achievements'], 'label' => 'Pencapaian', 'icon' => 'medal'],
        ['route' => 'certificate', 'active' => ['certificate'], 'label' => 'Sertifikat', 'icon' => 'certificate'],
        ['route' => 'leaderboard', 'active' => ['leaderboard'], 'label' => 'Peringkat', 'icon' => 'podium'],
    ];

    $accountItems = [
        ['route' => 'profile', 'params' => [], 'active' => fn () => request()->routeIs('profile') && request('tab', 'profile') === 'profile', 'label' => 'Profil Saya', 'icon' => 'user'],
        ['route' => 'profile', 'params' => ['tab' => 'settings'], 'active' => fn () => request()->routeIs('profile') && request('tab') === 'settings', 'label' => 'Pengaturan', 'icon' => 'settings'],
        ['route' => 'profile', 'params' => ['tab' => 'helpdesk'], 'active' => fn () => request()->routeIs('profile') && request('tab') === 'helpdesk', 'label' => 'Pusat Bantuan', 'icon' => 'help'],
    ];
@endphp

{{-- Sidebar: tetap di desktop, drawer di mobile (dikendalikan partials/topbar). --}}
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 hidden w-[17rem] shrink-0 flex-col overflow-y-auto border-r-2 border-ink bg-shell p-4 shadow-[var(--shadow-flat-xl)] lg:sticky lg:top-3 lg:z-auto lg:flex lg:max-h-[calc(100vh-1.5rem)] lg:rounded-l-xl lg:border-2 lg:shadow-[var(--shadow-flat)]"
    aria-label="Navigasi utama"
>
    <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5 rounded-md px-1 py-1">
        <span class="grid size-10 shrink-0 -rotate-3 place-items-center rounded-md border-2 border-ink bg-amber shadow-[var(--shadow-flat-sm)] transition-transform group-hover:rotate-0">
            <x-icon name="compass" class="size-6 text-ink" :stroke="2" />
        </span>
        <span class="min-w-0">
            <span class="block font-display text-[1.05rem] font-extrabold leading-none tracking-tight">OspekQuest</span>
            <span class="mt-0.5 block truncate text-[0.58rem] font-extrabold uppercase tracking-[0.14em] text-ink-faint">
                {{ $program['university'] === 'Universitas Muria Kudus' ? 'UMK · Orientasi ' : 'Orientasi ' }}{{ $program['class_of'] }}
            </span>
        </span>
    </a>

    @if ($navUser)
        <div class="mt-4 rounded-lg border-2 border-ink bg-paper p-3 shadow-[var(--shadow-flat-sm)]">
            <div class="flex items-center gap-2.5">
                <span class="relative grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink bg-sky font-display text-base font-extrabold">
                    {{ mb_strtoupper(mb_substr($navUser->name, 0, 1)) }}
                    <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-[1.5px] border-ink bg-teal"></span>
                </span>
                <div class="min-w-0">
                    <p class="truncate font-display text-[0.8rem] font-bold leading-tight">{{ $navUser->name }}</p>
                    <p class="truncate font-mono text-[0.62rem] font-bold text-ink-faint">{{ $navUser->nim }}</p>
                </div>
            </div>

            <div class="mt-3 border-t border-dashed border-ink/30 pt-2.5">
                <div class="flex items-baseline justify-between text-[0.62rem] font-extrabold uppercase tracking-[0.1em]">
                    <span class="text-amber-deep">Lv {{ $navUser->level }} · {{ $navUser->level_title }}</span>
                    <span class="numeric text-ink">{{ number_format($navUser->xp, 0, ',', '.') }} XP</span>
                </div>
                <x-progress :value="$navUser->level_progress" tone="amber" class="mt-1.5" label="Progres level" />
                <p class="mt-1 text-[0.6rem] font-bold text-ink-faint">
                    @if ($navUser->xp_to_next_level === null)
                        Level maksimum tercapai
                    @else
                        {{ number_format($navUser->xp_to_next_level, 0, ',', '.') }} XP menuju Lv {{ $navUser->level + 1 }}
                    @endif
                </p>
            </div>
        </div>
    @endif

    <nav class="mt-4 flex flex-col gap-0.5">
        <p class="mb-1 px-2 text-[0.58rem] font-extrabold uppercase tracking-[0.16em] text-ink-faint">Perjalanan</p>
        @foreach ($items as $item)
            @php $isActive = request()->routeIs(...$item['active']); @endphp
            <a
                href="{{ route($item['route']) }}"
                @if ($isActive) aria-current="page" @endif
                class="flex items-center gap-2.5 rounded-sm border-2 px-2.5 py-[0.44rem] font-display text-[0.79rem] font-bold transition {{ $isActive ? 'border-ink bg-amber shadow-[var(--shadow-flat-sm)]' : 'border-transparent text-ink-soft hover:border-ink/20 hover:bg-paper' }}"
            >
                <x-icon :name="$item['icon']" class="size-[1.05rem]" :stroke="$isActive ? 2.1 : 1.8" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <nav class="mt-4 flex flex-col gap-0.5">
        <p class="mb-1 px-2 text-[0.58rem] font-extrabold uppercase tracking-[0.16em] text-ink-faint">Akun</p>
        @foreach ($accountItems as $item)
            @php $isActive = $item['active'](); @endphp
            <a
                href="{{ route($item['route'], $item['params']) }}"
                @if ($isActive) aria-current="page" @endif
                class="flex items-center gap-2.5 rounded-sm border-2 px-2.5 py-[0.44rem] font-display text-[0.79rem] font-bold transition {{ $isActive ? 'border-ink bg-amber shadow-[var(--shadow-flat-sm)]' : 'border-transparent text-ink-soft hover:border-ink/20 hover:bg-paper' }}"
            >
                <x-icon :name="$item['icon']" class="size-[1.05rem]" :stroke="$isActive ? 2.1 : 1.8" />
                {{ $item['label'] }}
            </a>
        @endforeach

        @if ($navUser?->isMentor())
            <a href="{{ route('lo.dashboard') }}" class="flex items-center gap-2.5 rounded-sm border-2 px-2.5 py-[0.44rem] font-display text-[0.79rem] font-bold transition {{ request()->routeIs('lo.*') ? 'border-ink bg-amber shadow-[var(--shadow-flat-sm)]' : 'border-transparent text-ink-soft hover:border-ink/20 hover:bg-paper' }}">
                <x-icon name="shield" class="size-[1.05rem]" :stroke="1.8" />
                Verifikasi Bukti
            </a>
        @endif

        @if ($navUser?->isAdmin())
            <a href="{{ route('admin.accounts') }}" class="flex items-center gap-2.5 rounded-sm border-2 px-2.5 py-[0.44rem] font-display text-[0.79rem] font-bold transition {{ request()->routeIs('admin.accounts*') ? 'border-ink bg-amber shadow-[var(--shadow-flat-sm)]' : 'border-transparent text-ink-soft hover:border-ink/20 hover:bg-paper' }}">
                <x-icon name="users" class="size-[1.05rem]" :stroke="1.8" />
                Akun &amp; Gugus
            </a>
            <a href="{{ route('admin.quests.index') }}" class="flex items-center gap-2.5 rounded-sm border-2 px-2.5 py-[0.44rem] font-display text-[0.79rem] font-bold transition {{ request()->routeIs('admin.quests*') ? 'border-ink bg-amber shadow-[var(--shadow-flat-sm)]' : 'border-transparent text-ink-soft hover:border-ink/20 hover:bg-paper' }}">
                <x-icon name="layers" class="size-[1.05rem]" :stroke="1.8" />
                Quest Builder
            </a>
        @endif
    </nav>

    <div class="mt-auto pt-4">
        @if ($navUser?->mentor_name)
            <div class="rounded-md border-2 border-ink bg-sky/70 p-2.5">
                <p class="flex items-center gap-1.5 text-[0.56rem] font-extrabold uppercase tracking-[0.13em] text-ink/70">
                    <x-icon name="compass" class="size-3" :stroke="2.2" />
                    Mentor Pendamping
                </p>
                <p class="mt-1 font-display text-[0.72rem] font-bold leading-snug">{{ $navUser->mentor_name }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-2" data-submit-lock>
            @csrf
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-sm border-2 border-ink bg-paper px-2.5 py-[0.44rem] font-display text-[0.75rem] font-bold shadow-[var(--shadow-flat-sm)] transition hover:bg-vermilion hover:text-white">
                <x-icon name="logout" class="size-4" :stroke="1.9" />
                Keluar
            </button>
        </form>
    </div>
</aside>

<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-ink/50 backdrop-blur-[1px] lg:hidden" data-sidebar-close></div>