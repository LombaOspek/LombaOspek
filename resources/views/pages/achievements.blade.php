@extends('layouts.app')

@section('title', 'Pencapaian & Sijil Orientasi')

@section('content')
<div class="p-5 md:p-8 flex flex-col gap-6">

    <!-- BEGIN: Header & Quick Stats -->
    <header class="flex flex-col gap-5 border-b-2 border-black/10 pb-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('dashboard') }}" class="text-xs font-black text-[#E78B24] hover:underline flex items-center gap-1">
                        <span>← Kembali ke Papan Pemuka</span>
                    </a>
                </div>
                <h2 class="text-2xl sm:text-3xl font-display font-black text-[#1E1E1E] tracking-tight">
                    Pencapaian, Lencana & Sijil 🏆
                </h2>
                <p class="text-xs sm:text-sm font-semibold text-neutral-600 mt-0.5">
                    Koleksi anugerah kehormat dan sijil pengiktirafan sepanjang minggu orientasi.
                </p>
            </div>

            <!-- Streak Pill -->
            <div class="bg-[#FFE6AF] brutal-border rounded-2xl px-4 py-2 shadow-brutal-sm flex items-center gap-2.5 self-start sm:self-auto">
                <span class="text-2xl animate-bounce">🔥</span>
                <div>
                    <span class="block text-[9px] font-black uppercase text-neutral-500 tracking-wider leading-none">Rentetan Aktif</span>
                    <span class="text-xs font-black text-neutral-900 leading-tight">{{ $user->streak_days }} Hari Berturut-turut!</span>
                </div>
            </div>
        </div>

        <!-- 3 Mini Bento Quick Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" data-purpose="stats-summary">
            <!-- Mini Card 1: Unlocked Achievements -->
            <div class="bg-[#FFF9EB] brutal-border rounded-2xl p-4 shadow-brutal flex items-center gap-4 hover:-translate-y-0.5 transition-transform">
                <div class="w-12 h-12 rounded-xl bg-neo-yellow brutal-border flex items-center justify-center shrink-0 shadow-brutal-sm text-2xl">
                    🏆
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-[10px] font-black text-neutral-500 uppercase tracking-wider">Status Pencapaian</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="font-display font-black text-2xl text-neutral-900">{{ $unlockedCount }}</span>
                        <span class="text-xs font-bold text-neutral-600">/ {{ $totalAchievements }} Dibuka</span>
                    </div>
                    <div class="w-full bg-neutral-200 brutal-border rounded-full h-2 mt-2 overflow-hidden">
                        <div class="bg-[#E78B24] h-full rounded-full" style="width: {{ $totalAchievements > 0 ? ($unlockedCount / $totalAchievements) * 100 : 0 }}%;"></div>
                    </div>
                </div>
            </div>

            <!-- Mini Card 2: XP Rewards -->
            <div class="bg-[#F5F4F0] brutal-border rounded-2xl p-4 shadow-brutal flex items-center gap-4 hover:-translate-y-0.5 transition-transform">
                <div class="w-12 h-12 rounded-xl bg-[#A5C9CA] brutal-border flex items-center justify-center shrink-0 shadow-brutal-sm text-2xl">
                    ⚡
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-[10px] font-black text-neutral-500 uppercase tracking-wider">Mata Ganjaran</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="font-display font-black text-2xl text-neutral-900">{{ number_format($user->xp) }}</span>
                        <span class="text-xs font-bold text-neutral-600">XP Diperoleh</span>
                    </div>
                    <span class="text-[10px] font-extrabold text-emerald-700 block mt-1">+280 XP lagi ke Tahap Seterusnya</span>
                </div>
            </div>

            <!-- Mini Card 3: Badges Collection -->
            <div class="bg-[#FFE6AF] brutal-border rounded-2xl p-4 shadow-brutal flex items-center gap-4 hover:-translate-y-0.5 transition-transform">
                <div class="w-12 h-12 rounded-xl bg-[#FA7970] brutal-border flex items-center justify-center shrink-0 shadow-brutal-sm text-2xl text-white">
                    🎖️
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-[10px] font-black text-neutral-600 uppercase tracking-wider">Koleksi Lencana</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="font-display font-black text-2xl text-neutral-900">{{ $unlockedBadgesCount }}</span>
                        <span class="text-xs font-bold text-neutral-600">/ 6 Dikumpul</span>
                    </div>
                    <span class="text-[10px] font-extrabold text-neutral-800 block mt-1">Status: Calon Graduan Cemerlang</span>
                </div>
            </div>
        </div>
    </header>
    <!-- END: Header & Quick Stats -->

    <!-- BEGIN: Official Certificate Teaser Banner -->
    <div class="bg-gradient-to-r from-amber-100 via-amber-50 to-orange-100 brutal-border-3 rounded-3xl p-6 sm:p-8 shadow-brutal-lg flex flex-col lg:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white brutal-border flex items-center justify-center text-3xl sm:text-4xl shadow-brutal shrink-0 rotate-[-2deg]">
                📜
            </div>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 bg-black text-white text-[10px] font-black uppercase rounded-md">Dokumen Rasmi Berkanun</span>
                    <span class="text-xs font-bold text-amber-900">• Sesi 2025/2026</span>
                </div>
                <h3 class="font-display font-black text-xl sm:text-2xl text-neutral-900 leading-tight">
                    Sijil Tamat Orientasi Siswa Baharu
                </h3>
                <p class="text-xs sm:text-sm font-semibold text-neutral-700 mt-1 max-w-xl">
                    Diperakui sah oleh Pejabat Hal Ehwal Pelajar dan Naib Canselor universiti dengan nombor siri berkanun dan kod verifikasi digital.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full lg:w-auto shrink-0">
            <a href="{{ route('certificate') }}" class="w-full sm:w-auto px-6 py-3.5 bg-[#E78B24] hover:bg-[#c9741a] text-white font-display font-black text-xs sm:text-sm uppercase tracking-wider brutal-border rounded-xl shadow-brutal-sm brutal-btn-press text-center flex items-center justify-center gap-2">
                <span>Buka & Cetak Sijil</span>
                <span>➔</span>
            </a>
        </div>
    </div>
    <!-- END: Official Certificate Banner -->

    <!-- BEGIN: Achievements Challenges Grid -->
    <section class="space-y-4" data-purpose="achievements-section">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <h3 class="font-display font-black text-lg sm:text-xl text-[#1E1E1E]">Senarai Cabaran & Pencapaian</h3>
                <span class="px-2 py-0.5 bg-neutral-900 text-white rounded-md text-[10px] font-black tracking-wider uppercase">{{ $totalAchievements }} Rekod</span>
            </div>
            <div class="flex items-center gap-2 text-xs font-bold text-neutral-600">
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 border border-black inline-block"></span>
                    <span>{{ $unlockedCount }} Selesai</span>
                </span>
                <span>•</span>
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 border border-black inline-block"></span>
                    <span>{{ $inProgressCount }} Dalam Proses</span>
                </span>
                <span>•</span>
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-neutral-400 border border-black inline-block"></span>
                    <span>{{ $lockedCount }} Terkunci</span>
                </span>
            </div>
        </div>

        <!-- 2-Column Responsive Bento Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($achievements as $ach)
                <article class="bg-[#FFF9EB] brutal-border rounded-2xl p-5 shadow-brutal flex flex-col justify-between hover:-translate-y-0.5 transition-all">
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-neo-yellow brutal-border flex items-center justify-center text-2xl shadow-brutal-sm shrink-0">
                                    {{ $ach->icon }}
                                </div>
                                <div>
                                    <h4 class="font-display font-black text-base text-neutral-900 leading-tight">
                                        {{ $ach->title }}
                                    </h4>
                                    <p class="text-xs font-semibold text-neutral-600 mt-0.5">
                                        {{ $ach->description }}
                                    </p>
                                </div>
                            </div>

                            @if ($ach->is_unlocked)
                                <span class="px-2.5 py-1 bg-emerald-400 text-neutral-900 brutal-border rounded-lg text-[10px] font-black uppercase tracking-wider shadow-brutal-sm shrink-0 flex items-center gap-1">
                                    ✓ SELESAI
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-amber-200 text-neutral-900 brutal-border rounded-lg text-[10px] font-black uppercase tracking-wider shadow-brutal-sm shrink-0">
                                    {{ $ach->current_steps }} / {{ $ach->total_steps }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 space-y-1.5">
                            <div class="flex justify-between text-[11px] font-black text-neutral-700">
                                <span>Kemajuan Cabaran</span>
                                <span>{{ $ach->current_steps }} / {{ $ach->total_steps }} ({{ $ach->progress_percentage }}%)</span>
                            </div>
                            <div class="w-full bg-white brutal-border rounded-full h-3 p-[1px] overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $ach->is_unlocked ? 'bg-emerald-400' : 'bg-[#E78B24]' }}" style="width: {{ $ach->progress_percentage }}%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t-2 border-black/10 flex items-center justify-between text-xs">
                        <span class="text-[11px] font-bold text-neutral-500">Kategori: {{ $ach->category }}</span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-neo-yellow brutal-border rounded-md text-[11px] font-black text-neutral-900 shadow-brutal-sm">
                            <span>+{{ $ach->xp_reward }} XP</span>
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    <!-- END: Achievements Challenges Grid -->

    <!-- BEGIN: Badges Showcase (Koleksi Lencana) -->
    <section class="space-y-4 pt-4 border-t-2 border-black/10">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">🎖️</span>
                <h3 class="font-display font-black text-lg sm:text-xl text-[#1E1E1E]">Koleksi Lencana Kejuaraan</h3>
            </div>
            <span class="text-xs font-bold text-neutral-600">Dikumpul {{ $unlockedBadgesCount }} daripada 6</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach ($badges as $badge)
                <div class="p-4 rounded-2xl brutal-border text-center flex flex-col items-center justify-between shadow-brutal hover:-translate-y-1 transition-all {{ $badge['is_unlocked'] ? 'bg-white' : 'bg-neutral-100 opacity-60' }}">
                    <div class="w-14 h-14 rounded-2xl brutal-border flex items-center justify-center text-3xl shadow-brutal-sm mb-3 {{ $badge['color'] }}">
                        {{ $badge['icon'] }}
                    </div>
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider block text-neutral-500 mb-0.5">
                            {{ $badge['rarity'] }}
                        </span>
                        <h5 class="font-display font-black text-xs text-neutral-900 leading-tight">
                            {{ $badge['name'] }}
                        </h5>
                    </div>
                    <span class="mt-3 px-2 py-0.5 rounded-md text-[9px] font-black uppercase {{ $badge['is_unlocked'] ? 'bg-emerald-200 text-emerald-950 border border-black/20' : 'bg-neutral-200 text-neutral-500' }}">
                        {{ $badge['is_unlocked'] ? 'Dibuka ✓' : 'Terkunci 🔒' }}
                    </span>
                </div>
            @endforeach
        </div>
    </section>
    <!-- END: Badges Showcase -->

</div>
@endsection
