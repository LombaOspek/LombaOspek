/**
 * Import CSV bertahap dengan progress bar NYATA.
 *
 * Kenapa bertahap: import 1210 baris bisa 30+ detik di hosting gratis dan
 * tidak ada queue worker. Memecah jadi batch 200 baris membuat progres bisa
 * ditampilkan dari data nyata, bukan animasi palsu.
 *
 * Progressive enhancement: kalau fetch gagal atau JS mati, form tetap
 * mengirim POST biasa ke endpoint sinkron yang hasilnya sama.
 */

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(value);
}

export function initImportProgress() {
    const form = document.querySelector('[data-import-form]');
    if (!form) return;

    const fileInput = form.querySelector('input[type="file"]');
    const submitBtn = form.querySelector('[data-import-submit]');
    const panel = document.querySelector('[data-import-progress]');
    if (!fileInput || !submitBtn || !panel) return;

    const bar = panel.querySelector('[data-progress-bar]');
    const countEl = panel.querySelector('[data-progress-count]');
    const pctEl = panel.querySelector('[data-progress-percent]');
    const statusEl = panel.querySelector('[data-progress-status]');
    const credBox = panel.querySelector('[data-progress-credentials]');
    const credTable = panel.querySelector('[data-progress-credential-rows]');
    const skippedBox = panel.querySelector('[data-progress-skipped]');
    const skippedList = panel.querySelector('[data-progress-skipped-rows]');
    const doneBtn = panel.querySelector('[data-progress-done]');

    const allCredentials = [];

    const setProgress = (processed, total, created, skipped) => {
        const pct = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;

        if (bar) {
            bar.style.width = pct + '%';
            // aria-valuenow harus ikut: pembaca layar membacanya, bukan lebar CSS.
            bar.setAttribute('aria-valuenow', String(pct));
        }
        if (pctEl) pctEl.textContent = pct + '%';
        if (countEl) {
            countEl.textContent = `${formatNumber(processed)} / ${formatNumber(total)} baris · ${formatNumber(created)} dibuat`
                + (skipped > 0 ? ` · ${formatNumber(skipped)} dilewati` : '');
        }
    };

    const showCredentials = () => {
        if (!credBox || !credTable || allCredentials.length === 0) return;

        credTable.replaceChildren();

        allCredentials.forEach((row) => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-ink/10';

            [row.nim, row.name, row.password].forEach((value, i) => {
                const td = document.createElement('td');
                td.className = i === 2
                    ? 'px-2 py-1 font-mono text-[0.62rem] font-bold text-vermilion-deep'
                    : 'px-2 py-1 text-[0.62rem] font-semibold';
                // textContent: nama berasal dari CSV, tidak boleh jadi HTML.
                td.textContent = value;
                tr.appendChild(td);
            });

            credTable.appendChild(tr);
        });

        credBox.classList.remove('hidden');
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!fileInput.files || fileInput.files.length === 0) {
            window.Ospek?.toast({ title: 'Pilih berkas CSV dulu', tone: 'warning' });
            return;
        }

        panel.classList.remove('hidden');
        credBox?.classList.add('hidden');
        skippedBox?.classList.add('hidden');
        doneBtn?.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Mengunggah…';
        if (statusEl) statusEl.textContent = 'Menyiapkan berkas…';

        try {
            // Langkah 1: unggah berkas, dapatkan total baris.
            const initBody = new FormData();
            initBody.append('file', fileInput.files[0]);
            initBody.append('auto_generate_password', form.querySelector('[name="auto_generate_password"]')?.checked ? '1' : '0');

            const initRes = await fetch(form.dataset.initEndpoint, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: initBody,
                credentials: 'same-origin',
            });

            const initData = await initRes.json().catch(() => null);

            if (!initRes.ok || !initData?.ok) {
                throw new Error(initData?.message ?? 'Berkas tidak dapat diproses.');
            }

            const { import_id: importId, total } = initData;

            setProgress(0, total, 0, 0);
            if (statusEl) statusEl.textContent = `Memproses ${formatNumber(total)} baris…`;

            // Langkah 2: minta batch sampai selesai.
            let done = false;
            let guard = 0;

            while (!done && guard < 500) {
                guard++;

                const res = await fetch(
                    `${form.dataset.batchEndpoint.replace('__ID__', importId)}`,
                    {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf(),
                        },
                        body: JSON.stringify({}),
                        credentials: 'same-origin',
                    },
                );

                const data = await res.json().catch(() => null);

                if (!res.ok || !data?.ok) {
                    throw new Error(data?.message ?? 'Batch gagal diproses.');
                }

                if (Array.isArray(data.credentials)) {
                    allCredentials.push(...data.credentials);
                }

                if (Array.isArray(data.skipped_rows) && data.skipped_rows.length && skippedList) {
                    data.skipped_rows.forEach((line) => {
                        const li = document.createElement('li');
                        li.className = 'text-[0.62rem] font-semibold text-ink-soft';
                        li.textContent = '• ' + line;
                        skippedList.appendChild(li);
                    });

                    // Server hanya mengirim 10 baris pertama per batch. Tanpa
                    // keterangan ini, daftar tampak lengkap padahal dipotong.
                    if (data.skipped > data.skipped_rows.length) {
                        const more = document.createElement('li');
                        more.className = 'text-[0.58rem] font-bold italic text-ink-faint';
                        more.textContent = `…dan ${data.skipped - data.skipped_rows.length} baris lain di batch ini`;
                        skippedList.appendChild(more);
                    }

                    skippedBox?.classList.remove('hidden');
                }

                setProgress(data.processed, data.total, data.created, data.skipped);
                done = Boolean(data.done);
            }

            if (statusEl) {
                statusEl.textContent = done
                    ? 'Selesai. Unduh kredensial sekarang — sandi tidak bisa dilihat lagi.'
                    : 'Berhenti sebelum selesai. Muat ulang halaman lalu periksa daftar akun.';
            }

            showCredentials();

            if (doneBtn) {
                doneBtn.classList.remove('hidden');
                doneBtn.addEventListener('click', () => window.location.reload());
            }

            window.Ospek?.playTone('reward');
        } catch (error) {
            if (statusEl) statusEl.textContent = 'Gagal: ' + error.message;
            window.Ospek?.toast({
                title: 'Import gagal',
                message: error.message + ' — form biasa masih bisa dipakai.',
                tone: 'danger',
            });
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Mulai Import';
        }
    });
}
