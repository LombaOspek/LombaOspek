# 🏆 OspekQuest — Portal Gamifikasi Orientasi Mahasiswa Baru

Portal web interaktif bergaya **Neo-Brutalist** untuk mengelola orientasi mahasiswa baru (OSPEK/PKKMB). Dibangun dengan Laravel 12, Tailwind CSS v4, dan Vite.

> **Konteks Lomba:** Proyek ini dikembangkan untuk **INVOFEST 2026** — Universitas Muria Kudus, Program Studi Sistem Informasi.

---

## 📋 Persyaratan Sistem

| Komponen         | Versi Minimum  | Catatan                                            |
|------------------|----------------|----------------------------------------------------|
| **PHP**          | 8.3+           | Pastikan extension `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath` aktif |
| **Composer**     | 2.x            | [getcomposer.org](https://getcomposer.org/)        |
| **Node.js**      | 18+            | Disarankan LTS (v20/v22)                           |
| **npm**          | 9+             | Sudah bawaan Node.js                               |
| **Git**          | 2.x            | Untuk clone repository                             |
| **Database**     | SQLite (default) | MySQL/MariaDB juga didukung, lihat bagian konfigurasi |

### Cek Cepat di Terminal

```bash
php -v          # → PHP 8.3.x atau lebih tinggi
composer -V     # → Composer version 2.x.x
node -v         # → v18.x.x atau lebih tinggi
npm -v          # → 9.x.x atau lebih tinggi
```

---

## 🚀 Cara Menjalankan (Setup dari Nol)

### 1. Clone Repository

```bash
git clone https://github.com/LombaOspek/LombaOspek.git
cd LombaOspek
```

### 2. Jalankan Setup Otomatis

Proyek ini sudah menyediakan Composer script `setup` yang mengerjakan semuanya:

```bash
composer run setup
```

Script tersebut melakukan langkah-langkah berikut secara otomatis:
1. `composer install` — menginstal dependensi PHP
2. Menyalin `.env.example` → `.env` (jika belum ada)
3. `php artisan key:generate` — membuat APP_KEY (⚠️ **wajib, tanpa ini aplikasi error**)
4. `php artisan migrate --force` — membuat tabel database
5. `npm install` — menginstal dependensi JavaScript
6. `npm run build` — mem-build aset frontend (CSS/JS)

### 3. Isi Database dengan Data Demo

```bash
php artisan db:seed
```

### 4. Jalankan Server Lokal

```bash
php artisan serve
```

Buka browser ke **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 🔑 Akun Demo

Setelah menjalankan `php artisan db:seed`, akun-akun berikut tersedia:

| NIM            | Email                            | Password   | Role    |
|----------------|----------------------------------|------------|---------|
| `202653033`    | `202653033@students.umk.ac.id`   | `password` | Student |
| `MENTOR-01`    | `nissa@mentor.umk.ac.id`         | `password` | Mentor  |

**Cara login paling cepat:** Klik tombol kuning **"⚡ Log Masuk Pantas"** di halaman `/login`.

---

## 🗂️ Halaman & Fitur

| Route           | Halaman                       | Deskripsi                                      |
|-----------------|-------------------------------|-------------------------------------------------|
| `/login`        | Halaman Login                 | Login via NIM/Email + password, atau Quick Login |
| `/`             | Dashboard                     | Ringkasan XP, level, streak, misi aktif, leaderboard |
| `/misi`         | Peta Misi Orientasi           | Quest map interaktif dengan modal detail misi   |
| `/kuiz`         | Kuis Kampus                   | Kuis interaktif dengan timer, skor, dan XP      |
| `/pencapaian`   | Pencapaian & Lencana          | Koleksi badge/lencana dan progress pencapaian    |
| `/sijil`        | Sertifikat                    | Sertifikat orientasi yang bisa dicetak/diunduh   |
| `/profil`       | Profil & Pengaturan           | Edit profil, toggle settings, FAQ, tiket bantuan |

---

## ⚙️ Konfigurasi Database

### SQLite (Default — Tanpa Install Apapun)

File `.env.example` sudah dikonfigurasi untuk SQLite. Tidak perlu install server database.

```env
DB_CONNECTION=sqlite
```

File `database/database.sqlite` akan dibuat otomatis saat migration.

### MySQL / MariaDB (Opsional)

Jika ingin pakai MySQL, edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ospekquest
DB_USERNAME=root
DB_PASSWORD=
```

Pastikan database `ospekquest` sudah dibuat di MySQL sebelum menjalankan migration.

---

## 🧪 Menjalankan Test

```bash
php artisan test --compact
```

---

## 🛠️ Setup Manual (Kalau `composer run setup` Gagal)

Jika script otomatis bermasalah, jalankan langkah-langkah ini satu per satu:

```bash
# 1. Install dependensi PHP
composer install

# 2. Salin file environment
cp .env.example .env          # Linux/Mac
copy .env.example .env        # Windows CMD
Copy-Item .env.example .env   # Windows PowerShell

# 3. Generate APP_KEY (WAJIB!)
php artisan key:generate

# 4. Buat file database SQLite (skip jika pakai MySQL)
# Linux/Mac:
touch database/database.sqlite
# Windows PowerShell:
New-Item database/database.sqlite -ItemType File -Force

# 5. Jalankan migration
php artisan migrate

# 6. Isi data demo
php artisan db:seed

# 7. Install dependensi JavaScript
npm install

# 8. Build aset frontend
npm run build

# 9. Jalankan server
php artisan serve
```

---

## ❗ Troubleshooting

### Error: `MissingAppKeyException` / `No application encryption key has been specified`

**Penyebab:** File `.env` belum ada atau `APP_KEY` kosong.

```bash
cp .env.example .env        # Salin template environment
php artisan key:generate    # Generate encryption key
```

### Error: `SQLSTATE[HY000]: General error: 1 no such table`

**Penyebab:** Migration belum dijalankan.

```bash
php artisan migrate
php artisan db:seed
```

### Error: `Vite manifest not found` / Halaman polos tanpa CSS

**Penyebab:** Aset frontend belum di-build.

```bash
npm install
npm run build
```

Atau untuk development dengan hot-reload:

```bash
npm run dev
```

### Error: `Headers already sent` / `Cannot modify header information`

**Penyebab:** Ada karakter (spasi/BOM) sebelum tag `<?php` di salah satu file PHP, atau ada output sebelum response dikirim. Pastikan tidak ada `echo`/`print` di file config.

---

## 📁 Struktur Proyek

```
LombaOspek/
├── app/
│   ├── Enums/              # Enum: QuestStatus, UserRole, dsb.
│   ├── Http/Controllers/   # AuthController, DashboardController, dll.
│   ├── Models/             # User, Quest, QuizQuestion, Achievement, dll.
│   ├── Services/           # XpService, ProgressionService, AchievementService
│   └── View/Components/    # Blade component classes
├── database/
│   ├── migrations/         # Skema tabel
│   └── seeders/            # Data demo orientasi
├── resources/
│   └── views/
│       ├── layouts/        # Template utama (app.blade.php)
│       ├── components/     # Reusable Blade components
│       ├── auth/           # Halaman login
│       └── pages/          # Dashboard, misi, kuis, pencapaian, dll.
├── routes/
│   └── web.php             # Semua route aplikasi
├── public/                 # Entry point & aset hasil build
├── tests/                  # PHPUnit test suite
└── README.md               # ← Anda di sini
```

---

## 📜 Lisensi

Proyek ini dibuat untuk keperluan lomba **INVOFEST 2026**. Lisensi MIT.
