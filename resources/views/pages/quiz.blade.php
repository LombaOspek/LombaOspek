@extends('layouts.app')

@section('title', 'Kuis Interaktif')
@section('page-title', 'Kuis '.$moduleCode)
@section('page-kicker', $quest->title)

@section('content')
<div class="flex flex-col gap-4" id="quiz-app"
     data-start-url="{{ route('quiz.start') }}"
     data-submit-url="{{ route('quiz.submit') }}"
     data-module="{{ $moduleCode }}"
     data-limit="{{ $timeLimit }}"
     data-pass="{{ $passPercentage }}">

    {{-- Kepala kuis --}}
    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-display text-[1.45rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                Kuis {{ $quest->category }}
            </h1>
            <p class="mt-0.5 max-w-xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                {{ $questions->count() }} soal · batas {{ (int) round($timeLimit / 60) }} menit ·
                nilai lulus {{ $passPercentage }}%.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <span class="stamp bg-paper">
                <x-icon name="refresh" class="size-3" :stroke="2.2" />
                {{ $remainingAttempts }} dari {{ $maxAttempts }} percobaan tersisa
            </span>
            @if ($bestAttempt)
                <span class="stamp bg-teal text-white">
                    <x-icon name="star" class="size-3" :stroke="2.2" />
                    Terbaik {{ $bestAttempt->score }}/{{ $bestAttempt->total_questions }}
                </span>
            @endif
        </div>
    </section>

    @if ($hasPassed)
        {{-- Sudah lulus: tampilkan hasil, jangan biarkan mengulang demi XP. --}}
        <section class="rounded-lg border-2 border-ink bg-teal/15 p-5 shadow-[var(--shadow-flat)]">
            <div class="flex items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink bg-teal text-white">
                    <x-icon name="check" class="size-6" :stroke="2.6" />
                </span>
                <div class="min-w-0">
                    <h2 class="font-display text-[1.05rem] font-extrabold leading-tight">Anda sudah lulus kuis ini</h2>
                    <p class="mt-1 text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                        Skor terbaik <strong class="text-ink">{{ $bestAttempt->score }}/{{ $bestAttempt->total_questions }}</strong>
                        ({{ $bestAttempt->percentage }}%), waktu {{ gmdate('i:s', $bestAttempt->time_taken_seconds) }} menit.
                        Poin hanya diberikan sekali, jadi pengulangan tidak menambah XP.
                    </p>
                    <a href="{{ route('quests.index') }}" class="btn btn-primary mt-3">
                        <x-icon name="map" class="size-4" :stroke="2" />
                        Kembali ke Peta Misi
                    </a>
                </div>
            </div>
        </section>

        @if (is_array($bestAttempt->answers))
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <x-panel-header title="Pembahasan jawaban" subtitle="Tinjau kembali konsep yang diuji" icon="book" />
                <ol class="mt-3 flex flex-col gap-2.5">
                    @foreach ($bestAttempt->answers as $number => $row)
                        <li class="rounded-sm border-[1.5px] border-ink/40 p-2.5 {{ ($row['is_correct'] ?? false) ? 'bg-teal/10' : 'bg-vermilion/5' }}">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border-[1.5px] border-ink text-[0.58rem] font-bold {{ ($row['is_correct'] ?? false) ? 'bg-teal text-white' : 'bg-vermilion text-white' }}">
                                    {{ $number }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[0.74rem] font-bold leading-snug">{{ $row['question'] ?? '' }}</p>
                                    <p class="mt-1 text-[0.68rem] font-semibold text-ink-faint">
                                        Jawaban Anda: <strong class="{{ ($row['is_correct'] ?? false) ? 'text-teal-deep' : 'text-vermilion-deep' }}">{{ $row['user_answer'] ?? '—' }}</strong>
                                        · Kunci: <strong class="text-ink">{{ $row['correct_answer'] ?? '—' }}</strong>
                                    </p>
                                    @if (! empty($row['explanation']))
                                        <p class="mt-1 text-[0.68rem] font-medium leading-relaxed text-ink-soft">{{ $row['explanation'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

    @elseif ($remainingAttempts <= 0)
        <section class="rounded-lg border-2 border-ink bg-vermilion/10 p-5 shadow-[var(--shadow-flat)]">
            <h2 class="font-display text-[1.02rem] font-extrabold">Kesempatan kuis Anda habis</h2>
            <p class="mt-1 text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Anda telah memakai {{ $maxAttempts }} percobaan tanpa mencapai nilai lulus. Hubungi mentor kelompok lewat Pusat Bantuan untuk peninjauan ulang.
            </p>
            <a href="{{ route('profile', ['tab' => 'helpdesk']) }}" class="btn btn-accent mt-3">Ajukan tiket bantuan</a>
        </section>

    @else
        {{-- Layar mulai --}}
        <section id="quiz-intro" class="rounded-lg border-2 border-ink bg-paper p-5 shadow-[var(--shadow-flat-lg)]">
            <div class="grid gap-4 sm:grid-cols-[1.4fr_1fr]">
                <div>
                    <span class="stamp bg-amber">
                        <x-icon name="warning" class="size-3" :stroke="2.2" />
                        Baca sebelum mulai
                    </span>
                    <h2 class="mt-2 font-display text-[1.1rem] font-extrabold leading-tight">
                        {{ $questions->count() }} soal, {{ (int) round($timeLimit / 60) }} menit, sekali jalan.
                    </h2>
                    <ul class="mt-2.5 flex flex-col gap-1.5">
                        @foreach ([
                            'Timer berjalan di server — menutup tab tidak menghentikannya.',
                            'Nilai minimal '.$passPercentage.'% untuk menyelesaikan modul.',
                            'Kesempatan tersisa: '.$remainingAttempts.' dari '.$maxAttempts.'.',
                            'Poin hanya diberikan sekali saat lulus, bukan setiap percobaan.',
                        ] as $note)
                            <li class="flex gap-2 text-[0.72rem] font-medium leading-relaxed text-ink-soft">
                                <x-icon name="chevron-right" class="mt-px size-3.5 shrink-0 text-ink-faint" :stroke="2.4" />
                                <span>{{ $note }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex flex-col justify-between rounded-sm border-[1.5px] border-dashed border-ink/40 bg-paper-dim p-3">
                    <div>
                        <p class="text-[0.56rem] font-extrabold uppercase tracking-[0.13em] text-ink-faint">Percobaan ke-</p>
                        <p class="numeric text-[2rem] font-bold leading-none">{{ $attemptCount + 1 }}</p>
                        <p class="mt-1 text-[0.66rem] font-semibold text-ink-soft">
                            Total {{ $questions->count() }} soal · {{ $questions->sum('xp_reward') }} XP maksimum
                        </p>
                    </div>
                    <button type="button" id="start-quiz" class="btn btn-accent mt-3 w-full">
                        <x-icon name="play" class="size-4" :stroke="2.4" />
                        Mulai Sekarang
                    </button>
                </div>
            </div>
        </section>

        {{-- Layar kuis --}}
        <section id="quiz-runner" class="hidden flex-col gap-3">
            <div class="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-2 rounded-sm border-2 border-ink bg-paper px-3 py-2 shadow-[var(--shadow-flat-sm)]">
                <div class="flex items-center gap-2">
                    <span class="stamp bg-ink text-paper">
                        Soal <span id="q-index">1</span> / {{ $questions->count() }}
                    </span>
                    <span class="stamp bg-paper-dim" id="q-category">{{ $questions->first()->category }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="stamp bg-amber" id="quiz-timer" role="timer" aria-live="off">
                        <x-icon name="clock" class="size-3" :stroke="2.2" />
                        <span id="timer-text">--:--</span>
                    </span>
                    <x-progress :value="0" tone="teal" height="h-1.5" class="w-20 sm:w-28" label="Progres menjawab" id="answer-progress" />
                </div>
            </div>

            <form id="quiz-form" class="flex flex-col gap-3" novalidate>
                @foreach ($questions as $question)
                    <fieldset
                        class="question-card rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] {{ $loop->first ? '' : 'hidden' }}"
                        data-number="{{ $question->question_number }}"
                        data-category="{{ $question->category }}"
                    >
                        <legend class="sr-only">Soal {{ $question->question_number }}</legend>

                        <div class="flex items-start justify-between gap-2">
                            <p class="font-display text-[0.92rem] font-bold leading-snug text-pretty">
                                {{ $question->question }}
                            </p>
                            <span class="stamp shrink-0 bg-amber/40">+{{ $question->xp_reward }} XP</span>
                        </div>

                        <div class="mt-3 flex flex-col gap-1.5" role="radiogroup" aria-label="Pilihan jawaban soal {{ $question->question_number }}">
                            @foreach ($question->options as $letter => $text)
                                <label class="option-label flex cursor-pointer items-start gap-2.5 rounded-sm border-2 border-ink/60 bg-paper-dim p-2.5 transition hover:border-ink hover:bg-white">
                                    <input
                                        type="radio"
                                        name="q{{ $question->question_number }}"
                                        value="{{ $letter }}"
                                        class="peer sr-only"
                                    >
                                    <span class="grid size-6 shrink-0 place-items-center rounded-sm border-2 border-ink bg-paper font-mono text-[0.66rem] font-bold peer-checked:bg-ink peer-checked:text-paper">
                                        {{ $letter }}
                                    </span>
                                    <span class="text-[0.76rem] font-medium leading-relaxed text-ink peer-checked:font-bold">
                                        {{ $text }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <div class="flex flex-wrap items-center justify-between gap-2 rounded-sm border-2 border-ink bg-paper px-3 py-2 shadow-[var(--shadow-flat-sm)]">
                    <button type="button" id="prev-q" class="btn btn-ghost btn-sm" disabled>
                        <x-icon name="arrow-left" class="size-3.5" :stroke="2.2" />
                        Sebelumnya
                    </button>

                    <div class="flex items-center gap-1.5">
                        <button type="button" id="next-q" class="btn btn-primary btn-sm">
                            Berikutnya
                            <x-icon name="arrow-right" class="size-3.5" :stroke="2.2" />
                        </button>
                        <button type="submit" id="submit-quiz" class="btn btn-accent btn-sm hidden">
                            <x-icon name="check" class="size-3.5" :stroke="2.6" />
                            Kumpulkan
                        </button>
                    </div>
                </div>
            </form>
        </section>

        {{-- Hasil --}}
        <section id="quiz-result" class="hidden flex-col gap-3">
            <div class="rounded-lg border-2 border-ink bg-paper p-5 shadow-[var(--shadow-flat-lg)]">
                <div class="flex items-start gap-3">
                    <span id="result-badge" class="grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink bg-amber">
                        <x-icon name="trophy" class="size-6" :stroke="2" />
                    </span>
                    <div class="min-w-0">
                        <h2 id="result-title" class="font-display text-[1.1rem] font-extrabold leading-tight">Hasil kuis</h2>
                        <p id="result-desc" class="mt-1 text-[0.78rem] font-medium leading-relaxed text-ink-soft"></p>
                    </div>
                </div>

                <dl class="mt-3 grid grid-cols-3 gap-2">
                    <div class="rounded-sm border-[1.5px] border-ink/50 p-2 text-center">
                        <dt class="text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Skor</dt>
                        <dd id="result-score" class="numeric text-[1.2rem] font-bold leading-none">–</dd>
                    </div>
                    <div class="rounded-sm border-[1.5px] border-ink/50 p-2 text-center">
                        <dt class="text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Nilai</dt>
                        <dd id="result-percent" class="numeric text-[1.2rem] font-bold leading-none">–</dd>
                    </div>
                    <div class="rounded-sm border-[1.5px] border-ink/50 p-2 text-center">
                        <dt class="text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">XP</dt>
                        <dd id="result-xp" class="numeric text-[1.2rem] font-bold leading-none">–</dd>
                    </div>
                </dl>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a id="result-next" href="{{ route('quests.index') }}" class="btn btn-primary">
                        <x-icon name="map" class="size-4" :stroke="2" />
                        Lanjut ke Peta Misi
                    </a>
                    <button type="button" id="retry-quiz" class="btn btn-ghost hidden">
                        <x-icon name="refresh" class="size-4" :stroke="2" />
                        Coba Lagi
                    </button>
                </div>
            </div>

            <div id="result-review" class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <x-panel-header title="Pembahasan" subtitle="Pelajari kekeliruan Anda" icon="book" />
                <ol id="review-list" class="mt-3 flex flex-col gap-2.5"></ol>
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script type="module">
    const app = document.getElementById('quiz-app');
    const startBtn = document.getElementById('start-quiz');

    if (startBtn && app) {
        const endpoints = {
            start: app.dataset.startUrl,
            submit: app.dataset.submitUrl,
        };
        const moduleCode = app.dataset.module;
        const limit = Number(app.dataset.limit);
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        const intro = document.getElementById('quiz-intro');
        const runner = document.getElementById('quiz-runner');
        const result = document.getElementById('quiz-result');
        const form = document.getElementById('quiz-form');
        const cards = Array.from(document.querySelectorAll('.question-card'));
        const timerBox = document.getElementById('quiz-timer');
        const timerText = document.getElementById('timer-text');

        let current = 0;
        let expiresAt = null;
        let ticker = null;

        function paint(index) {
            current = index;

            cards.forEach((card, i) => card.classList.toggle('hidden', i !== index));

            document.getElementById('q-index').textContent = String(index + 1);
            document.getElementById('q-category').textContent = cards[index].dataset.category;
            document.getElementById('prev-q').disabled = index === 0;

            const last = index === cards.length - 1;
            document.getElementById('next-q').classList.toggle('hidden', last);
            document.getElementById('submit-quiz').classList.toggle('hidden', !last);
        }

        function answered() {
            return cards.filter((card) => card.querySelector('input:checked')).length;
        }

        function refreshProgress() {
            const bar = document.getElementById('answer-progress');
            const value = Math.round((answered() / cards.length) * 100);

            bar.setAttribute('aria-valuenow', String(value));
            bar.querySelector('span').style.width = value + '%';
        }

        function startTimer(deadline) {
            expiresAt = deadline;

            const render = () => {
                const left = Math.max(0, Math.round((expiresAt - Date.now()) / 1000));
                timerText.textContent = `${String(Math.floor(left / 60)).padStart(2, '0')}:${String(left % 60).padStart(2, '0')}`;

                if (left <= 30) {
                    timerBox.className = 'stamp bg-vermilion text-white';
                }

                if (left === 0) {
                    clearInterval(ticker);
                    form.requestSubmit();
                }
            };

            render();
            ticker = setInterval(render, 1000);
        }

        startBtn.addEventListener('click', async () => {
            startBtn.disabled = true;
            startBtn.textContent = 'Menyiapkan…';

            try {
                const response = await fetch(endpoints.start, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ module_code: moduleCode }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    window.Ospek?.toast({
                        title: 'Kuis belum bisa dimulai',
                        message: data.message ?? 'Coba muat ulang halaman.',
                        tone: 'warning',
                    });
                    startBtn.disabled = false;
                    startBtn.textContent = 'Mulai Sekarang';
                    return;
                }

                intro.classList.add('hidden');
                runner.classList.remove('hidden');
                runner.classList.add('flex');

                startTimer(new Date(data.expires_at).getTime());
                paint(0);
                refreshProgress();
                window.Ospek?.playTone('reward');
            } catch (error) {
                window.Ospek?.toast({
                    title: 'Gagal menghubungi server',
                    message: 'Periksa koneksi Anda lalu coba lagi.',
                    tone: 'danger',
                });
                startBtn.disabled = false;
                startBtn.textContent = 'Mulai Sekarang';
            }
        });

        document.getElementById('next-q').addEventListener('click', () => paint(Math.min(cards.length - 1, current + 1)));
        document.getElementById('prev-q').addEventListener('click', () => paint(Math.max(0, current - 1)));
        form.addEventListener('change', refreshProgress);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (answered() < cards.length) {
                window.Ospek?.toast({
                    title: 'Masih ada soal kosong',
                    message: `Anda baru menjawab ${answered()} dari ${cards.length} soal.`,
                    tone: 'warning',
                });
                return;
            }

            clearInterval(ticker);

            const answers = {};
            cards.forEach((card) => {
                const picked = card.querySelector('input:checked');
                answers[card.dataset.number] = picked ? picked.value : null;
            });

            document.getElementById('submit-quiz').disabled = true;

            try {
                const response = await fetch(endpoints.submit, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        module_code: moduleCode,
                        answers,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    window.Ospek?.toast({
                        title: 'Jawaban ditolak',
                        message: data.message ?? 'Terjadi kesalahan saat menilai.',
                        tone: 'danger',
                    });
                    document.getElementById('submit-quiz').disabled = false;
                    return;
                }

                renderResult(data);
            } catch (error) {
                window.Ospek?.toast({
                    title: 'Koneksi terputus',
                    message: 'Jawaban tidak terkirim. Muat ulang halaman.',
                    tone: 'danger',
                });
                document.getElementById('submit-quiz').disabled = false;
            }
        });

        function renderResult(data) {
            runner.classList.add('hidden');
            result.classList.remove('hidden');
            result.classList.add('flex');

            document.getElementById('result-score').textContent = `${data.score}/${data.total}`;
            document.getElementById('result-percent').textContent = `${data.percentage}%`;
            document.getElementById('result-xp').textContent = `+${data.xp_earned}`;

            const title = document.getElementById('result-title');
            const desc = document.getElementById('result-desc');
            const badge = document.getElementById('result-badge');
            const retry = document.getElementById('retry-quiz');

            if (data.is_passed) {
                title.textContent = data.percentage === 100 ? 'Sempurna — semua jawaban benar.' : 'Lulus. Modul selesai.';
                desc.textContent = `Anda memperoleh ${data.xp_earned} XP. ${data.remaining_attempts} percobaan tidak terpakai karena kuis sudah lulus.`;
                badge.className = 'grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink bg-teal text-white';
                window.Ospek?.playTone('reward');
            } else {
                title.textContent = 'Belum mencapai nilai lulus.';
                desc.textContent = `Nilai minimal ${app.dataset.pass}%. Sisa percobaan: ${data.remaining_attempts}.`;
                badge.className = 'grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink bg-vermilion text-white';

                if (data.remaining_attempts > 0) {
                    retry.classList.remove('hidden');
                    retry.addEventListener('click', () => window.location.reload(), { once: true });
                }

                window.Ospek?.playTone('warn');
            }

            const list = document.getElementById('review-list');
            list.replaceChildren();

            Object.entries(data.evaluations ?? {}).forEach(([number, row]) => {
                const item = document.createElement('li');
                item.className = `rounded-sm border-[1.5px] border-ink/40 p-2.5 ${row.is_correct ? 'bg-teal/10' : 'bg-vermilion/5'}`;

                const head = document.createElement('p');
                head.className = 'text-[0.72rem] font-bold leading-snug';
                head.textContent = `${number}. ${row.question ?? ''}`;

                const meta = document.createElement('p');
                meta.className = 'mt-1 text-[0.66rem] font-semibold text-ink-faint';
                meta.textContent = `Jawaban Anda: ${row.user_answer ?? '—'} · Kunci: ${row.correct_answer ?? '—'}`;

                item.append(head, meta);

                if (row.explanation) {
                    const note = document.createElement('p');
                    note.className = 'mt-1 text-[0.66rem] font-medium leading-relaxed text-ink-soft';
                    note.textContent = row.explanation;
                    item.appendChild(note);
                }

                list.appendChild(item);
            });

            if (Array.isArray(data.newly_unlocked) && data.newly_unlocked.length > 0) {
                data.newly_unlocked.forEach((badge) => {
                    window.Ospek?.toast({
                        title: `Lencana terbuka: ${badge.title}`,
                        message: `+${badge.xp} XP bonus.`,
                        tone: 'success',
                    });
                });
            }

            result.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        window.addEventListener('beforeunload', (event) => {
            if (ticker !== null && runner && !runner.classList.contains('hidden')) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }
</script>
@endpush