# RENCANA — OspekQuest: dari "Dashboard Tugas" menjadi Petualangan yang Imersif

> Status: **Fase 1–4 sudah dieksekusi & diverifikasi.** Rencana aslinya dipertahankan
> apa adanya di bawah sebagai catatan keputusan; status tiap fase dicatat di §8.
> Fase 5–6 belum dikerjakan.

---

## 1. Diagnosis — kenapa terasa seperti dashboard tugas biasa

Ini bukan kesan subjektif. Diukur dari kodenya:

| Gejala | Bukti terukur | Akibat ke pengguna |
|---|---|---|
| Gerak hampir tidak ada | Seluruh `app.css` (399 baris) hanya punya **1 `@keyframes`** dan **3 deklarasi `transition`** | Semua yang terjadi terasa "muncul tiba-tiba" — tidak ada umpan balik |
| "Peta Misi" bukan peta | `absolute: 0`, `svg: 0`, `path: 0`, `connector: 0` di `missions.blade.php`. Isinya `<ol>` bertumpuk vertikal | Namanya peta, tampilannya daftar tugas |
| Tanpa ilustrasi | `public/images/` hanya berisi `preview.png` (screenshot). Nol referensi gambar di seluruh view | Tidak ada yang bisa diingat mata; semua kotak + teks |
| Tanpa maskot/karakter | Pencarian `maskot|mascot|illustration` di seluruh views: **0** | Tidak ada "wajah" aplikasi — tidak ada yang menemani pemain |
| Momen menang tidak dirayakan | `award()` mengembalikan XP, view hanya menampilkan toast | Naik level dan buka lencana terasa sama dengan simpan form |
| Suara jarang dipakai | `playTone()` ada dan dipanggil, tapi hanya `tap`/`reward` dasar | Potensi audio 90% tidak terpakai |
| Reward tidak pernah "terbuka" | Lencana tampil sebagai grid statis yang sudah jadi | Tidak ada antisipasi, tidak ada kejutan |
| Tidak terasa seperti permainan | Tidak ada progres yang "terisi", tidak ada combo, tidak ada misi harian | Motivasi kembali hanya karena kewajiban |

**Akar masalah:** aplikasi ini punya **data gamifikasi** (XP, level, 7 lencana, 8 modul,
peringkat) tapi **tidak punya bahasa visual dan gerak gamifikasi**. Semua angka benar,
tapi tidak pernah "dibunyikan".

---

## 2. Prinsip yang saya pegang

1. **Imersif ≠ ramai.** Tambah gerak dan tekstur, tapi jangan tambah kebisingan. Setiap
   animasi harus punya arti (menandakan progres, reward, atau bahaya) — bukan hiasan.
2. **Hormati `prefers-reduced-motion`.** Semua animasi punya jalur mati. Sudah ada
   helper `prefersReducedMotion()` di `app.js` — akan dipakai konsisten.
3. **Tanpa aset berbayar / tanpa unduh gambar.** Semua ilustrasi dibuat sebagai **SVG
   vektor buatan sendiri** (konsisten dengan 56 ikon kustom yang sudah ada). Tidak ada
   emoji, tidak ada aset dari internet, tidak ada file berat.
4. **Kontras WCAG AA tidak boleh turun.** Token warna yang sudah diaudit
   (`ink #17150f`, `teal #217a6f`, dll.) dipertahankan. Gerak tidak boleh mengorbankan
   keterbacaan.
5. **Performa dijaga.** Target tambahan < 25 KB CSS+JS. Tanpa library animasi eksternal
   (GSAP/Framer). Semua CSS + Web Animations API.
6. **Logika tidak disentuh.** Rencana ini murni presentasi. Server tetap sumber kebenaran;
   tidak ada XP/gating yang dipindah ke klien.

---

## 3. Yang akan dibangun

### Fase 1 — Bahasa Gerak (fondasi, menyentuh semua halaman)

**File:** `resources/css/app.css` (+~180 baris), `resources/js/app.js` (+~140 baris)

| # | Nama | Detail teknis |
|---|---|---|
| 1.1 | **Design token gerak** | `--ease-out-back`, `--ease-out-quint`, `--dur-fast: 120ms`, `--dur-base: 260ms`, `--dur-slow: 520ms` |
| 1.2 | **7 keyframe baru** | `rise-in` (kartu masuk berurutan), `pop-in` (badge/XP), `stamp-in` (seal lencana), `shimmer-sweep` (kilau saat reward), `float-idle` (maskot), `progress-fill`, `pulse-ring` |
| 1.3 | **Stagger otomatis** | Atribut `data-stagger` → JS menambahkan `animation-delay` per anak (40 ms). Dipakai di daftar modul & lencana |
| 1.4 | **Reveal on scroll** | `IntersectionObserver` sekali-pasang, `data-reveal` → kelas `.is-visible`. Fallback: langsung terlihat bila JS mati |
| 1.5 | **Counter angka** | XP/level naik berhitung (`requestAnimationFrame`, easing), bukan lompat. Hormati reduced-motion |
| 1.6 | **Skeleton → isi** | Kartu statistik tampil dengan shimmer 300 ms saat halaman dimuat |

### Fase 2 — Momen Reward (inti rasa "game")

| # | Nama | Detail |
|---|---|---|
| 2.1 | **XP Burst** | Saat modul selesai: `+350 XP` melayang dari kartu ke bilah XP di sidebar (WAAPI, 700 ms), lalu bilah berdenyut |
| 2.2 | **Level Up overlay** | Modal satu layar: nomor level besar, gelar baru, kilau, tombol "Lanjut". Muncul saat `user_level` berubah dibanding `data-level` di `<html>` |
| 2.3 | **Lencana terbuka** | Lencana tersegel (abu, garis putus) → **dipecah** jadi versi berwarna dengan `stamp-in` + partikel geometris kecil (SVG, bukan canvas) |
| 2.4 | **Suara baru** | Tambah `levelup` (arpeggio 5 nada), `unlock` (sudah ada, dipakai lebih agresif), `complete` (2 nada pendek) |
| 2.5 | **Progress bar hidup** | Bilah XP punya ujung berkilau + angka berjalan; segmen per level terlihat |

### Fase 3 — Peta Misi yang benar-benar Peta

Ini perubahan terbesar dan paling terasa.

| # | Nama | Detail |
|---|---|---|
| 3.1 | **Jalur meliuk SVG** | Satu `<svg>` latar dengan `path` bergelombang yang menghubungkan 8 node. `stroke-dasharray` + `stroke-dashoffset` dianimasikan sesuai `completed/total` → **jalur terisi mengikuti progres nyata** |
| 3.2 | **Node besar & berbeda per status** | Selesai: medali penuh + centang. Berjalan: berdenyut + cincin. Terkunci: gembok + garis putus. Ukuran menyesuaikan pentingnya modul |
| 3.3 | **Zona per hari** | Pengelompokan visual "Hari 1–2", "Hari 3–5", "Hari 6–8" dengan label seperti bab |
| 3.4 | **Rute alternatif di mobile** | Di layar < 768 px jalur jadi vertikal lurus, bukan SVG meliuk |
| 3.5 | **Penanda "Anda di sini"** | Pin berdenyut di node aktif, dengan bayangan |
| 3.6 | **Seret/geser halus** | Scroll halus ke node aktif saat halaman dibuka (bukan lompat) |

### Fase 4 — Identitas Visual (aset yang selama ini tidak ada)

| # | Nama | Detail |
|---|---|---|
| 4.1 | **Maskot "Kompas"** | Karakter vektor: kompas hidup dengan mata, ekspresi 4 varian (netral, senang, kaget, bangga). SVG murni, ~2 KB |
| 4.2 | **6 ilustrasi modul** | Ikon besar (bukan 24px) 96×96 per modul: gedung registrasi, perpustakaan, lingkaran gugus, kuis, auditorium, stan MCC, proyek, upacara. Gaya isometrik garis sama dengan ikon kustom |
| 4.3 | **Tekstur latar** | Pola titik + garis kontur peta (SVG `pattern`) di halaman peta misi, sangat tipis |
| 4.4 | **7 lencana diperkaya** | Perisai yang ada + pita, permata, dan garis ukir berbeda per lencana — supaya tiap lencana punya siluet unik |
| 4.5 | **Pita level** | Banner gelar level dengan pola berbeda tiap pita |

### Fase 5 — Interaksi Kecil yang Menambah "Rasa"

| # | Nama | Detail |
|---|---|---|
| 5.1 | **Hover magnetik** | Kartu modul bergeser 2 px ke arah kursor (maks 4 px), CSS custom property |
| 5.2 | **Klik = tekan fisik** | Semua tombol: shadow hilang + `translate-y-[2px]` saat aktif (sudah ada sebagian, diseragamkan) |
| 5.3 | **Combo harian** | Indikator "rentetan" menyala bila hari ini ada aktivitas |
| 5.4 | **Bar progres global di topbar** | Bilah tipis di paling atas halaman menunjukkan `completed/total` |
| 5.5 | **Kursor khusus di area peta** | `cursor: grab` / `grabbing` saat menggeser peta |

### Fase 6 — Halaman Login sebagai Gerbang

Saat ini login adalah form biasa. Akan dibuat:
- Panel kiri: ilustrasi besar + maskot + 3 penanda ("8 modul", "7 lencana", "1 sertifikat")
- Animasi masuk bertahap (rise-in berurutan)
- Tetap sederhana, tetap < 1 layar, tetap aksesibel

---

## 4. Urutan Pengerjaan & Ketergantungan

```
Fase 1 (fondasi gerak)
   └─> Fase 2 (reward)      ← butuh 1.1–1.3 & event hook
   └─> Fase 5 (interaksi)   ← butuh 1.1
Fase 4 (aset vektor)
   └─> Fase 3 (peta)        ← butuh 4.2 sebagai isi node
   └─> Fase 6 (login)       ← butuh 4.1 maskot
```

Dikerjakan berurutan: **1 → 4 → 3 → 2 → 5 → 6**, karena peta (fase 3) adalah perubahan
visual terbesar dan butuh ilustrasi (fase 4) sudah ada.

---

## 5. Definisi Selesai (perlu bukti, bukan klaim)

| Kriteria | Cara saya membuktikan |
|---|---|
| Gerak terasa | Screenshot before/after + rekam `@keyframes` count naik dari 1 → ≥ 8 |
| Peta benar-benar peta | Screenshot 1440 px & 390 px, jalur SVG terlihat, node aktif berdenyut |
| Reward terasa | Uji: selesaikan modul → screenshot XP burst + overlay level up |
| Lencana "terbuka" | Screenshot state tersegel vs terbuka |
| Maskot & ilustrasi bukan AI slop | Render galeri aset → tinjau dengan vision (seperti yang sudah saya lakukan pada 56 ikon) |
| Tidak ada regresi | `php artisan test` tetap **93/93 lulus** |
| Aksesibilitas aman | Kontras tetap ≥ 4.5:1; semua animasi mati saat `prefers-reduced-motion` |
| Performa aman | Ukuran bundel CSS+JS sebelum vs sesudah dicatat |
| Mobile tetap rapi | Screenshot 390 px semua halaman yang diubah |

---

## 6. Yang TIDAK akan saya lakukan

- **Tidak** menambah library animasi (GSAP, Framer Motion, AOS) — bundel tetap kecil.
- **Tidak** memakai emoji sebagai ikon — sudah ada 56 ikon SVG kustom, akan ditambah bukan diganti.
- **Tidak** mengunduh aset dari internet atau memakai gambar berlisensi tidak jelas.
- **Tidak** memakai gradient blur/glassmorphism — bertentangan dengan neo-brutalis yang sudah dipilih.
- **Tidak** mengubah logika server, skema database, atau aturan anti-curang.
- **Tidak** menambah halaman baru — hanya memperkaya yang ada.
- **Tidak** memakai canvas untuk partikel (berat di perangkat murah) — pakai SVG/CSS.

---

## 7. Perkiraan Dampak per Halaman

| Halaman | Perubahan | Dampak "game feel" |
|---|---|---|
| `/login` | Ilustrasi + maskot + masuk bertahap | **Tinggi** — kesan pertama |
| `/dashboard` | Statistik berhitung, XP burst, maskot menyapa, lencana tersegel | **Tinggi** |
| `/misi` (peta) | Jalur SVG, node besar, zona hari, pin "Anda di sini" | **Sangat tinggi** |
| `/misi/{id}` | Ilustrasi besar per modul, modal verifikasi dianimasikan | **Tinggi** |
| `/pencapaian` | 7 lencana diperkaya + animasi pecah segel | **Tinggi** |
| `/kuis` | Timer berdenyut, transisi soal, hasil dianimasikan | **Sedang** |
| `/peringkat` | Bar bergerak, podium diperkaya | **Sedang** |
| `/sijil` | Tidak diubah (sudah final & sudah dicetak) | — |

---

## 8. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Animasi berlebihan → terasa murah | Setiap animasi wajib punya alasan fungsional; durasi ≤ 600 ms; tidak ada animasi berulang tanpa akhir kecuali node aktif |
| Ilustrasi SVG buatan sendiri terlihat amatir | Dibuat dengan grid 24/96 yang konsisten, lalu **diverifikasi ulang dengan vision** seperti ikon sebelumnya |
| Peta SVG menyulitkan di mobile | Jalur vertikal sederhana < 768 px; diuji di 390 px |
| `prefers-reduced-motion` terlewat | Satu blok `@media` global yang mematikan semua `animation`+`transition`, plus penjagaan di JS |
| Waktu habis sebelum deadline (7 Nov) | Fase 1–3 adalah inti; Fase 4–6 bisa dipotong tanpa merusak apa pun |

---

## 9. Pertanyaan yang Perlu Keputusan Anda

1. **Maskot**: saya usulkan karakter **kompas hidup** (sesuai logo `compass` yang sudah
   dipakai di sidebar). Alternatif: komet, atau burung merpati (sesuai nama gugus).
   Anda pilih mana?
2. **Intensitas**: "imersif" versi **sedang** (elegan, gerak halus, tetap profesional
   untuk juri) atau **ramai** (warna lebih berani, lebih banyak gerak & suara)?
   Rekomendasi saya: sedang-ke-ramai, karena ini lomba desain — juri menilai
   "Kreativitas dan Inovasi" 25%.
3. **Suara**: aktif secara default, atau mati sampai pengguna menyalakan? (Saat ini
   mengikuti preferensi `sound_effects` akun, default dari seeder.)

Kalau Anda tidak menjawab, saya lanjut dengan: **maskot kompas**, intensitas
**sedang-ke-ramai**, suara **mengikuti preferensi akun seperti sekarang**.

---

## 8. Status eksekusi (diverifikasi, bukan diklaim)

Setiap baris di bawah ini dicek dengan alat, bukan dari ingatan.

| Fase | Isi | Status | Bukti |
|---|---|---|---|
| **1. Fondasi gerak** | 7 `@keyframes` (`node-pulse`, `rise-in`, `pop-in`, `stamp-in`, `float-idle`, `shimmer-sweep`, `pulse-soft`), stagger baris, reveal saat scroll | ✅ Selesai | `app.css` punya **7** `@keyframes` (sebelumnya 1); `animate-rise` terpasang per baris misi dengan delay bertingkat |
| **2. Momen reward** | XP burst melayang ke bilah XP, overlay **Level Up** dengan gelar dari config, konfeti, suara | ✅ Selesai | `test-reward.mjs` di Chrome headless: `overlay: true`, `burstChip: true`, `detected.title = "Mentor Muda"`, **0 error konsol** |
| **3. Peta Misi** | Rail progres terisi sesuai capaian + ilustrasi 96×96 per modul | ✅ Selesai | Rail terukur `w=3, h=1124`; isi biru **396/1041 px = 38%** pada akun 3/8 modul. 8 ilustrasi ter-render (`viewBox="0 0 96 96"` ×8) |
| **4. Maskot & palet WIRA** | Maskot resmi WIRA di 4 halaman, palet biru `#2758a3` + kuning `#f7bc29` | ✅ Selesai | Maskot dioptimalkan **83%** (1.688 KB → 291 KB); 17/17 pasangan warna lolos WCAG AA |
| **5. Zona hari & jalur meliuk** | Peta misi jadi jalur SVG meliuk dengan zona per hari | ⬜ Belum | — |
| **6. Lencana diperkaya & login sebagai gerbang** | 7 lencana dengan ilustrasi, login dianimasikan | ⬜ Belum | — |

### Cacat yang ditemukan saat verifikasi (dan sudah diperbaiki)

Semua ini **tidak terlihat dari membaca kode** — hanya muncul saat diukur di browser:

| Cacat | Cara ditemukan | Perbaikan |
|---|---|---|
| Badge `.stamp` memakai `white-space: nowrap` → meluber keluar viewport 390px (overflow **87px**) | `overflow.mjs` mengukur `scrollWidth - clientWidth` di 390px | `white-space: normal` + `max-width: 100%`, `nowrap` hanya ≥640px |
| Grid item tanpa `min-w-0` → kartu modul memaksa lebar **461px** di viewport 390px | `wide.mjs` memindai elemen dengan `width > viewport` | `min-w-0` pada kedua kolom grid dashboard |
| `App\Support\Badge` masih memakai palet **teal/amber/sky** lama | Pencarian heksadesimal di seluruh `app/` | Dipetakan ke palet WIRA + alias dipertahankan agar data lama tidak pecah |
| Ilustrasi modul pertama terlalu kecil (32px) dan **3 pasang kembar** (modul 3/7, 5/8, stan ambigu) | `vision_analyze` pada strip ilustrasi | Dinaikkan ke 56px, ditambah ilustrasi khusus `library()`, `project()`, `medal()`; 8 modul kini unik |
| `levelTitles()` membaca `meta.textContent` yang **selalu `null`** → overlay selalu menampilkan teks cadangan | `meta-check.mjs` membandingkan `getAttribute('content')` vs `textContent` | Dibaca lewat `getAttribute('content')`; gelar level benar |
| Tombol `Tandai Selesai` tidak membawa nilai XP → burst menampilkan `+0 XP` | Menelusuri `data-reward-xp` yang tidak pernah diisi | Ditambahkan `data-reward-xp="{{ $quest->xp_reward }}"` |

### Batasan yang tetap dipatuhi

- **Tanpa library animasi** (tanpa GSAP/AOS/ScrollTrigger) — semua CSS + `requestAnimationFrame`.
- **`prefers-reduced-motion`** dihormati: `xpBurst()` dan konfeti langsung keluar bila aktif.
- **Tanpa glassmorphism/gradient blur** — bayangan tetap flat, garis tetap 2–3px.
- **Logika tetap di server** — klien hanya menampilkan; tidak ada aturan XP, level, atau verifikasi yang pindah ke browser.
- **XSS tertutup** — overlay Level Up membangun DOM lewat `textContent`, bukan `innerHTML`.
