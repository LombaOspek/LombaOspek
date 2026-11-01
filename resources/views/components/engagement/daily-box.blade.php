@props(['box'])

{{--
    Kotak Misteri Harian.

    Modal ini muncul sendiri sekali per hari (ditandai localStorage hanya untuk
    kenyamanan tampilan — bukan sebagai pengaman). Hadiah sesungguhnya
    ditentukan server saat klaim; kalau localStorage dihapus atau klien mengubah
    nilainya, server tetap menolak klaim kedua lewat engagement_claims.
--}}
<div
    id="daily-box-modal"
    class="fixed inset-0 z-[70] hidden items-center justify-center bg-ink/70 p-4"
    data-daily-box
    data-claimed="{{ $box['claimed'] ? '1' : '0' }}"
    data-box-key="{{ $box['key'] }}"
    data-user="{{ auth()->id() }}"
    data-endpoint="{{ route('engagement.daily-box') }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="daily-box-title"
>
    <div
        class="relative w-full max-w-sm animate-box-bounce rounded-xl border-2 border-ink bg-paper p-5 shadow-[var(--shadow-flat-xl)]"
        tabindex="-1"
        data-box-card
    >
        {{-- Tombol tutup: kotak tetap bisa dilewati tanpa mengklaim. --}}
        <button
            type="button"
            class="absolute right-2 top-2 grid size-7 place-items-center rounded-sm border-2 border-ink bg-paper transition hover:bg-paper-dim"
            data-daily-box-close
            aria-label="Tutup Kotak Misteri"
        >
            <x-icon name="close" class="size-3.5" :stroke="2.6" />
        </button>

        <p class="stamp bg-gold text-ink">Kotak Misteri Harian</p>

        <h2 id="daily-box-title" class="mt-2 font-display text-[1.2rem] font-extrabold leading-tight">
            Ada kotak menunggu!
        </h2>
        <p class="mt-1 text-[0.75rem] font-medium leading-relaxed text-ink-soft">
            Satu kotak per hari. Buka sekarang sebelum hangus saat tengah malam.
        </p>

        {{-- Kotak: tombol besar ala neo-brutalism, bukan ilustrasi gambar. --}}
        <button
            type="button"
            class="mx-auto mt-4 block w-full rounded-lg border-2 border-ink bg-brand p-4 shadow-[var(--shadow-flat-lg)] transition active:translate-x-[2px] active:translate-y-[2px] active:shadow-[var(--shadow-flat-press)] disabled:cursor-not-allowed disabled:opacity-70"
            data-daily-box-open
        >
            <span class="relative mx-auto grid size-20 place-items-center rounded-md border-2 border-ink bg-gold" data-box-lid>
                <x-icon name="gift" class="size-10 text-ink" :stroke="2" />
                <span class="absolute -right-2 -top-2 grid size-6 place-items-center rounded-full border-2 border-ink bg-vermilion text-[0.55rem] font-extrabold text-white">?</span>
            </span>
            <span class="mt-2 block font-display text-[0.85rem] font-extrabold uppercase tracking-[0.06em] text-white" data-box-label>
                Ketuk untuk membuka
            </span>
        </button>

        {{-- Hasil: disembunyikan sampai server membalas. --}}
        <div class="mt-3 hidden rounded-sm border-2 border-ink bg-success/15 px-3 py-2 text-center" data-box-result>
            <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Anda mendapat</p>
            <p class="numeric text-[1.4rem] font-extrabold leading-none" data-box-xp>+0 XP</p>
        </div>

        <p class="mt-3 text-center text-[0.6rem] font-semibold text-ink-faint">
            Hadiah ditentukan server · tidak bisa diulang hari ini
        </p>
    </div>
</div>
