# OspekQuest — Kebutuhan Sistem & Cara Menjalankan

Portal gamifikasi orientasi mahasiswa baru **Program Studi Sistem Informasi, Fakultas Teknik,
Universitas Muria Kudus**. Dibuat untuk **Web Design Competition INVOFEST 2026**, subtema
*Smart Education & Campus Innovation (SDG 4)*.

---

## 1. Kebutuhan Perangkat Lunak

| Komponen | Versi minimum | Versi teruji |
|---|---|---|
| PHP | 8.2 | 8.4.x |
| Composer | 2.6 | 2.x |
| Node.js | 20 LTS | 24.20.0 |
| npm | 10 | 11.x |
| MySQL / MariaDB | 8.0 / 10.4 | MariaDB 10.4.32 |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` atau `imagick`, `zip` | — |

`gd`/`imagick` diperlukan untuk validasi gambar bukti misi. `fileinfo` wajib agar unggahan
non-gambar (mis. `.php` yang menyamar) ditolak.

---

## 2. Pemasangan Pertama Kali

```bash
# 1. Ambil dependensi
composer install
npm install

# 2. Siapkan berkas lingkungan
cp .env.example .env
php artisan key:generate

# 3. Isi kredensial basis data di .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=ospekquest
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Buat basis data (sekali saja)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ospekquest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 5. Bangun skema + data awal
php artisan migrate --seed

# 6. Kompilasi aset (WAJIB — Tailwind tidak dimuat dari CDN)
npm run build
```

> **Catatan:** aset **tidak** boleh dimuat lewat CDN Tailwind. Layout memakai
> `@vite([...])`, sehingga `npm run build` (atau `npm run dev`) harus dijalankan sebelum
> halaman dibuka. Tanpa langkah ini tampilan akan polos tanpa gaya.

---

## 3. Variabel Lingkungan Penting

Selain variabel standar Laravel, aplikasi memakai variabel khusus berikut:

| Variabel | Default | Keterangan |
|---|---|---|
| `APP_TIMEZONE` | `Asia/Jakarta` | Zona waktu perhitungan hari orientasi.
| `OSPEK_START_DATE` | `2026-10-26` | Tanggal hari pertama orientasi (Y-m-d).
| `OSPEK_TOTAL_DAYS` | `8` | Jumlah hari orientasi; dipakai untuk *clamp* "Hari ke-N dari 8".
| `OSPEK_DEMO_LOGIN` | `false` | Bila `true`, tombol isi-cepat akun demo muncul di halaman masuk. **Matikan saat produksi.**

Semua nilai di atas dibaca oleh `config/ospek.php`.

---

## 4. Menjalankan Aplikasi

```bash
# Terminal 1 — server aplikasi
php artisan serve --port=8123

# Terminal 2 (opsional) — aset saat pengembangan
npm run dev
```

Buka `http://127.0.0.1:8123`.

### Akun demo (hasil seeder)

| Peran | NIM / ID | Kata sandi |
|---|---|---|
| Mahasiswa | `202653033` | `password` |
| Mahasiswa | `202653044` | `password` |
| Liaison Officer (Gugus 04) | `LO-2022-001` | `password` |
| Liaison Officer (Gugus 01) | `LO-2022-002` | `password` |
| Superadmin | `ADMIN-01` | `password` |

Ganti seluruh kata sandi ini sebelum aplikasi dipakai di luar lingkungan demo.

---

## 5. Perintah Harian

```bash
php artisan test --compact          # seluruh suite (69 tes, 215 asersi)
php artisan migrate:fresh --seed    # reset total + data contoh
npm run build                       # kompilasi ulang CSS/JS
php artisan optimize:clear          # bersihkan cache config/route/view
```

---

## 6. Peran Pengguna & Hak Akses

| Peran | Nilai `role` | Bisa apa |
|---|---|---|
| Mahasiswa baru | `student` | Mengerjakan 8 modul, kuis, melihat peta misi/pencapaian/sertifikat/peringkat, mengajukan tiket bantuan. |
| Liaison Officer | `lo` | Meninjau & memutuskan bukti misi mahasiswa **di gugusnya sendiri**. |
| Mentor | `mentor` | Membalas tiket bantuan. |
| Superadmin | `admin` | Manajemen akun & gugus, import massal CSV, Quest Builder, akses seluruh area. |

Otorisasi ditegakkan lewat middleware `auth`, `role`, dan `mentor` (lihat `bootstrap/app.php`).
Tidak ada jalan pintas seperti `User::first()` atau rute `/quick-login`.

---

## 7. Mekanisme Anti-Curang

Setiap modul punya `verification_method` yang menentukan cara penyelesaiannya:

| Metode | Alur | Bukti diambil |
|---|---|---|
| `none` | Tombol "Selesaikan" langsung | — |
| `token` | Mahasiswa memasukkan kode dari stan panitia | Server, hash `sha256` |
| `gps` | Server mengukur jarak Haversine ke titik acuan | Server, `distance_meters` |
| `photo` | Unggah bukti → antre → LO menyetujui | Server, berkas privat |
| kuis | Nilai `pass_percentage` menentukan kelulusan | Server, `quiz_attempts` |

Aturan yang ditegakkan server (bukan JavaScript):

1. **Modul terkunci tidak bisa diselesaikan** — status dihitung ulang dari `user_quests`,
   `step_number`, dan `unlock_day` pada setiap permintaan.
2. **XP idempoten** — setiap pemberian XP memakai `idempotency_key` unik di tabel
   `xp_transactions`; mengulang permintaan atau mengulang kuis tidak menambah XP.
3. **Token sekali pakai per akun**, dengan kuota dan tanggal kedaluwarsa opsional.
4. **Radius GPS dihitung di server**; akurasi perangkat di atas 500 m ditolak.
5. **Bukti foto tidak memberi XP** sampai LO gugus menyetujui.
6. **LO hanya bisa meninjau gugusnya** — permintaan untuk mahasiswa lain ditolak walaupun URL
   dimanipulasi.
7. **Berkas bukti disimpan privat** di `storage/app/private` dan hanya disajikan lewat rute
   ber-otorisasi.
8. **Kuis memakai timer sisi server** dan batas percobaan; waktu mulai diambil dari sesi,
   bukan dari isi permintaan.

---

## 8. Struktur Direktori Penting

```
app/
  Enums/          UserRole, QuestStatus, VerificationMethod, SubmissionStatus, ...
  Models/         User, Quest, UserQuest, QuestSubmission, Achievement, Group, ...
  Services/       XpService, ProgressionService, VerificationService,
                  QuizService, AchievementService, LeaderboardService, CertificateService
  Http/
    Controllers/  Auth, Dashboard, Quest, Quiz, Achievement, Certificate,
                  Leaderboard, Profile, Account, Mentor, LiaisonOfficer,
                  QuestBuilder, Verification
    Middleware/   EnsureRole, EnsureUserIsMentor, TrackDailyActivity
  Support/        Icon (56 ikon SVG kustom), Badge
config/ospek.php  identitas program, kalender orientasi, penanda tangan sertifikat
database/
  migrations/     skema lengkap
  seeders/        DatabaseSeeder, QuestSeeder, QuizQuestionSeeder, AchievementSeeder, ...
resources/
  css/app.css     token warna WCAG AA, utilitas neo-brutalis
  views/
    layouts/      app.blade.php
    partials/     sidebar, topbar, footer
    components/   icon, badge, stat-tile, panel-header, progress, status-pill, verify-modal
    pages/        dashboard, missions, quest-detail, quiz, achievements, certificate,
                  leaderboard, profile, lo/, admin/
routes/web.php    seluruh rute
tests/Feature/    69 tes
```

---

## 9. Pemecahan Masalah

| Gejala | Sebab & solusi |
|---|---|
| Tampilan tanpa gaya | Aset belum dibangun → `npm run build`; pastikan `public/build/manifest.json` ada. |
| `SQLSTATE[HY000] [1049] Unknown database` | Basis data belum dibuat → jalankan langkah 4. |
| "Hari ke-74 dari 8" | `OSPEK_START_DATE`/`OSPEK_TOTAL_DAYS` salah; nilai hari di-*clamp* ke total. |
| Tombol "Selesaikan" tidak muncul | Modul memakai verifikasi token/GPS/foto/kuis — tombol diganti aksi verifikasi. |
| Bukti foto tidak menambah XP | Normal: XP baru masuk setelah LO gugus menyetujui. |
| LO tidak bisa menyetujui | Mahasiswa bukan anggota gugus LO tersebut (ditolak sesuai desain). |
| Sertifikat tidak bisa dibuka | Sertifikat hanya tersedia setelah seluruh 8 modul selesai. |

---

## 10. Sebelum Dikumpulkan

- [ ] `npm run build` dijalankan dan `public/build/` ikut terkirim
- [ ] `APP_DEBUG=false`, `APP_ENV=production` di `.env` contoh yang diserahkan
- [ ] `OSPEK_DEMO_LOGIN=false`
- [ ] Kata sandi akun demo diganti atau dihapus
- [ ] `.env` **tidak** ikut diunggah; cukup `.env.example`
- [ ] `php artisan test` lulus seluruhnya
- [ ] Sertifikat diuji cetak (A4 landscape, satu halaman)