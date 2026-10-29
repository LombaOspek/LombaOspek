<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk | OspekQuest - MABA ORIENTASI 2025</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&family=Fredoka:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['"Fredoka"', 'sans-serif'],
                    },
                    colors: {
                        'brutal-cream': '#f4efe6',
                        'brutal-yellow': '#fde047',
                        'brutal-amber': '#f59e0b',
                        'brutal-emerald': '#34d399',
                        'brutal-mint': '#6ee7b7',
                        'brutal-sky': '#7dd3fc',
                        'brutal-rose': '#fda4af',
                        'brutal-orange': '#fb923c',
                        'brutal-card': '#fffdfa',
                    },
                    boxShadow: {
                        'brutal-sm': '3px 3px 0px 0px #000000',
                        'brutal': '5px 5px 0px 0px #000000',
                        'brutal-lg': '8px 8px 0px 0px #000000',
                        'brutal-xl': '12px 12px 0px 0px #000000',
                    },
                    borderWidth: {
                        '3': '3px',
                    }
                }
            }
        }
    </script>

    <style data-purpose="custom-neo-brutalism">
        .bg-grid-dots {
            background-color: #f4efe6;
            background-image: radial-gradient(#000000 1.2px, transparent 1.2px);
            background-size: 24px 24px;
        }
        .brutal-btn-press {
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .brutal-btn-press:hover {
            transform: translate(-2px, -2px);
            box-shadow: 7px 7px 0px 0px #000000;
        }
        .brutal-btn-press:active {
            transform: translate(3px, 3px);
            box-shadow: 1px 1px 0px 0px #000000;
        }
    </style>
</head>
<body class="bg-grid-dots min-h-screen text-neutral-900 flex flex-col justify-between p-4 sm:p-6 md:p-8 selection:bg-amber-300">

    <!-- BEGIN: Top Navigation Bar -->
    <header class="max-w-7xl mx-auto w-full mb-6">
        <nav class="flex items-center justify-between bg-white border-3 border-black rounded-2xl px-5 py-3.5 shadow-brutal">
            <!-- Brand Identity -->
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brutal-yellow border-2 border-black rounded-xl flex items-center justify-center font-display font-black text-xl shadow-brutal-sm rotate-[-3deg]">
                    ⚔️
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <span class="font-display font-black text-xl tracking-tight uppercase">Ospek<span class="text-amber-500">Quest</span></span>
                    <span class="bg-black text-white text-[11px] font-black uppercase px-2.5 py-0.5 rounded-full border border-black tracking-wider">
                        Maba Orientasi 2025
                    </span>
                </div>
            </a>

            <!-- Quick Action Badges -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 bg-emerald-100 border-2 border-black rounded-xl px-3 py-1 text-xs font-black shadow-brutal-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse border border-black"></span>
                    SERVER ORIENTASI AKTIF
                </div>
                <a href="#bantuan" onclick="document.getElementById('bantuan-modal').classList.remove('hidden')" class="bg-white hover:bg-neutral-100 border-2 border-black px-3.5 py-1.5 rounded-xl font-black text-xs uppercase brutal-btn-press shadow-brutal-sm inline-flex items-center gap-1.5">
                    <span>🆘</span> Bantuan MABA
                </a>
            </div>
        </nav>
    </header>
    <!-- END: Top Navigation Bar -->

    <!-- BEGIN: Main Content Container -->
    <main class="max-w-7xl mx-auto w-full my-auto flex items-center justify-center py-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch w-full">

            <!-- BEGIN: Left Panel (Gamification & Branding) -->
            <section class="lg:col-span-6 flex flex-col justify-between space-y-6" data-purpose="branding-panel">
                <!-- Welcome Hero Badge & Title -->
                <div class="bg-white border-3 border-black rounded-3xl p-6 md:p-8 shadow-brutal-lg relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-amber-200 border-3 border-black rounded-full opacity-40 pointer-events-none"></div>
                    <div class="inline-flex items-center gap-2 bg-brutal-yellow border-2 border-black rounded-xl px-3 py-1.5 font-black text-xs uppercase tracking-wide shadow-brutal-sm mb-4">
                        <span>✨</span> Gerbang Pengembaraan Mahasiswa Baharu
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight leading-tight mb-4">
                        Selamat Datang, Mahasiswa Baharu 2025! 🎓
                    </h1>
                    <p class="text-neutral-700 font-semibold text-base sm:text-lg leading-relaxed">
                        Mulakan pengembaraan orientasi kampus anda di <strong class="text-black font-extrabold underline decoration-amber-400 decoration-4">OspekQuest</strong>. Selesaikan misi harian, kumpul mata XP, buka lencana kejuaraan, dan tebus Sijil Tamat Orientasi digital rasmi universiti.
                    </p>
                </div>

                <!-- Gamification Highlight Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 md:gap-4" data-purpose="stat-badges">
                    <!-- Highlight 1: Daily Streak -->
                    <div class="bg-[#fed7aa] border-3 border-black rounded-2xl p-4 shadow-brutal flex flex-col justify-between brutal-btn-press cursor-default">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl">🔥</span>
                            <span class="text-[10px] font-black uppercase bg-black text-white px-2 py-0.5 rounded-md">Aktif</span>
                        </div>
                        <div>
                            <div class="font-black text-xl leading-none">9 Hari</div>
                            <div class="text-xs font-black text-neutral-800 mt-1 uppercase tracking-tight">Rentetan Maba</div>
                        </div>
                    </div>

                    <!-- Highlight 2: Leaderboard -->
                    <div class="bg-brutal-rose border-3 border-black rounded-2xl p-4 shadow-brutal flex flex-col justify-between brutal-btn-press cursor-default">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl">🏆</span>
                            <span class="text-[10px] font-black uppercase bg-black text-white px-2 py-0.5 rounded-md">Kampus</span>
                        </div>
                        <div>
                            <div class="font-black text-xl leading-none">Top 10</div>
                            <div class="text-xs font-black text-neutral-800 mt-1 uppercase tracking-tight">Papan Pendahulu</div>
                        </div>
                    </div>

                    <!-- Highlight 3: XP Pool -->
                    <div class="bg-brutal-sky col-span-2 sm:col-span-1 border-3 border-black rounded-2xl p-4 shadow-brutal flex flex-col justify-between brutal-btn-press cursor-default">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl">⚡</span>
                            <span class="text-[10px] font-black uppercase bg-black text-white px-2 py-0.5 rounded-md">Ganjaran</span>
                        </div>
                        <div>
                            <div class="font-black text-xl leading-none">+1,320 XP</div>
                            <div class="text-xs font-black text-neutral-800 mt-1 uppercase tracking-tight">Menanti Anda</div>
                        </div>
                    </div>
                </div>

                <!-- Mascot & Motivational Banner -->
                <div class="bg-brutal-card border-3 border-black rounded-3xl p-5 shadow-brutal flex items-center gap-4">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-amber-300 border-3 border-black rounded-2xl flex-shrink-0 flex items-center justify-center text-3xl sm:text-4xl shadow-brutal-sm">
                        🦊
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-black text-sm uppercase">Karan Sang Musang Pandu</span>
                            <span class="bg-emerald-300 border border-black font-extrabold text-[10px] px-2 py-0.5 rounded-full uppercase">Maskot</span>
                        </div>
                        <p class="text-xs sm:text-sm font-semibold text-neutral-700 leading-snug">
                            "Kekalkan semangat membara! Hadir tepat pada masanya di setiap aktiviti fakulti & raih lencana emas sulung anda hari ini."
                        </p>
                    </div>
                </div>

                <!-- Quick Maba Tip Box -->
                <div class="bg-amber-100 border-2 border-dashed border-black rounded-2xl p-4 flex items-start gap-3">
                    <div class="text-xl">💡</div>
                    <div class="text-xs sm:text-sm font-semibold text-neutral-800">
                        <strong class="font-black text-black">Tip Maba Rasmi:</strong> Masukkan No. Matrik (NIM) rasmi anda (contoh: <code class="font-mono bg-white px-1.5 py-0.5 rounded border border-black font-bold">MABA-2025-089</code>) atau gunakan butang Log Masuk Pantas untuk akses serta-merta.
                    </div>
                </div>
            </section>
            <!-- END: Left Panel -->

            <!-- BEGIN: Right Panel (Login Form) -->
            <section class="lg:col-span-6 flex flex-col justify-center" data-purpose="login-form-container">
                <div class="bg-white border-3 border-black rounded-3xl p-6 sm:p-8 md:p-10 shadow-brutal-xl relative">

                    <!-- Card Header & Badge -->
                    <div class="flex items-center justify-between border-b-3 border-black pb-5 mb-6">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider text-neutral-500">Akses Portal Rasmi</span>
                            <h2 class="text-2xl sm:text-3xl font-black text-black tracking-tight">Log Masuk Portal Maba</h2>
                        </div>
                        <span class="bg-brutal-yellow border-2 border-black px-3 py-1 rounded-xl text-xs font-black shadow-brutal-sm uppercase">
                            Sesi 2025/2026
                        </span>
                    </div>

                    <!-- Quick 1-Click Demo Login Banner (Super convenient!) -->
                    <div class="mb-6 bg-neo-yellow border-3 border-black rounded-2xl p-4 shadow-brutal flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🚀</span>
                            <div>
                                <span class="block font-black text-xs text-neutral-900 uppercase tracking-wider">Akses Ujian / Demo Segera:</span>
                                <span class="text-xs font-bold text-neutral-800">Masuk sebagai Karan Mehra (MABA Aktif)</span>
                            </div>
                        </div>
                        <a href="{{ route('quick-login') }}" class="w-full sm:w-auto px-4 py-2 bg-black hover:bg-neutral-800 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-brutal-sm brutal-btn-press text-center whitespace-nowrap">
                            Log Masuk Pantas ➔
                        </a>
                    </div>

                    <!-- Role Toggle Tabs -->
                    <div class="grid grid-cols-2 gap-2 bg-neutral-100 p-1.5 border-2 border-black rounded-2xl mb-6">
                        <button class="role-btn py-2.5 px-4 font-black text-xs sm:text-sm uppercase rounded-xl border-2 border-black bg-brutal-yellow shadow-brutal-sm text-center flex items-center justify-center gap-2" id="tab-maba" onclick="setRole('maba')" type="button">
                            <span>🎒</span> Pelajar / MABA
                        </button>
                        <button class="role-btn py-2.5 px-4 font-black text-xs sm:text-sm uppercase rounded-xl border-2 border-transparent text-neutral-600 hover:text-black hover:border-black text-center flex items-center justify-center gap-2" id="tab-mentor" onclick="setRole('mentor')" type="button">
                            <span>👔</span> Mentor / Senior
                        </button>
                    </div>

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <div class="mb-6 bg-rose-200 border-2 border-black p-3.5 rounded-xl shadow-brutal-sm flex items-center justify-between text-xs font-black text-rose-950">
                            <div class="flex items-center gap-2">
                                <span class="text-base">⚠️</span>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Main Login Form -->
                    <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                        @csrf

                        <!-- Field 1: Matric Number / Campus Email -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-black uppercase tracking-wider text-black" for="identity">
                                No. Matrik / NIM / Emel Pelajar <span class="text-rose-600">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base">
                                    🆔
                                </div>
                                <input type="text" id="identity" name="identity" value="{{ old('identity', 'MABA-2025-089') }}" required placeholder="Contoh: MABA-2025-089 atau karan.mehra@maba.ac.id" class="w-full pl-11 pr-4 py-3 bg-[#fefdfb] border-2 border-black rounded-xl font-bold text-sm sm:text-base text-black placeholder:text-neutral-400 shadow-brutal-sm focus:outline-none focus:ring-4 focus:ring-amber-300 focus:border-black transition">
                            </div>
                            <p class="text-[11px] font-semibold text-neutral-500 pl-1">
                                Gunakan format NIM yang tertera pada surat kemasukan kolej.
                            </p>
                        </div>

                        <!-- Field 2: Password -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-black uppercase tracking-wider text-black" for="password">
                                    Kata Laluan / Kod Akses MABA <span class="text-rose-600">*</span>
                                </label>
                                <a href="javascript:void(0)" onclick="alert('Kata laluan lalai untuk sesi demo ialah: password')" class="text-[11px] font-black text-amber-700 hover:text-black underline">
                                    Lupa Kata Laluan?
                                </a>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base">
                                    🔒
                                </div>
                                <input type="password" id="password" name="password" value="password" required placeholder="••••••••••••" class="w-full pl-11 pr-12 py-3 bg-[#fefdfb] border-2 border-black rounded-xl font-bold text-sm sm:text-base text-black placeholder:text-neutral-400 shadow-brutal-sm focus:outline-none focus:ring-4 focus:ring-amber-300 focus:border-black transition">
                                <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-sm font-bold text-neutral-600 hover:text-black focus:outline-none">
                                    <span id="eye-icon">👁️</span>
                                </button>
                            </div>
                        </div>

                        <!-- Checkbox: Remember device -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" name="remember" id="remember" checked class="w-5 h-5 rounded-md border-2 border-black text-black focus:ring-0 focus:ring-offset-0 accent-amber-400 shadow-[2px_2px_0_0_#000]">
                                <span class="text-xs sm:text-sm font-bold text-neutral-800">
                                    Ingat sesi pada peranti ini
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full py-4 bg-brutal-yellow hover:bg-amber-300 border-3 border-black rounded-2xl font-black text-sm sm:text-base uppercase tracking-wider shadow-brutal brutal-btn-press flex items-center justify-center gap-3">
                            <span>Buka Portal Pengembaraan</span>
                            <span class="text-xl">➔</span>
                        </button>
                    </form>

                    <!-- Security & Protocol Footer -->
                    <div class="mt-8 pt-6 border-t-2 border-neutral-200 flex flex-col sm:flex-row items-center justify-between text-neutral-500 text-xs font-semibold gap-3 text-center sm:text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm">🛡️</span>
                            <span>Enkripsi Selamat 256-Bit SSL</span>
                        </div>
                        <div>
                            <span>Bantuan Pantas:</span>
                            <span class="font-bold text-black underline">Bil. Kaunseling 4B</span>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END: Right Panel -->

        </div>
    </main>
    <!-- END: Main Content Container -->

    <!-- Footer Copyright -->
    <footer class="max-w-7xl mx-auto w-full text-center py-4 text-xs font-bold text-neutral-600">
        &copy; 2025 OspekQuest • Universiti Kebangsaan • Semua Hak Terpelihara.
    </footer>

    <!-- Help Modal -->
    <div id="bantuan-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white border-3 border-black rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-brutal-xl relative">
            <div class="flex items-center justify-between border-b-2 border-black pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🆘</span>
                    <h3 class="font-display font-black text-xl">Pusat Bantuan MABA</h3>
                </div>
                <button type="button" onclick="document.getElementById('bantuan-modal').classList.add('hidden')" class="text-xl font-black">✕</button>
            </div>
            <div class="space-y-3 text-xs sm:text-sm font-semibold text-neutral-700">
                <p><strong>1. Butiran Demo:</strong> Anda boleh menggunakan No. Matrik <code class="bg-amber-100 px-1 py-0.5 rounded font-mono font-bold">MABA-2025-089</code> dan Kata Laluan <code class="bg-amber-100 px-1 py-0.5 rounded font-mono font-bold">password</code>.</p>
                <p><strong>2. Masalah Log Masuk?</strong> Pastikan tiada ruang kosong sebelum atau selepas No. Matrik anda.</p>
                <p><strong>3. Talian Kecemasan Panitia:</strong> Hubungi Bilik Kaunseling 4B di talian sambungan kampus <strong>ext. 4410</strong>.</p>
            </div>
            <button type="button" onclick="document.getElementById('bantuan-modal').classList.add('hidden')" class="w-full mt-6 py-2.5 bg-neo-yellow border-2 border-black rounded-xl font-black text-xs uppercase shadow-brutal-sm brutal-btn-press">
                Tutup Maklumat
            </button>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const eye = document.getElementById('eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.textContent = '🙈';
            } else {
                pwd.type = 'password';
                eye.textContent = '👁️';
            }
        }

        function setRole(role) {
            const mabaBtn = document.getElementById('tab-maba');
            const mentorBtn = document.getElementById('tab-mentor');
            const input = document.getElementById('identity');

            if (role === 'maba') {
                mabaBtn.className = "role-btn py-2.5 px-4 font-black text-xs sm:text-sm uppercase rounded-xl border-2 border-black bg-brutal-yellow shadow-brutal-sm text-center flex items-center justify-center gap-2";
                mentorBtn.className = "role-btn py-2.5 px-4 font-black text-xs sm:text-sm uppercase rounded-xl border-2 border-transparent text-neutral-600 hover:text-black hover:border-black text-center flex items-center justify-center gap-2";
                input.value = "MABA-2025-089";
            } else {
                mentorBtn.className = "role-btn py-2.5 px-4 font-black text-xs sm:text-sm uppercase rounded-xl border-2 border-black bg-brutal-yellow shadow-brutal-sm text-center flex items-center justify-center gap-2";
                mabaBtn.className = "role-btn py-2.5 px-4 font-black text-xs sm:text-sm uppercase rounded-xl border-2 border-transparent text-neutral-600 hover:text-black hover:border-black text-center flex items-center justify-center gap-2";
                input.value = "kak.syakirah@mentor.ac.id";
            }
        }
    </script>
</body>
</html>
