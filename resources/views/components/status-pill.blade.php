@props([
    'status',
    'reason' => null,
    'compact' => false,
])

@php
    $map = [
        'completed' => ['label' => 'Selesai', 'icon' => 'check-circle', 'class' => 'bg-teal text-white'],
        'in_progress' => ['label' => 'Berjalan', 'icon' => 'play', 'class' => 'bg-amber text-ink'],
        'available' => ['label' => 'Siap', 'icon' => 'sparkle', 'class' => 'bg-paper text-ink'],
        'locked' => ['label' => 'Terkunci', 'icon' => 'lock', 'class' => 'bg-paper-dim text-ink-faint'],
    ];

    $key = $status instanceof \App\Enums\QuestStatus ? $status->value : (string) $status;
    $state = $map[$key] ?? $map['locked'];
@endphp

<span
    {{ $attributes->merge(['class' => 'stamp '.$state['class'].($compact ? ' px-1.5 py-0' : '')]) }}
    @if ($reason) title="{{ $reason }}" @endif
>
    <x-icon :name="$state['icon']" class="size-3" :stroke="2.2" />
    {{ $state['label'] }}
</span>