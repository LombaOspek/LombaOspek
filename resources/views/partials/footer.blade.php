@php $program = config('ospek.program'); @endphp

<footer class="border-ink px-4 py-3 text-center lg:rounded-b-xl lg:border-2 lg:border-t-0 lg:bg-paper lg:px-6">
    <p class="text-[0.62rem] font-semibold leading-relaxed text-ink-faint">
        <span class="font-display font-bold text-ink">{{ $program['name'] }}</span>
        · {{ $program['study_program'] }}, {{ $program['faculty'] }}, {{ $program['university'] }}
        <span class="mx-1 hidden sm:inline">—</span>
        <span class="block sm:inline">{{ $program['subtheme'] }}</span>
    </p>
</footer>