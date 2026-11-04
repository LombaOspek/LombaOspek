@extends('layouts.app')

@section('title', 'Jadwal Orientasi')
@section('page-title', 'Jadwal Orientasi')
@section('page-kicker', 'Acara per hari dan modul yang menyertainya')

@section('content')
    @php
        $statusMeta = [
            'done' => ['label' => 'Selesai', 'tone' => 'bg-success text-white', 'icon' => 'check'],
            'open' => ['label' => 'Terbuka', 'tone' => 'bg-gold text-ink', 'icon' => 'play'],
            'locked' => ['label' => 'Terkunci', 'tone' => 'bg-paper-dim text-ink-faint', 'icon' => 'lock'],
        ];
    @endphp

    {{-- Ringkasan hari ini + langkah berikutnya setelah acara --}}
    <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b-2 border-ink/10 p-4">
            <div class="min-w-0">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.14em] text-ink-faint">
                    Hari ini
                </p>
                <h2 class="mt-0.5 font-display text-xl font-extrabold leading-tight tracking-tight">
                    Hari ke-{{ $today }} dari {{ $totalDays }}
                </h2>
                <p class="mt-1 text-[0.74rem] font-medium text-ink-soft">
                    @if ($orientationOver)
                        Rangkaian orientasi sudah berakhir. Seluruh agenda di bawah tersimpan sebagai arsip.
                    @else
                        {{ $todayItems->count() }} agenda hari ini.
                        @if ($nextAction)
                            Setelah acara, kerjakan modul berikutnya.
                        @endif
                    @endif
                </p>
            </div>

            @if ($nextAction)
                <a
                    href="{{ route('quests.show', $nextAction['quest']) }}"
                    class="btn btn-primary btn-sm shrink-0"
                >
                    <x-icon name="play" class="size-3.5" :stroke="2.4" />
                    {{ $nextAction['quest']->title }}
                </a>
            @endif
        </div>

        {{-- Agenda hari ini, ditonjolkan: inilah yang dicari mahasiswa pagi-pagi. --}}
        @if ($todayItems->isNotEmpty())
            <ul class="divide-y-2 divide-ink/10">
                @foreach ($todayItems as $item)
                    <li class="flex flex-wrap items-start gap-3 p-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-sm border-2 border-ink bg-brand text-white">
                            <x-icon :name="$item['schedule']->kind_icon" class="size-5" :stroke="2" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-[0.6rem] font-bold uppercase tracking-[0.1em] text-brand-deep">
                                {{ $item['schedule']->time_range ?? 'Sepanjang hari' }}
                                · {{ $item['schedule']->kind_label }}
                            </p>
                            <p class="mt-0.5 font-display text-[0.9rem] font-extrabold leading-snug">
                                {{ $item['schedule']->title }}
                            </p>
                            @if ($item['schedule']->location || $item['schedule']->speaker)
                                <p class="mt-0.5 text-[0.7rem] font-medium text-ink-soft">
                                    @if ($item['schedule']->location)
                                        <x-icon name="pin" class="inline size-3 -translate-y-px" :stroke="2" />
                                        {{ $item['schedule']->location }}
                                    @endif
                                    @if ($item['schedule']->speaker)
                                        · {{ $item['schedule']->speaker }}
                                    @endif
                                </p>
                            @endif

                            @if ($item['quest'])
                                @php $meta = $statusMeta[$item['quest_status']] ?? $statusMeta['locked']; @endphp
                                <a
                                    href="{{ route('quests.show', $item['quest']) }}"
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-sm border-2 border-ink px-2 py-1 text-[0.62rem] font-extrabold transition hover:-translate-y-px hover:shadow-[var(--shadow-flat-sm)] {{ $meta['tone'] }}"
                                >
                                    <x-icon :name="$meta['icon']" class="size-3" :stroke="2.6" />
                                    Modul: {{ $item['quest']->title }}
                                    <span class="opacity-70">· {{ $meta['label'] }}</span>
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="p-4 text-[0.76rem] font-semibold text-ink-faint">
                Tidak ada agenda terjadwal untuk hari ini.
            </p>
        @endif
    </section>

    {{-- Seluruh rangkaian 8 hari --}}
    <section class="mt-4 space-y-3">
        <h2 class="font-display text-lg font-extrabold tracking-tight">Rangkaian lengkap</h2>

        @foreach ($days as $day)
            @php
                $count = $counts[$day['day']] ?? 0;
                $isToday = $day['is_today'];
            @endphp

            <article
                id="hari-{{ $day['day'] }}"
                class="overflow-hidden rounded-lg border-2 bg-paper shadow-[var(--shadow-flat-sm)] {{ $isToday ? 'border-brand' : 'border-ink' }}"
            >
                <header class="flex flex-wrap items-center justify-between gap-2 border-b-2 border-ink/10 px-4 py-2.5 {{ $isToday ? 'bg-brand/10' : ($day['is_past'] ? 'bg-paper-dim/60' : 'bg-paper') }}">
                    <div class="flex items-center gap-2">
                        <span class="grid size-8 place-items-center rounded-sm border-2 border-ink font-display text-[0.78rem] font-extrabold {{ $isToday ? 'bg-brand text-white' : 'bg-paper-dim text-ink' }}">
                            {{ $day['day'] }}
                        </span>
                        <p class="font-display text-[0.86rem] font-extrabold leading-tight">
                            Hari ke-{{ $day['day'] }}
                            @if ($isToday)
                                <span class="stamp ml-1 bg-gold text-ink">Hari ini</span>
                            @elseif ($day['is_past'])
                                <span class="ml-1 text-[0.6rem] font-bold uppercase tracking-wider text-ink-faint">Lewat</span>
                            @endif
                        </p>
                    </div>

                    <p class="font-mono text-[0.6rem] font-bold uppercase tracking-[0.1em] text-ink-faint">
                        {{ $count }} agenda
                    </p>
                </header>

                @if ($day['items']->isEmpty())
                    <p class="px-4 py-3 text-[0.72rem] font-semibold text-ink-faint">
                        Belum ada agenda pada hari ini.
                    </p>
                @else
                    <ul class="divide-y divide-ink/10">
                        @foreach ($day['items'] as $item)
                            <li class="flex flex-wrap items-start gap-2.5 px-4 py-2.5">
                                <span class="w-20 shrink-0 font-mono text-[0.64rem] font-bold text-ink-soft">
                                    {{ $item['schedule']->time_range ?? '—' }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="font-display text-[0.78rem] font-bold leading-snug">
                                        {{ $item['schedule']->title }}
                                    </p>
                                    <p class="mt-0.5 text-[0.64rem] font-medium text-ink-faint">
                                        {{ $item['schedule']->kind_label }}
                                        @if ($item['schedule']->location)
                                            · {{ $item['schedule']->location }}
                                        @endif
                                    </p>

                                    @if ($item['quest'])
                                        @php $meta = $statusMeta[$item['quest_status']] ?? $statusMeta['locked']; @endphp
                                        <a
                                            href="{{ route('quests.show', $item['quest']) }}"
                                            class="mt-1 inline-flex items-center gap-1 rounded-xs border border-ink/30 bg-paper-dim px-1.5 py-0.5 text-[0.58rem] font-bold transition hover:border-ink hover:bg-gold/30"
                                        >
                                            <x-icon :name="$meta['icon']" class="size-2.5" :stroke="2.6" />
                                            {{ $item['quest']->code }} · {{ $meta['label'] }}
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @endforeach
    </section>
@endsection
