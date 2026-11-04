/**
 * Toggle "Modul Peta" vs "Side Quest" di Quest Builder.
 *
 * Side Quest tidak menempati urutan peta, jadi saat dipilih kolom Urutan Modul
 * disembunyikan dan kolom Syarat Modul Selesai ditampilkan. Kalau keduanya
 * terlihat sekaligus, panitia akan mengisi urutan yang tidak dipakai dan
 * bertanya-tanya mengapa kodenya tetap SQ-xx.
 */
export function initQuestKindToggle() {
    const radios = document.querySelectorAll('[data-quest-kind]');
    if (radios.length === 0) return;

    const mainOnly = document.querySelectorAll('[data-main-quest-only]');
    const sideOnly = document.querySelectorAll('[data-side-quest-only]');

    const apply = () => {
        const isSide = document.querySelector('[data-quest-kind]:checked')?.value === '1';

        mainOnly.forEach((el) => {
            el.hidden = isSide;
            // Input di dalam kontainer tersembunyi tidak dikirim browser;
            // `disabled` juga mematikannya dari validasi server.
            el.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = isSide;
                if (input.required) input.dataset.wasRequired = '1';
            });
        });

        sideOnly.forEach((el) => {
            el.hidden = !isSide;
            el.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !isSide;
            });
        });

        // Kartu pilihan ikut berubah warna supaya pilihan aktif terbaca jelas.
        radios.forEach((radio) => {
            const card = radio.closest('label');
            const active = radio.checked;
            const isSideCard = radio.value === '1';

            card.classList.toggle('border-brand', active && !isSideCard);
            card.classList.toggle('bg-brand/10', active && !isSideCard);
            card.classList.toggle('border-vermilion', active && isSideCard);
            card.classList.toggle('bg-vermilion/10', active && isSideCard);
            card.classList.toggle('border-ink/30', !active);
            card.classList.toggle('bg-paper-dim', !active);
        });
    };

    radios.forEach((radio) => radio.addEventListener('change', apply));
    apply();
}