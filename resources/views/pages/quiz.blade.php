@extends('layouts.app')

@section('title', 'Kuiz Interaktif Gamifikasi')

@section('content')
<div class="p-5 md:p-8 flex flex-col justify-between min-h-[820px]">

    <div>
        <!-- BEGIN: Quiz Top Header -->
        <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b-2 border-black/10 pb-5 mb-5">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('quests.index') }}" class="text-xs font-black text-[#E78B24] hover:underline flex items-center gap-1">
                        <span>← Kembali ke Peta Misi</span>
                    </a>
                </div>
                <h2 class="text-2xl sm:text-3xl font-display font-black text-[#1E1E1E] tracking-tight">
                    Kuiz Interaktif Harian ⚡
                </h2>
                <p class="text-xs sm:text-sm font-semibold text-neutral-600 mt-0.5">
                    Modul 4: Pengenalan Fakulti & Sistem Kredit Semester (SKS)
                </p>
            </div>

            <!-- Real-Time Countdown Timer -->
            <div class="flex items-center gap-2.5 bg-[#FFF3D6] brutal-border px-4 py-2 rounded-2xl shadow-brutal-sm self-start sm:self-auto">
                <span class="text-xl animate-pulse">⏱️</span>
                <div>
                    <span class="block text-[9px] font-black uppercase text-amber-900 leading-none">Baki Masa</span>
                    <span id="quiz-timer" class="font-mono text-sm font-black text-amber-950">04:59s</span>
                </div>
            </div>
        </header>
        <!-- END: Quiz Header -->

        <!-- Quiz Step Circles Indicator -->
        <div class="mb-5 bg-white brutal-border rounded-2xl p-4 shadow-brutal-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black text-neutral-800" id="question-tracker-text">
                    Soalan 1 daripada {{ $questions->count() }}
                </span>
                <span class="text-[10px] font-bold text-neutral-500">• Pilih satu jawapan paling tepat</span>
            </div>

            <div class="flex items-center gap-2" id="step-indicators">
                @foreach ($questions as $q)
                    <button type="button" 
                            onclick="goToQuestion({{ $q->question_number }})"
                            id="step-btn-{{ $q->question_number }}"
                            class="step-indicator w-8 h-8 rounded-full brutal-border text-xs font-black flex items-center justify-center transition-all cursor-pointer {{ $loop->first ? 'bg-neo-yellow ring-2 ring-black scale-105' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }}">
                        {{ $q->question_number }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Question Containers -->
        @foreach ($questions as $q)
            <div id="question-card-{{ $q->question_number }}" class="question-card {{ $loop->first ? '' : 'hidden' }}">
                <div class="bg-[#FAF8F5] brutal-border rounded-2xl p-5 sm:p-7 shadow-brutal mb-4">

                    <!-- Meta Badges -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full bg-amber-200 brutal-border text-[10px] font-black uppercase text-amber-900">
                                {{ $q->category }}
                            </span>
                            <span class="text-xs font-black text-emerald-700">
                                Ganjaran: +{{ $q->xp_reward }} XP
                            </span>
                        </div>
                        <span class="text-[11px] font-bold text-neutral-400">
                            #MOD4-Q{{ $q->question_number }}
                        </span>
                    </div>

                    <!-- Question Prompt -->
                    <h3 class="font-display font-black text-base sm:text-lg md:text-xl text-neutral-900 mb-6 leading-snug">
                        {{ $q->question }}
                    </h3>

                    <!-- 4 Option Choices -->
                    <div class="space-y-3" id="options-group-{{ $q->question_number }}">
                        @foreach (['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $letter => $optionText)
                            <button type="button" 
                                    onclick="selectAnswer({{ $q->question_number }}, '{{ $letter }}', this)" 
                                    class="quiz-option-btn w-full text-left p-4 bg-white brutal-border rounded-xl font-bold text-xs sm:text-sm text-neutral-800 shadow-brutal-sm hover:bg-neutral-50 flex items-center gap-3.5 transition-all brutal-btn-press"
                                    data-option="{{ $letter }}">
                                <span class="option-badge w-8 h-8 rounded-lg bg-neutral-100 brutal-border flex items-center justify-center font-display font-black text-xs shrink-0">
                                    {{ $letter }}
                                </span>
                                <span class="leading-snug">{{ $optionText }}</span>
                            </button>
                        @endforeach
                    </div>

                    <!-- Hint Box (Toggleable) -->
                    <div id="hint-box-{{ $q->question_number }}" class="hidden mt-4 p-3.5 bg-amber-100/80 border-2 border-dashed border-amber-800/40 rounded-xl flex items-start gap-2.5 text-xs text-amber-950 font-semibold">
                        <span class="text-base shrink-0">💡</span>
                        <div>
                            <strong class="font-black text-amber-900">Petunjuk:</strong> {{ $q->hint }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    <!-- Bottom Action Controls -->
    <div class="flex items-center justify-between pt-4 border-t-2 border-black/10 flex-wrap gap-3">
        <button type="button" onclick="prevQuestion()" id="btn-prev" class="px-5 py-2.5 bg-white hover:bg-neutral-100 font-display font-black text-xs brutal-border rounded-xl shadow-brutal-sm brutal-btn-press disabled:opacity-40 disabled:pointer-events-none" disabled>
            ← Kembali
        </button>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" onclick="toggleHint()" class="px-4 py-2.5 bg-white hover:bg-neutral-100 font-display font-black text-xs brutal-border rounded-xl shadow-brutal-sm brutal-btn-press flex items-center gap-1.5">
                <span>💡</span>
                <span>Petunjuk</span>
            </button>

            <button type="button" onclick="nextQuestion()" id="btn-next" class="px-6 py-2.5 bg-[#C96614] hover:bg-[#b05810] text-white font-display font-black text-xs sm:text-sm brutal-border rounded-xl shadow-brutal-sm brutal-btn-press flex items-center gap-2">
                <span id="btn-next-text">Seterusnya ➔</span>
            </button>
        </div>
    </div>

</div>

<!-- Quiz Completed Celebration Modal -->
<div id="quiz-result-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white brutal-border-3 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-brutal-xl text-center relative overflow-hidden">
        <div class="w-20 h-20 rounded-full bg-neo-yellow brutal-border mx-auto flex items-center justify-center text-4xl shadow-brutal mb-4" id="modal-icon">
            🎉
        </div>

        <span class="px-3 py-1 rounded-full bg-emerald-200 brutal-border text-xs font-black uppercase text-emerald-950 shadow-brutal-sm inline-block mb-2">
            Kuiz Modul 4 Tamat
        </span>

        <h3 class="font-display font-black text-2xl text-neutral-900 mb-1" id="modal-status-text">
            Tahniah, Cemerlang!
        </h3>
        <p class="text-xs font-semibold text-neutral-600 mb-5 px-4" id="modal-desc-text">
            Anda telah berjaya menjawab kuiz pengenalan fakulti & Sistem SKS.
        </p>

        <!-- Stats Bento Box -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="p-3.5 bg-[#FAF8F5] brutal-border rounded-xl text-center">
                <span class="text-[10px] font-black uppercase text-neutral-400 block">Skor Akhir</span>
                <span id="modal-score" class="font-display font-black text-2xl text-neutral-900 block mt-0.5">5 / 5</span>
            </div>
            <div class="p-3.5 bg-neo-yellow brutal-border rounded-xl text-center">
                <span class="text-[10px] font-black uppercase text-amber-900 block">Mata Diperoleh</span>
                <span id="modal-xp" class="font-display font-black text-2xl text-amber-950 block mt-0.5">+150 XP</span>
            </div>
        </div>

        <div class="space-y-2.5">
            <a href="{{ route('quests.index') }}" class="w-full py-3 bg-[#E78B24] hover:bg-[#c9741a] text-white font-display font-black text-xs uppercase tracking-wider brutal-border rounded-xl shadow-brutal-sm brutal-btn-press block">
                Kembali ke Peta Misi (Buka Modul 5) ➔
            </a>
            <a href="{{ route('dashboard') }}" class="w-full py-2.5 bg-white hover:bg-neutral-100 font-display font-black text-xs brutal-border rounded-xl shadow-brutal-sm brutal-btn-press block">
                Ke Papan Pemuka
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentQuestion = 1;
    const totalQuestions = {{ $questions->count() }};
    const userAnswers = {};
    let startTime = Date.now();

    // 5-minute Countdown Timer
    let timeLeft = 300;
    const timerEl = document.getElementById('quiz-timer');
    const timerInterval = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = '00:00s';
            submitQuizAnswers();
            return;
        }
        timeLeft--;
        const mins = String(Math.floor(timeLeft / 60)).padStart(2, '0');
        const secs = String(timeLeft % 60).padStart(2, '0');
        timerEl.textContent = `${mins}:${secs}s`;
    }, 1000);

    function selectAnswer(questionNum, optionLetter, buttonEl) {
        userAnswers[questionNum] = optionLetter;
        playSound('click');

        // Reset styling for options in this question
        const container = document.getElementById(`options-group-${questionNum}`);
        container.querySelectorAll('.quiz-option-btn').forEach(btn => {
            btn.className = "quiz-option-btn w-full text-left p-4 bg-white brutal-border rounded-xl font-bold text-xs sm:text-sm text-neutral-800 shadow-brutal-sm hover:bg-neutral-50 flex items-center gap-3.5 transition-all brutal-btn-press";
            btn.querySelector('.option-badge').className = "option-badge w-8 h-8 rounded-lg bg-neutral-100 brutal-border flex items-center justify-center font-display font-black text-xs shrink-0";
        });

        // Highlight selected
        buttonEl.className = "quiz-option-btn w-full text-left p-4 bg-neo-yellow brutal-border rounded-xl font-bold text-xs sm:text-sm text-neutral-900 shadow-brutal-lg ring-2 ring-black flex items-center gap-3.5 transition-all brutal-btn-press";
        buttonEl.querySelector('.option-badge').className = "option-badge w-8 h-8 rounded-lg bg-white brutal-border flex items-center justify-center font-display font-black text-xs shrink-0 text-amber-950";

        // Mark step indicator as answered
        const stepBtn = document.getElementById(`step-btn-${questionNum}`);
        stepBtn.classList.remove('bg-neutral-100');
        stepBtn.classList.add('bg-emerald-400', 'text-white');

        showToast('Jawapan Dipilih', `Pilihan ${optionLetter} telah direkodkan untuk Soalan ${questionNum}`, '📝');
    }

    function goToQuestion(num) {
        if (num < 1 || num > totalQuestions) return;

        // Hide current
        document.getElementById(`question-card-${currentQuestion}`).classList.add('hidden');
        document.getElementById(`step-btn-${currentQuestion}`).classList.remove('ring-2', 'ring-black', 'scale-105');

        // Show target
        currentQuestion = num;
        document.getElementById(`question-card-${currentQuestion}`).classList.remove('hidden');
        document.getElementById(`step-btn-${currentQuestion}`).classList.add('ring-2', 'ring-black', 'scale-105');

        // Update Tracker Text
        document.getElementById('question-tracker-text').textContent = `Soalan ${currentQuestion} daripada ${totalQuestions}`;

        // Update Prev Button
        document.getElementById('btn-prev').disabled = (currentQuestion === 1);

        // Update Next Button Text
        const nextText = document.getElementById('btn-next-text');
        if (currentQuestion === totalQuestions) {
            nextText.textContent = "Hantar Jawapan Kuiz 🎉";
        } else {
            nextText.textContent = "Seterusnya ➔";
        }

        playSound('click');
    }

    function prevQuestion() {
        if (currentQuestion > 1) {
            goToQuestion(currentQuestion - 1);
        }
    }

    function nextQuestion() {
        if (currentQuestion < totalQuestions) {
            goToQuestion(currentQuestion + 1);
        } else {
            submitQuizAnswers();
        }
    }

    function toggleHint() {
        const hint = document.getElementById(`hint-box-${currentQuestion}`);
        if (hint) {
            hint.classList.toggle('hidden');
            playSound('click');
        }
    }

    function submitQuizAnswers() {
        clearInterval(timerInterval);
        const timeTaken = Math.round((Date.now() - startTime) / 1000);

        // Submit via AJAX
        fetch("{{ route('quiz.submit') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                answers: userAnswers,
                time_taken: timeTaken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                playSound('fanfare');

                document.getElementById('modal-score').textContent = `${data.score} / ${data.total}`;
                document.getElementById('modal-xp').textContent = `+${data.xp_earned} XP`;

                if (data.score === data.total) {
                    document.getElementById('modal-status-text').textContent = "Skor Sempurna 100%! 🏆";
                    document.getElementById('modal-desc-text').textContent = "Tahniah! Anda telah menjawab semua soalan dengan tepat dan membuka lencana Juara Kuiz!";
                } else if (data.is_passed) {
                    document.getElementById('modal-status-text').textContent = "Tahniah, Anda Lulus! 🎉";
                    document.getElementById('modal-desc-text').textContent = "Kejayaan cemerlang! Modul 4 telah disahkan selesai dan Modul 5 dibuka.";
                } else {
                    document.getElementById('modal-status-text').textContent = "Perlu Tingkatkan Pemahaman";
                    document.getElementById('modal-desc-text').textContent = "Anda memperoleh " + data.score + " daripada " + data.total + ". Sila semak semula nota fakulti.";
                }

                document.getElementById('quiz-result-modal').classList.remove('hidden');
            }
        })
        .catch(err => {
            console.error(err);
            // Fallback: standard page reload
            window.location.reload();
        });
    }
</script>
@endpush
@endsection
