@php
    $program = config('ospek.program');
    $signatories = config('ospek.signatories');
    $issued = $issuedAt ?? now();

    // Saat APP_URL masih localhost (development), tampilkan URL verifikasi
    // publik yang netral di sertifikat agar dokumen tidak memuat alamat lokal.
    $verifyUrl = url('/verifikasi/'.$status['code']);
    $publicUrl = str_contains($verifyUrl, 'localhost') || str_contains($verifyUrl, '127.0.0.1')
        ? 'ospekquest.umk.ac.id/verifikasi/'.$status['code']
        : $verifyUrl;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sertifikat · {{ $user->name }}</title>
    <meta name="robots" content="noindex">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        .cert-serif { font-family: 'Cormorant Garamond', Georgia, serif; }

        @media print {
            @page { size: A4 landscape; margin: 6mm; }

            /* Isi sertifikat mengisi tinggi halaman cetak supaya tidak ada
               sisa ruang kosong besar di bawah dokumen. */
            html, body {
                height: 100%;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            body {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .cert-sheet {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-canvas p-4 sm:p-6 print:bg-white print:p-0">

    {{-- Toolbar --}}
    <div class="no-print mx-auto mb-4 flex w-full max-w-5xl flex-wrap items-center justify-between gap-2">
        <a href="{{ route('achievements') }}" class="btn btn-ghost btn-sm">
            <x-icon name="arrow-left" class="size-3.5" :stroke="2.2" />
            Pencapaian
        </a>

        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('certificate.verify', $status['code']) }}" class="btn btn-ghost btn-sm">
                <x-icon name="shield" class="size-3.5" :stroke="2" />
                Halaman verifikasi
            </a>
            @if ($status['eligible'])
                <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
                    <x-icon name="certificate" class="size-3.5" :stroke="2" />
                    Cetak / Simpan PDF
                </button>
            @else
                <span class="stamp bg-vermilion/15 text-vermilion-deep">
                    <x-icon name="lock" class="size-3" :stroke="2.2" />
                    Terkunci
                </span>
            @endif
        </div>
    </div>

    @unless ($status['eligible'])
        <div class="no-print mx-auto mb-4 w-full max-w-5xl rounded-lg border-2 border-ink bg-amber/25 p-3.5 shadow-[var(--shadow-flat-sm)]">
            <p class="flex items-start gap-2 text-[0.74rem] font-semibold leading-relaxed text-ink-soft">
                <x-icon name="warning" class="mt-px size-4 shrink-0 text-vermilion-deep" :stroke="2" />
                <span>
                    Sertifikat baru dapat dicetak setelah seluruh {{ $status['total'] }} modul selesai.
                    Progres Anda saat ini <strong class="text-ink">{{ $status['completed'] }}/{{ $status['total'] }}</strong> —
                    kurang {{ $status['missing'] }} modul lagi.
                    Halaman ini tetap ditampilkan sebagai pratinjau.
                </span>
            </p>
        </div>
    @endunless

    {{-- Lembar sertifikat --}}
    <article class="cert-sheet relative mx-auto w-full max-w-5xl overflow-hidden rounded-lg border-[3px] border-ink bg-paper p-1 shadow-[var(--shadow-flat-xl)] print:shadow-none">
        {{-- Bingkai ganda --}}
        <div class="relative border-2 border-ink/70 p-5 sm:p-8">
            {{-- Ornamen sudut geometris, bukan emoji --}}
            <div class="pointer-events-none absolute left-2 top-2 size-6 border-l-2 border-t-2 border-ink/60"></div>
            <div class="pointer-events-none absolute right-2 top-2 size-6 border-r-2 border-t-2 border-ink/60"></div>
            <div class="pointer-events-none absolute bottom-2 left-2 size-6 border-b-2 border-l-2 border-ink/60"></div>
            <div class="pointer-events-none absolute bottom-2 right-2 size-6 border-b-2 border-r-2 border-ink/60"></div>

            {{-- Kepala --}}
            <header class="flex flex-col items-center text-center">
                <div class="flex items-center gap-2">
                    <span class="grid size-9 place-items-center rounded-sm border-2 border-ink bg-amber">
                        <x-icon name="compass" class="size-5" :stroke="2" />
                    </span>
                    <div class="text-left">
                        <p class="font-display text-[0.82rem] font-extrabold uppercase leading-none tracking-[0.1em]">
                            {{ $program['university'] }}
                        </p>
                        <p class="mt-0.5 text-[0.58rem] font-bold uppercase tracking-[0.14em] text-ink-faint">
                            {{ $program['faculty'] }} · {{ $program['study_program'] }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 h-0.5 w-24 bg-ink"></div>

                <h1 class="cert-serif mt-3 text-[1.7rem] font-bold uppercase leading-none tracking-[0.06em] sm:text-[2.3rem]">
                    Sertifikat Orientasi
                </h1>
                <p class="mt-1 text-[0.6rem] font-extrabold uppercase tracking-[0.2em] text-ink-faint">
                    Mahasiswa Baru · Tahun Akademik {{ $program['academic_year'] }}
                </p>
            </header>

            {{-- Badan --}}
            <div class="mx-auto mt-5 max-w-2xl text-center">
                <p class="text-[0.66rem] font-semibold uppercase tracking-[0.14em] text-ink-faint">
                    Diberikan kepada
                </p>

                <p class="cert-serif mt-1.5 text-[1.6rem] font-bold leading-tight sm:text-[2.1rem]">
                    {{ $user->name }}
                </p>

                <p class="mt-0.5 font-mono text-[0.72rem] font-bold text-ink-faint">
                    NIM {{ $user->nim }}
                </p>

                <p class="mt-3 text-[0.74rem] font-medium leading-relaxed text-ink-soft text-pretty">
                    atas keberhasilan menyelesaikan seluruh rangkaian
                    <strong class="text-ink">{{ $program['name'] }}</strong> — program orientasi berbasis gamifikasi
                    {{ $program['study_program'] }}, {{ $program['faculty'] }},
                    {{ $program['university'] }} —
                    dengan menyelesaikan {{ $completedQuests }} modul, kuis akademik, dan refleksi yang dipersyaratkan.
                </p>

                <div class="mt-4 flex flex-wrap items-center justify-center gap-1.5">
                    <span class="stamp bg-amber/40">Tema: Smart Ecosystems</span>
                    <span class="stamp bg-paper-dim">Subtema: Smart Education</span>
                    <span class="stamp bg-teal/20">Peringkat {{ $rank }} / {{ $participants }}</span>
                </div>
            </div>

            {{-- Tanda tangan & segel --}}
            <div class="mt-6 grid items-end gap-4 border-t-2 border-dashed border-ink/30 pt-4 sm:grid-cols-3">
                <div class="text-center sm:text-left">
                    <p class="cert-serif text-[0.95rem] font-bold italic leading-tight">{{ $signatories['academic']['name'] }}</p>
                    <div class="mx-auto mt-1 h-px w-40 bg-ink sm:mx-0"></div>
                    <p class="mt-1 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">{{ $signatories['academic']['role'] }}</p>
                    <p class="text-[0.54rem] font-semibold text-ink-faint">{{ $signatories['academic']['identifier'] }}</p>
                </div>

                <div class="flex flex-col items-center">
                    {{-- Segel: geometris, bukan gradient blur --}}
                    <div class="relative grid size-20 place-items-center rounded-full border-[3px] border-ink bg-amber">
                        <div class="absolute inset-1.5 rounded-full border border-dashed border-ink/60"></div>
                        <div class="text-center">
                            <x-icon name="shield" class="mx-auto size-5" :stroke="2" />
                            <p class="mt-0.5 text-[0.42rem] font-extrabold uppercase leading-none tracking-[0.1em]">Sah</p>
                        </div>
                    </div>
                    <p class="mt-1.5 font-mono text-[0.58rem] font-bold text-ink-faint">
                        {{ $status['code'] }}
                    </p>
                </div>

                <div class="text-center sm:text-right">
                    <p class="cert-serif text-[0.95rem] font-bold italic leading-tight">{{ $signatories['committee']['name'] }}</p>
                    <div class="mx-auto mt-1 h-px w-40 bg-ink sm:ml-auto sm:mr-0"></div>
                    <p class="mt-1 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">{{ $signatories['committee']['role'] }}</p>
                    <p class="text-[0.54rem] font-semibold text-ink-faint">{{ $signatories['committee']['identifier'] }}</p>
                </div>
            </div>

            {{-- Kaki --}}
            <footer class="mt-4 flex flex-col items-center justify-between gap-1 border-t-2 border-ink/15 pt-2.5 text-center sm:flex-row sm:text-left">
                <p class="text-[0.56rem] font-semibold text-ink-faint">
                    Diterbitkan {{ $issued->translatedFormat('d F Y') }} · Kudus, Jawa Tengah
                </p>
                <p class="font-mono text-[0.54rem] font-bold text-ink-faint">
                    Verifikasi: {{ $publicUrl }}
                </p>
            </footer>
        </div>
    </article>

    <p class="no-print mx-auto mt-3 max-w-5xl text-center text-[0.62rem] font-semibold leading-relaxed text-ink-faint">
        Gunakan "Cetak / Simpan PDF" lalu pilih <em>Landscape</em> dan nonaktifkan header-footer browser untuk hasil terbaik.
    </p>
</body>
</html>