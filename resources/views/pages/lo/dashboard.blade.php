@extends('layouts.app')

@section('title', 'Portal LO — Verifikasi')
@section('page-title', 'Portal LO · Verifikasi Bukti')
@section('page-kicker', $stats['group']?->name ?? 'Belum ada gugus')

@section('content')
@php
    $badgeTone = fn (string $c) => match ($c) {
        'teal' => 'bg-teal/20',
        'sky' => 'bg-sky/50',
        'vermilion' => 'bg-vermilion/15',
        default => 'bg-amber/40',
    };
@endphp

<div class="flex flex-col gap-4">

    {{-- Sapaan + status sistem --}}
    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-1.5">
                <span class="stamp bg-vermilion text-white">
                    <x-icon name="shield" class="size-3" :stroke="2.2" />
                    Portal LO (Kakak Tingkat)
                </span>
                <span class="stamp bg-teal text-white">
                    <span class="size-1.5 rounded-full bg-white"></span>
                    SIAKAD UMK Terhubung
                </span>
            </p>
            <h1 class="mt-1.5 font-display text-[1.5rem] font-extrabold leading-tight tracking-tight sm:text-[1.8rem]">
                Halo, {{ \Illuminate\Support\Str::before($officer->name, ' ') }}.
            </h1>
            <p class="mt-0.5 max-w-2xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Tinjau bukti misi mahasiswa gugus Anda. XP baru dikreditkan setelah Anda menyetujui bukti —
                pastikan NIM, nama, atau token terlihat jelas sebelum meluluskan.
            </p>
        </div>

        @if ($stats['group'])
            <span class="stamp {{ $badgeTone($stats['group']->badge_color) }} border-2 border-ink px-3 py-1.5 text-[0.7rem]">
                <x-icon name="users" class="size-3.5" :stroke="2" />
                {{ $stats['group']->name }}
            </span>
        @endif
    </section>

    {{-- Statistik verifikasi --}}
    <section class="grid grid-cols-2 gap-2.5 lg:grid-cols-4" aria-label="Statistik verifikasi">
        <x-stat-tile
            :value="$stats['pending']"
            label="Menunggu Verifikasi"
            icon="clock"
            :tone="$stats['pending'] > 0 ? 'amber' : 'paper'"
            caption="Bukti belum ditinjau"
        />
        <x-stat-tile
            :value="$stats['approved']"
            label="Disetujui"
            icon="check-circle"
            tone="teal"
            :caption="'+'.$stats['approved_today'].' hari ini'"
        />
        <x-stat-tile
            :value="$stats['rejected']"
            label="Ditolak"
            icon="x"
            tone="paper"
            caption="Perlu perbaikan mahasiswa"
        />
        <x-stat-tile
            :value="$stats['efficiency'].'%'"
            label="Rasio Kelulusan"
            icon="trending"
            tone="sky"
            :caption="$stats['members'].' mahasiswa dibimbing'"
        />
    </section>

    {{-- Antrean --}}
    <section class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)]">
        <div class="p-4 pb-3">
            <x-panel-header
                title="Antrean Verifikasi Bukti Misi"
                :subtitle="$stats['pending'].' bukti belum disemak'"
                icon="layers"
            />

            {{-- Filter status + pencarian --}}
            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $key => $label)
                    <a
                        href="{{ route('lo.dashboard', array_merge(request()->query(), ['status' => $key])) }}"
                        @if ($status === $key) aria-current="page" @endif
                        class="stamp {{ $status === $key ? 'bg-ink text-paper' : 'bg-paper hover:bg-paper-dim' }}"
                    >{{ $label }}</a>
                @endforeach

                <form method="GET" action="{{ route('lo.dashboard') }}" class="ml-auto flex items-center gap-1.5">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari nama atau NIM…"
                        class="w-40 rounded-sm border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none sm:w-52"
                    >
                    <button type="submit" class="btn btn-ghost btn-sm">
                        <x-icon name="search" class="size-3.5" :stroke="2.2" />
                        Cari
                    </button>
                </form>
            </div>
        </div>

        @if ($submissions->isEmpty())
            <div class="border-t-2 border-ink/10 p-6 text-center">
                <x-icon name="check-circle" class="mx-auto size-8 text-teal-deep" :stroke="1.8" />
                <p class="mt-2 font-display text-[0.9rem] font-bold">
                    {{ $status === 'pending' ? 'Tidak ada bukti menunggu.' : 'Belum ada data pada filter ini.' }}
                </p>
                <p class="mt-0.5 text-[0.72rem] font-medium text-ink-faint">
                    {{ $status === 'pending' ? 'Semua bukti mahasiswa gugus Anda sudah ditinjau.' : 'Coba pilih status lain.' }}
                </p>
            </div>
        @else
            <div class="divide-y-2 divide-ink/10 border-t-2 border-ink/10">
                @foreach ($submissions as $submission)
                    @php
                        $student = $submission->user;
                        $quest = $submission->quest;
                        $isPending = $submission->status === \App\Enums\SubmissionStatus::Pending;
                    @endphp

                    <article class="p-3.5 sm:p-4">
                        <div class="flex flex-wrap items-start gap-3">

                            {{-- Bukti thumbnail --}}
                            <div class="shrink-0">
                                @if ($submission->proof_path)
                                    <a
                                        href="{{ route('lo.proof', $submission) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="block size-16 overflow-hidden rounded-sm border-2 border-ink bg-paper-dim shadow-[var(--shadow-flat-sm)] transition hover:-translate-y-0.5"
                                        title="Buka bukti ukuran penuh"
                                    >
                                        <img
                                            src="{{ route('lo.proof', $submission) }}"
                                            alt="Bukti dari {{ $student?->name }}"
                                            class="size-full object-cover"
                                            loading="lazy"
                                        >
                                    </a>
                                @else
                                    <span class="grid size-16 place-items-center rounded-sm border-2 border-ink bg-paper-dim">
                                        <x-icon :name="$submission->method->icon()" class="size-6 text-ink-faint" :stroke="1.9" />
                                    </span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="stamp {{ $submission->status->badgeClasses() }}">
                                        {{ $submission->status->label() }}
                                    </span>
                                    <span class="stamp bg-paper-dim">{{ $submission->method->label() }}</span>
                                    <span class="stamp bg-amber/40">+{{ $quest?->xp_reward }} XP</span>
                                </div>

                                <p class="mt-1.5 font-display text-[0.86rem] font-bold leading-snug">
                                    {{ $quest?->title }}
                                </p>

                                <p class="mt-0.5 text-[0.68rem] font-semibold text-ink-faint">
                                    {{ $student?->name }} · {{ $student?->nim }} · {{ $student?->major }}
                                    · {{ $submission->created_at->diffForHumans() }}
                                </p>

                                @if ($submission->note)
                                    <p class="mt-1.5 rounded-xs border-l-2 border-ink/30 bg-paper-dim px-2 py-1 text-[0.68rem] font-medium leading-relaxed text-ink-soft">
                                        {{ $submission->note }}
                                    </p>
                                @endif

                                @if ($submission->rejection_reason)
                                    <p class="mt-1.5 rounded-xs border-l-2 border-vermilion bg-vermilion/10 px-2 py-1 text-[0.68rem] font-semibold leading-relaxed text-vermilion-deep">
                                        Ditolak: {{ $submission->rejection_reason }}
                                    </p>
                                @endif

                                @if ($submission->distance_meters !== null)
                                    <p class="mt-1 text-[0.64rem] font-semibold text-teal-deep">
                                        Jarak terukur: {{ number_format($submission->distance_meters, 0, ',', '.') }} m
                                    </p>
                                @endif
                            </div>

                            {{-- Aksi --}}
                            @if ($isPending)
                                <div class="flex w-full shrink-0 flex-wrap gap-1.5 sm:w-auto">
                                    <form method="POST" action="{{ route('lo.approve', $submission) }}" data-submit-lock>
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <x-icon name="check" class="size-3.5" :stroke="2.6" />
                                            Luluskan
                                        </button>
                                    </form>

                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-sm hover:bg-vermilion hover:text-white"
                                        data-reject-toggle="reject-{{ $submission->id }}"
                                    >
                                        <x-icon name="x" class="size-3.5" :stroke="2.6" />
                                        Tolak
                                    </button>
                                </div>
                            @elseif ($submission->reviewed_at)
                                <span class="shrink-0 text-[0.62rem] font-semibold text-ink-faint">
                                    Ditinjau {{ $submission->reviewed_at->diffForHumans() }}
                                    @if ($submission->reviewer)
                                        <br>oleh {{ $submission->reviewer->name }}
                                    @endif
                                </span>
                            @endif
                        </div>

                        {{-- Form penolakan (tersembunyi) --}}
                        @if ($isPending)
                            <form
                                method="POST"
                                action="{{ route('lo.reject', $submission) }}"
                                id="reject-{{ $submission->id }}"
                                class="mt-2.5 hidden flex-col gap-1.5 rounded-sm border-2 border-vermilion/50 bg-vermilion/5 p-2.5"
                                data-submit-lock
                            >
                                @csrf
                                <label for="reason-{{ $submission->id }}" class="text-[0.62rem] font-extrabold uppercase tracking-[0.1em] text-vermilion-deep">
                                    Alasan penolakan (wajib)
                                </label>
                                <input
                                    type="text"
                                    id="reason-{{ $submission->id }}"
                                    name="reason"
                                    required
                                    minlength="10"
                                    maxlength="300"
                                    placeholder="Contoh: Gambar buram, NIM tidak terbaca."
                                    class="w-full rounded-xs border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-medium focus:outline-none"
                                >
                                <div class="flex gap-1.5">
                                    <button type="submit" class="btn btn-accent btn-sm">Kirim penolakan</button>
                                    <button type="button" class="btn btn-ghost btn-sm" data-reject-toggle="reject-{{ $submission->id }}">Batal</button>
                                </div>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($submissions->hasPages())
                <div class="border-t-2 border-ink/10 p-3">
                    {{ $submissions->links() }}
                </div>
            @endif
        @endif
    </section>

    {{-- Catatan integritas --}}
    <section class="rounded-lg border-2 border-ink bg-teal/15 p-4 shadow-[var(--shadow-flat)]">
        <div class="flex items-start gap-2.5">
            <span class="grid size-8 shrink-0 place-items-center rounded-sm border-[1.5px] border-ink bg-paper">
                <x-icon name="shield" class="size-4" :stroke="2" />
            </span>
            <div class="min-w-0">
                <p class="font-display text-[0.8rem] font-bold leading-tight">Anda hanya dapat meninjau gugus Anda</p>
                <p class="mt-1 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                    Sistem menolak permintaan verifikasi untuk mahasiswa di luar
                    {{ $stats['group']?->name ?? 'gugus Anda' }}, termasuk bila URL dimanipulasi.
                    Bukti disimpan di penyimpanan privat dan hanya dapat dibuka oleh LO berwenang.
                </p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script type="module">
    document.querySelectorAll('[data-reject-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const form = document.getElementById(btn.dataset.rejectToggle);
            if (!form) return;
            form.classList.toggle('hidden');
            form.classList.toggle('flex');
            window.Ospek?.playTone('tap');
        });
    });
</script>
@endpush