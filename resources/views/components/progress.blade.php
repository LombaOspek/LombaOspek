@props([
    'value' => 0,
    'tone' => 'teal',
    'height' => 'h-2.5',
    'label' => null,
])

@php
    $toneClass = match ($tone) {
        'amber' => 'bg-amber',
        'vermilion' => 'bg-vermilion',
        'ink' => 'bg-ink',
        default => 'bg-teal',
    };
    $width = max(0, min(100, (int) $value));
@endphp

<div
    {{ $attributes->merge(['class' => 'rail '.$height]) }}
    role="progressbar"
    aria-valuenow="{{ $width }}"
    aria-valuemin="0"
    aria-valuemax="100"
    @if ($label) aria-label="{{ $label }}" @endif
>
    <span class="{{ $toneClass }}" style="width: {{ $width }}%"></span>
</div>