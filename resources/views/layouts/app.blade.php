<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OspekQuest') - Portal Orientasi Mahasiswa</title>

    <!-- Tailwind CSS CDN with plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&family=Fredoka:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind Custom Configuration Matching Stitch UI/UX -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['"Fredoka"', 'sans-serif'],
                    },
                    colors: {
                        canvas: '#E5E4E2',
                        cardbg: '#F5F4F0',
                        neo: {
                            yellow: '#F9D976',
                            orange: '#F5A25D',
                            blue: '#A5C9CA',
                            red: '#FA7970',
                            peach: '#FFE6AF',
                            cream: '#FFF9EB',
                            amber: '#E78B24',
                            podium: '#C86E2D',
                            bronze: '#C78C35',
                        },
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
                        'brutal-sm': '2px 2px 0px 0px #1E1E1E',
                        'brutal': '3px 3px 0px 0px #1E1E1E',
                        'brutal-4': '4px 4px 0px 0px #1E1E1E',
                        'brutal-lg': '5px 5px 0px 0px #1E1E1E',
                        'brutal-xl': '8px 8px 0px 0px #1E1E1E',
                        'brutal-2xl': '12px 12px 0px 0px #000000',
                        'brutal-active': '1px 1px 0px 0px #1E1E1E'
                    }
                }
            }
        }
    </script>

    <!-- Custom Neo-Brutalist CSS Styles -->
    <style data-purpose="custom-brutalist-borders">
        .brutal-border {
            border: 2px solid #1E1E1E;
        }
        .brutal-border-3 {
            border: 3px solid #1E1E1E;
        }
        .brutal-border-b {
            border-bottom: 2px solid #1E1E1E;
        }
        .brutal-border-r {
            border-right: 2px solid #1E1E1E;
        }
        .dashed-divider-r {
            border-right: 2.5px dashed #A8A6A1;
        }
        .dashed-divider-b {
            border-bottom: 2.5px dashed #A8A6A1;
        }
        .bg-grid-dots {
            background-color: #f4efe6;
            background-image: radial-gradient(#1E1E1E 1.2px, transparent 1.2px);
            background-size: 24px 24px;
        }
        .brutal-btn-press {
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .brutal-btn-press:hover {
            transform: translate(-2px, -2px);
            box-shadow: 5px 5px 0px 0px #1E1E1E;
        }
        .brutal-btn-press:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0px 0px #1E1E1E;
        }
        .conic-chart {
            background: conic-gradient(#F5A25D 0% 73%, #E5E7EB 73% 100%);
        }
        /* Custom Neo Scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #E5E4E2;
        }
        ::-webkit-scrollbar-thumb {
            background: #B8B5AD;
            border-radius: 9999px;
            border: 1px solid #1E1E1E;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#DCDAD5] text-[#1E1E1E] font-sans antialiased min-h-screen p-2 sm:p-4 md:p-6 lg:p-8 flex items-center justify-center selection:bg-neo-yellow">

    <!-- BEGIN: Master Container Wrapper -->
    <div class="w-full max-w-[1440px] bg-[#EBE9E4] brutal-border rounded-[28px] sm:rounded-[32px] shadow-brutal-lg overflow-hidden flex flex-col xl:flex-row min-h-[920px] relative">

        <!-- Mobile Header Bar -->
        <div class="xl:hidden flex items-center justify-between p-4 bg-[#EBE9E4] brutal-border-b z-20">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-neo-yellow brutal-border flex items-center justify-center font-display font-bold text-lg shadow-brutal-sm rotate-[-3deg]">
                    ⚔️
                </div>
                <div>
                    <span class="font-display font-bold text-lg leading-none block">OspekQuest</span>
                    <span class="text-[9px] font-black uppercase text-neutral-500 tracking-wider">Maba 2025</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Streak badge -->
                <div class="flex items-center gap-1 bg-[#FFE6AF] brutal-border px-2.5 py-1 rounded-xl text-xs font-black shadow-brutal-sm">
                    <span>🔥</span>
                    <span>{{ auth()->user()->streak_days ?? 9 }} Hari</span>
                </div>

                <!-- Mobile Hamburger Toggle -->
                <button type="button" onclick="toggleMobileSidebar()" class="p-2 bg-white brutal-border rounded-xl shadow-brutal-sm brutal-btn-press" aria-label="Toggle menu">
                    <span class="material-symbols-outlined text-xl block">menu</span>
                </button>
            </div>
        </div>

        <!-- Sidebar Component -->
        @include('components.sidebar')

        <!-- Main Dynamic Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto max-h-[920px]">
            @yield('content')
        </div>

    </div>
    <!-- END: Master Container Wrapper -->

    <!-- Global Toast Notification Component -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none">
        <div id="toast-template" class="hidden pointer-events-auto bg-white brutal-border rounded-2xl p-4 shadow-brutal-lg max-w-sm flex items-start gap-3.5 transform transition-all duration-300 translate-y-8 opacity-0">
            <div id="toast-icon" class="w-10 h-10 rounded-xl bg-neo-yellow brutal-border flex items-center justify-center text-xl shrink-0 shadow-brutal-sm">
                🎉
            </div>
            <div class="flex-1 min-w-0">
                <h4 id="toast-title" class="font-display font-black text-sm text-[#1E1E1E] leading-tight">Mesej Sistem</h4>
                <p id="toast-desc" class="text-xs font-semibold text-neutral-600 mt-0.5 leading-snug">Operasi berjaya dilaksanakan.</p>
            </div>
            <button type="button" onclick="dismissToast(this.parentElement)" class="text-neutral-400 hover:text-black">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </div>

    <!-- Audio Effects Engine & Sound Utility -->
    <script>
        const soundEnabled = {{ (auth()->user()->sound_effects ?? true) ? 'true' : 'false' }};
        const audioCtx = (window.AudioContext || window.webkitAudioContext) ? new (window.AudioContext || window.webkitAudioContext)() : null;

        function playSound(type) {
            if (!soundEnabled || !audioCtx) return;
            try {
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                const now = audioCtx.currentTime;

                if (type === 'click') {
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(320, now);
                    osc.frequency.exponentialRampToValueAtTime(150, now + 0.08);
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.08);
                    osc.start(now);
                    osc.stop(now + 0.08);
                } else if (type === 'success' || type === 'xp') {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(440, now);
                    osc.frequency.setValueAtTime(587.33, now + 0.08);
                    osc.frequency.setValueAtTime(880, now + 0.16);
                    gain.gain.setValueAtTime(0.25, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.3);
                    osc.start(now);
                    osc.stop(now + 0.3);
                } else if (type === 'fanfare') {
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(523.25, now);
                    osc.frequency.setValueAtTime(659.25, now + 0.1);
                    osc.frequency.setValueAtTime(783.99, now + 0.2);
                    osc.frequency.setValueAtTime(1046.50, now + 0.3);
                    gain.gain.setValueAtTime(0.18, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.6);
                    osc.start(now);
                    osc.stop(now + 0.6);
                }
            } catch (e) {
                console.warn('Audio playback suppressed', e);
            }
        }

        // Global Toast Notification Helper
        function showToast(title, message, icon = '✨') {
            const container = document.getElementById('toast-container');
            const template = document.getElementById('toast-template');
            if (!container || !template) return;

            const clone = template.cloneNode(true);
            clone.id = 'toast-' + Date.now();
            clone.classList.remove('hidden');

            clone.querySelector('#toast-title').textContent = title;
            clone.querySelector('#toast-desc').textContent = message;
            clone.querySelector('#toast-icon').textContent = icon;

            container.appendChild(clone);

            playSound('success');

            requestAnimationFrame(() => {
                clone.classList.remove('translate-y-8', 'opacity-0');
                clone.classList.add('translate-y-0', 'opacity-100');
            });

            setTimeout(() => {
                dismissToast(clone);
            }, 4000);
        }

        function dismissToast(el) {
            if (!el) return;
            el.classList.add('translate-y-8', 'opacity-0');
            setTimeout(() => {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 300);
        }

        // Toggle Mobile Sidebar Drawer
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar-container');
            if (!sidebar) return;
            sidebar.classList.toggle('hidden');
        }

        // Auto display session toast if flashed from controller
        @if(session('toast'))
            document.addEventListener('DOMContentLoaded', function() {
                showToast(
                    "{{ session('toast.title') }}",
                    "{{ session('toast.message') }}",
                    "{{ session('toast.icon', '🎉') }}"
                );
            });
        @endif
    </script>

    @stack('scripts')
</body>
</html>
