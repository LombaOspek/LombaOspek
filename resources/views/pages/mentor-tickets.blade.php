@extends('layouts.app')

@section('title', 'Tiket Mentor')
@section('page-title', 'Tiket Bantuan')
@section('page-kicker', $openCount.' menunggu jawaban')

@section('content')
<div class="flex flex-col gap-4">

    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-display text-[1.45rem] font-extrabold leading-tight tracking-tight sm:text-[1.7rem]">
                Jawab pertanyaan mahasiswa.
            </h1>
            <p class="mt-0.5 max-w-xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Tiket masuk tanpa balasan otomatis. Jawaban Anda langsung terlihat di halaman Pusat Bantuan mahasiswa.
            </p>
        </div>
        <span class="stamp bg-amber">
            <x-icon name="ticket" class="size-3" :stroke="2.2" />
            {{ $openCount }} terbuka
        </span>
    </section>

    @if ($tickets->isEmpty())
        <section class="rounded-lg border-2 border-dashed border-ink/40 bg-paper p-6 text-center">
            <x-icon name="check-circle" class="mx-auto size-8 text-teal-deep" :stroke="1.8" />
            <p class="mt-2 font-display text-[0.9rem] font-bold">Tidak ada tiket masuk.</p>
            <p class="mt-0.5 text-[0.72rem] font-medium text-ink-faint">Semua pertanyaan mahasiswa sudah terjawab.</p>
        </section>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($tickets as $ticket)
                @php $status = $ticket->status; @endphp
                <article class="rounded-lg border-2 border-ink bg-paper shadow-[var(--shadow-flat)] {{ $status === \App\Enums\TicketStatus::Open ? '' : 'opacity-85' }}">
                    <div class="flex flex-wrap items-start justify-between gap-2 border-b-2 border-ink/10 p-3.5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="stamp {{ $status->badgeClasses() }}">{{ $status->label() }}</span>
                                <span class="stamp bg-paper-dim">{{ $ticket->category }}</span>
                                <span class="stamp bg-paper-dim font-mono">#{{ $ticket->id }}</span>
                            </div>
                            <h2 class="mt-1.5 font-display text-[0.9rem] font-bold leading-snug">{{ $ticket->subject }}</h2>
                            <p class="mt-0.5 text-[0.64rem] font-semibold text-ink-faint">
                                {{ $ticket->user?->name }} · {{ $ticket->user?->nim }} ·
                                {{ $ticket->user?->group_name }} ·
                                {{ $ticket->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <div class="p-3.5">
                        <p class="text-[0.76rem] font-medium leading-relaxed text-ink-soft">{{ $ticket->message }}</p>

                        @if ($ticket->reply)
                            <div class="mt-2.5 rounded-sm border-l-2 border-teal bg-teal/10 p-2.5">
                                <p class="text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-teal-deep">
                                    Balasan {{ $ticket->answeredBy?->name ?? 'mentor' }}
                                    @if ($ticket->answered_at)
                                        · {{ $ticket->answered_at->diffForHumans() }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-[0.74rem] font-medium leading-relaxed text-ink">{{ $ticket->reply }}</p>
                            </div>
                        @endif

                        <details class="mt-2.5" {{ $status === \App\Enums\TicketStatus::Open ? 'open' : '' }}>
                            <summary class="cursor-pointer font-display text-[0.7rem] font-bold text-ink underline decoration-dotted underline-offset-2">
                                {{ $ticket->reply ? 'Ubah balasan' : 'Tulis balasan' }}
                            </summary>

                            <form method="POST" action="{{ route('mentor.tickets.reply', $ticket) }}" class="mt-2 flex flex-col gap-2" data-submit-lock>
                                @csrf
                                <label for="reply-{{ $ticket->id }}" class="sr-only">Balasan</label>
                                <textarea
                                    id="reply-{{ $ticket->id }}"
                                    name="reply"
                                    rows="3"
                                    required
                                    minlength="10"
                                    maxlength="2000"
                                    class="w-full rounded-sm border-2 border-ink bg-white p-2 text-[0.76rem] font-medium leading-relaxed focus:outline-none"
                                    placeholder="Tulis jawaban yang jelas dan bisa ditindaklanjuti mahasiswa."
                                >{{ $ticket->reply }}</textarea>

                                <div class="flex flex-wrap items-center gap-2">
                                    <select name="status" class="rounded-sm border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none">
                                        <option value="answered" @selected($status === \App\Enums\TicketStatus::Answered)>Sudah dijawab</option>
                                        <option value="resolved" @selected($status === \App\Enums\TicketStatus::Resolved)>Tandai selesai</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <x-icon name="send" class="size-3.5" :stroke="2" />
                                        Kirim balasan
                                    </button>
                                </div>
                            </form>
                        </details>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-2">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection