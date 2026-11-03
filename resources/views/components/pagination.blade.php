@props([
    'paginator',
    'label' => 'akun',
])

@php
    /*
     * Paginasi custom, berbahasa Indonesia.
     *
     * links() bawaan Laravel memakai bahasa Inggris ("Showing 1 to 10 of 3007
     * results") dan tombol Previous/Next. Untuk panitia yang tidak berbahasa
     * Inggris, itu satu lapisan kebingungan yang tidak perlu.
     *
     * Jendela nomor dibatasi: dengan 301 halaman, menampilkan semua nomor
     * membuat baris tombol meluap ke luar layar di ponsel.
     */
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $perPage = $paginator->perPage();
    $total = $paginator->total();

    $from = $total === 0 ? 0 : (($current - 1) * $perPage) + 1;
    $to = min($current * $perPage, $total);

    // Jendela: 1 ... 4 5 [6] 7 8 ... 30
    $window = 1;
    $pages = [];

    for ($i = 1; $i <= $last; $i++) {
        $isEdge = $i === 1 || $i === $last;
        $isNear = abs($i - $current) <= $window;

        if ($isEdge || $isNear) {
            $pages[] = $i;
        } elseif (end($pages) !== '…') {
            $pages[] = '…';
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between']) }}>
    <p class="font-mono text-[0.64rem] font-semibold text-ink-soft">
        Menampilkan
        <span class="font-bold text-ink">{{ number_format($from, 0, ',', '.') }}</span>–<span class="font-bold text-ink">{{ number_format($to, 0, ',', '.') }}</span>
        dari <span class="font-bold text-ink">{{ number_format($total, 0, ',', '.') }}</span> {{ $label }}
    </p>

    @if ($last > 1)
        <nav class="flex flex-wrap items-center gap-1" aria-label="Navigasi halaman">
            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="pg-btn pg-btn-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">
                    <x-icon name="arrow-left" class="size-3" :stroke="2.6" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pg-btn" aria-label="Halaman sebelumnya">
                    <x-icon name="arrow-left" class="size-3" :stroke="2.6" />
                </a>
            @endif

            @foreach ($pages as $page)
                @if ($page === '…')
                    <span class="pg-gap" aria-hidden="true">…</span>
                @elseif ($page === $current)
                    {{-- Halaman aktif: bukan tautan, jadi tidak bisa "diklik ke diri sendiri". --}}
                    <span class="pg-btn pg-btn-active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="pg-btn" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pg-btn" aria-label="Halaman berikutnya">
                    <x-icon name="arrow-right" class="size-3" :stroke="2.6" />
                </a>
            @else
                <span class="pg-btn pg-btn-disabled" aria-disabled="true" aria-label="Halaman berikutnya">
                    <x-icon name="arrow-right" class="size-3" :stroke="2.6" />
                </span>
            @endif
        </nav>
    @endif
</div>