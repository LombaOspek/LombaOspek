<!-- BEGIN: Left Column (Sidebar Navigation) -->
<aside id="sidebar-container" class="hidden xl:flex w-full xl:w-[260px] 2xl:w-[280px] bg-[#EBE9E4] xl:dashed-divider-r p-5 sm:p-6 flex-col justify-between shrink-0 z-30 transition-all" data-purpose="sidebar-navigation">
    <div>
        <!-- Brand / App Logo -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2 mb-6 group">
            <div class="w-11 h-11 rounded-2xl bg-neo-yellow brutal-border flex items-center justify-center font-display font-black text-2xl shadow-brutal-sm rotate-[-3deg] group-hover:rotate-0 transition-transform">
                ⚔️
            </div>
            <div>
                <h1 class="font-display font-black text-2xl tracking-wide leading-none text-[#1E1E1E]">Ospek<span class="text-[#E78B24]">Quest</span></h1>
                <span class="text-[10px] font-black uppercase tracking-wider text-neutral-500">Maba Orientasi 2025</span>
            </div>
        </a>

        <!-- Student Profile Mini Card Header -->
        @php
            $currentUser = auth()->user() ?? \App\Models\User::first();
        @endphp
        <div class="flex flex-col items-center text-center p-3.5 mb-6 bg-white/70 brutal-border rounded-2xl shadow-brutal-sm relative overflow-hidden" data-user-id="{{ $currentUser->nim ?? 'MABA-2025-089' }}">
            <div class="w-16 h-16 rounded-full brutal-border bg-[#7EC8E3] overflow-hidden shadow-brutal-sm flex items-center justify-center relative mb-2">
                <svg class="w-14 h-14 translate-y-1.5 text-slate-800" fill="currentColor" viewBox="0 0 64 64">
                    <circle cx="32" cy="24" r="16" fill="#F8B195"></circle>
                    <path d="M16 54 C16 42, 24 38, 32 38 C40 38, 48 42, 48 54 Z" fill="#2C3E50"></path>
                    <path d="M22 18 C25 8, 39 8, 42 18 Z" fill="#2C3E50"></path>
                </svg>
            </div>

            <h3 class="font-display font-black text-sm text-[#1E1E1E] leading-tight">{{ $currentUser->name ?? 'Karan Mehra' }}</h3>
            <span class="text-[10px] font-mono font-bold text-neutral-600 bg-neutral-200/60 px-2 py-0.5 rounded-md mt-0.5 border border-black/10">
                {{ $currentUser->nim ?? 'MABA-2025-089' }}
            </span>

            <div class="w-full mt-3 pt-2.5 border-t border-dashed border-neutral-300">
                <div class="flex items-center justify-between text-[11px] font-black mb-1">
                    <span class="text-[#E78B24] uppercase tracking-wider">Tahap {{ $currentUser->level ?? 4 }}</span>
                    <span class="text-neutral-700">{{ number_format($currentUser->xp ?? 1320) }} XP</span>
                </div>
                <div class="w-full bg-neutral-200 rounded-full h-2 overflow-hidden border border-black/40">
                    <div class="bg-[#F5A25D] h-full rounded-full transition-all duration-500" style="width: {{ min(100, (($currentUser->xp ?? 1320) % 350) / 3.5) }}%;"></div>
                </div>
                <span class="text-[9px] font-extrabold text-neutral-500 block mt-1">
                    {{ $currentUser->group_name ?? 'Alpha Harimau 🐯' }}
                </span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="space-y-2">
            <!-- Papan Pemuka -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-display font-black text-xs md:text-sm transition-all brutal-btn-press {{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'bg-neo-yellow brutal-border shadow-brutal-sm text-[#1E1E1E]' : 'hover:bg-white/60 text-neutral-700' }}">
                <span class="text-lg">🏠</span>
                <span>Papan Pemuka</span>
            </a>

            <!-- Peta Misi Orientasi -->
            <a href="{{ route('quests.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-display font-black text-xs md:text-sm transition-all brutal-btn-press {{ request()->routeIs('quests.*') ? 'bg-neo-yellow brutal-border shadow-brutal-sm text-[#1E1E1E]' : 'hover:bg-white/60 text-neutral-700' }}">
                <span class="text-lg">🗺️</span>
                <span>Peta Misi Maba</span>
            </a>

            <!-- Kuiz Interaktif -->
            <a href="{{ route('quiz') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-display font-black text-xs md:text-sm transition-all brutal-btn-press {{ request()->routeIs('quiz*') ? 'bg-neo-yellow brutal-border shadow-brutal-sm text-[#1E1E1E]' : 'hover:bg-white/60 text-neutral-700' }}">
                <span class="text-lg">⚡</span>
                <span>Kuiz Interaktif</span>
            </a>

            <!-- Pencapaian & Sijil -->
            <a href="{{ route('achievements') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-display font-black text-xs md:text-sm transition-all brutal-btn-press {{ request()->routeIs('achievements') || request()->routeIs('certificate') ? 'bg-neo-yellow brutal-border shadow-brutal-sm text-[#1E1E1E]' : 'hover:bg-white/60 text-neutral-700' }}">
                <span class="text-lg">🏆</span>
                <span>Pencapaian & Sijil</span>
            </a>

            <!-- Profil Mahasiswa -->
            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-display font-black text-xs md:text-sm transition-all brutal-btn-press {{ request()->routeIs('profile') && request('tab') !== 'helpdesk' ? 'bg-neo-yellow brutal-border shadow-brutal-sm text-[#1E1E1E]' : 'hover:bg-white/60 text-neutral-700' }}">
                <span class="text-lg">👤</span>
                <span>Profil & Tetapan</span>
            </a>

            <!-- Pusat Bantuan -->
            <a href="{{ route('profile', ['tab' => 'helpdesk']) }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-display font-black text-xs md:text-sm transition-all brutal-btn-press {{ request('tab') === 'helpdesk' ? 'bg-neo-yellow brutal-border shadow-brutal-sm text-[#1E1E1E]' : 'hover:bg-white/60 text-neutral-700' }}">
                <span class="text-lg">🆘</span>
                <span>Pusat Bantuan</span>
            </a>
        </nav>
    </div>

    <!-- Bottom Mentorship Snippet & Logout -->
    <div class="mt-6 pt-4 border-t border-dashed border-neutral-400/80 space-y-3">
        <div class="bg-[#A5C9CA] brutal-border rounded-xl p-3 shadow-brutal-sm">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm">🧭</span>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-900">Pembimbing Maba</span>
            </div>
            <p class="text-xs font-black text-slate-900 leading-tight">{{ $currentUser->mentor_name ?? 'Kak Syakirah • Bilik 4B' }}</p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full py-2 px-3 bg-white hover:bg-rose-50 text-rose-700 font-display font-black text-xs brutal-border rounded-xl shadow-brutal-sm brutal-btn-press flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">logout</span>
                <span>Log Keluar</span>
            </button>
        </form>
    </div>
</aside>
<!-- END: Left Column (Sidebar Navigation) -->
