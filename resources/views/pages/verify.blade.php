@php
    $program = config('ospek.program');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Sertifikat · {{ $program['name'] }}</title>
    <meta name="robots" content="index">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="paper-dots min-h-screen">
    <div class="mx-auto flex min-h-screen w-full max-w-2xl flex-col px-4 py-6 sm:px-6">

        <header class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-sm border-2 border-ink bg-amber">
                    <x-icon name="shield" class="size-5" :stroke="2" />
                </span>
                <span>
                    <span class="block font-display text-[0.9rem] font-extrabold leading-none">Verifikasi Sertifikat</span>
                    <span class="mt-0.5 block text-[0.54rem] font-extrabold uppercase tracking-[0.13em] text-ink-faint">
                        {{ $program['name'] }}
                    </span>
                </span>
            </span>
        </header>

        <main class="my-auto flex flex-col gap-3 py-6">

            {{-- Formulir pencarian kode --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <form method="GET" action="{{ route('certificate.verify') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="code" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">
                            Kode sertifikat
                        </label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            value="{{ $code }}"
                            required
                            placeholder="OSQ-26-XXXXXXXX"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 font-mono text-[0.8rem] font-bold uppercase tracking-[0.08em] focus:outline-none"
                        >
                    </div>
                    <button type="submit" class="btn btn-primary shrink-0">
                        <x-icon name="search" class="size-4" :stroke="2.2" />
                        Periksa
                    </button>
                </form>
                <p class="mt-1.5 text-[0.62rem] font-medium leading-snug text-ink-faint">
                    Kode tertera di bagian bawah sertifikat yang diterbitkan sistem.
                </p>
            </section>

            @if ($code !== null)
                @if ($isValid && $subject)
                    <section class="overflow-hidden rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat-lg)]">
                        <div class="flex items-center gap-2.5 border-b-2 border-ink bg-teal px-4 py-3 text-white">
                            <span class="grid size-8 place-items-center rounded-full border-2 border-white/70">
                                <x-icon name="check" class="size-4" :stroke="3" />
                            </span>
                            <div>
                                <p class="font-display text-[0.86rem] font-extrabold leading-tight">Sertifikat sah</p>
                                <p class="text-[0.62rem] font-semibold text-white/85">Kode terdaftar pada sistem orientasi.</p>
                            </div>
                        </div>

                        <dl class="divide-y-2 divide-ink/10">
                            <div class="flex items-baseline justify-between gap-3 px-4 py-2.5">
                                <dt class="text-[0.64rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Nama</dt>
                                <dd class="font-display text-[0.82rem] font-bold">{{ $subject['name'] }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 px-4 py-2.5">
                                <dt class="text-[0.64rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">NIM</dt>
                                <dd class="font-mono text-[0.78rem] font-bold">{{ $subject['nim'] }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 px-4 py-2.5">
                                <dt class="text-[0.64rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Program Studi</dt>
                                <dd class="text-[0.76rem] font-bold">{{ $subject['study_program'] }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 px-4 py-2.5">
                                <dt class="text-[0.64rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Modul selesai</dt>
                                <dd class="numeric text-[0.76rem] font-bold">{{ $subject['completed_quests'] }} / {{ $subject['total_quests'] }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 px-4 py-2.5">
                                <dt class="text-[0.64rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Tanggal terbit</dt>
                                <dd class="text-[0.76rem] font-bold">{{ $subject['completed_at']?->translatedFormat('d F Y') }}</dd>
                            </div>
                        </dl>

                        <div class="border-t-2 border-ink/10 px-4 py-2.5">
                            <p class="text-[0.6rem] font-medium leading-snug text-ink-faint">
                                Halaman ini hanya menampilkan data kelulusan orientasi. Data pribadi lain tidak dipublikasikan.
                            </p>
                        </div>
                    </section>
                @else
                    <section class="rounded-lg border-2 border-vermilion bg-vermilion/10 p-4 shadow-[var(--shadow-flat)]">
                        <div class="flex items-start gap-2.5">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full border-2 border-ink bg-vermilion text-white">
                                <x-icon name="x" class="size-4" :stroke="3" />
                            </span>
                            <div>
                                <p class="font-display text-[0.86rem] font-extrabold leading-tight">Kode tidak ditemukan</p>
                                <p class="mt-0.5 text-[0.72rem] font-medium leading-relaxed text-ink-soft">
                                    Kode <span class="font-mono font-bold">{{ $code }}</span> tidak terdaftar sebagai sertifikat yang sudah diterbitkan.
                                    Periksa kembali penulisan kode, atau hubungi panitia orientasi bila Anda yakin sertifikat sudah diterbitkan.
                                </p>
                            </div>
                        </div>
                    </section>
                @endif
            @else
                <section class="rounded-lg border-2 border-dashed border-ink/40 bg-paper p-4">
                    <p class="flex items-start gap-2 text-[0.72rem] font-medium leading-relaxed text-ink-soft">
                        <x-icon name="info" class="mt-px size-4 shrink-0 text-teal-deep" :stroke="2" />
                        <span>Masukkan kode sertifikat untuk memeriksa keabsahan. Sistem akan menampilkan nama, NIM, dan jumlah modul yang diselesaikan.</span>
                    </p>
                </section>
            @endif
        </main>

        <footer class="text-center">
            <p class="text-[0.6rem] font-semibold leading-relaxed text-ink-faint">
                {{ $program['university'] }} · {{ $program['study_program'] }}
            </p>
            <a href="{{ route('login') }}" class="mt-1 inline-block text-[0.62rem] font-bold text-ink underline decoration-dotted underline-offset-2">
                Masuk ke portal
            </a>
        </footer>
    </div>
</body>
</html>