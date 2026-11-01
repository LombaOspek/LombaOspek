@props(['value', 'label', 'icon', 'tone' => 'paper', 'caption' => null, 'counter' => null])

@php
    $toneMap = [
        'gold', 'amber' => 'bg-gold',
        'brand', 'teal' => 'bg-brand text-white',
        'brand-soft', 'sky' => 'bg-brand-soft',
        'paper' => 'bg-paper',
        'vermilion' => 'bg-vermilion text-white',
        'success' => 'bg-success text-white',
    ];
    $classes = $toneMap[$tone] ?? $toneMap['paper'];
@endphp

<div {{ $attributes->merge(['class' => "relative overflow-hidden rounded-lg border-2 border-ink px-3.5 py-3 shadow-[var(--shadow-flat)] {$classes}"]) }}>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.13em] opacity-70">{{ $label }}</p>
            <p class="numeric mt-1 text-[1.45rem] font-bold leading-none" @if ($counter !== null) data-counter="{{ $counter }}" data-xp-value @endif>{{ $value }}</p>
            @if ($caption)
                <p class="mt-1 text-[0.66rem] font-semibold leading-snug opacity-75 [text-wrap:pretty]">{{ $caption }}</p>
            @endif
        </div>
        <span class="grid size-7 shrink-0 place-items-center rounded-sm border-[1.5px] border-current/40 bg-black/5">
            <x-icon :name="$icon" class="size-4" :stroke="1.9" />
        </span>
    </div>
</div>