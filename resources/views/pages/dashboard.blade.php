@extends('layouts.app')

@section('title', 'Papan Pemuka Orientasi')

@section('content')
<div class="p-5 md:p-8 flex flex-col gap-6">

    <!-- BEGIN: Top Header Greeting & Quick Badges -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-2 border-black/10 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-200 brutal-border text-[10px] font-black uppercase text-emerald-950 shadow-brutal-sm">
                    ● Orientasi Berlangsung
                </span>
                <span class="text-xs font-bold text-neutral-500">• Hari Ke-4</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-display font-black text-[#1E1E1E] tracking-tight">
                Selamat Datang, {{ $user->name }}! 👋
            </h2>
            <p class="text-xs sm:text-sm font-semibold text-neutral-600 mt-0.5">
                {{ $user->faculty }} • {{ $user->major }}
            </p>
        </div>

        <!-- Quick Top Metrics Pills -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Streak Flame -->
            <div class="bg-[#FED7AA] brutal-border rounded-2xl px-3.5 py-2 shadow-brutal-sm flex items-center gap-2.5">
                <span class="text-2xl animate-bounce">🔥</span>
                <div>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-amber-900 leading-none">Rentetan Aktif</span>
                    <span class="text-xs font-black text-amber-950">{{ $user->streak_days }} Hari Berturut-turut</span>
                </div>
            </div>

            <!-- Total XP Badge -->
            <div class="bg-neo-yellow brutal-border rounded-2xl px-3.5 py-2 shadow-brutal-sm flex items-center gap-2.5">
                <span class="text-xl">⚡</span>
                <div>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-amber-900 leading-none">Mata Ganjaran</span>
                    <span class="text-xs font-black text-amber-950">{{ number_format($user->xp) }} XP</span>
                </div>
            </div>

            <!-- Campus Rank -->
            <div class="bg-[#A5C9CA] brutal-border rounded-2xl px-3.5 py-2 shadow-brutal-sm flex items-center gap-2">
                <span class="text-xl">🎖️</span>
                <div>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-800 leading-none">Kedudukan</span>
                    <span class="text-xs font-black text-slate-900">Ke-{{ $userRank }} Kampus</span>
                </div>
            </div>
        </div>
    </header>
    <!-- END: Top Header -->

    <!-- BEGIN: 3 Bento Overview Stats Cards -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4" data-purpose="bento-stats">
        <!-- Stat 1: Completed Modules -->
        <div class="bg-[#FAF8F5] brutal-border rounded-2xl p-4 sm:p-5 shadow-brutal flex items-center justify-between hover:-translate-y-0.5 transition-transform">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-neutral-400">Kemajuan Misi Orientasi</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="font-display font-black text-2xl sm:text-3xl text-[#1E1E1E]">{{ $completedQuestsCount }} / {{ $totalQuestsCount }}</span>
                    <span class="text-xs font-bold text-emerald-600 font-display font-black">{{ $completionPercentage }}% Siap</span>
                </div>
                <div class="w-40 bg-neutral-200 rounded-full h-2.5 mt-2.5 overflow-hidden border border-[#1E1E1E]">
                    <div class="h-full bg-emerald-400 transition-all duration-500" style="width: {{ $completionPercentage }}%;"></div>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 brutal-border flex items-center justify-center text-2xl shadow-brutal-sm shrink-0">
                🗺️
            </div>
        </div>

        <!-- Stat 2: Total XP & Clan -->
        <div class="bg-neo-yellow brutal-border rounded-2xl p-4 sm:p-5 shadow-brutal flex items-center justify-between hover:-translate-y-0.5 transition-transform">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-950">Kumpulan Orientasi</span>
                <div class="text-lg font-display font-black text-neutral-900 mt-1 leading-tight">
                    {{ $user->group_name }}
                </div>
                <span class="inline-block mt-1 text-[11px] font-extrabold text-amber-950/80">
                    Status: {{ $user->rank_title }}
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-white brutal-border flex items-center justify-center text-2xl shadow-brutal-sm shrink-0">
                🐯
            </div>
        </div>

        <!-- Stat 3: Target Grand Reward -->
        <div class="bg-neo-peach brutal-border rounded-2xl p-4 sm:p-5 shadow-brutal flex items-center justify-between hover:-translate-y-0.5 transition-transform">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-900">Sasaran Seterusnya</span>
                <div class="text-base font-display font-black text-[#1E1E1E] mt-1 leading-tight">
                    Lencana Mahasiswa Sejati
                </div>
                <span class="inline-block mt-1 text-[11px] font-bold text-neutral-700">
                    Tinggal {{ max(0, $totalQuestsCount - $completedQuestsCount) }} misi lagi untuk ditebus
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-[#F5A25D] brutal-border flex items-center justify-center text-2xl shadow-brutal-sm text-white shrink-0">
                🎖️
            </div>
        </div>
    </section>
    <!-- END: 3 Bento Overview Stats -->

    <!-- BEGIN: Main Dashboard Grid (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- LEFT COLUMN (Span 7 or 8): Active Quest & Mission Tracker -->
        <div class="lg:col-span-7 xl:col-span-8 flex flex-col gap-6">

            <!-- Featured Active Quest Banner -->
            @if ($activeQuest)
                <div class="bg-[#FFF9EB] brutal-border-3 rounded-2xl p-5 sm:p-6 shadow-brutal-lg relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 rounded-xl bg-neo-yellow brutal-border text-xs font-black uppercase tracking-wider shadow-brutal-sm flex items-center gap-1.5">
                            <span class="animate-pulse">⚡</span> Misi Aktif Sekarang: Modul {{ $activeQuest->step_number }}
                        </span>
                        <span class="text-xs font-black text-amber-900 bg-amber-100 border border-black/20 px-2.5 py-0.5 rounded-lg">
                            +{{ $activeQuest->xp_reward }} XP
                        </span>
                    </div>

                    <h3 class="text-xl sm:text-2xl font-display font-black text-neutral-900 mb-2 leading-snug">
                        {{ $activeQuest->title }}
                    </h3>
                    <p class="text-xs sm:text-sm font-semibold text-neutral-700 mb-4 leading-relaxed">
                        {{ $activeQuest->description }}
                    </p>

                    <div class="flex items-center gap-4 text-xs font-extrabold text-neutral-600 mb-5 flex-wrap">
                        <div class="flex items-center gap-1.5">
                            <span>📍</span>
                            <span>{{ $activeQuest->location }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span>⏱️</span>
                            <span>{{ $activeQuest->deadline }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span>🏷️</span>
                            <span>{{ $activeQuest->category }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap">
                        @if ($activeQuest->action_type === 'quiz')
                            <a href="{{ route('quiz') }}" class="px-6 py-3 bg-[#E78B24] hover:bg-[#c9741a] text-white font-display font-black text-xs sm:text-sm uppercase tracking-wider brutal-border rounded-xl shadow-brutal-sm brutal-btn-press flex items-center gap-2">
                                <span>Ambil Kuiz Interaktif Sekarang</span>
                                <span>➔</span>
                            </a>
                        @else
                            <form method="POST" action="{{ route('quests.complete', $activeQuest->id) }}">
                                @csrf
                                <button type="submit" class="px-6 py-3 bg-emerald-400 hover:bg-emerald-300 text-neutral-900 font-display font-black text-xs sm:text-sm uppercase tracking-wider brutal-border rounded-xl shadow-brutal-sm brutal-btn-press flex items-center gap-2">
                                    <span>Sahkan Kehadiran & Selesaikan Misi</span>
                                    <span>✓</span>
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('quests.index') }}" class="px-4 py-3 bg-white hover:bg-neutral-100 font-display font-black text-xs sm:text-sm brutal-border rounded-xl shadow-brutal-sm brutal-btn-press">
                            Lihat Peta Misi Penuh
                        </a>
                    </div>
                </div>
            @endif

            <!-- Mission Progression List -->
            <div class="bg-white brutal-border rounded-2xl p-5 sm:p-6 shadow-brutal">
                <div class="flex items-center justify-between border-b-2 border-black/10 pb-4 mb-4">
                    <div class="flex items-center gap-2">
                        <h4 class="font-display font-black text-lg text-[#1E1E1E]">Senarai Misi Orientasi</h4>
                        <span class="px-2 py-0.5 bg-neutral-900 text-white rounded-md text-[10px] font-black uppercase">7 Modul</span>
                    </div>
                    <a href="{{ route('quests.index') }}" class="text-xs font-black text-[#E78B24] hover:underline flex items-center gap-1">
                        <span>Peta Interaktif</span>
                        <span>➔</span>
                    </a>
                </div>

                <div class="space-y-3">
                    @foreach ($quests as $q)
                        <div class="p-3.5 brutal-border rounded-xl flex items-center justify-between gap-3 transition-all hover:bg-neutral-50 {{ $q->user_status === 'completed' ? 'bg-[#FAF8F5]' : ($q->user_status === 'in_progress' ? 'bg-[#FFF9EB] border-amber-500' : 'bg-neutral-100 opacity-75') }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl brutal-border flex items-center justify-center text-xl shrink-0 {{ $q->user_status === 'completed' ? 'bg-emerald-200' : ($q->user_status === 'in_progress' ? 'bg-neo-yellow' : 'bg-neutral-200') }}">
                                    @if ($q->user_status === 'completed')
                                        ✓
                                    @elseif ($q->user_status === 'in_progress')
                                        {{ $q->icon }}
                                    @else
                                        🔒
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-mono font-bold text-neutral-500">{{ $q->code }}</span>
                                        <h5 class="font-display font-black text-xs sm:text-sm text-neutral-900 truncate">
                                            {{ $q->title }}
                                        </h5>
                                    </div>
                                    <span class="text-[11px] font-semibold text-neutral-600 block truncate">
                                        {{ $q->location }} • {{ $q->category }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-0.5 rounded-md bg-neo-yellow/70 border border-black/20 text-[10px] font-black">
                                    +{{ $q->xp_reward }} XP
                                </span>

                                @if ($q->user_status === 'completed')
                                    <span class="px-2.5 py-1 bg-emerald-300 text-emerald-950 brutal-border rounded-lg text-[10px] font-black uppercase">
                                        Selesai
                                    </span>
                                @elseif ($q->user_status === 'in_progress')
                                    @if ($q->action_type === 'quiz')
                                        <a href="{{ route('quiz') }}" class="px-3 py-1 bg-[#E78B24] text-white brutal-border rounded-lg text-[10px] font-black uppercase shadow-brutal-sm brutal-btn-press">
                                            Kuiz ➔
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('quests.complete', $q->id) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-emerald-400 text-neutral-900 brutal-border rounded-lg text-[10px] font-black uppercase shadow-brutal-sm brutal-btn-press">
                                                Sahkan ✓
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="px-2.5 py-1 bg-neutral-200 text-neutral-600 border border-neutral-400 rounded-lg text-[10px] font-bold uppercase">
                                        Terkunci
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Orientation Schedule / Upcoming Events -->
            <div class="bg-white brutal-border rounded-2xl p-5 sm:p-6 shadow-brutal">
                <div class="flex items-center justify-between border-b-2 border-black/10 pb-4 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📅</span>
                        <h4 class="font-display font-black text-lg text-[#1E1E1E]">Jadual & Aktiviti Orientasi</h4>
                    </div>
                    <span class="text-xs font-black bg-amber-200 brutal-border px-2.5 py-0.5 rounded-lg">Hari 4 daripada 7</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($events as $event)
                        <div class="p-3.5 brutal-border rounded-xl bg-[#FAF8F5] flex flex-col justify-between hover:bg-white transition-all">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-mono font-bold text-neutral-700 bg-neutral-200/60 px-2 py-0.5 rounded border border-black/10">
                                        ⏱️ {{ $event['time'] }}
                                    </span>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-md border border-black/20 {{ $event['badge_color'] }}">
                                        {{ $event['badge'] }}
                                    </span>
                                </div>
                                <h5 class="font-display font-black text-xs sm:text-sm text-neutral-900 leading-snug">
                                    {{ $event['title'] }}
                                </h5>
                            </div>
                            <span class="text-[11px] font-semibold text-neutral-500 mt-2 block">
                                📍 {{ $event['location'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
        <!-- END: Left Column -->

        <!-- RIGHT COLUMN (Span 5 or 4): Donut Chart, Campus Leaderboard, Quick Shortcuts -->
        <div class="lg:col-span-5 xl:col-span-4 flex flex-col gap-6">

            <!-- Donut Chart & Orientation Progress Card -->
            <div class="bg-white brutal-border rounded-2xl p-5 sm:p-6 shadow-brutal text-center flex flex-col items-center">
                <span class="text-[10px] font-black uppercase tracking-wider text-neutral-500 mb-3">Kemajuan Keseluruhan Maba</span>

                <!-- Donut Chart Ring -->
                <div class="relative w-36 h-36 rounded-full conic-chart brutal-border flex items-center justify-center shadow-brutal mb-4">
                    <div class="w-24 h-24 rounded-full bg-white brutal-border flex flex-col items-center justify-center">
                        <span class="font-display font-black text-2xl text-neutral-900">73%</span>
                        <span class="text-[9px] font-extrabold uppercase text-neutral-500">Tamat</span>
                    </div>
                </div>

                <h4 class="font-display font-black text-base text-neutral-900 leading-tight mb-1">
                    Minggu Orientasi Sedang Berjalan
                </h4>
                <p class="text-xs font-semibold text-neutral-600 mb-4 px-2">
                    Tahniah! Anda telah melengkapkan 3 daripada 7 modul utama. Selesaikan Kuiz SKS hari ini untuk membuka Modul 5.
                </p>

                <a href="{{ route('achievements') }}" class="w-full py-2.5 bg-neo-yellow hover:bg-amber-300 font-display font-black text-xs uppercase tracking-wider brutal-border rounded-xl shadow-brutal-sm brutal-btn-press text-center block">
                    Semak Sijil & Lencana ➔
                </a>
            </div>

            <!-- Campus Leaderboard Card -->
            <div class="bg-white brutal-border rounded-2xl p-5 sm:p-6 shadow-brutal">
                <div class="flex items-center justify-between border-b-2 border-black/10 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🏆</span>
                        <h4 class="font-display font-black text-base text-neutral-900">Papan Juara MABA</h4>
                    </div>
                    <span class="text-[10px] font-black bg-rose-100 text-rose-800 border border-black/20 px-2 py-0.5 rounded-md uppercase">
                        Kampus Utama
                    </span>
                </div>

                <div class="space-y-2.5">
                    @foreach ($leaderboard as $index => $leader)
                        <div class="flex items-center justify-between p-2.5 rounded-xl brutal-border {{ $leader->id === $user->id ? 'bg-neo-yellow/50 border-amber-600 shadow-brutal-sm' : 'bg-[#FAF8F5]' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-6 h-6 rounded-lg brutal-border flex items-center justify-center font-display font-black text-xs shrink-0 {{ $index === 0 ? 'bg-neo-yellow' : ($index === 1 ? 'bg-neutral-200' : ($index === 2 ? 'bg-[#FFE6AF]' : 'bg-white')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <div class="font-display font-black text-xs text-neutral-900 truncate">
                                        {{ $leader->name }}
                                        @if ($leader->id === $user->id)
                                            <span class="text-[10px] text-amber-900">(Anda)</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-bold text-neutral-500 block truncate">
                                        {{ $leader->group_name }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-black text-neutral-900 shrink-0">
                                {{ number_format($leader->xp) }} XP
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Quick Access Links & Help Desk -->
            <div class="bg-[#FFE6AF] brutal-border rounded-2xl p-5 shadow-brutal space-y-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💡</span>
                    <div>
                        <h4 class="font-display font-black text-sm text-neutral-900">Pusat Sokongan Segera</h4>
                        <p class="text-[11px] font-bold text-neutral-700">Perlukan bantuan pensyarah atau mentor?</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="{{ route('profile', ['tab' => 'helpdesk']) }}" class="py-2 px-3 bg-white hover:bg-neutral-100 brutal-border rounded-xl font-display font-black text-xs shadow-brutal-sm text-center brutal-btn-press block">
                        FAQ Orientasi
                    </a>
                    <a href="{{ route('certificate') }}" class="py-2 px-3 bg-white hover:bg-neutral-100 brutal-border rounded-xl font-display font-black text-xs shadow-brutal-sm text-center brutal-btn-press block">
                        Pratonton Sijil
                    </a>
                </div>
            </div>

        </div>
        <!-- END: Right Column -->

    </div>
    <!-- END: Main Dashboard Grid -->

</div>
@endsection
