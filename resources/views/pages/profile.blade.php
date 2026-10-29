@extends('layouts.app')

@section('title', 'Profil & Pengaturan')
@section('page-title', 'Profil & Pengaturan')
@section('page-kicker', 'Tab: '.($activeTab === 'helpdesk' ? 'Pusat Bantuan' : ($activeTab === 'settings' ? 'Pengaturan' : 'Profil')))

@section('content')
@php
    $tabs = [
        ['key' => 'profile', 'label' => 'Profil', 'icon' => 'user'],
        ['key' => 'settings', 'label' => 'Pengaturan', 'icon' => 'settings'],
        ['key' => 'helpdesk', 'label' => 'Bantuan', 'icon' => 'help'],
    ];
@endphp

<div class="flex flex-col gap-4">

    <nav class="flex flex-wrap items-center gap-1.5" aria-label="Tab pengaturan">
        @foreach ($tabs as $tab)
            <a
                href="{{ route('profile', ['tab' => $tab['key']]) }}"
                @if ($activeTab === $tab['key']) aria-current="page" @endif
                class="inline-flex items-center gap-1.5 rounded-sm border-2 border-ink px-2.5 py-1.5 font-display text-[0.74rem] font-bold transition
                    {{ $activeTab === $tab['key'] ? 'bg-ink text-paper shadow-[var(--shadow-flat-sm)]' : 'bg-paper text-ink-soft hover:bg-paper-dim' }}"
            >
                <x-icon :name="$tab['icon']" class="size-3.5" :stroke="2" />
                {{ $tab['label'] }}
                @if ($tab['key'] === 'helpdesk' && $pendingTickets > 0)
                    <span class="grid size-4 place-items-center rounded-full border border-ink bg-amber text-[0.52rem] font-extrabold text-ink">
                        {{ $pendingTickets }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>

    @if ($activeTab === 'profile')
        <div class="grid gap-4 xl:grid-cols-[1fr_1.6fr]">

            {{-- Kartu identitas --}}
            <section class="flex flex-col gap-3">
                <div class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                    <div class="flex items-center gap-3">
                        <span class="grid size-14 shrink-0 place-items-center rounded-full border-2 border-ink bg-sky font-display text-[1.3rem] font-extrabold">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-display text-[0.95rem] font-extrabold leading-tight">{{ $user->name }}</p>
                            <p class="font-mono text-[0.68rem] font-bold text-ink-faint">{{ $user->nim }}</p>
                            <span class="stamp mt-1 bg-teal text-white">
                                <x-icon name="check-circle" class="size-2.5" :stroke="2.6" />
                                Mahasiswa Aktif
                            </span>
                        </div>
                    </div>

                    <dl class="mt-3 flex flex-col gap-1.5 border-t-2 border-dashed border-ink/25 pt-2.5 text-[0.72rem]">
                        <div class="flex items-baseline justify-between gap-2">
                            <dt class="font-semibold text-ink-faint">Level</dt>
                            <dd class="font-bold">{{ $user->level }} · {{ $user->level_title }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <dt class="font-semibold text-ink-faint">Total XP</dt>
                            <dd class="numeric font-bold">{{ number_format($user->xp, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <dt class="font-semibold text-ink-faint">Kelompok</dt>
                            <dd class="truncate font-bold">{{ $user->group_name ?: '—' }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <dt class="font-semibold text-ink-faint">Peringkat</dt>
                            <dd class="font-bold">#{{ $rank }}</dd>
                        </div>
                    </dl>

                    <div class="mt-2.5">
                        <div class="flex items-baseline justify-between text-[0.6rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">
                            <span>Progres level</span>
                            <span class="numeric">{{ $user->level_progress }}%</span>
                        </div>
                        <x-progress :value="$user->level_progress" tone="amber" height="h-2" class="mt-1" />
                    </div>
                </div>

                <div class="rounded-lg border-2 border-ink bg-teal/15 p-3.5 shadow-[var(--shadow-flat-sm)]">
                    <p class="text-[0.56rem] font-extrabold uppercase tracking-[0.13em] text-ink/70">Rekap orientasi</p>
                    <div class="mt-1.5 flex items-end justify-between gap-2">
                        <p class="font-display text-[1.5rem] font-extrabold leading-none">
                            {{ $completedQuests }}<span class="text-[0.9rem] font-bold text-ink-faint">/{{ $totalQuests }}</span>
                        </p>
                        <p class="text-right text-[0.64rem] font-semibold leading-tight text-ink-soft">
                            modul selesai<br>{{ $achievementCount }} lencana terbuka
                        </p>
                    </div>
                </div>

                @if ($certificate['eligible'])
                    <a href="{{ route('certificate') }}" class="btn btn-primary w-full">
                        <x-icon name="certificate" class="size-4" :stroke="2" />
                        Buka sertifikat Anda
                    </a>
                @endif
            </section>

            {{-- Formulir profil --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header
                    title="Data akademik"
                    subtitle="NIM, email, dan peran hanya dapat diubah oleh admin fakultas"
                    icon="user"
                />

                <form method="POST" action="{{ route('profile.update') }}" class="mt-3 flex flex-col gap-3" data-submit-lock>
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">
                                Nama lengkap <span class="text-vermilion">*</span>
                            </label>
                            <input
                                type="text" id="name" name="name" required maxlength="120"
                                value="{{ old('name', $user->name) }}"
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                            >
                            @error('name')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">NIM</label>
                            <input type="text" value="{{ $user->nim }}" readonly
                                class="mt-1 w-full cursor-not-allowed rounded-sm border-2 border-ink/30 bg-paper-dim px-2.5 py-2 font-mono text-[0.8rem] font-bold text-ink-faint">
                        </div>

                        <div>
                            <label class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">Email kampus</label>
                            <input type="email" value="{{ $user->email }}" readonly
                                class="mt-1 w-full cursor-not-allowed rounded-sm border-2 border-ink/30 bg-paper-dim px-2.5 py-2 text-[0.8rem] font-semibold text-ink-faint">
                        </div>

                        <div>
                            <label for="phone" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">Nomor WhatsApp</label>
                            <input
                                type="tel" id="phone" name="phone" maxlength="25"
                                value="{{ old('phone', $user->phone) }}"
                                placeholder="0812-3456-7890"
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                            >
                            @error('phone')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="group_name" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">Kelompok orientasi</label>
                            <input
                                type="text" id="group_name" name="group_name" maxlength="60"
                                value="{{ old('group_name', $user->group_name) }}"
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label for="faculty" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">Fakultas</label>
                            <input
                                type="text" id="faculty" name="faculty" maxlength="120"
                                value="{{ old('faculty', $user->faculty) }}"
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                            >
                        </div>

                        <div class="sm:col-span-2">
                            <label for="major" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">Program studi</label>
                            <input
                                type="text" id="major" name="major" maxlength="120"
                                value="{{ old('major', $user->major) }}"
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t-2 border-dashed border-ink/25 pt-3">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="check" class="size-4" :stroke="2.6" />
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </section>
        </div>

    @elseif ($activeTab === 'settings')
        <div class="grid gap-4 xl:grid-cols-2">

            {{-- Preferensi --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header title="Preferensi" subtitle="Perubahan langsung tersimpan saat Anda menekan simpan" icon="settings" />

                <form method="POST" action="{{ route('profile.settings') }}" class="mt-3 flex flex-col gap-2" data-pref-form="sound" data-submit-lock>
                    @csrf

                    @foreach ([
                        ['name' => 'sound_effects', 'value' => $user->sound_effects, 'icon' => 'bell', 'title' => 'Efek suara', 'desc' => 'Bunyi pendek saat menyelesaikan modul atau membuka lencana.'],
                        ['name' => 'email_notifications', 'value' => $user->email_notifications, 'icon' => 'mail', 'title' => 'Notifikasi email', 'desc' => 'Ringkasan modul dan pengumuman resmi kampus.'],
                        ['name' => 'whatsapp_notifications', 'value' => $user->whatsapp_notifications, 'icon' => 'mobile', 'title' => 'Pengingat WhatsApp', 'desc' => 'Arahan mentor sebelum sesi luring dimulai.'],
                        ['name' => 'reduced_motion', 'value' => $user->dark_mode, 'icon' => 'eye', 'title' => 'Kurangi animasi', 'desc' => 'Matikan gerakan dan denyut pada elemen dekoratif.'],
                    ] as $pref)
                        <label class="flex cursor-pointer items-start justify-between gap-3 rounded-sm border-[1.5px] border-ink/40 bg-paper-dim p-2.5 transition hover:border-ink/70">
                            <span class="flex min-w-0 items-start gap-2.5">
                                <span class="grid size-7 shrink-0 place-items-center rounded-sm border-[1.5px] border-ink/60 bg-paper">
                                    <x-icon :name="$pref['icon']" class="size-3.5" :stroke="2" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-display text-[0.76rem] font-bold leading-tight">{{ $pref['title'] }}</span>
                                    <span class="mt-0.5 block text-[0.66rem] font-medium leading-snug text-ink-faint">{{ $pref['desc'] }}</span>
                                </span>
                            </span>
                            <input
                                type="checkbox"
                                name="{{ $pref['name'] }}"
                                value="1"
                                @checked($pref['value'])
                                class="mt-1 size-4 shrink-0 rounded-xs border-2 border-ink accent-teal"
                            >
                        </label>
                    @endforeach

                    <button type="submit" class="btn btn-primary mt-1 w-full">
                        <x-icon name="check" class="size-4" :stroke="2.6" />
                        Simpan preferensi
                    </button>
                </form>

                <p class="mt-2 text-[0.62rem] font-medium leading-snug text-ink-faint">
                    Matikan toggle lalu simpan: nilai benar-benar berubah menjadi nonaktif (bug lama membuat toggle tidak bisa dimatikan).
                </p>
            </section>

            {{-- Kata sandi --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header title="Keamanan akun" subtitle="Gunakan minimal 8 karakter yang tidak dipakai di layanan lain" icon="key" />

                <form method="POST" action="{{ route('profile.password') }}" class="mt-3 flex flex-col gap-3" data-submit-lock>
                    @csrf

                    <div>
                        <label for="current_password" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">
                            Kata sandi saat ini <span class="text-vermilion">*</span>
                        </label>
                        <input
                            type="password" id="current_password" name="current_password" required
                            autocomplete="current-password"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                        >
                        @error('current_password')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">
                            Kata sandi baru <span class="text-vermilion">*</span>
                        </label>
                        <input
                            type="password" id="password" name="password" required minlength="8"
                            autocomplete="new-password"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                        >
                        @error('password')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block font-display text-[0.68rem] font-bold uppercase tracking-[0.08em]">
                            Ulangi kata sandi baru <span class="text-vermilion">*</span>
                        </label>
                        <input
                            type="password" id="password_confirmation" name="password_confirmation" required
                            autocomplete="new-password"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 text-[0.8rem] font-semibold focus:outline-none"
                        >
                    </div>

                    <button type="submit" class="btn btn-accent w-full">
                        <x-icon name="shield" class="size-4" :stroke="2" />
                        Ganti kata sandi
                    </button>
                </form>
            </section>
        </div>

    @else
        {{-- Pusat Bantuan --}}
        <div class="grid gap-4 xl:grid-cols-[1.35fr_1fr]">

            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header title="Pertanyaan umum" :subtitle="$faqs->count().' panduan resmi orientasi'" icon="help" />

                @if ($faqs->isEmpty())
                    <p class="mt-3 rounded-sm border-[1.5px] border-dashed border-ink/35 p-3 text-[0.72rem] font-semibold text-ink-faint">
                        Belum ada FAQ yang dipublikasikan.
                    </p>
                @else
                    <div class="mt-3 flex flex-col gap-1.5" id="faq-list">
                        @foreach ($faqs as $faq)
                            <details class="group rounded-sm border-[1.5px] border-ink/45 bg-paper-dim open:border-ink open:bg-paper">
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-2 p-2.5">
                                    <span class="min-w-0">
                                        <span class="block text-[0.54rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">{{ $faq->category }}</span>
                                        <span class="mt-0.5 block font-display text-[0.76rem] font-bold leading-snug">{{ $faq->question }}</span>
                                    </span>
                                    <span class="mt-0.5 shrink-0 text-ink-faint transition group-open:rotate-45">
                                        <x-icon name="plus" class="size-4" :stroke="2.4" />
                                    </span>
                                </summary>
                                <p class="border-t border-dashed border-ink/25 px-2.5 py-2 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                                    {{ $faq->answer }}
                                </p>
                            </details>
                        @endforeach
                    </div>
                @endif
            </section>

            <div class="flex flex-col gap-4">
                {{-- Formulir tiket --}}
                <section class="rounded-lg border-2 border-ink bg-amber/20 p-4 shadow-[var(--shadow-flat)]">
                    <x-panel-header title="Kirim tiket" subtitle="Dijawab mentor kelompok, bukan balasan otomatis" icon="ticket" />

                    <form method="POST" action="{{ route('help.ticket') }}" class="mt-3 flex flex-col gap-2.5" data-submit-lock>
                        @csrf

                        <div>
                            <label for="subject" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                                Subjek <span class="text-vermilion">*</span>
                            </label>
                            <input
                                type="text" id="subject" name="subject" required minlength="5" maxlength="120"
                                value="{{ old('subject') }}"
                                placeholder="Contoh: Modul 5 tidak bisa dibuka"
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-1.5 text-[0.76rem] font-semibold focus:outline-none"
                            >
                            @error('subject')<p class="mt-1 text-[0.62rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="category" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Kategori</label>
                            <select
                                id="category" name="category" required
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-1.5 text-[0.76rem] font-semibold focus:outline-none"
                            >
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            @error('category')<p class="mt-1 text-[0.62rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="message" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                                Pesan <span class="text-vermilion">*</span>
                            </label>
                            <textarea
                                id="message" name="message" rows="4" required minlength="20" maxlength="2000"
                                placeholder="Jelaskan kendala Anda secara spesifik agar mentor bisa membantu lebih cepat."
                                class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2.5 py-1.5 text-[0.76rem] font-medium leading-relaxed focus:outline-none"
                            >{{ old('message') }}</textarea>
                            @error('message')<p class="mt-1 text-[0.62rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            <x-icon name="send" class="size-4" :stroke="2" />
                            Kirim ke mentor
                        </button>
                    </form>
                </section>

                {{-- Riwayat tiket --}}
                <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                    <x-panel-header title="Tiket Anda" :subtitle="$tickets->count().' tiket terkirim'" icon="layers" />

                    @if ($tickets->isEmpty())
                        <p class="mt-2.5 rounded-sm border-[1.5px] border-dashed border-ink/35 p-2.5 text-[0.7rem] font-semibold leading-snug text-ink-faint">
                            Belum ada tiket. Kirim pertanyaan bila ada kendala selama orientasi.
                        </p>
                    @else
                        <ul class="mt-2.5 flex max-h-[26rem] flex-col gap-2 overflow-y-auto pr-1">
                            @foreach ($tickets as $ticket)
                                <li class="rounded-sm border-[1.5px] border-ink/45 p-2.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="min-w-0 font-display text-[0.74rem] font-bold leading-snug">{{ $ticket->subject }}</p>
                                        <span class="stamp shrink-0 text-[0.52rem] {{ $ticket->status->badgeClasses() }}">
                                            {{ $ticket->status->label() }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-[0.54rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">
                                        {{ $ticket->category }} · {{ $ticket->created_at->diffForHumans(short: true) }}
                                    </p>
                                    <p class="mt-1 text-[0.68rem] font-medium leading-relaxed text-ink-soft">{{ $ticket->message }}</p>

                                    @if ($ticket->reply)
                                        <div class="mt-1.5 rounded-xs border-l-2 border-teal bg-teal/10 p-2">
                                            <p class="text-[0.54rem] font-extrabold uppercase tracking-[0.1em] text-teal-deep">
                                                Balasan {{ $ticket->answeredBy?->name ?? 'mentor' }}
                                            </p>
                                            <p class="mt-0.5 text-[0.68rem] font-medium leading-relaxed text-ink">{{ $ticket->reply }}</p>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        </div>
    @endif
</div>
@endsection