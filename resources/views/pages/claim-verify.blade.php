@php
    $program = config('ospek.program');
    $status = $redemption?->status;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Klaim · {{ $program['name'] }}</title>

    <link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('favicon-16.png') }}" sizes="16x16" type="image/png">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas px-4 py-8 sm:py-12">
    <main class="mx-auto w-full max-w-lg">

        <div class="mb-5 flex items-center gap-2">
            <span class="stamp bg-ink text-paper">PANITIA</span>
            <p class="font-display text-sm font-bold text-ink-soft">Verifikasi klaim hadiah</p>
        </div>

        <div class="overflow-hidden rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat-lg)]">

            @if ($isValid)
                {{-- Pita status: warna mengikuti status klaim, bukan hiasan. --}}
                <div class="flex items-center justify-between gap-3 border-b-2 border-ink px-5 py-3
                    @if ($status === 'pending') bg-gold text-ink
                    @elseif ($status === 'fulfilled') bg-success text-white
                    @else bg-vermilion text-white @endif">
                    <p class="font-display text-base font-extrabold">
                        @if ($status === 'pending')
                            BELUM DISERAHKAN
                        @elseif ($status === 'fulfilled')
                            SUDAH DISERAHKAN
                        @else
                            DIBATALKAN
                        @endif
                    </p>
                    <span class="font-mono text-[0.7rem] font-bold">{{ $code }}</span>
                </div>

                <div class="p-5">
                    <p class="font-mono text-[0.62rem] font-bold uppercase tracking-[0.14em] text-ink-faint">
                        {{ $reward?->category ?? 'hadiah' }}
                    </p>
                    <h1 class="mt-1 font-display text-2xl font-extrabold leading-tight tracking-tight">
                        {{ $reward?->name ?? 'Hadiah tidak ditemukan' }}
                    </h1>

                    @if ($reward?->description)
                        <p class="mt-1.5 text-sm text-ink-soft">{{ $reward->description }}</p>
                    @endif

                    <dl class="mt-5 space-y-2.5 border-t-2 border-dashed border-ink/20 pt-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-soft">Penerima</dt>
                            <dd class="text-right font-semibold">{{ $owner?->name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-soft">NIM</dt>
                            <dd class="text-right font-mono font-bold">{{ $owner?->nim ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-soft">Gugus</dt>
                            <dd class="text-right font-semibold">{{ $owner?->group_name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-soft">Koin terpakai</dt>
                            <dd class="text-right font-mono font-bold">{{ number_format($redemption->coins_spent, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-soft">Ditukar pada</dt>
                            <dd class="text-right font-semibold">{{ $redemption->created_at?->translatedFormat('j M Y, H:i') }}</dd>
                        </div>
                        @if ($redemption->fulfilled_at)
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-soft">Diserahkan pada</dt>
                                <dd class="text-right font-semibold">{{ $redemption->fulfilled_at->translatedFormat('j M Y, H:i') }}</dd>
                            </div>
                        @endif
                        @if ($redemption->cancelled_reason)
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-soft">Alasan batal</dt>
                                <dd class="text-right font-semibold">{{ $redemption->cancelled_reason }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($status === 'fulfilled')
                        <p class="mt-5 rounded-sm border-2 border-ink bg-paper-dim p-3 text-xs leading-relaxed text-ink-soft">
                            Klaim ini <strong>sudah diserahkan</strong>. Jangan serahkan barang kedua kali
                            untuk klaim yang sama.
                        </p>
                    @elseif ($status === 'cancelled')
                        <p class="mt-5 rounded-sm border-2 border-ink bg-paper-dim p-3 text-xs leading-relaxed text-ink-soft">
                            Klaim ini <strong>dibatalkan</strong>; koin sudah dikembalikan ke mahasiswa.
                            Jangan serahkan barang.
                        </p>
                    @endif
                </div>
            @else
                <div class="p-6 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full border-2 border-ink bg-vermilion/15">
                        <x-icon name="x" class="size-7 text-vermilion-deep" :stroke="2.6" />
                    </span>
                    <h1 class="mt-4 font-display text-xl font-extrabold tracking-tight">Kode klaim tidak dikenal</h1>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-ink-soft">
                        Kode <span class="font-mono font-bold">{{ $code }}</span> tidak ada di sistem.
                        Periksa kembali — kode berbentuk <span class="font-mono">OQ-XXXX-XXXX</span>
                        dan peka terhadap salah ketik.
                    </p>
                </div>
            @endif
        </div>

        <p class="mt-5 text-center text-xs text-ink-faint">
            {{ $program['name'] }} · {{ $program['university'] }}
        </p>
    </main>
</body>
</html>
