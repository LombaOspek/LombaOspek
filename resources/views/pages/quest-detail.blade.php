@extends('layouts.app')

@section('title', 'Modul '.$quest->step_number)
@section('page-title', 'Modul '.$quest->step_number.' · '.$quest->category)
@section('page-kicker', $status->label())

@section('content')
@php
    $isOpen = $status->isOpen();
    $isCompleted = $status === \App\Enums\QuestStatus::Completed;
    $isQuiz = $quest->action_type === \App\Enums\QuestActionType::Quiz;
    $needsReflection = $quest->action_type === \App\Enums\QuestActionType::Reflection;
@endphp

<div class="flex flex-col gap-4">

    <nav class="flex items-center gap-1.5 text-[0.68rem] font-bold text-ink-faint" aria-label="Breadcrumb">
        <a href="{{ route('quests.index') }}" class="inline-flex items-center gap-1 transition hover:text-vermilion">
            <x-icon name="arrow-left" class="size-3.5" :stroke="2.2" />
            Peta Misi
        </a>
        <x-icon name="chevron-right" class="size-3" :stroke="2.2" />
        <span class="text-ink">Modul {{ $quest->step_number }}</span>
    </nav>

    <div class="grid gap-4 xl:grid-cols-[1.55fr_1fr]">
        <div class="flex flex-col gap-4">

            <section class="overflow-hidden rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat-lg)]">
                <div class="h-2 w-full border-b-2 border-ink {{ $isCompleted ? 'bg-teal' : ($isOpen ? 'stripe-amber' : 'bg-paper-dim') }}"></div>

                <div class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="stamp bg-ink text-paper font-mono">{{ $quest->code }}</span>
                        <x-status-pill :status="$status" :reason="$lockedReason" />
                        <span class="stamp bg-paper-dim">{{ $quest->action_type->label() }}</span>
                        <span class="stamp bg-amber/40">+{{ $quest->xp_reward }} XP</span>
                    </div>

                    <h1 class="mt-2.5 font-display text-[1.3rem] font-extrabold leading-tight tracking-tight sm:text-[1.55rem]">
                        {{ $quest->title }}
                    </h1>
                    <p class="mt-2 text-[0.82rem] font-medium leading-relaxed text-ink-soft text-pretty">
                        {{ $quest->description }}
                    </p>

                    @if ($quest->objective)
                        <div class="mt-3 rounded-sm border-[1.5px] border-dashed border-ink/40 bg-paper-dim p-2.5">
                            <p class="flex items-start gap-2 text-[0.72rem] font-semibold leading-relaxed text-ink-soft">
                                <x-icon name="target" class="mt-px size-4 shrink-0 text-amber-deep" :stroke="2" />
                                <span>{{ $quest->objective }}</span>
                            </p>
                        </div>
                    @endif

                    <dl class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <div class="rounded-sm border-[1.5px] border-ink/50 p-2">
                            <dt class="text-[0.56rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Lokasi</dt>
                            <dd class="mt-0.5 text-[0.72rem] font-bold leading-snug">{{ $quest->location ?: 'Daring' }}</dd>
                        </div>
                        <div class="rounded-sm border-[1.5px] border-ink/50 p-2">
                            <dt class="text-[0.56rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Estimasi</dt>
                            <dd class="mt-0.5 text-[0.72rem] font-bold leading-snug">±{{ $quest->estimated_minutes }} menit</dd>
                        </div>
                        <div class="rounded-sm border-[1.5px] border-ink/50 p-2">
                            <dt class="text-[0.56rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Jadwal</dt>
                            <dd class="mt-0.5 text-[0.72rem] font-bold leading-snug">{{ $quest->deadline ?: 'Sesuai jadwal' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Area aksi --}}
                <div class="border-t-2 border-ink bg-paper-dim p-4 sm:p-5">
                    @if ($isCompleted)
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="grid size-9 place-items-center rounded-full border-2 border-ink bg-teal text-white">
                                <x-icon name="check" class="size-5" :stroke="2.6" />
                            </span>
                            <div class="min-w-0">
                                <p class="font-display text-[0.82rem] font-bold leading-tight">Modul ini sudah selesai</p>
                                <p class="text-[0.68rem] font-medium text-ink-faint">
                                    @if ($userQuest?->completed_at)
                                        Diselesaikan {{ $userQuest->completed_at->translatedFormat('d F Y, H.i') }}
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('quests.index') }}" class="btn btn-ghost btn-sm ml-auto">Kembali ke peta</a>
                        </div>

                        @if ($userQuest?->note)
                            <div class="mt-3 rounded-sm border-[1.5px] border-ink/40 bg-paper p-2.5">
                                <p class="text-[0.56rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">Catatan Anda</p>
                                <p class="mt-1 whitespace-pre-line text-[0.72rem] font-medium leading-relaxed text-ink-soft">{{ $userQuest->note }}</p>
                            </div>
                        @endif

                    @elseif (! $isOpen)
                        <div class="flex items-start gap-2.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full border-2 border-ink bg-paper">
                                <x-icon name="lock" class="size-4" :stroke="2.1" />
                            </span>
                            <div>
                                <p class="font-display text-[0.82rem] font-bold leading-tight">Modul masih terkunci</p>
                                <p class="mt-0.5 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                                    {{ $lockedReason ?: 'Selesaikan modul sebelumnya terlebih dahulu.' }}
                                </p>
                                <a href="{{ route('quests.index') }}" class="btn btn-ghost btn-sm mt-2">Lihat modul yang terbuka</a>
                            </div>
                        </div>

                    @elseif ($isQuiz)
                        <div class="flex flex-wrap items-center justify-between gap-2.5">
                            <div class="min-w-0">
                                <p class="font-display text-[0.82rem] font-bold leading-tight">Modul ini dinilai lewat kuis</p>
                                <p class="mt-0.5 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                                    Kuis tidak punya tombol "selesai" — nilai kelulusan yang menyelesaikan modul.
                                </p>
                            </div>
                            <a href="{{ route('quiz', ['module' => $quest->quiz_module_code]) }}" class="btn btn-accent">
                                <x-icon name="quiz" class="size-4" :stroke="2" />
                                Kerjakan Kuis
                            </a>
                        </div>

                    @else
                        <form
                            method="POST"
                            action="{{ route('quests.complete', $quest) }}"
                            class="flex flex-col gap-3"
                            data-submit-lock
                        >
                            @csrf

                            @if ($needsReflection)
                                <div>
                                    <label for="reflection" class="block font-display text-[0.7rem] font-bold uppercase tracking-[0.08em]">
                                        Catatan refleksi <span class="text-vermilion">*</span>
                                    </label>
                                    <p class="mt-0.5 text-[0.64rem] font-medium text-ink-faint">
                                        Minimal 80 karakter. Tuliskan apa yang Anda pelajari dan rencana tindak lanjut.
                                    </p>
                                    <textarea
                                        id="reflection"
                                        name="reflection"
                                        rows="5"
                                        required
                                        minlength="80"
                                        maxlength="1200"
                                        class="mt-1.5 w-full rounded-sm border-2 border-ink bg-white p-2.5 text-[0.78rem] font-medium leading-relaxed text-ink focus:outline-none"
                                        placeholder="Contoh: Dari sesi ini saya memahami bahwa…"
                                    >{{ old('reflection') }}</textarea>
                                    @error('reflection')
                                        <p class="mt-1 text-[0.66rem] font-bold text-vermilion-deep">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <label class="flex cursor-pointer items-start gap-2 rounded-sm border-[1.5px] border-dashed border-ink/40 bg-paper p-2.5">
                                <input type="checkbox" name="confirm" value="1" required class="mt-0.5 size-4 rounded-xs border-2 border-ink accent-teal">
                                <span class="text-[0.7rem] font-semibold leading-snug text-ink-soft">
                                    Saya menyatakan telah benar-benar mengikuti kegiatan modul ini dan data yang saya berikan sesuai kenyataan.
                                </span>
                            </label>
                            @error('confirm')
                                <p class="text-[0.66rem] font-bold text-vermilion-deep">{{ $message }}</p>
                            @enderror

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <x-icon name="check" class="size-4" :stroke="2.6" />
                                    Tandai Selesai (+{{ $quest->xp_reward }} XP)
                                </button>
                                <a href="{{ route('quests.index') }}" class="btn btn-ghost btn-sm">Batal</a>
                            </div>
                        </form>
                    @endif
                </div>
            </section>
        </div>

        <aside class="flex flex-col gap-4">
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <x-panel-header title="Posisi Anda" icon="trending" />
                <dl class="mt-2.5 flex flex-col gap-1.5 text-[0.72rem]">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="font-semibold text-ink-faint">Modul selesai</dt>
                        <dd class="numeric font-bold">{{ $user->userQuests()->where('status', \App\Enums\QuestStatus::Completed)->count() }} / {{ \App\Models\Quest::count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="font-semibold text-ink-faint">Total XP</dt>
                        <dd class="numeric font-bold">{{ number_format($user->xp, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="font-semibold text-ink-faint">Level</dt>
                        <dd class="font-bold">{{ $user->level }} · {{ $user->level_title }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="font-semibold text-ink-faint">Rentetan</dt>
                        <dd class="font-bold">{{ $user->streak_days }} hari</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border-2 border-ink bg-teal/15 p-4 shadow-[var(--shadow-flat)]">
                <p class="flex items-center gap-1.5 text-[0.58rem] font-extrabold uppercase tracking-[0.13em] text-ink/70">
                    <x-icon name="help" class="size-3.5" :stroke="2.2" />
                    Butuh bantuan?
                </p>
                <p class="mt-1 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                    Kirim tiket ke mentor kelompok Anda bila modul tidak bisa dibuka atau instruksi kurang jelas.
                </p>
                <a href="{{ route('profile', ['tab' => 'helpdesk']) }}" class="btn btn-ghost btn-sm mt-2 w-full">
                    <x-icon name="ticket" class="size-3.5" :stroke="2" />
                    Ajukan tiket
                </a>
            </section>
        </aside>
    </div>
</div>
@endsection