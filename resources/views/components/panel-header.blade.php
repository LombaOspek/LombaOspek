@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'action' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3 border-b-2 border-ink/15 pb-3']) }}>
    <div class="flex items-center gap-2.5">
        @if ($icon)
            <span class="grid size-9 shrink-0 place-items-center rounded-sm border-2 border-ink bg-paper shadow-[var(--shadow-flat-sm)]">
                <x-icon :name="$icon" class="size-[1.15rem]" />
            </span>
        @endif
        <div>
            <h2 class="font-display text-[0.95rem] font-bold leading-tight">{{ $title }}</h2>
            @if ($subtitle)
                <p class="text-[0.72rem] font-medium leading-snug text-ink-faint">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if ($action)
        <a href="{{ $action }}" class="inline-flex items-center gap-1 font-display text-[0.72rem] font-bold text-ink underline decoration-2 underline-offset-4 transition hover:text-vermilion">
            {{ $actionLabel ?? 'Lihat semua' }}
            <x-icon name="arrow-right" class="size-3.5" :stroke="2.2" />
        </a>
    @endif

    {{ $slot }}
</div>