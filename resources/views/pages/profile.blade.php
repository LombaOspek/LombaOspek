@extends('layouts.app')

@section('title', 'Profil, Tetapan & Pusat Bantuan')

@section('content')
<div class="p-5 md:p-8 flex flex-col gap-6">

    <!-- Top Navigation Header & Segmented Tabs -->
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-2 border-black/10 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('dashboard') }}" class="text-xs font-black text-[#E78B24] hover:underline flex items-center gap-1">
                    <span>← Kembali ke Papan Pemuka</span>
                </a>
            </div>
            <h2 class="text-2xl sm:text-3xl font-display font-black text-[#1E1E1E] tracking-tight">
                Pengurusan Akaun Siswa ⚙️
            </h2>
            <p class="text-xs sm:text-sm font-semibold text-neutral-600 mt-0.5">
                Konfigurasi maklumat rasmi, keutamaan sistem, dan saluran bantuan orientasi.
            </p>
        </div>

        <!-- Segmented Tab Navigators -->
        <nav aria-label="Tab Konfigurasi" class="flex items-center p-1.5 bg-white brutal-border rounded-xl shadow-brutal-sm gap-1.5 overflow-x-auto self-start md:self-auto">
            <button type="button" onclick="switchProfileTab('profile')" id="tab-btn-profile" class="tab-btn flex items-center gap-2 px-4 py-2 rounded-lg font-display font-black text-xs md:text-sm brutal-border bg-neo-yellow text-[#1E1E1E] shadow-brutal-sm transition-all brutal-btn-press whitespace-nowrap">
                <span class="text-base">👤</span>
                <span>Profil Mahasiswa</span>
            </button>
            <button type="button" onclick="switchProfileTab('settings')" id="tab-btn-settings" class="tab-btn flex items-center gap-2 px-4 py-2 rounded-lg font-display font-bold text-xs md:text-sm border-2 border-transparent text-neutral-700 hover:text-black hover:bg-neutral-100 transition-all whitespace-nowrap">
                <span class="text-base">⚙️</span>
                <span>Tetapan Akaun</span>
            </button>
            <button type="button" onclick="switchProfileTab('helpdesk')" id="tab-btn-helpdesk" class="tab-btn flex items-center gap-2 px-4 py-2 rounded-lg font-display font-bold text-xs md:text-sm border-2 border-transparent text-neutral-700 hover:text-black hover:bg-neutral-100 transition-all whitespace-nowrap">
                <span class="text-base">🆘</span>
                <span>Pusat Bantuan</span>
            </button>
        </nav>
    </header>

    <!-- ============================================================ -->
    <!-- TAB PANE 1: PROFIL MAHASISWA -->
    <!-- ============================================================ -->
    <div id="pane-profile" class="tab-pane flex flex-col gap-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Student Bio & Badge Card (Left Column) -->
            <div class="lg:col-span-4 flex flex-col gap-4">
                <div class="bg-[#F5F4F0] brutal-border rounded-2xl p-6 shadow-brutal flex flex-col items-center text-center relative overflow-hidden">
                    <!-- Verified Floating Tag -->
                    <div class="absolute top-4 right-4 rotate-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#10b981] text-white brutal-border rounded-full text-[10px] font-black uppercase tracking-wider shadow-brutal-sm">
                            <span>✓</span> MABA AKTIF 2025
                        </span>
                    </div>

                    <!-- Avatar Frame -->
                    <div class="relative mt-3 mb-4 group">
                        <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full brutal-border bg-[#7EC8E3] overflow-hidden shadow-brutal flex items-center justify-center relative">
                            <svg class="w-24 h-24 sm:w-28 sm:h-28 translate-y-3 text-slate-800" fill="currentColor" viewBox="0 0 64 64">
                                <circle cx="32" cy="24" r="16" fill="#F8B195"></circle>
                                <path d="M16 54 C16 42, 24 38, 32 38 C40 38, 48 42, 48 54 Z" fill="#2C3E50"></path>
                                <path d="M22 18 C25 8, 39 8, 42 18 Z" fill="#2C3E50"></path>
                            </svg>
                        </div>
                        <button type="button" onclick="showToast('Foto Avatar', 'Ciri muat naik foto profil akan disegerakkan dengan kad pintar kampus.', '📷')" class="absolute -bottom-2 -right-1 px-3 py-1 bg-neo-yellow hover:bg-amber-300 text-[#1E1E1E] brutal-border rounded-xl font-display font-black text-[11px] shadow-brutal-sm flex items-center gap-1 brutal-btn-press">
                            <span>📷</span> Tukar
                        </button>
                    </div>

                    <h3 class="font-display font-black text-xl text-[#1E1E1E] leading-snug">{{ $user->name }}</h3>
                    <p class="font-mono text-xs font-bold text-neutral-600 mb-3 bg-[#EBE9E4] px-2.5 py-0.5 rounded border border-black/30">
                        {{ $user->nim }}
                    </p>

                    <div class="w-full bg-[#FFE6AF] brutal-border rounded-xl p-3.5 shadow-brutal-sm text-left mb-4">
                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-900 block mb-1">Status Keahlian Ospek</span>
                        <div class="flex items-center justify-between text-xs font-bold text-[#1E1E1E]">
                            <span>Kumpulan Misi:</span>
                            <span class="font-black text-amber-950">{{ $user->group_name }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-bold text-[#1E1E1E] mt-1">
                            <span>Mata XP Terkumpul:</span>
                            <span class="font-black text-emerald-800">{{ number_format($user->xp) }} XP</span>
                        </div>
                    </div>

                    <!-- Academic Quick Facts -->
                    <div class="w-full grid grid-cols-2 gap-2 text-left">
                        <div class="bg-white brutal-border rounded-xl p-2.5 shadow-brutal-sm">
                            <span class="text-[9px] font-black uppercase text-neutral-400 block">Semester</span>
                            <span class="font-display font-black text-xs text-[#1E1E1E]">1 (Ganjil 2025)</span>
                        </div>
                        <div class="bg-white brutal-border rounded-xl p-2.5 shadow-brutal-sm">
                            <span class="text-[9px] font-black uppercase text-neutral-400 block">Sijil Layak</span>
                            <span class="font-display font-black text-xs text-amber-800">Emas (Tahap 1)</span>
                        </div>
                    </div>
                </div>

                <!-- Mentorship Support Snippet -->
                <div class="bg-[#A5C9CA] brutal-border rounded-2xl p-4 shadow-brutal flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-white brutal-border flex items-center justify-center font-bold text-xl shadow-brutal-sm shrink-0">
                        🧭
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-display font-black text-xs text-[#1E1E1E] leading-tight">Senior Pembimbing</h4>
                        <p class="text-[11px] font-bold text-neutral-800 truncate">{{ $user->mentor_name }}</p>
                    </div>
                </div>
            </div>

            <!-- Main Profile Form (Right Column) -->
            <div class="lg:col-span-8">
                <form method="POST" action="{{ route('profile.update') }}" class="bg-[#F5F4F0] brutal-border rounded-2xl p-6 sm:p-8 shadow-brutal flex flex-col justify-between gap-6">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between border-b-2 border-black/10 pb-3 mb-6">
                            <div>
                                <h3 class="font-display font-black text-lg text-[#1E1E1E]">Maklumat Rasmi Mahasiswa</h3>
                                <p class="text-xs font-semibold text-neutral-600">Sila pastikan butiran berpadanan dengan kad pendaftaran kampus anda.</p>
                            </div>
                            <span class="text-2xl">🆔</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Nama Penuh -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-wider text-[#1E1E1E]" for="name">
                                    Nama Penuh <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2.5 bg-white brutal-border rounded-xl font-bold text-sm text-black shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                            </div>

                            <!-- No. Matrik (NIM) - Readonly -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-wider text-[#1E1E1E]">
                                    No. Matrik (NIM)
                                </label>
                                <input type="text" value="{{ $user->nim }}" readonly class="w-full px-3.5 py-2.5 bg-neutral-200/70 border-2 border-neutral-400 rounded-xl font-mono font-bold text-sm text-neutral-600 cursor-not-allowed">
                            </div>

                            <!-- Emel Rasmi -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-wider text-[#1E1E1E]">
                                    Emel Rasmi Mahasiswa
                                </label>
                                <input type="email" value="{{ $user->email }}" readonly class="w-full px-3.5 py-2.5 bg-neutral-200/70 border-2 border-neutral-400 rounded-xl font-bold text-sm text-neutral-600 cursor-not-allowed">
                            </div>

                            <!-- No. Telefon -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-wider text-[#1E1E1E]" for="phone">
                                    No. Telefon / WhatsApp
                                </label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-3.5 py-2.5 bg-white brutal-border rounded-xl font-bold text-sm text-black shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                            </div>

                            <!-- Fakulti -->
                            <div class="flex flex-col gap-1.5 md:col-span-2">
                                <label class="text-xs font-black uppercase tracking-wider text-[#1E1E1E]" for="faculty">
                                    Fakulti Pengajian
                                </label>
                                <input type="text" id="faculty" name="faculty" value="{{ old('faculty', $user->faculty) }}" class="w-full px-3.5 py-2.5 bg-white brutal-border rounded-xl font-bold text-sm text-black shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                            </div>

                            <!-- Program / Jurusan -->
                            <div class="flex flex-col gap-1.5 md:col-span-2">
                                <label class="text-xs font-black uppercase tracking-wider text-[#1E1E1E]" for="major">
                                    Program Pengajian / Pengkhususan
                                </label>
                                <input type="text" id="major" name="major" value="{{ old('major', $user->major) }}" class="w-full px-3.5 py-2.5 bg-white brutal-border rounded-xl font-bold text-sm text-black shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                            </div>

                            <!-- Kumpulan Misi -->
                            <div class="flex flex-col gap-1.5 md:col-span-2">
                                <label class="text-xs font-black uppercase tracking-wider text-[#1E1E1E]" for="group_name">
                                    Kumpulan Orientasi
                                </label>
                                <input type="text" id="group_name" name="group_name" value="{{ old('group_name', $user->group_name) }}" class="w-full px-3.5 py-2.5 bg-white brutal-border rounded-xl font-bold text-sm text-black shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t-2 border-black/10 flex items-center justify-end">
                        <button type="submit" class="px-6 py-3 bg-neo-yellow hover:bg-amber-300 brutal-border rounded-xl font-display font-black text-xs sm:text-sm uppercase tracking-wider shadow-brutal brutal-btn-press flex items-center gap-2">
                            <span>Simpan Kemaskini Profil</span>
                            <span>✓</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB PANE 2: TETAPAN AKAUN -->
    <!-- ============================================================ -->
    <div id="pane-settings" class="tab-pane hidden flex-col gap-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Preferences Toggles (Left Column) -->
            <div class="lg:col-span-6 bg-white brutal-border rounded-2xl p-6 sm:p-8 shadow-brutal space-y-6">
                <div class="border-b-2 border-black/10 pb-3">
                    <h3 class="font-display font-black text-lg text-[#1E1E1E]">Keutamaan Sistem & Pemberitahuan</h3>
                    <p class="text-xs font-semibold text-neutral-600">Urus cara sistem berinteraksi dan menghantar amaran misi.</p>
                </div>

                <form method="POST" action="{{ route('profile.settings') }}" class="space-y-4">
                    @csrf

                    <!-- Toggle 1: Sound Effects -->
                    <div class="flex items-center justify-between p-3.5 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🔔</span>
                            <div>
                                <h4 class="font-display font-black text-xs sm:text-sm text-neutral-900 leading-tight">Kesan Audio Gamifikasi</h4>
                                <p class="text-[11px] font-semibold text-neutral-500">Mainkan audio semasa XP bertambah atau kuiz selesai.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="sound_effects" value="1" {{ $user->sound_effects ? 'checked' : '' }} class="w-6 h-6 rounded-md border-2 border-black accent-amber-400 shadow-[2px_2px_0_0_#000]">
                    </div>

                    <!-- Toggle 2: WhatsApp Notifications -->
                    <div class="flex items-center justify-between p-3.5 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📱</span>
                            <div>
                                <h4 class="font-display font-black text-xs sm:text-sm text-neutral-900 leading-tight">Peringatan WhatsApp Misi</h4>
                                <p class="text-[11px] font-semibold text-neutral-500">Terima amaran jadual sebelum sesi bermula.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="whatsapp_notifications" value="1" {{ $user->whatsapp_notifications ? 'checked' : '' }} class="w-6 h-6 rounded-md border-2 border-black accent-amber-400 shadow-[2px_2px_0_0_#000]">
                    </div>

                    <!-- Toggle 3: Email Bulletins -->
                    <div class="flex items-center justify-between p-3.5 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📧</span>
                            <div>
                                <h4 class="font-display font-black text-xs sm:text-sm text-neutral-900 leading-tight">Buletin Emel Fakulti</h4>
                                <p class="text-[11px] font-semibold text-neutral-500">Ringkasan pengumuman aktiviti harian universiti.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="email_notifications" value="1" {{ $user->email_notifications ? 'checked' : '' }} class="w-6 h-6 rounded-md border-2 border-black accent-amber-400 shadow-[2px_2px_0_0_#000]">
                    </div>

                    <!-- Toggle 4: Dark Mode Preference -->
                    <div class="flex items-center justify-between p-3.5 bg-[#FAF8F5] brutal-border rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🌙</span>
                            <div>
                                <h4 class="font-display font-black text-xs sm:text-sm text-neutral-900 leading-tight">Tema Kontras Tinggi</h4>
                                <p class="text-[11px] font-semibold text-neutral-500">Maksimumkan kejelasan teks semasa sesi malam.</p>
                            </div>
                        </div>
                        <input type="checkbox" name="dark_mode" value="1" {{ $user->dark_mode ? 'checked' : '' }} class="w-6 h-6 rounded-md border-2 border-black accent-amber-400 shadow-[2px_2px_0_0_#000]">
                    </div>

                    <button type="submit" class="w-full py-3 bg-neo-yellow hover:bg-amber-300 brutal-border rounded-xl font-display font-black text-xs uppercase tracking-wider shadow-brutal brutal-btn-press">
                        Simpan Keutamaan
                    </button>
                </form>
            </div>

            <!-- Change Password Card (Right Column) -->
            <div class="lg:col-span-6 bg-white brutal-border rounded-2xl p-6 sm:p-8 shadow-brutal space-y-6">
                <div class="border-b-2 border-black/10 pb-3">
                    <h3 class="font-display font-black text-lg text-[#1E1E1E]">Keselamatan & Kata Laluan</h3>
                    <p class="text-xs font-semibold text-neutral-600">Pastikan akaun anda dilindungi dengan kata laluan kukuh.</p>
                </div>

                <form method="POST" action="{{ route('profile.settings') }}" class="space-y-4">
                    @csrf

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-black uppercase text-[#1E1E1E]" for="current_password">
                            Kata Laluan Semasa <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required placeholder="••••••••••••" class="w-full px-3.5 py-2.5 bg-[#FAF8F5] brutal-border rounded-xl font-bold text-sm shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-black uppercase text-[#1E1E1E]" for="new_password">
                            Kata Laluan Baharu <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="new_password" name="password" required placeholder="Minimum 6 aksara" class="w-full px-3.5 py-2.5 bg-[#FAF8F5] brutal-border rounded-xl font-bold text-sm shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-black uppercase text-[#1E1E1E]" for="new_password_confirmation">
                            Sahkan Kata Laluan Baharu <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="new_password_confirmation" name="password_confirmation" required placeholder="••••••••••••" class="w-full px-3.5 py-2.5 bg-[#FAF8F5] brutal-border rounded-xl font-bold text-sm shadow-brutal-sm focus:outline-none focus:ring-2 focus:ring-amber-300">
                    </div>

                    <button type="submit" class="w-full py-3 bg-[#E78B24] hover:bg-[#c9741a] text-white brutal-border rounded-xl font-display font-black text-xs uppercase tracking-wider shadow-brutal brutal-btn-press">
                        Tukar Kata Laluan Akaun
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB PANE 3: PUSAT BANTUAN & FAQ -->
    <!-- ============================================================ -->
    <div id="pane-helpdesk" class="tab-pane hidden flex-col gap-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- FAQs Accordion (Left Column) -->
            <div class="lg:col-span-7 bg-white brutal-border rounded-2xl p-6 sm:p-8 shadow-brutal space-y-4">
                <div class="flex items-center justify-between border-b-2 border-black/10 pb-3 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">❓</span>
                        <h3 class="font-display font-black text-lg text-[#1E1E1E]">Soalan Lazim Orientasi (FAQ)</h3>
                    </div>
                    <span class="text-xs font-bold text-neutral-500">5 Panduan</span>
                </div>

                <div class="space-y-3" id="faq-accordion">
                    @foreach ($faqs as $index => $faq)
                        <div class="brutal-border rounded-xl overflow-hidden bg-[#FAF8F5]">
                            <button type="button" onclick="toggleFaq({{ $index }})" class="w-full p-4 text-left font-display font-black text-xs sm:text-sm text-neutral-900 flex items-center justify-between gap-3 hover:bg-neutral-100 transition-colors">
                                <span>{{ $faq->question }}</span>
                                <span id="faq-icon-{{ $index }}" class="text-base shrink-0 font-bold">＋</span>
                            </button>
                            <div id="faq-ans-{{ $index }}" class="hidden p-4 pt-0 text-xs sm:text-sm font-semibold text-neutral-700 leading-relaxed border-t border-dashed border-neutral-300/80 bg-white">
                                <p class="pt-3">{{ $faq->answer }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Emergency Contact Bar -->
                <div class="mt-6 p-4 bg-rose-100 brutal-border rounded-xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">🚨</span>
                        <div>
                            <h5 class="font-display font-black text-xs text-rose-950">Talian Kecemasan Medikal / Keselamatan</h5>
                            <span class="text-[11px] font-bold text-rose-800">Bilik Rawatan & Balai Pengawal 24 Jam: <strong>03-8921 5555</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Help Ticket & Previous Queries (Right Column) -->
            <div class="lg:col-span-5 flex flex-col gap-6">

                <!-- Help Ticket Submission Card -->
                <div class="bg-[#FFF9EB] brutal-border rounded-2xl p-6 shadow-brutal space-y-4">
                    <div class="border-b-2 border-black/10 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📩</span>
                            <h4 class="font-display font-black text-base text-neutral-900">Borang Pertanyaan ke Mentor</h4>
                        </div>
                        <p class="text-[11px] font-semibold text-neutral-600 mt-0.5">Pertanyaan anda akan dijawab oleh fasilitator kumpulan Alpha Harimau.</p>
                    </div>

                    <form method="POST" action="{{ route('help.ticket') }}" class="space-y-3">
                        @csrf

                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-black uppercase text-neutral-800" for="subject">Subjek Pertanyaan</label>
                            <input type="text" id="subject" name="subject" required placeholder="Contoh: Pertanyaan Lokasi Makmal SKS" class="w-full px-3 py-2 bg-white brutal-border rounded-xl font-bold text-xs shadow-brutal-sm">
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-black uppercase text-neutral-800" for="category">Kategori</label>
                            <select id="category" name="category" class="w-full px-3 py-2 bg-white brutal-border rounded-xl font-bold text-xs shadow-brutal-sm">
                                <option value="Akademik & SKS">Akademik & SKS</option>
                                <option value="Kolej & Asrama">Kolej & Asrama</option>
                                <option value="Kad Pelajar">Kad Pelajar</option>
                                <option value="Kesihatan">Kesihatan</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-black uppercase text-neutral-800" for="message">Mesej Terperinci</label>
                            <textarea id="message" name="message" rows="3" required placeholder="Tulis soalan anda dengan jelas di sini..." class="w-full px-3 py-2 bg-white brutal-border rounded-xl font-bold text-xs shadow-brutal-sm"></textarea>
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-neo-yellow hover:bg-amber-300 brutal-border rounded-xl font-display font-black text-xs uppercase tracking-wider shadow-brutal-sm brutal-btn-press">
                            Hantar Mesej ke Pembimbing ➔
                        </button>
                    </form>
                </div>

                <!-- Previous Queries List -->
                @if ($tickets->isNotEmpty())
                    <div class="bg-white brutal-border rounded-2xl p-5 shadow-brutal space-y-3">
                        <h4 class="font-display font-black text-xs uppercase text-neutral-500 tracking-wider">
                            Tiket Bantuan Anda ({{ $tickets->count() }})
                        </h4>

                        <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                            @foreach ($tickets as $ticket)
                                <div class="p-3 bg-[#FAF8F5] brutal-border rounded-xl text-left text-xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-display font-black text-neutral-900">{{ $ticket->subject }}</span>
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-black/20 text-[9px] font-black uppercase">
                                            Dijawab
                                        </span>
                                    </div>
                                    <p class="text-[11px] font-medium text-neutral-600 mb-2">{{ $ticket->message }}</p>

                                    @if ($ticket->reply)
                                        <div class="p-2.5 bg-amber-50 border-l-3 border-[#E78B24] rounded text-[11px] font-semibold text-neutral-800">
                                            <strong class="font-black text-[#E78B24] block mb-0.5">💬 Maklum Balas Senior:</strong>
                                            {{ $ticket->reply }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
    function switchProfileTab(tabName) {
        // Hide all panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.add('hidden');
            pane.classList.remove('flex');
        });

        // Reset button styling
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.className = "tab-btn flex items-center gap-2 px-4 py-2 rounded-lg font-display font-bold text-xs md:text-sm border-2 border-transparent text-neutral-700 hover:text-black hover:bg-neutral-100 transition-all whitespace-nowrap";
        });

        // Activate selected
        const targetPane = document.getElementById(`pane-${tabName}`);
        const targetBtn = document.getElementById(`tab-btn-${tabName}`);

        if (targetPane && targetBtn) {
            targetPane.classList.remove('hidden');
            targetPane.classList.add('flex');
            targetBtn.className = "tab-btn flex items-center gap-2 px-4 py-2 rounded-lg font-display font-black text-xs md:text-sm brutal-border bg-neo-yellow text-[#1E1E1E] shadow-brutal-sm transition-all brutal-btn-press whitespace-nowrap";
        }

        playSound('click');
    }

    function toggleFaq(index) {
        const ans = document.getElementById(`faq-ans-${index}`);
        const icon = document.getElementById(`faq-icon-${index}`);
        if (!ans || !icon) return;

        if (ans.classList.contains('hidden')) {
            ans.classList.remove('hidden');
            icon.textContent = '－';
        } else {
            ans.classList.add('hidden');
            icon.textContent = '＋';
        }
        playSound('click');
    }

    // Auto switch tab if URL query ?tab=... is present
    document.addEventListener('DOMContentLoaded', () => {
        const initialTab = "{{ $activeTab }}";
        if (initialTab && ['profile', 'settings', 'helpdesk'].includes(initialTab)) {
            switchProfileTab(initialTab);
        }
    });
</script>
@endpush
@endsection
