@php
    $program = config('ospek.program');
@endphp
<!DOCTYPE html>
<html lang="id"
      data-sound="{{ auth()->user()?->sound_effects ? '1' : '0' }}"
      data-level="{{ auth()->user()->level ?? 1 }}"
      data-level-title="{{ auth()->user()->level_title ?? 'Maba Pemula' }}"
      data-xp="{{ auth()->user()->xp ?? 0 }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        // Gelar level dikirim sekali ke klien agar overlay Level Up tidak
        // menduplikasi daftar gelar yang sudah ada di config.
        $levelTitles = collect(config('ospek.levels'))->map(fn ($band) => $band['title'])->all();
    @endphp
    <meta name="level-titles" content="{{ json_encode($levelTitles, JSON_UNESCAPED_UNICODE) }}">
    <meta name="theme-color" content="#2758a3">
    <meta name="description" content="{{ $program['name'] }} — {{ $program['tagline'] }} {{ $program['university'] }}. Selesaikan modul orientasi, kumpulkan XP, dan raih sertifikat resmi.">

    <title>@yield('title', 'Portal Orientasi') · {{ $program['name'] }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-canvas">
    <a href="#main" class="sr-only-focusable absolute left-4 top-4 z-50 rounded-sm border-2 border-ink bg-gold px-3 py-2 font-display text-sm font-bold">
        Lompat ke konten utama
    </a>

    <div class="mx-auto flex min-h-screen w-full max-w-[1560px] flex-col gap-0 lg:flex-row lg:gap-0 lg:p-3 xl:p-5">
        @include('partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col lg:ml-0">
            @include('partials.topbar')

            <main id="main" class="flex-1 border-ink px-4 pb-10 pt-4 sm:px-6 lg:border-x-2 lg:border-b-2 lg:bg-shell lg:px-7 lg:pb-8 lg:pt-6 lg:shadow-[var(--shadow-flat)] first:lg:rounded-none">
                @yield('content')
            </main>

            @include('partials.footer')
        </div>
    </div>

    {{-- Notifikasi sesi: ditampilkan sekali saat halaman dimuat. --}}
    <div id="toast-host" class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-[min(92vw,22rem)] flex-col gap-2"></div>

    {{-- Penanda tone flash: dipakai halaman untuk memicu efek reward. --}}
    @if (session('toast'))
        <span hidden data-flash-tone="{{ session('toast.tone', 'neutral') }}"></span>
    @endif

    @if (session('toast'))
        @push('scripts')
            <script type="application/json" id="session-toast">@json(session('toast'))</script>
            <script type="module">
                const payload = JSON.parse(document.getElementById('session-toast').textContent);
                window.Ospek?.toast(payload);
            </script>
        @endpush
    @endif

    @stack('scripts')
</body>
</html>