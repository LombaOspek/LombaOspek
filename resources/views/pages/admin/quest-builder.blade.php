@extends('layouts.app')

@section('title', 'Quest Builder')
@section('page-title', 'Cipta & Konfigurasi Misi')
@section('page-kicker', 'Superadmin · Quest Builder')

@section('content')
@php
    $editing = $editing ?? null;
    $action = $editing ? route('admin.quests.update', $editing) : route('admin.quests.store');
    $val = fn (string $key, mixed $default = null) => old($key, $editing?->{$key} ?? $default);
@endphp

<div class="flex flex-col gap-4">

    <section class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-1.5">
                <span class="stamp bg-vermilion text-white">
                    <x-icon name="shield" class="size-3" :stroke="2.2" />
                    Superadmin
                </span>
                <span class="stamp bg-paper">{{ $drafts }} draf misi</span>
            </p>
            <h1 class="mt-1.5 font-display text-[1.5rem] font-extrabold leading-tight tracking-tight sm:text-[1.8rem]">
                {{ $editing ? 'Ubah Misi' : 'Cipta Misi Baru' }}
            </h1>
            <p class="mt-0.5 max-w-2xl text-[0.78rem] font-medium leading-relaxed text-ink-soft">
                Rancang misi orientasi beserta mekanisme verifikasinya. Pilih token atau GPS untuk menutup celah curang.
            </p>
        </div>
    </section>

    @if ($errors->any())
        <div class="rounded-lg border-2 border-vermilion bg-vermilion/10 p-3" role="alert">
            <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.1em] text-vermilion-deep">Periksa kembali</p>
            <ul class="mt-1 flex flex-col gap-0.5">
                @foreach ($errors->all() as $error)
                    <li class="text-[0.7rem] font-semibold text-vermilion-deep">• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="grid gap-4 xl:grid-cols-[1.55fr_1fr]" data-submit-lock>
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        {{-- Kolom form --}}
        <div class="flex flex-col gap-4">

            {{-- 1. Info dasar --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header title="Informasi Dasar Misi" subtitle="Wajib diisi" icon="flag" />

                <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="title" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                            Nama Misi <span class="text-vermilion">*</span>
                        </label>
                        <input type="text" id="title" name="title" required maxlength="150" value="{{ $val('title') }}"
                            placeholder="Contoh: Safari Digital & Menara Sains Komputer"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div>
                        <label for="category" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Kategori <span class="text-vermilion">*</span></label>
                        <input type="text" id="category" name="category" required maxlength="80" value="{{ $val('category', 'Eksplorasi Kampus') }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div>
                        <label for="xp_reward" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Hadiah XP <span class="text-vermilion">*</span></label>
                        <input type="number" id="xp_reward" name="xp_reward" required min="10" max="1000" value="{{ $val('xp_reward', 250) }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Deskripsi & Instruksi <span class="text-vermilion">*</span></label>
                        <textarea id="description" name="description" rows="3" required minlength="20" maxlength="2000"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-medium leading-relaxed focus:outline-none">{{ $val('description') }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="objective" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Tujuan Pembelajaran</label>
                        <input type="text" id="objective" name="objective" maxlength="500" value="{{ $val('objective') }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div>
                        <label for="location" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Lokasi</label>
                        <input type="text" id="location" name="location" maxlength="150" value="{{ $val('location') }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div>
                        <label for="estimated_minutes" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Estimasi (menit)</label>
                        <input type="number" id="estimated_minutes" name="estimated_minutes" min="1" max="600" value="{{ $val('estimated_minutes', 30) }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div>
                        <label for="step_number" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Urutan Modul</label>
                        <input type="number" id="step_number" name="step_number" min="1" max="99" value="{{ $val('step_number', $nextStep) }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div>
                        <label for="unlock_day" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Terbuka Hari ke-</label>
                        <input type="number" id="unlock_day" name="unlock_day" min="1" max="30" value="{{ $val('unlock_day', 1) }}"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>

                    <div>
                        <label for="difficulty" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Tingkat Kesulitan</label>
                        <select id="difficulty" name="difficulty" class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                            <option value="1" @selected((int) $val('difficulty', 2) === 1)>Mudah (15 menit)</option>
                            <option value="2" @selected((int) $val('difficulty', 2) === 2)>Sedang (30 menit)</option>
                            <option value="3" @selected((int) $val('difficulty', 2) === 3)>Sulit (60 menit)</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Status</label>
                        <select id="status" name="status" class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                            <option value="draft" @selected($val('status') === 'draft')>Draf (belum tampil)</option>
                            <option value="published" @selected($val('status', 'published') === 'published')>Publikasikan</option>
                        </select>
                    </div>

                    <div>
                        <label for="action_type" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Tipe Aksi</label>
                        <select id="action_type" name="action_type" class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                            @foreach ($actionTypes as $type)
                                <option value="{{ $type->value }}" @selected($val('action_type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            {{-- 2. Target sasaran --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header title="Target Sasaran" subtitle="Filter multi-tenant" icon="target" />

                <div class="mt-3 flex flex-col gap-1.5">
                    @foreach (['all' => 'Seluruh universitas', 'faculty' => 'Fakultas tertentu', 'major' => 'Program studi tertentu'] as $scope => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-sm border-[1.5px] border-ink/40 bg-paper-dim px-2 py-1.5">
                            <input type="radio" name="target_scope" value="{{ $scope }}"
                                @checked($val('target_scope', 'all') === $scope)
                                class="size-3.5 border-2 border-ink accent-teal"
                                data-scope-radio>
                            <span class="text-[0.72rem] font-semibold">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div id="major-targets" class="mt-2.5 {{ $val('target_scope', 'all') === 'major' ? '' : 'hidden' }} rounded-sm border-[1.5px] border-dashed border-ink/40 p-2.5">
                    <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Pilih program studi sasaran</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @foreach ($majors as $major)
                            <label class="flex cursor-pointer items-center gap-1.5 rounded-xs border-[1.5px] border-ink/40 bg-paper px-1.5 py-1">
                                <input type="checkbox" name="target_study_programs[]" value="{{ $major }}"
                                    @checked(in_array($major, (array) $val('target_study_programs', []), true))
                                    class="size-3 border-2 border-ink accent-teal">
                                <span class="text-[0.66rem] font-semibold">{{ $major }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- 3. Kolaborasi UKM --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header title="Kolaborasi UKM" subtitle="Opsional" icon="flag" />
                <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                    <div>
                        <label for="sponsor_name" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Sponsor Misi</label>
                        <input type="text" id="sponsor_name" name="sponsor_name" maxlength="120" value="{{ $val('sponsor_name') }}"
                            placeholder="Contoh: Muria Computing Club"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>
                    <div>
                        <label for="bonus_reward" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">Hadiah Bonus</label>
                        <input type="text" id="bonus_reward" name="bonus_reward" maxlength="120" value="{{ $val('bonus_reward') }}"
                            placeholder="Contoh: Stiker Hologram + Pin"
                            class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.76rem] font-semibold focus:outline-none">
                    </div>
                </div>
            </section>

            {{-- 4. Mekanisme verifikasi --}}
            <section class="rounded-lg border-2 border-ink bg-amber/15 p-4 shadow-[var(--shadow-flat)] sm:p-5">
                <x-panel-header title="Mekanisme Verifikasi" subtitle="Sistem validasi anti-curang" icon="shield" />

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($methods as $method)
                        <label class="flex cursor-pointer items-start gap-2 rounded-sm border-2 border-ink bg-paper p-2.5 transition hover:bg-white has-[:checked]:bg-amber/40 has-[:checked]:shadow-[var(--shadow-flat-sm)]">
                            <input type="radio" name="verification_method" value="{{ $method->value }}"
                                @checked($val('verification_method', 'none') === $method->value)
                                class="mt-0.5 size-3.5 border-2 border-ink accent-teal" data-method-radio>
                            <span class="min-w-0">
                                <span class="flex items-center gap-1 font-display text-[0.72rem] font-bold">
                                    <x-icon :name="$method->icon()" class="size-3.5" :stroke="2" />
                                    {{ $method->label() }}
                                </span>
                                <span class="mt-0.5 block text-[0.64rem] font-medium leading-snug text-ink-soft">{{ $method->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                {{-- Konfigurasi token --}}
                <div id="config-token" class="mt-3 {{ $val('verification_method') === 'token' ? '' : 'hidden' }} rounded-sm border-2 border-dashed border-ink/50 bg-paper p-2.5">
                    <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Konfigurasi token</p>
                    <div class="mt-1.5 grid gap-2 sm:grid-cols-3">
                        <div>
                            <label for="token_code" class="block text-[0.62rem] font-bold text-ink-soft">Kode Token</label>
                            <input type="text" id="token_code" name="token_code" maxlength="40"
                                placeholder="{{ $editing?->token_hint ? 'Kosongkan = tetap' : 'MCC-UMK-99' }}"
                                class="mt-1 w-full rounded-xs border-2 border-ink bg-white px-2 py-1 font-mono text-[0.7rem] font-bold uppercase focus:outline-none">
                            <p class="mt-0.5 text-[0.58rem] font-medium text-ink-faint">Disimpan sebagai hash.</p>
                        </div>
                        <div>
                            <label for="token_quota" class="block text-[0.62rem] font-bold text-ink-soft">Kuota Klaim</label>
                            <input type="number" id="token_quota" name="token_quota" min="1" max="10000" value="{{ $val('token_quota') }}"
                                placeholder="400"
                                class="mt-1 w-full rounded-xs border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none">
                        </div>
                        <div>
                            <label for="token_expires_at" class="block text-[0.62rem] font-bold text-ink-soft">Kedaluwarsa</label>
                            <input type="datetime-local" id="token_expires_at" name="token_expires_at"
                                value="{{ $val('token_expires_at') ? \Illuminate\Support\Carbon::parse($val('token_expires_at'))->format('Y-m-d\TH:i') : '' }}"
                                class="mt-1 w-full rounded-xs border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none">
                        </div>
                    </div>
                </div>

                {{-- Konfigurasi GPS --}}
                <div id="config-gps" class="mt-3 {{ $val('verification_method') === 'gps' ? '' : 'hidden' }} rounded-sm border-2 border-dashed border-ink/50 bg-paper p-2.5">
                    <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Geofence lokasi</p>
                    <div class="mt-1.5 grid gap-2 sm:grid-cols-3">
                        <div>
                            <label for="latitude" class="block text-[0.62rem] font-bold text-ink-soft">Latitude</label>
                            <input type="text" id="latitude" name="latitude" value="{{ $val('latitude') }}" placeholder="-6.8048000"
                                class="mt-1 w-full rounded-xs border-2 border-ink bg-white px-2 py-1 font-mono text-[0.7rem] font-bold focus:outline-none">
                        </div>
                        <div>
                            <label for="longitude" class="block text-[0.62rem] font-bold text-ink-soft">Longitude</label>
                            <input type="text" id="longitude" name="longitude" value="{{ $val('longitude') }}" placeholder="110.8405000"
                                class="mt-1 w-full rounded-xs border-2 border-ink bg-white px-2 py-1 font-mono text-[0.7rem] font-bold focus:outline-none">
                        </div>
                        <div>
                            <label for="radius_meters" class="block text-[0.62rem] font-bold text-ink-soft">Radius (m)</label>
                            <input type="number" id="radius_meters" name="radius_meters" min="10" max="5000" value="{{ $val('radius_meters', 150) }}"
                                class="mt-1 w-full rounded-xs border-2 border-ink bg-white px-2 py-1 text-[0.7rem] font-semibold focus:outline-none">
                        </div>
                    </div>
                    <p class="mt-1.5 text-[0.6rem] font-medium text-ink-faint">
                        Titik acuan Auditorium UMK: <span class="font-mono font-bold">-6.8048, 110.8405</span>
                    </p>
                </div>
            </section>

            {{-- Aksi --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <p class="font-display text-[0.8rem] font-bold">Siap dipublikasikan?</p>
                <p class="mt-0.5 text-[0.68rem] font-medium text-ink-soft">
                    Simpan sebagai draf untuk ditinjau, atau publikasikan agar langsung muncul di peta misi mahasiswa.
                </p>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    <button type="submit" name="status" value="draft" class="btn btn-ghost">
                        <x-icon name="layers" class="size-4" :stroke="2" />
                        Simpan Draf
                    </button>
                    <button type="submit" name="status" value="published" class="btn btn-primary">
                        <x-icon name="check" class="size-4" :stroke="2.6" />
                        Simpan &amp; Publikasikan
                    </button>
                </div>
            </section>
        </div>

        {{-- Preview --}}
        <aside class="flex flex-col gap-4">
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <x-panel-header title="Pratonton Layar Mahasiswa" subtitle="Live mockup" icon="eye" />

                <div class="mt-3 rounded-xl border-2 border-ink bg-paper-dim p-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="stamp bg-amber" id="pv-xp">+250 XP</span>
                        <span class="stamp bg-paper" id="pv-category">Eksplorasi</span>
                    </div>
                    <p class="mt-2 font-display text-[0.86rem] font-bold leading-snug" id="pv-title">Nama misi…</p>
                    <p class="mt-1 line-clamp-3 text-[0.68rem] font-medium leading-relaxed text-ink-soft" id="pv-desc">
                        Deskripsi misi akan tampil di sini.
                    </p>

                    <div class="mt-2.5 flex items-center gap-1.5 rounded-xs border-[1.5px] border-ink bg-paper px-2 py-1.5">
                        <x-icon name="shield" class="size-3.5 text-teal-deep" :stroke="2" />
                        <span class="text-[0.64rem] font-bold" id="pv-method">Tanpa bukti</span>
                    </div>

                    <div class="mt-2.5 rounded-sm border-2 border-ink bg-amber px-3 py-2 text-center font-display text-[0.74rem] font-bold shadow-[var(--shadow-flat-sm)]">
                        Mulakan Misi Ini →
                    </div>
                </div>

                <p class="mt-2 rounded-sm border-[1.5px] border-dashed border-ink/35 bg-paper-dim p-2 text-[0.62rem] font-medium leading-snug text-ink-faint">
                    Tip: misi dengan token atau GPS meningkatkan partisipasi nyata dan menutup peluang titip absen.
                </p>
            </section>

            {{-- Daftar misi --}}
            <section class="rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]">
                <x-panel-header title="Daftar Misi" :subtitle="$quests->count().' misi tersimpan'" icon="layers" />

                <ul class="mt-2.5 flex max-h-[24rem] flex-col gap-1 overflow-y-auto pr-1">
                    @foreach ($quests as $quest)
                        <li class="rounded-xs border-[1.5px] border-ink/40 bg-paper-dim px-2 py-1.5">
                            <div class="flex items-start justify-between gap-1.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[0.7rem] font-bold leading-tight">{{ $quest->title }}</p>
                                    <p class="text-[0.58rem] font-semibold text-ink-faint">
                                        {{ $quest->code }} · {{ $quest->verification_method->label() }} · {{ $quest->submissions_count }} bukti
                                    </p>
                                </div>
                                <span class="stamp shrink-0 {{ $quest->status === 'draft' ? 'bg-paper-dim text-ink-faint' : 'bg-teal text-white' }} text-[0.5rem]">
                                    {{ $quest->status }}
                                </span>
                            </div>
                            <a href="{{ route('admin.quests.index', ['edit' => $quest->id]) }}"
                                class="mt-1 inline-block text-[0.6rem] font-bold text-ink underline decoration-dotted underline-offset-2 hover:text-vermilion">
                                Ubah
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </form>
</div>
@endsection

@push('scripts')
<script type="module">
    // Tampilkan konfigurasi sesuai metode verifikasi yang dipilih.
    const tokenBox = document.getElementById('config-token');
    const gpsBox = document.getElementById('config-gps');

    document.querySelectorAll('[data-method-radio]').forEach((radio) => {
        radio.addEventListener('change', () => {
            tokenBox.classList.toggle('hidden', radio.value !== 'token' || !radio.checked);
            gpsBox.classList.toggle('hidden', radio.value !== 'gps' || !radio.checked);
        });
    });

    // Target program studi hanya relevan untuk scope "major".
    const majorBox = document.getElementById('major-targets');

    document.querySelectorAll('[data-scope-radio]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (radio.checked) {
                majorBox.classList.toggle('hidden', radio.value !== 'major');
            }
        });
    });

    // Preview live.
    const title = document.getElementById('title');
    const desc = document.getElementById('description');
    const xp = document.getElementById('xp_reward');
    const category = document.getElementById('category');

    const pvTitle = document.getElementById('pv-title');
    const pvDesc = document.getElementById('pv-desc');
    const pvXp = document.getElementById('pv-xp');
    const pvCategory = document.getElementById('pv-category');
    const pvMethod = document.getElementById('pv-method');

    const sync = () => {
        pvTitle.textContent = title.value || 'Nama misi…';
        pvDesc.textContent = desc.value || 'Deskripsi misi akan tampil di sini.';
        pvXp.textContent = '+' + (xp.value || 0) + ' XP';
        pvCategory.textContent = category.value || 'Kategori';
    };

    [title, desc, xp, category].forEach((el) => el?.addEventListener('input', sync));

    document.querySelectorAll('[data-method-radio]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const label = radio.closest('label')?.querySelector('.font-display')?.textContent.trim();
            if (radio.checked) pvMethod.textContent = label || 'Tanpa bukti';
        });
    });

    sync();
</script>
@endpush