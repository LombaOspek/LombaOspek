@extends('layouts.app')

@section('title', 'Pencapaian')
@section('page-title', 'Pencapaian & Lencana')
@section('page-kicker', $unlockedCount.' / '.$totalAchievements.' lencana terbuka')

@section('content')
<div class="flex flex-col gap-4">

    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-display text-[1.45rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                Yang sudah Anda raih.
            </h1>
            <p class="mt-0.5 max-w-xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Lencana dihitung otomatis dari data nyata — modul selesai, kuis lulus, rentetan, dan tiket terjawab.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <span class="stamp bg-paper">
                <x-icon name="check-circle" class="size-3" :stroke="2.2" />
                {{ $unlockedCount }} selesai
            </span>
            <span class="stamp bg-paper">
                <x-icon name="trending" class="size-3" :stroke="2.2" />
                {{ $inProgressCount }} berjalan
            </span>
            <span class="stamp bg-paper">
                <x-icon name="lock" class="size-3" :stroke="2.2" />
                {{ $lockedCount }} terkunci
            </span>
        </div>
    </section>

    {{-- Ringkasan metrik nyata --}}
    <section class="grid grid-cols-2 gap-2.5 lg:grid-cols-4" aria-label="Metrik pencapaian">
        <x-stat-tile :value="$metrics['quests_completed']" label="Modul Selesai" icon="map" tone="teal" />
        <x-stat-tile :value="$metrics['quizzes_passed']" label="Kuis Lulus" icon="quiz" tone="paper" />
        <x-stat-tile :value="$metrics['streak_days'].' hari'" label="Rentetan" icon="flame" tone="amber" />
        <x-stat-tile :value="number_format($metrics['total_xp'], 0, ',', '.')" label="Total XP" icon="bolt" tone="paper" :caption="'+'.$badgeXpTotal.' XP dari lencana'" />
    </section>

    {{-- Sertifikat --}}
    <section class="overflow-hidden rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat-lg)]">
        <div class="grid gap-0 sm:grid-cols-[1fr_auto]">
            <div class="p-4 sm:p-5">
                <span class="stamp {{ $certificate['eligible'] ? 'bg-teal text-white' : 'bg-paper-dim' }}">
                    <x-icon :name="$certificate['eligible'] ? 'check-circle' : 'lock'" class="size-3" :stroke="2.2" />
                    {{ $certificate['eligible'] ? 'Siap diunduh' : 'Belum memenuhi syarat' }}
                </span>

                <h2 class="mt-2 font-display text-[1.1rem] font-extrabold leading-tight">
                    Sertifikat Orientasi Mahasiswa Baru
                </h2>
                <p class="mt-1 max-w-lg text-[0.76rem] font-medium leading-relaxed text-ink-soft">
                    @if ($certificate['eligible'])
                        Seluruh {{ $certificate['total'] }} modul selesai. Sertifikat Anda memuat kode verifikasi publik yang dapat dicek pihak lain.
                    @else
                        Selesaikan {{ $certificate['missing'] }} modul lagi ({{ $certificate['completed'] }}/{{ $certificate['total'] }}) untuk membuka sertifikat.
                    @endif
                </p>

                <div class="mt-2.5 flex items-center gap-2">
                    <x-progress :value="$certificate['percentage']" tone="{{ $certificate['eligible'] ? 'teal' : 'amber' }}" class="max-w-xs flex-1" label="Syarat sertifikat" />
                    <span class="numeric text-[0.7rem] font-bold">{{ $certificate['percentage'] }}%</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($certificate['eligible'])
                        <a href="{{ route('certificate') }}" class="btn btn-primary">
                            <x-icon name="certificate" class="size-4" :stroke="2" />
                            Buka Sertifikat
                        </a>
                    @else
                        <a href="{{ route('quests.index') }}" class="btn btn-primary">
                            <x-icon name="map" class="size-4" :stroke="2" />
                            Lanjutkan Modul
                        </a>
                    @endif
                    <a href="{{ route('certificate.verify', $certificate['code']) }}" class="btn btn-ghost btn-sm">
                        <x-icon name="shield" class="size-3.5" :stroke="2" />
                        Cek kode verifikasi
                    </a>
                </div>
            </div>

            {{-- Tiket sobek dekoratif --}}
            <div class="hidden w-24 flex-col items-center justify-center border-l-2 border-dashed border-ink/40 bg-amber/25 p-3 sm:flex">
                <span class="grid size-12 place-items-center rounded-full border-2 border-ink bg-paper">
                    <x-icon name="medal" class="size-6" :stroke="1.9" />
                </span>
                <p class="mt-2 text-center text-[0.54rem] font-extrabold uppercase leading-tight tracking-[0.1em] text-ink/70">
                    Kode<br>{{ $certificate['code'] }}
                </p>
            </div>
        </div>
    </section>

    {{-- Daftar lencana --}}
    <section>
        <div class="mb-2 flex items-center justify-between gap-2">
            <h2 class="font-display text-[1rem] font-extrabold">Koleksi Lencana</h2>
            <span class="stamp bg-paper">{{ $unlockedCount }} / {{ $totalAchievements }}</span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($rows as $row)
                @php
                    $ach = $row['achievement'];
                    $unlocked = $row['is_unlocked'];
                @endphp

                <article class="relative flex flex-col rounded-lg border-2 border-ink {{ $unlocked ? 'bg-paper shadow-[var(--shadow-flat)]' : 'border-dashed bg-paper-dim/60 shadow-none' }}">
                    <div class="flex items-start gap-2.5 p-3.5">
                        {{-- Lencana perisai: satu SVG utuh, ikon aman di area tengah --}}
                        <x-badge
                            :icon="$ach->icon"
                            :tone="$ach->badge_color"
                            :unlocked="$unlocked"
                            class="size-12 shrink-0"
                            :label="$ach->title"
                        />

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-1.5">
                                <h3 class="font-display text-[0.84rem] font-bold leading-tight">{{ $ach->title }}</h3>
                                @if ($unlocked)
                                    <span class="grid size-5 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-teal text-white">
                                        <x-icon name="check" class="size-3" :stroke="3" />
                                    </span>
                                @else
                                    <span class="grid size-5 shrink-0 place-items-center rounded-full border-[1.5px] border-ink/40 text-ink-faint">
                                        <x-icon name="lock" class="size-2.5" :stroke="2.4" />
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-[0.68rem] font-medium leading-relaxed text-ink-soft">{{ $ach->description }}</p>
                        </div>
                    </div>

                    <div class="mt-auto border-t-2 border-dashed border-ink/25 px-3.5 py-2.5">
                        <div class="flex items-center justify-between gap-2 text-[0.62rem] font-extrabold uppercase tracking-[0.08em]">
                            <span class="text-ink-faint">{{ $ach->metric->label() }}</span>
                            <span class="numeric text-ink">
                                @if ($row['total_steps'] >= 100)
                                    {{ number_format($row['current_steps'], 0, ',', '.') }} / {{ number_format($row['total_steps'], 0, ',', '.') }}
                                @else
                                    {{ $row['current_steps'] }} / {{ $row['total_steps'] }}
                                @endif
                            </span>
                        </div>
                        <x-progress :value="$row['percentage']" tone="{{ $unlocked ? 'teal' : 'amber' }}" height="h-1.5" class="mt-1.5" />
                        <div class="mt-1.5 flex items-center justify-between gap-2">
                            <span class="stamp bg-amber/40 text-[0.54rem]">+{{ $ach->xp_reward }} XP</span>
                            <span class="text-[0.58rem] font-bold text-ink-faint">
                                @if ($unlocked)
                                    Terbuka {{ $row['unlocked_at']?->translatedFormat('d M Y') }}
                                @else
                                    {{ $row['percentage'] }}%
                                @endif
                            </span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection