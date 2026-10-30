@props([
    'quest',
    'userQuest' => null,
    'submission' => null,
])

@php
    use App\Enums\SubmissionStatus;
    use App\Enums\VerificationMethod;

    $method = $quest->verification_method;
    $isPending = $submission?->status === SubmissionStatus::Pending;
    $isRejected = $submission?->status === SubmissionStatus::Rejected;
@endphp

{{--
    Modal verifikasi anti-curang. Semua keputusan diambil server lewat endpoint
    `verify.*`; JavaScript hanya mengirim input dan menampilkan hasil.
--}}
<div
    id="verify-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/60 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="verify-modal-title"
    data-quest-id="{{ $quest->id }}"
>
    <div class="w-full max-w-lg overflow-hidden rounded-xl border-2 border-ink bg-paper shadow-[var(--shadow-flat-xl)]">

        {{-- Kepala --}}
        <div class="flex items-start justify-between gap-2 border-b-2 border-ink bg-amber/25 px-4 py-3">
            <div class="flex items-start gap-2.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-sm border-2 border-ink bg-paper">
                    <x-icon :name="$method->icon()" class="size-5" :stroke="2" />
                </span>
                <div class="min-w-0">
                    <h2 id="verify-modal-title" class="font-display text-[0.92rem] font-extrabold leading-tight">
                        Verifikasi {{ $method->label() }}
                    </h2>
                    <p class="mt-0.5 text-[0.64rem] font-semibold text-ink-soft">{{ $quest->title }}</p>
                </div>
            </div>

            <button
                type="button"
                class="grid size-7 shrink-0 place-items-center rounded-xs border-2 border-ink bg-paper transition hover:bg-vermilion hover:text-white"
                data-modal-close
                aria-label="Tutup dialog verifikasi"
            >
                <x-icon name="x" class="size-3.5" :stroke="2.6" />
            </button>
        </div>

        <div class="max-h-[70vh] overflow-y-auto p-4">

            @if ($isPending)
                {{-- Sudah mengunggah, menunggu LO --}}
                <div class="rounded-sm border-2 border-amber bg-amber/20 p-3">
                    <p class="flex items-center gap-1.5 font-display text-[0.8rem] font-bold">
                        <x-icon name="clock" class="size-4" :stroke="2.2" />
                        Menunggu tinjauan LO
                    </p>
                    <p class="mt-1 text-[0.7rem] font-medium leading-relaxed text-ink-soft">
                        Bukti Anda sudah terkirim {{ $submission->created_at->diffForHumans() }}.
                        XP akan dikreditkan setelah LO gugus menyetujui.
                    </p>
                </div>

            @else
                @if ($isRejected)
                    <div class="mb-3 rounded-sm border-2 border-vermilion bg-vermilion/10 p-2.5">
                        <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.1em] text-vermilion-deep">Ditolak sebelumnya</p>
                        <p class="mt-0.5 text-[0.7rem] font-semibold leading-relaxed text-vermilion-deep">
                            {{ $submission->rejection_reason }}
                        </p>
                        <p class="mt-1 text-[0.64rem] font-medium text-ink-soft">Silakan unggah ulang bukti yang lebih jelas.</p>
                    </div>
                @endif

                {{-- TOKEN --}}
                @if ($method === VerificationMethod::Token)
                    <div class="rounded-sm border-2 border-ink/40 bg-paper-dim p-3">
                        <p class="text-[0.68rem] font-medium leading-relaxed text-ink-soft">
                            Kunjungi lokasi misi, temui panitia di stan, lalu minta Kode Token Rahasia.
                            Kode berupa huruf besar dan angka yang tercetak pada stiker stan.
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="stamp bg-paper">Sekali pakai per akun</span>
                            @if ($quest->tokenQuotaLeft() !== null)
                                <span class="stamp bg-paper">Sisa kuota {{ $quest->tokenQuotaLeft() }}</span>
                            @endif
                            @if ($quest->token_expires_at)
                                <span class="stamp bg-paper">s.d. {{ $quest->token_expires_at->translatedFormat('d M H:i') }}</span>
                            @endif
                        </div>
                    </div>

                    <form id="form-token" class="mt-3 flex flex-col gap-2">
                        <label for="token-input" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                            Kode Token Rahasia
                        </label>
                        <input
                            type="text"
                            id="token-input"
                            name="token"
                            required
                            minlength="4"
                            maxlength="40"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="MCC-UMK-99"
                            class="w-full rounded-sm border-2 border-ink bg-white px-2.5 py-2 font-mono text-[0.86rem] font-bold uppercase tracking-[0.12em] focus:outline-none"
                        >
                        <p id="token-error" class="hidden rounded-xs border-[1.5px] border-vermilion bg-vermilion/10 px-2 py-1.5 text-[0.68rem] font-semibold text-vermilion-deep"></p>

                        <button type="submit" class="btn btn-primary mt-1 w-full" data-submit>
                            <x-icon name="key" class="size-4" :stroke="2" />
                            Verifikasi Token
                        </button>
                    </form>

                {{-- GPS --}}
                @elseif ($method === VerificationMethod::Gps)
                    <div class="rounded-sm border-2 border-ink/40 bg-paper-dim p-3">
                        <p class="text-[0.68rem] font-medium leading-relaxed text-ink-soft">
                            Anda harus berada dalam radius <strong class="text-ink">{{ $quest->radius_meters }} m</strong>
                            dari {{ $quest->location ?: 'lokasi misi' }}. Pastikan GPS/Lokasi perangkat aktif.
                        </p>

                        <dl class="mt-2 flex flex-col gap-1 text-[0.66rem]">
                            <div class="flex items-baseline justify-between gap-2">
                                <dt class="font-semibold text-ink-faint">Target lokasi</dt>
                                <dd class="font-mono font-bold">{{ $quest->latitude }}, {{ $quest->longitude }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-2">
                                <dt class="font-semibold text-ink-faint">Koordinat Anda</dt>
                                <dd id="geo-coords" class="font-mono font-bold text-ink-faint">Menunggu izin sensor…</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-2">
                                <dt class="font-semibold text-ink-faint">Jarak</dt>
                                <dd id="geo-distance" class="font-bold">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-3 flex flex-col gap-2">
                        <p id="geo-error" class="hidden rounded-xs border-[1.5px] border-vermilion bg-vermilion/10 px-2 py-1.5 text-[0.68rem] font-semibold text-vermilion-deep"></p>

                        <button type="button" id="btn-detect" class="btn btn-primary w-full" data-geo>
                            <x-icon name="pin" class="size-4" :stroke="2" />
                            Deteksi Lokasi Saya
                        </button>
                        <p class="text-center text-[0.6rem] font-medium text-ink-faint">
                            Jarak dihitung di server — koordinat palsu tetap ditolak.
                        </p>
                    </div>

                {{-- FOTO --}}
                @elseif ($method === VerificationMethod::Photo)
                    <div class="rounded-sm border-2 border-ink/40 bg-paper-dim p-3">
                        <p class="text-[0.68rem] font-medium leading-relaxed text-ink-soft">
                            Unggah tangkapan layar atau foto yang memperlihatkan NIM dan nama Anda.
                            Bukti ditinjau LO gugus sebelum XP diberikan.
                        </p>
                    </div>

                    <form id="form-photo" class="mt-3 flex flex-col gap-2" enctype="multipart/form-data">
                        <label for="proof-input" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                            Bukti Foto
                        </label>
                        <input
                            type="file"
                            id="proof-input"
                            name="proof"
                            accept="image/png,image/jpeg,image/webp"
                            required
                            class="w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-medium file:mr-2 file:rounded-xs file:border-0 file:bg-amber file:px-2 file:py-1 file:font-bold focus:outline-none"
                        >
                        <p class="text-[0.6rem] font-medium text-ink-faint">JPG/PNG/WebP, maksimal 5 MB.</p>

                        <label for="note-input" class="mt-1 block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                            Catatan (opsional)
                        </label>
                        <textarea
                            id="note-input"
                            name="note"
                            rows="2"
                            maxlength="500"
                            placeholder="Contoh: Tangkapan layar SIAKAD dengan NIM terlihat."
                            class="w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.72rem] font-medium leading-relaxed focus:outline-none"
                        ></textarea>

                        <p id="photo-error" class="hidden rounded-xs border-[1.5px] border-vermilion bg-vermilion/10 px-2 py-1.5 text-[0.68rem] font-semibold text-vermilion-deep"></p>

                        <button type="submit" class="btn btn-primary mt-1 w-full" data-submit>
                            <x-icon name="camera" class="size-4" :stroke="2" />
                            Kirim Bukti untuk Ditinjau
                        </button>
                    </form>
                @endif
            @endif

            {{-- Hasil sukses --}}
            <div id="verify-success" class="hidden flex-col items-center gap-2 rounded-sm border-2 border-teal bg-teal/15 p-4 text-center">
                <span class="grid size-12 place-items-center rounded-full border-2 border-ink bg-teal text-white">
                    <x-icon name="check" class="size-6" :stroke="2.8" />
                </span>
                <p class="font-display text-[0.95rem] font-extrabold">Verifikasi berhasil</p>
                <p id="verify-success-desc" class="text-[0.72rem] font-medium leading-relaxed text-ink-soft"></p>
                <p id="verify-xp" class="stamp bg-amber text-[0.7rem]">+0 XP</p>
                <a href="{{ route('quests.index') }}" class="btn btn-primary btn-sm mt-1">Lanjut ke Peta Misi</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    const modal = document.getElementById('verify-modal');
    if (!modal) throw new Error('modal missing');

    const questId = modal.dataset.questId;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const base = `/verifikasi-misi/${questId}`;

    const openers = document.querySelectorAll('[data-open-verify]');
    const closers = modal.querySelectorAll('[data-modal-close]');

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        modal.querySelector('input, button')?.focus();
        window.Ospek?.playTone('tap');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    openers.forEach((btn) => btn.addEventListener('click', openModal));
    closers.forEach((btn) => btn.addEventListener('click', closeModal));
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    function showError(el, message) {
        if (!el) return;
        el.textContent = message;
        el.classList.remove('hidden');
    }

    function showSuccess(payload) {
        modal.querySelectorAll('form, #geo-error, #token-error, #photo-error').forEach((el) => el.classList.add('hidden'));
        const box = document.getElementById('verify-success');
        box.classList.remove('hidden');
        box.classList.add('flex');
        document.getElementById('verify-success-desc').textContent = payload.message ?? 'Bukti partisipasi Anda valid.';
        document.getElementById('verify-xp').textContent = '+' + (payload.xp_awarded ?? 0) + ' XP';
        window.Ospek?.playTone('reward');
        setTimeout(() => window.location.reload(), 2200);
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(body),
        });
        return { ok: res.ok, data: await res.json().catch(() => ({})) };
    }

    // --- Token ---
    document.getElementById('form-token')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('token-input');
        const err = document.getElementById('token-error');
        err.classList.add('hidden');

        const { ok, data } = await postJson(`${base}/token`, { token: input.value.trim() });

        if (!ok || !data.success) {
            showError(err, data.message ?? 'Token tidak valid.');
            window.Ospek?.playTone('warn');
            return;
        }
        showSuccess(data);
    });

    // --- GPS ---
    document.getElementById('btn-detect')?.addEventListener('click', () => {
        const err = document.getElementById('geo-error');
        const coords = document.getElementById('geo-coords');
        const dist = document.getElementById('geo-distance');
        err.classList.add('hidden');

        if (!navigator.geolocation) {
            showError(err, 'Perangkat ini tidak mendukung geolokasi.');
            return;
        }

        coords.textContent = 'Mendeteksi…';

        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                const { latitude, longitude, accuracy } = pos.coords;
                coords.textContent = `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;

                const { ok, data } = await postJson(`${base}/lokasi`, { latitude, longitude, accuracy });

                if (!ok || !data.success) {
                    dist.textContent = '—';
                    showError(err, data.message ?? 'Lokasi tidak valid.');
                    window.Ospek?.playTone('warn');
                    return;
                }

                dist.textContent = `${data.distance ?? 0} m — dalam radius`;
                showSuccess(data);
            },
            (error) => {
                coords.textContent = 'Gagal';
                showError(err, error.code === error.PERMISSION_DENIED
                    ? 'Izin lokasi ditolak. Aktifkan pada pengaturan peramban.'
                    : 'Gagal membaca lokasi. Coba di area terbuka.');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    });

    // --- Foto ---
    document.getElementById('form-photo')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('photo-error');
        err.classList.add('hidden');

        const form = new FormData(e.target);
        const res = await fetch(`${base}/foto`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: form,
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.success) {
            showError(err, data.message ?? 'Gagal mengunggah bukti.');
            return;
        }

        const box = document.getElementById('verify-success');
        e.target.classList.add('hidden');
        box.classList.remove('hidden');
        box.classList.add('flex');
        document.getElementById('verify-success-desc').textContent = data.message;
        document.getElementById('verify-xp').textContent = 'Menunggu LO';
        window.Ospek?.playTone('reward');
    });
</script>
@endpush