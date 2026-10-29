@php
    $program = config('ospek.program');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk · {{ $program['name'] }}</title>
    <meta name="description" content="Masuk ke portal orientasi {{ $program['name'] }} — {{ $program['university'] }}.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="paper-dots min-h-screen">
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-5 sm:px-6 sm:py-7">

        {{-- Bar identitas --}}
        <header class="flex items-center justify-between gap-3">
            <a href="{{ route('login') }}" class="flex items-center gap-2.5">
                <span class="grid size-10 place-items-center rounded-md border-2 border-ink bg-amber shadow-[var(--shadow-flat-sm)]">
                    <x-icon name="compass" class="size-6" :stroke="2" />
                </span>
                <span>
                    <span class="block font-display text-[1.05rem] font-extrabold leading-none">OspekQuest</span>
                    <span class="mt-0.5 block text-[0.56rem] font-extrabold uppercase tracking-[0.14em] text-ink-faint">
                        {{ $program['study_program'] }}
                    </span>
                </span>
            </a>

            <span class="stamp hidden bg-paper sm:inline-flex">
                <span class="size-1.5 rounded-full bg-teal"></span>
                Sesi {{ $program['academic_year'] }}
            </span>
        </header>

        <main class="my-auto grid w-full items-start gap-5 py-6 lg:grid-cols-[1.05fr_0.95fr] lg:gap-7">

            {{-- Panel kiri: konteks & bukti --}}
            <section class="flex flex-col gap-3">
                <div class="rounded-xl border-2 border-ink bg-paper p-5 shadow-[var(--shadow-flat-lg)] sm:p-6">
                    <span class="stamp bg-teal text-white">
                        <x-icon name="sparkle" class="size-3" :stroke="2.2" />
                        Orientasi Berbasis Gamifikasi
                    </span>

                    <h1 class="mt-3 font-display text-[1.6rem] font-extrabold leading-[1.12] tracking-tight text-balance sm:text-[2rem]">
                        Delapan modul, satu perjalanan,<br class="hidden sm:block">
                        sertifikat resmi di akhir.
                    </h1>

                    <p class="mt-2.5 max-w-lg text-[0.8rem] font-medium leading-relaxed text-ink-soft text-pretty">
                        {{ $program['name'] }} memandu mahasiswa baru {{ $program['study_program'] }}
                        {{ $program['university'] }} menyelesaikan orientasi secara terstruktur.
                        Setiap modul memiliki gerbang waktu, kuis, dan lencana — progres Anda tercatat rapi dan dapat diverifikasi.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="stamp bg-paper-dim">8 Modul</span>
                        <span class="stamp bg-paper-dim">Kuis Bertimer</span>
                        <span class="stamp bg-paper-dim">Lencana Otomatis</span>
                        <span class="stamp bg-paper-dim">Sertifikat Terverifikasi</span>
                    </div>
                </div>

                {{-- Tiga bukti nilai, bukan angka dekoratif --}}
                <div class="grid gap-2.5 sm:grid-cols-3">
                    <div class="rounded-lg border-2 border-ink bg-teal/15 p-3 shadow-[var(--shadow-flat-sm)]">
                        <x-icon name="map" class="size-5 text-teal-deep" :stroke="2" />
                        <p class="mt-1.5 font-display text-[0.76rem] font-bold leading-tight">Gerbang waktu</p>
                        <p class="mt-0.5 text-[0.66rem] font-medium leading-snug text-ink-soft">
                            Modul terbuka berurutan, tidak bisa dilompati.
                        </p>
                    </div>
                    <div class="rounded-lg border-2 border-ink bg-amber/25 p-3 shadow-[var(--shadow-flat-sm)]">
                        <x-icon name="quiz" class="size-5 text-amber-deep" :stroke="2" />
                        <p class="mt-1.5 font-display text-[0.76rem] font-bold leading-tight">Kuis adil</p>
                        <p class="mt-0.5 text-[0.66rem] font-medium leading-snug text-ink-soft">
                            Tiga kesempatan, timer dihitung di server.
                        </p>
                    </div>
                    <div class="rounded-lg border-2 border-ink bg-sky/50 p-3 shadow-[var(--shadow-flat-sm)]">
                        <x-icon name="certificate" class="size-5" :stroke="2" />
                        <p class="mt-1.5 font-display text-[0.76rem] font-bold leading-tight">Bisa diverifikasi</p>
                        <p class="mt-0.5 text-[0.66rem] font-medium leading-snug text-ink-soft">
                            Setiap sertifikat punya kode cek publik.
                        </p>
                    </div>
                </div>

                <div class="rounded-lg border-2 border-ink bg-paper p-3.5 shadow-[var(--shadow-flat-sm)]">
                    <p class="flex items-start gap-2 text-[0.68rem] font-semibold leading-relaxed text-ink-soft">
                        <x-icon name="info" class="mt-px size-4 shrink-0 text-teal-deep" :stroke="2" />
                        <span>
                            Tema karya: <strong class="text-ink">{{ $program['theme'] }}</strong> —
                            subtema {{ $program['subtheme'] }}.
                        </span>
                    </p>
                </div>
            </section>

            {{-- Panel kanan: formulir --}}
            <section class="rounded-xl border-2 border-ink bg-paper shadow-[var(--shadow-flat-xl)]">
                <div class="border-b-2 border-ink px-5 py-3.5">
                    <h2 class="font-display text-[1.02rem] font-extrabold leading-tight">Masuk ke portal</h2>
                    <p class="mt-0.5 text-[0.68rem] font-semibold text-ink-faint">
                        Gunakan NIM atau email kampus Anda.
                    </p>
                </div>

                <div class="p-5">
                    @if ($errors->any())
                        <div class="mb-4 flex items-start gap-2 rounded-sm border-2 border-vermilion bg-vermilion/10 p-2.5" role="alert">
                            <x-icon name="warning" class="mt-px size-4 shrink-0 text-vermilion-deep" :stroke="2" />
                            <p class="text-[0.7rem] font-bold leading-snug text-vermilion-deep">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}" class="flex flex-col gap-3.5" data-submit-lock>
                        @csrf

                        <div>
                            <label for="identity" class="block font-display text-[0.7rem] font-bold uppercase tracking-[0.08em]">
                                NIM / Email
                            </label>
                            <div class="relative mt-1">
                                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-faint">
                                    <x-icon name="user" class="size-4" :stroke="1.9" />
                                </span>
                                <input
                                    type="text"
                                    id="identity"
                                    name="identity"
                                    value="{{ old('identity') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    inputmode="text"
                                    aria-describedby="identity-hint"
                                    placeholder="202653033"
                                    class="w-full rounded-sm border-2 border-ink bg-white py-2 pl-8 pr-3 font-mono text-[0.82rem] font-bold text-ink placeholder:font-sans placeholder:font-medium placeholder:text-ink-faint/70 focus:outline-none"
                                >
                            </div>
                            <p id="identity-hint" class="mt-1 text-[0.7rem] font-medium leading-snug text-ink-soft">
                                NIM tercantum pada surat penerimaan mahasiswa baru.
                            </p>
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <label for="password" class="block font-display text-[0.7rem] font-bold uppercase tracking-[0.08em]">
                                    Kata Sandi
                                </label>
                                <button
                                    type="button"
                                    class="text-[0.64rem] font-bold text-ink-faint underline decoration-dotted underline-offset-2 transition hover:text-vermilion"
                                    onclick="document.getElementById('forgot-note').classList.toggle('hidden')"
                                >
                                    Lupa sandi?
                                </button>
                            </div>

                            <div class="relative mt-1">
                                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-faint">
                                    <x-icon name="lock" class="size-4" :stroke="1.9" />
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-sm border-2 border-ink bg-white py-2 pl-8 pr-10 font-sans text-[0.82rem] font-bold text-ink placeholder:text-ink-faint/70 focus:outline-none"
                                >
                                <button
                                    type="button"
                                    id="password-toggle"
                                    class="absolute right-2 top-1/2 grid size-6 -translate-y-1/2 place-items-center rounded-xs text-ink-faint transition hover:text-ink"
                                    aria-label="Tampilkan kata sandi"
                                    aria-pressed="false"
                                >
                                    <x-icon name="eye" class="size-4" :stroke="1.9" />
                                </button>
                            </div>

                            <p id="forgot-note" class="mt-1.5 hidden rounded-xs border-[1.5px] border-dashed border-ink/40 bg-paper-dim p-2 text-[0.64rem] font-semibold leading-snug text-ink-soft">
                                Hubungi mentor kelompok atau Administrasi Akademik Fakultas Teknik untuk pengaturan ulang kata sandi. Petugas tidak akan pernah meminta sandi Anda melalui pesan pribadi.
                            </p>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                class="size-4 rounded-xs border-2 border-ink accent-teal"
                            >
                            <span class="text-[0.7rem] font-semibold text-ink-soft">Ingat saya di perangkat ini</span>
                        </label>

                        <button type="submit" class="btn btn-primary w-full">
                            <x-icon name="arrow-right" class="size-4" :stroke="2.2" />
                            Masuk ke Portal
                        </button>
                    </form>

                    @if ($demoEnabled)
                        <div class="mt-4 border-t-2 border-dashed border-ink/25 pt-3.5">
                            <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">
                                Akses demonstrasi
                            </p>
                            <div class="mt-1.5 rounded-sm border-[1.5px] border-dashed border-ink/40 bg-paper-dim p-2.5">
                                <p class="font-mono text-[0.72rem] font-bold leading-relaxed text-ink-soft">
                                    NIM <span class="text-ink">{{ $demoNim }}</span> · Sandi
                                    <span class="text-ink">password</span>
                                </p>
                                <p class="mt-1 text-[0.66rem] font-medium leading-snug text-ink-faint">
                                    Akun contoh untuk meninjau alur portal sebelum pendaftaran dibuka.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        <footer class="mt-4 text-center">
            <p class="text-[0.62rem] font-semibold leading-relaxed text-ink-faint">
                {{ $program['name'] }} · {{ $program['study_program'] }}, {{ $program['faculty'] }},
                {{ $program['university'] }}
            </p>
        </footer>
    </div>

    <div id="toast-host" class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-[min(92vw,22rem)] flex-col gap-2"></div>

    <script type="module">
        const input = document.getElementById('password');
        const toggle = document.getElementById('password-toggle');

        toggle?.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(reveal));
            toggle.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    </script>
</body>
</html>