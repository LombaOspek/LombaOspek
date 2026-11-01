{{--
    FOMO Live Feed.

    Menampilkan aktivitas XP NYATA mahasiswa lain di kampus yang sama, dibaca
    server dari xp_transactions (bukan simulasi acak). Karena itu widget ini
    tidak butuh endpoint polling: datanya sudah ikut terkirim bersama halaman,
    dan rotasi antarentri murni animasi sisi klien.

    Kalau tidak ada aktivitas dalam jendela waktu, feed tidak dirender sama
    sekali — kotak kosong yang berbunyi "belum ada aktivitas" justru mematikan
    efek FOMO yang ingin dibangun.
--}}
@if (($liveFeed ?? collect())->isNotEmpty())
    <script type="application/json" id="live-feed-data">@json($liveFeed)</script>
    <div
        id="live-feed-host"
        class="pointer-events-none fixed bottom-24 left-4 z-40 hidden w-[min(88vw,20rem)] flex-col gap-2"
        data-live-feed
        data-rotate-ms="{{ (int) config('ospek.engagement.live_feed.rotate_ms', 9000) }}"
        aria-live="polite"
        aria-atomic="false"
    ></div>
@endif
