@extends('layouts.app')

@section('title', 'Peta Misi Orientasi')

@section('content')
<div class="p-5 md:p-8 flex flex-col gap-6">

    <!-- BEGIN: Header Bar -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-2 border-black/10 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('dashboard') }}" class="text-xs font-black text-[#E78B24] hover:underline flex items-center gap-1">
                    <span>← Kembali ke Papan Pemuka</span>
                </a>
            </div>
            <h2 class="text-2xl sm:text-3xl font-display font-black text-[#1E1E1E] tracking-tight">
                Peta Kembara Orientasi Siswa 🗺️
            </h2>
            <p class="text-xs sm:text-sm font-semibold text-neutral-600 mt-0.5">
                Ikuti laluan modul interaktif langkah demi langkah untuk menamatkan orientasi rasmi.
            </p>
        </div>

        <!-- Filter Buttons -->
        <div class="flex items-center gap-1.5 p-1 bg-white brutal-border rounded-xl shadow-brutal-sm overflow-x-auto">
            <button type="button" onclick="filterQuests('all')" class="quest-filter-btn px-3 py-1.5 bg-neo-yellow brutal-border rounded-lg font-display font-black text-xs text-neutral-900 shadow-brutal-sm" data-filter="all">
                Semua Misi (7)
            </button>
            <button type="button" onclick="filterQuests('completed')" class="quest-filter-btn px-3 py-1.5 rounded-lg font-display font-bold text-xs text-neutral-600 hover:text-black hover:bg-neutral-100" data-filter="completed">
                Selesai ({{ $completedQuestsCount }})
            </button>
            <button type="button" onclick="filterQuests('in_progress')" class="quest-filter-btn px-3 py-1.5 rounded-lg font-display font-bold text-xs text-neutral-600 hover:text-black hover:bg-neutral-100" data-filter="in_progress">
                Aktif (1)
            </button>
            <button type="button" onclick="filterQuests('locked')" class="quest-filter-btn px-3 py-1.5 rounded-lg font-display font-bold text-xs text-neutral-600 hover:text-black hover:bg-neutral-100" data-filter="locked">
                Terkunci (3)
            </button>
        </div>
    </header>
    <!-- END: Header Bar -->

    <!-- BEGIN: 3 Bento Stats Banner -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-[#FAF8F5] brutal-border rounded-2xl p-4 shadow-brutal flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-neutral-400">Kemajuan Modul</span>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <span class="text-2xl font-display font-black text-[#1E1E1E]">{{ $completedQuestsCount }} / {{ $totalQuestsCount }}</span>
                    <span class="text-xs font-bold text-emerald-600">{{ $completionPercentage }}% Tamat</span>
                </div>
                <div class="w-36 h-2 bg-neutral-200 rounded-full mt-2.5 overflow-hidden border border-[#1E1E1E]">
                    <div class="h-full bg-emerald-400 transition-all duration-500" style="width: {{ $completionPercentage }}%;"></div>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 brutal-border flex items-center justify-center text-xl shadow-brutal-sm shrink-0">
                🗺️
            </div>
        </div>

        <div class="bg-neo-yellow brutal-border rounded-2xl p-4 shadow-brutal flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-950">Ganjaran Terkumpul</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-2xl font-display font-black text-[#1E1E1E]">{{ number_format($user->xp) }}</span>
                    <span class="text-xs font-black text-amber-900">XP</span>
                </div>
                <span class="inline-block mt-1 text-[11px] font-bold text-amber-950/80">Kedudukan: Ke-10 Seluruh Kampus</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-white brutal-border flex items-center justify-center text-xl shadow-brutal-sm shrink-0">
                ⚡
            </div>
        </div>

        <div class="bg-neo-peach brutal-border rounded-2xl p-4 shadow-brutal flex items-center justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-900">Sasaran Utama</span>
                <div class="text-sm font-display font-black text-[#1E1E1E] mt-0.5 leading-tight">Lencana Mahasiswa Sejati</div>
                <span class="inline-block mt-1 text-[11px] font-bold text-neutral-700">Tinggal {{ max(0, $totalQuestsCount - $completedQuestsCount) }} misi lagi untuk dibuka</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-[#F5A25D] brutal-border flex items-center justify-center text-xl shadow-brutal-sm text-white shrink-0">
                🎖️
            </div>
        </div>
    </section>
    <!-- END: Bento Stats Banner -->

    <!-- BEGIN: Main Interactive Journey Content -->
    <div class="mt-4 flex flex-col xl:flex-row gap-8 items-start relative">

        <!-- Winding Path Column (Duolingo / Candy Crush Style) -->
        <div class="w-full xl:w-2/3 relative py-6 bg-white/40 brutal-border rounded-3xl p-4 sm:p-8 shadow-brutal overflow-hidden" data-purpose="quest-vertical-path">

            <!-- Continuous Snake Trail SVG -->
            <div class="absolute inset-0 pointer-events-none flex justify-center z-0">
                <svg class="w-full h-full max-w-[500px]" fill="none" preserveAspectRatio="none" viewBox="0 0 400 1320">
                    <path d="M 200 40 
                             C 100 120, 90 190, 200 240 
                             C 310 290, 310 380, 200 440 
                             C 90 500, 90 590, 200 640 
                             C 310 700, 310 790, 200 840 
                             C 90 900, 90 990, 200 1040 
                             C 310 1100, 300 1200, 200 1260" 
                          stroke="#1E1E1E" stroke-dasharray="14 10" stroke-linecap="round" stroke-width="8"></path>
                </svg>
            </div>

            <!-- Path Items Container -->
            <div class="relative z-10 flex flex-col gap-12 sm:gap-16">

                @foreach ($quests as $index => $quest)
                    @php
                        $isLeft = ($index % 2 === 0);
                        $statusClass = $quest->user_status;
                    @endphp

                    <article class="quest-item flex flex-col md:flex-row items-center justify-center gap-4 md:gap-8" 
                             data-status="{{ $statusClass }}"
                             data-quest-id="{{ $quest->id }}">

                        <!-- Card Left (if left oriented) -->
                        <div class="w-full md:w-[260px] order-2 md:order-1 {{ $isLeft ? 'block' : 'hidden md:block md:invisible' }} text-right">
                            @if ($isLeft)
                                <div onclick="openQuestModal({{ $quest->id }})" class="bg-white brutal-border rounded-2xl p-4 shadow-brutal hover:-translate-y-1 transition-transform text-left cursor-pointer brutal-btn-press {{ $quest->user_status === 'in_progress' ? 'ring-4 ring-amber-400' : '' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-mono font-black px-2 py-0.5 rounded bg-neutral-100 border border-black/20">
                                            {{ $quest->code }}
                                        </span>
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded-md {{ $quest->user_status === 'completed' ? 'bg-emerald-200 text-emerald-950' : ($quest->user_status === 'in_progress' ? 'bg-neo-yellow text-amber-950' : 'bg-neutral-200 text-neutral-600') }}">
                                            {{ $quest->user_status === 'completed' ? 'Selesai ✓' : ($quest->user_status === 'in_progress' ? 'Aktif ⚡' : 'Terkunci 🔒') }}
                                        </span>
                                    </div>
                                    <h4 class="font-display font-black text-sm text-neutral-900 leading-tight mb-1">
                                        {{ $quest->title }}
                                    </h4>
                                    <p class="text-[11px] font-semibold text-neutral-600 line-clamp-2 mb-3">
                                        {{ $quest->description }}
                                    </p>
                                    <div class="flex items-center justify-between pt-2 border-t border-dashed border-neutral-300 text-[10px] font-extrabold text-neutral-500">
                                        <span>📍 {{ $quest->location }}</span>
                                        <span class="text-amber-800 font-black">+{{ $quest->xp_reward }} XP</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Center Winding Node -->
                        <div class="order-1 md:order-2 shrink-0 relative flex items-center justify-center">
                            @if ($quest->user_status === 'in_progress')
                                <div class="absolute w-20 h-20 rounded-full bg-amber-400/40 animate-ping"></div>
                            @endif

                            <button type="button" 
                                    onclick="openQuestModal({{ $quest->id }})" 
                                    class="w-14 h-14 sm:w-16 sm:h-16 rounded-full brutal-border flex items-center justify-center text-2xl font-black shadow-brutal-lg brutal-btn-press cursor-pointer relative z-10 transition-transform {{ $quest->user_status === 'completed' ? 'bg-emerald-400 text-white' : ($quest->user_status === 'in_progress' ? 'bg-neo-yellow ring-4 ring-black scale-110' : 'bg-neutral-200 text-neutral-400') }}">
                                @if ($quest->user_status === 'completed')
                                    ✓
                                @elseif ($quest->user_status === 'in_progress')
                                    {{ $quest->icon }}
                                @else
                                    🔒
                                @endif
                            </button>
                        </div>

                        <!-- Card Right (if right oriented) -->
                        <div class="w-full md:w-[260px] order-3 {{ !$isLeft ? 'block' : 'hidden md:block md:invisible' }}">
                            @if (!$isLeft)
                                <div onclick="openQuestModal({{ $quest->id }})" class="bg-white brutal-border rounded-2xl p-4 shadow-brutal hover:-translate-y-1 transition-transform text-left cursor-pointer brutal-btn-press {{ $quest->user_status === 'in_progress' ? 'ring-4 ring-amber-400' : '' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-mono font-black px-2 py-0.5 rounded bg-neutral-100 border border-black/20">
                                            {{ $quest->code }}
                                        </span>
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded-md {{ $quest->user_status === 'completed' ? 'bg-emerald-200 text-emerald-950' : ($quest->user_status === 'in_progress' ? 'bg-neo-yellow text-amber-950' : 'bg-neutral-200 text-neutral-600') }}">
                                            {{ $quest->user_status === 'completed' ? 'Selesai ✓' : ($quest->user_status === 'in_progress' ? 'Aktif ⚡' : 'Terkunci 🔒') }}
                                        </span>
                                    </div>
                                    <h4 class="font-display font-black text-sm text-neutral-900 leading-tight mb-1">
                                        {{ $quest->title }}
                                    </h4>
                                    <p class="text-[11px] font-semibold text-neutral-600 line-clamp-2 mb-3">
                                        {{ $quest->description }}
                                    </p>
                                    <div class="flex items-center justify-between pt-2 border-t border-dashed border-neutral-300 text-[10px] font-extrabold text-neutral-500">
                                        <span>📍 {{ $quest->location }}</span>
                                        <span class="text-amber-800 font-black">+{{ $quest->xp_reward }} XP</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </article>
                @endforeach

            </div>
        </div>

        <!-- Right Side Widgets & Campus Map Guide -->
        <div class="w-full xl:w-1/3 flex flex-col gap-6">

            <!-- Active Quest Fast Track Box -->
            @if ($activeQuest)
                <div class="bg-neo-yellow brutal-border rounded-2xl p-5 sm:p-6 shadow-brutal">
                    <span class="px-2.5 py-0.5 bg-black text-white text-[10px] font-black uppercase rounded-md mb-2 inline-block">
                        Laluan Semasa
                    </span>
                    <h3 class="font-display font-black text-xl text-neutral-900 leading-tight mb-2">
                        {{ $activeQuest->title }}
                    </h3>
                    <p class="text-xs font-bold text-neutral-800 mb-4">
                        {{ $activeQuest->description }}
                    </p>

                    @if ($activeQuest->action_type === 'quiz')
                        <a href="{{ route('quiz') }}" class="w-full py-3 bg-white hover:bg-neutral-100 text-neutral-900 brutal-border rounded-xl font-display font-black text-xs uppercase tracking-wider shadow-brutal-sm brutal-btn-press flex items-center justify-center gap-2">
                            <span>Mula Kuiz Modul 4</span>
                            <span>⚡</span>
                        </a>
                    @else
                        <form method="POST" action="{{ route('quests.complete', $activeQuest->id) }}">
                            @csrf
                            <button type="submit" class="w-full py-3 bg-white hover:bg-neutral-100 text-neutral-900 brutal-border rounded-xl font-display font-black text-xs uppercase tracking-wider shadow-brutal-sm brutal-btn-press flex items-center justify-center gap-2">
                                <span>Selesaikan Misi Ini (+{{ $activeQuest->xp_reward }} XP)</span>
                                <span>✓</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            <!-- Campus Landmarks Guide -->
            <div class="bg-white brutal-border rounded-2xl p-5 sm:p-6 shadow-brutal">
                <div class="flex items-center gap-2 border-b-2 border-black/10 pb-3 mb-4">
                    <span class="text-xl">🏛️</span>
                    <h4 class="font-display font-black text-base text-neutral-900">Panduan Mercu Tanda Kampus</h4>
                </div>

                <div class="space-y-3">
                    <div class="p-3 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="font-display font-black text-xs text-neutral-900">Dewan Canselor Utama (DCU)</div>
                        <p class="text-[11px] font-semibold text-neutral-600 mt-0.5">Lokasi Majlis Amanat Dekan & Ikrar Integriti.</p>
                    </div>
                    <div class="p-3 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="font-display font-black text-xs text-neutral-900">Perpustakaan Tun Sri Lanang</div>
                        <p class="text-[11px] font-semibold text-neutral-600 mt-0.5">Pusat pinjaman e-buku dan makmal rujukan digital.</p>
                    </div>
                    <div class="p-3 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="font-display font-black text-xs text-neutral-900">Makmal Komputer Al-Khawarizmi</div>
                        <p class="text-[11px] font-semibold text-neutral-600 mt-0.5">Pusat ujian kuiz dan pendaftaran kursus fakulti.</p>
                    </div>
                    <div class="p-3 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="font-display font-black text-xs text-neutral-900">Bilik Kaunseling & Mentor 4B</div>
                        <p class="text-[11px] font-semibold text-neutral-600 mt-0.5">Bilik perjumpaan senior pembimbing kumpulan Harimau.</p>
                    </div>
                </div>
            </div>

            <!-- Grand Finale Reward Banner -->
            <div class="bg-[#FFE6AF] brutal-border rounded-2xl p-5 shadow-brutal flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-[#E78B24] brutal-border flex items-center justify-center text-3xl shadow-brutal-sm text-white shrink-0">
                    🏆
                </div>
                <div>
                    <h5 class="font-display font-black text-sm text-neutral-900">Ganjaran Tamat 7 Modul</h5>
                    <p class="text-xs font-semibold text-neutral-700 mt-0.5">
                        Tebus Sijil Orientasi Digital Berkanun + 500 XP bonus kejuaraan!
                    </p>
                </div>
            </div>

        </div>

    </div>
    <!-- END: Main Interactive Journey -->

</div>

<!-- Quest Detail Interactive Modal -->
<div id="quest-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white brutal-border-3 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-brutal-xl relative">
        <div class="flex items-center justify-between border-b-2 border-black pb-3 mb-4">
            <div class="flex items-center gap-2">
                <span id="modal-quest-icon" class="text-3xl">🗺️</span>
                <div>
                    <span id="modal-quest-code" class="text-[10px] font-mono font-bold bg-neutral-100 px-2 py-0.5 rounded border border-black/20">MOD-01</span>
                    <h3 id="modal-quest-title" class="font-display font-black text-lg sm:text-xl leading-tight">Tajuk Misi</h3>
                </div>
            </div>
            <button type="button" onclick="closeQuestModal()" class="text-2xl font-black text-neutral-400 hover:text-black">✕</button>
        </div>

        <div class="space-y-4 text-xs sm:text-sm font-semibold text-neutral-700">
            <div>
                <span class="block text-[10px] font-black uppercase text-neutral-400 tracking-wider">Penerangan Tugasan</span>
                <p id="modal-quest-desc" class="mt-1 leading-relaxed text-neutral-800">Butiran misi...</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="p-3 bg-[#FAF8F5] brutal-border rounded-xl">
                    <span class="block text-[9px] font-black uppercase text-neutral-400">Lokasi Fizikal</span>
                    <span id="modal-quest-location" class="font-bold text-neutral-900 block mt-0.5">Dewan Canselor</span>
                </div>
                <div class="p-3 bg-[#FAF8F5] brutal-border rounded-xl">
                    <span class="block text-[9px] font-black uppercase text-neutral-400">Ganjaran XP</span>
                    <span id="modal-quest-xp" class="font-bold text-amber-800 block mt-0.5">+100 XP</span>
                </div>
            </div>

            <div id="modal-quest-action-container" class="pt-4 border-t border-dashed border-neutral-300">
                <!-- Action Form / Buttons dynamically rendered -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const questsData = @json($quests);

    function filterQuests(status) {
        document.querySelectorAll('.quest-filter-btn').forEach(btn => {
            if (btn.getAttribute('data-filter') === status) {
                btn.className = "quest-filter-btn px-3 py-1.5 bg-neo-yellow brutal-border rounded-lg font-display font-black text-xs text-neutral-900 shadow-brutal-sm";
            } else {
                btn.className = "quest-filter-btn px-3 py-1.5 rounded-lg font-display font-bold text-xs text-neutral-600 hover:text-black hover:bg-neutral-100";
            }
        });

        document.querySelectorAll('.quest-item').forEach(item => {
            if (status === 'all' || item.getAttribute('data-status') === status) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function openQuestModal(questId) {
        const quest = questsData.find(q => q.id === questId);
        if (!quest) return;

        document.getElementById('modal-quest-icon').textContent = quest.icon;
        document.getElementById('modal-quest-code').textContent = quest.code;
        document.getElementById('modal-quest-title').textContent = quest.title;
        document.getElementById('modal-quest-desc').textContent = quest.description;
        document.getElementById('modal-quest-location').textContent = quest.location;
        document.getElementById('modal-quest-xp').textContent = '+' + quest.xp_reward + ' XP';

        const actionContainer = document.getElementById('modal-quest-action-container');
        actionContainer.innerHTML = '';

        if (quest.user_status === 'completed') {
            actionContainer.innerHTML = `
                <div class="flex items-center justify-center gap-2 p-3 bg-emerald-100 text-emerald-900 brutal-border rounded-xl font-display font-black text-xs uppercase">
                    <span>✓</span> Modul Ini Telah Berjaya Diselesaikan
                </div>
            `;
        } else if (quest.user_status === 'in_progress') {
            if (quest.action_type === 'quiz') {
                actionContainer.innerHTML = `
                    <a href="{{ route('quiz') }}" class="w-full py-3.5 bg-[#E78B24] hover:bg-[#c9741a] text-white brutal-border rounded-xl font-display font-black text-xs uppercase tracking-wider shadow-brutal-sm brutal-btn-press flex items-center justify-center gap-2">
                        <span>Buka Kuiz Interaktif Sekarang</span>
                        <span>⚡</span>
                    </a>
                `;
            } else {
                actionContainer.innerHTML = `
                    <form method="POST" action="/misi/${quest.id}/complete">
                        @csrf
                        <button type="submit" class="w-full py-3.5 bg-emerald-400 hover:bg-emerald-300 text-neutral-900 brutal-border rounded-xl font-display font-black text-xs uppercase tracking-wider shadow-brutal-sm brutal-btn-press flex items-center justify-center gap-2">
                            <span>Sahkan Kehadiran & Selesaikan Misi (+${quest.xp_reward} XP)</span>
                            <span>✓</span>
                        </button>
                    </form>
                `;
            }
        } else {
            actionContainer.innerHTML = `
                <div class="flex items-center justify-center gap-2 p-3 bg-neutral-100 text-neutral-600 border border-neutral-300 rounded-xl font-display font-bold text-xs uppercase">
                    <span>🔒</span> Modul Terkunci. Selesaikan modul sebelumnya terlebih dahulu.
                </div>
            `;
        }

        document.getElementById('quest-modal').classList.remove('hidden');
        playSound('click');
    }

    function closeQuestModal() {
        document.getElementById('quest-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
