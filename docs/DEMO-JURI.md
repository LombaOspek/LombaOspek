# Panduan Demo untuk Juri — OspekQuest

Dokumen ini dipakai **operator** (Anda) saat memandu juri. Isinya: perintah
penyiapan di server produksi, tiga alur akun demo yang bisa diikuti juri dari
login sampai fitur terakhir, dan daftar hal yang perlu diperiksa sebelum hari-H.

---

# 1. Penyiapan di server produksi

## 1.1. Prasyarat

| Kebutuhan | Versi minimum | Catatan |
|---|---|---|
| PHP | 8.3 | ekstensi: `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`, `fileinfo` |
| MySQL / MariaDB | 8.0 / 10.6 | MariaDB 10.4 juga cukup |
| Composer | 2.6 | |
| Node.js | 20 | hanya untuk build aset, tidak perlu jalan di produksi |

## 1.2. Perintah berurutan

Jalankan dari direktori proyek. **Urutannya penting** — mengacak urutan adalah
sumber kegagalan paling umum.

```bash
# ── 1. Dependensi ────────────────────────────────────────────────
composer install --no-dev --optimize-autoloader

# ── 2. Konfigurasi ───────────────────────────────────────────────
cp .env.example .env
php artisan key:generate
```

Isi `.env` minimal seperti ini:

```env
APP_NAME=OspekQuest
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ospek.namadomain.ac.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ospekquest
DB_USERNAME=ospek
DB_PASSWORD=••••••

# Tanggal mulai orientasi. WAJIB diisi tanggal acara, kalau tidak
# aplikasi menganggap orientasi sudah lewat dan gerbang hari ikut salah.
OSPEK_START_DATE=2026-11-10
OSPEK_TOTAL_DAYS=8

# Side Quest yang wajib untuk sertifikat (default SQ-01).
OSPEK_REQUIRED_SIDE_QUEST=SQ-01
```

```bash
# ── 3. Bangun aset (dijalankan di mesin build, bukan di server) ──
npm ci
npm run build
# hasilnya di public/build/ — ini yang diunggah ke server

# ── 4. Database ──────────────────────────────────────────────────
php artisan migrate --force

# ── 5. Isi seluruh data (quest, jadwal, hadiah, lencana, FAQ) ────
php artisan db:seed --force
```

Seeder `DatabaseSeeder` menjalankan berurutan:

| Seeder | Isi |
|---|---|
| `GroupSeeder` | gugus dasar |
| `QuestSeeder` | **13 modul** dalam 3 babak |
| `OrientationScheduleSeeder` | **14 agenda** jadwal, 13 tertaut ke modul |
| `SideQuestSeeder` | **5 Side Quest** |
| `QuizQuestionSeeder` | soal kuis MOD-04 |
| `AchievementSeeder` | **7 lencana** |
| `RewardSeeder` | **9 hadiah** + gambar katalog |
| `FaqSeeder` | pertanyaan umum |

```bash
# ── 6. Tautan penyimpanan (WAJIB, kalau tidak gambar hadiah 404) ─
php artisan storage:link

# ── 7. Susun gugus & LO ──────────────────────────────────────────
php artisan groups:rebuild
```

`groups:rebuild` mengelompokkan seluruh mahasiswa menjadi gugus **satu prodi
per gugus**, maksimal 70 anggota, dan menugaskan LO sejurusan. Perintah ini
idempoten — aman dijalankan ulang.

```bash
# ── 8. Akun demo untuk juri ──────────────────────────────────────
php artisan demo:accounts --password=SandiPilihanAnda
```

Membuat tiga akun: `DEMO-MHS`, `DEMO-LO`, `DEMO-ADMIN`. Saldo awal mahasiswa
**900 XP / 700 koin** — cukup untuk mencoba Bursa (lanyard 150 koin).

```bash
# ── 9. Optimasi produksi ─────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **Kalau nanti mengubah `.env`**, jalankan `php artisan config:clear` dulu,
> kalau tidak perubahan tidak terpakai.

## 1.3. Perintah verifikasi setelah penyiapan

```bash
php artisan test                 # 320 tes harus lulus
php artisan demo:accounts --show # ketiga akun demo ada
```

Cek cepat di browser:

| URL | Harus menampilkan |
|---|---|
| `/login` | form masuk, ada favicon di tab |
| `/bursa` | 9 kartu hadiah **bergambar semua** |
| `/jadwal` | 8 hari, 14 agenda |
| `/misi` | 13 modul, urutan hari menanjak |
| `/admin/bursa` | panel "Pindai QR Klaim" |

## 1.4. Kalau perlu menyetel ulang data demo

```bash
# Kosongkan lalu isi ulang dari nol (HAPUS semua progres mahasiswa)
php artisan migrate:fresh --seed --force
php artisan groups:rebuild
php artisan demo:accounts --password=SandiPilihanAnda

# Atau hanya setel ulang saldo akun demo
php artisan demo:accounts --fresh --password=SandiPilihanAnda
```

## 1.5. Kesalahan yang sering terjadi

| Gejala | Sebab | Perbaikan |
|---|---|---|
| Gambar hadiah tidak muncul | `storage:link` belum dijalankan | `php artisan storage:link` |
| Semua halaman 500 | `APP_KEY` kosong | `php artisan key:generate` |
| Perubahan `.env` tidak terpakai | config ter-cache | `php artisan config:clear` |
| "Hari ke-74 dari 8", badge hari salah | `OSPEK_START_DATE` belum diisi | isi tanggal acara, lalu `config:clear` |
| Aset/CSS tidak termuat | `public/build` tidak diunggah | jalankan `npm run build` lalu unggah |
| Gugus kosong setelah seeding | `groups:rebuild` belum jalan | `php artisan groups:rebuild` |

---

# 2. Alur demo Mahasiswa (`DEMO-MHS`)

Masuk di `/login` dengan NIM `DEMO-MHS`.

## 2.1. Yang dilihat pertama

Setelah masuk, mendarat di **`/dashboard`** (Dasbor):

- **Fokus Hari Ini** — nomor hari, progres hari itu, daftar modul
- **Agenda hari ini** — acara dari tabel jadwal + tautan "Lihat jadwal lengkap"
- **Misi Selanjutnya** — modul terdekat
- **Tantangan Harian**
- **Gugus Wars** — tarik tambang antar-gugus
- **Kotak Misteri** — muncul otomatis ~900 ms setelah halaman terbuka

## 2.2. Urutan demo yang disarankan

### Langkah 1 — Jadwal (`/jadwal`)

Tunjukkan bahwa **acara dan modul terhubung**:

1. Kartu "Hari ini" menampilkan agenda hari berjalan.
2. Di bawahnya, rangkaian 8 hari lengkap dengan jam dan lokasi.
3. Setiap agenda yang punya modul menampilkan tombol `MOD-xx · Terbuka/Selesai`.
4. Contoh yang paling meyakinkan: **Hari 2 — Seminar SKS & Etika Akademik
   (08.00–10.00) → tombol MOD-04 Kuis**. Inilah pola "seminar selesai, kuis
   terbuka".

### Langkah 2 — Peta Misi (`/misi`)

1. Tunjukkan **urutan hari menanjak**: 1,1,2,2,3,3,4,4,5,6,6,7,8.
2. Tiga babak: Pengenalan Kampus, Eksplorasi Fakultas, Aksi & Penutupan.
3. Sebut bahwa node terkunci dibedakan: **belum waktunya** vs **belum urutannya**.
4. Buka modul pertama → tunjukkan halaman detail dengan **unggah bukti foto**.

### Langkah 3 — Anti-curang (ini yang paling ditanyakan juri)

1. Buka modul bertipe **Token** (mis. MOD-02 Perpustakaan) → butuh kode dari
   petugas di lokasi.
2. Buka modul bertipe **GPS** (MOD-08 Apel) → kehadiran diverifikasi koordinat,
   radius 150 m dari auditorium.
3. Tekankan: **bukti tidak bisa diklaim dari kamar**.
4. Setelah mahasiswa mengunggah bukti, bukti masuk antrean LO **sejurusan** —
   LO beda jurusan tidak bisa melihatnya.

### Langkah 4 — Kuis (`/kuis`)

1. Kerjakan kuis MOD-04 (5 soal).
2. XP masuk, lencana bisa terbuka.
3. Kalau naik level, overlay **"Level Naik"** muncul — tunjukkan bahwa overlay
   hanya muncul di halaman yang benar.

### Langkah 5 — Side Quest (`/misi-samping`)

1. **5 misi sosial**, terkunci bertahap: butuh 0/2/4/6/8 modul utama selesai.
2. **SQ-01 Sapa Lima Teman** — refleksi tertulis, wajib untuk sertifikat.
3. **SQ-02 Foto Bareng Gugus** — **butuh bukti foto**, ditinjau LO.
4. Tekankan: Side Quest **tidak memblokir** perjalanan utama, tetapi memberi
   koin jauh lebih besar (100% XP vs 10% untuk modul).

### Langkah 6 — Bursa Hadiah (`/bursa`)

1. **9 hadiah bergambar**: lanyard, pin, stiker, mug, tote bag, kaos, voucher,
   pulsa.
2. Di ponsel tampil **2 kolom** supaya tidak perlu menggulir jauh.
3. Tukar Lanyard (150 koin) → muncul **kode klaim `OQ-XXXX-XXXX`** + **QR**.
4. Tunjukkan QR-nya: *"panitia memindai ini, tidak perlu mengetik kode."*

### Langkah 7 — Peringkat & Pencapaian

1. `/peringkat` — papan peringkat + frame avatar berdasarkan peringkat.
2. `/pencapaian` — 7 lencana dengan progres.

### Langkah 8 — Sertifikat (`/sijil`)

1. Status kelengkapan syarat.
2. `/verifikasi/{kode}` — halaman publik untuk memeriksa keaslian sertifikat
   (bisa dibuka tanpa login, cocok untuk ditunjukkan ke pihak luar).

### Langkah 9 — Profil & Bantuan

1. `/profil` — data diri, pengaturan, dan tiket bantuan ke mentor.

---

# 3. Alur demo LO (`DEMO-LO`)

LO = Liaison Officer (kakak tingkat pendamping). Masuk dengan NIM `DEMO-LO`.

## 3.1. Halaman verifikasi bukti (`/lo/verifikasi`)

Ini inti peran LO.

1. Antrean bukti dari mahasiswa **gugusnya sendiri**.
2. Aturan yang ditegakkan server: **LO hanya melihat bukti mahasiswa sejurusan**.
   LO Informatika tidak bisa melihat bukti mahasiswa Sistem Informasi —
   tunjukkan ini sebagai bukti anti-curang.
3. Buka satu bukti → lihat foto, keterangan, waktu unggah.
4. **Setujui** → XP dan koin mahasiswa masuk, ledger tercatat.
5. **Tolak** → wajib menulis alasan.

## 3.2. Serah terima hadiah

1. Buka `/admin/bursa` **hanya jika LO juga diberi akses**; secara default LO
   hanya menyerahkan hadiah **mahasiswa gugusnya sendiri**.
2. Pada klaim berstatus *Menunggu*, tekan **Serahkan**.
3. Klaim yang sudah diserahkan **tidak bisa diserahkan dua kali** — tunjukkan
   pesan penolakannya.

## 3.3. Tiket bantuan (`/lo/tiket`)

1. Daftar tiket dari mahasiswa.
2. Balas dan ubah status (Sudah dijawab / Tandai selesai).

## 3.4. Yang TIDAK bisa dilakukan LO

- Mengubah harga/stok hadiah (hanya admin — satu harga untuk seluruh kampus)
- Mengakses panel akun & gugus
- Melihat bukti mahasiswa dari jurusan lain

---

# 4. Alur demo Admin (`DEMO-ADMIN`)

Masuk dengan NIM `DEMO-ADMIN`.

## 4.1. Akun & Gugus (`/admin/akun`)

1. **3.068 mahasiswa**, 51 gugus, pencarian dan filter.
2. **Tambah Akun** — buat LO atau mahasiswa tunggal tanpa CSV.
3. **Edit** — ubah nama, email, jurusan, gugus.
4. **Reset sandi** — sandi baru ditampilkan **sekali**.
5. **Hapus** — soft delete, wajib mengetik NIM/nama untuk konfirmasi.
6. **Pulihkan** — akun terhapus bisa dikembalikan **beserta riwayat XP/koin**.
7. Jelaskan alasannya: hard delete akan menghapus bukti saat sengketa
   *"saya sudah selesaikan modul 8"*.

## 4.2. Import CSV

1. Unduh template, isi, unggah.
2. **Progress bar nyata** — batch berjalan per anggaran waktu, bukan animasi.
3. Di akhir: panel **kredensial tampil sekali** + tombol **Unduh CSV**.
4. Tekankan: sandi **tidak pernah** tersimpan sebagai plaintext di database.

## 4.3. Kelola Bursa (`/admin/bursa`)

1. **CRUD hadiah**: nama, kode, harga, stok, kuota per mahasiswa, kategori,
   gambar (maks 2 MB), aktif/nonaktif.
2. **Pindai QR Klaim** — panel pemindai kamera. Kalau browser tidak mendukung
   `BarcodeDetector`, tombol kamera **tidak ditampilkan** (bukan tombol mati).
3. Masukkan kode manual `OQ-XXXX-XXXX` → halaman verifikasi menampilkan status
   klaim: **BELUM DISERAHKAN / SUDAH DISERAHKAN / DIBATALKAN**.
4. Tunjukkan peringatan di halaman itu: *"Jangan serahkan barang kedua kali."*

## 4.4. Quest Builder (`/admin/misi`)

1. **Jenis Misi**: pilih **Modul Peta** atau **Side Quest**.
   - Modul Peta → isi Urutan Modul, kode otomatis `MOD-xx`
   - Side Quest → isi **Syarat Modul Utama Selesai**, kode otomatis `SQ-xx`
2. **Tipe Aksi**: refleksi, bukti foto, token, GPS, kuis, check-in.
3. **Target Sasaran**: seluruh universitas / fakultas / program studi.
4. Semua dropdown memakai **komponen custom** — bisa diketik untuk mencari,
   bisa dioperasikan keyboard.

## 4.5. Verifikasi bukti sebagai admin

Admin bisa melihat **semua** bukti (bukan hanya sejurusan), berguna saat
sengketa atau saat LO berhalangan.

---

# 5. Skenario 5 menit untuk sesi demo

Kalau waktu di depan juri terbatas, urutan terpendek yang tetap menunjukkan
kedalaman:

| Menit | Apa | Di mana |
|:--:|---|---|
| 0–1 | Login mahasiswa, tunjukkan Dasbor + agenda hari ini | `/dashboard` |
| 1–2 | Buka **Jadwal**, tunjukkan seminar → kuis | `/jadwal` |
| 2–3 | Buka **Peta Misi**, tunjukkan urutan hari & 3 babak | `/misi` |
| 3–3,5 | Tukar hadiah, tunjukkan **kode klaim + QR** | `/bursa` |
| 3,5–4 | Login **LO**, setujui bukti mahasiswa | `/lo/verifikasi` |
| 4–4,5 | Login **admin**, pindai/verifikasi QR klaim | `/admin/bursa` |
| 4,5–5 | Tunjukkan Side Quest yang butuh foto + syarat bertahap | `/misi-samping` |

---

# 6. Pertanyaan juri yang sering muncul

| Pertanyaan | Jawaban singkat | Buktinya |
|---|---|---|
| "Bagaimana mencegah curang?" | Bukti foto ditinjau LO sejurusan; beberapa modul butuh token lokasi atau GPS radius 150 m | `VerificationService`, `AntiCheatVerificationTest` |
| "XP bisa di-farm?" | Tidak — setiap award membawa idempotency key unik | `XpService` |
| "Kenapa XP dan koin dipisah?" | XP mengukur progres dan tidak pernah berkurang; koin mata uang belanja dengan ledger sendiri | `CoinService` |
| "Bagaimana kalau salah hapus akun?" | Soft delete + tombol Pulihkan, riwayat utuh | `AccountLifecycleTest` |
| "Data mahasiswa dari mana?" | Import CSV dengan progress nyata, sandi tampil sekali | `StudentImportProgressTest` |
| "Bagaimana panitia tahu klaim asli?" | QR berisi URL verifikasi; kode teks tetap ada sebagai cadangan | `RewardClaimQrTest` |
| "Kenapa pakai Laravel, bukan HTML biasa?" | Laravel backend yang mengeluarkan HTML/CSS/JS; tidak ada SPA framework | — |
| "Mobile-nya bagaimana?" | 0 overflow di 360/393/430 px; Bursa 2 kolom; sidebar jadi drawer | uji overflow |

---

# 7. Daftar periksa sebelum hari-H

- [ ] `OSPEK_START_DATE` sudah tanggal acara, bukan tanggal lama
- [ ] `php artisan storage:link` sudah jalan — buka `/bursa`, **9 gambar tampil**
- [ ] `/jadwal` menampilkan 8 hari, 14 agenda
- [ ] `/misi` urutan hari **menanjak** (1,1,2,2,3,3,4,4,5,6,6,7,8)
- [ ] Ketiga akun demo bisa login
- [ ] `/admin/akun` tidak 500
- [ ] Panel "Pindai QR Klaim" muncul di `/admin/bursa`
- [ ] Sandi demo **sudah diganti** dari nilai bawaan
- [ ] Link video demo & hosting terisi di proposal
- [ ] 102 LO uji diganti LO sungguhan (jika ada)
