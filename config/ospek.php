<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Program
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh sidebar, sertifikat, dan verifikasi publik. Semua nilai di
    | sini sengaja diletakkan di config agar tidak perlu menyentuh view ketika
    | nama kampus atau sesi akademik berubah.
    |
    */

    'program' => [
        'name' => 'OspekQuest',
        'tagline' => 'Gamifikasi Orientasi Mahasiswa Baru',
        'university' => 'Universitas Muria Kudus',
        'faculty' => 'Fakultas Teknik',
        'study_program' => 'Program Studi Sistem Informasi',
        'class_of' => '2026',
        'academic_year' => '2026/2027',
        'theme' => 'Smart Ecosystems: Digital Solutions for a Sustainable Future',
        'subtheme' => 'Smart Education & Campus Innovation (SDG 4)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Penanda Tangan Sertifikat
    |--------------------------------------------------------------------------
    |
    | Dicetak pada sertifikat. Dipisah dari view supaya penggantian pejabat
    | tidak perlu menyentuh template, dan supaya tidak ada NIM yang di-hardcode
    | (versi sebelumnya menuliskan NIM satu orang untuk semua mahasiswa).
    |
    */

    'signatories' => [
        'academic' => [
            'name' => 'Dr. Ir. Muhammad Arifin, S.Kom., M.Kom.',
            'role' => 'Wakil Dekan III',
            'identifier' => 'NIDN 0621048301',
        ],
        'committee' => [
            'name' => 'Aldiansyah Fayruz',
            'role' => 'Ketua Tim OspekQuest',
            'identifier' => 'NIM 202653033',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kalender Orientasi
    |--------------------------------------------------------------------------
    |
    | `start_date` dipakai untuk menghitung "Hari ke-N" serta gerbang waktu
    | tiap modul. Modul dengan `unlock_day` lebih besar dari hari berjalan
    | otomatis terkunci walau modul sebelumnya sudah selesai.
    |
    | Tanggal dapat ditimpa lewat environment agar demo tidak menampilkan
    | "hari ke-74 dari 8" ketika acara sudah lewat.
    |
    */

    'orientation' => [
        'start_date' => env('OSPEK_START_DATE', '2026-10-26'),
        'days' => (int) env('OSPEK_TOTAL_DAYS', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Babak Tematik Peta Misi
    |--------------------------------------------------------------------------
    |
    | Peta misi tidak lagi dipecah per hari (satu palang untuk satu hari dengan
    | satu node). Hari-hari dikelompokkan menjadi babak yang punya narasi
    | perjalanan, sehingga satu palang menaungi 3-5 node sekaligus dan lintasan
    | terasa seperti peta game petualangan.
    |
    | `from` / `to` adalah nomor hari orientasi (inklusif). Modul masuk ke babak
    | berdasarkan `unlock_day`-nya. Urutan array menentukan urutan babak.
    |
    */
    'chapters' => [
        [
            'key' => 'pengenalan',
            'title' => 'Pengenalan Kampus',
            'from' => 1,
            'to' => 2,
            'tagline' => 'Kenali rumah baru Anda',
            'narrative' => 'Dua hari pertama untuk mengurus administrasi, menemukan titik layanan, dan memahami aturan akademik sebelum petualangan sesungguhnya dimulai.',
            'icon' => 'compass',
        ],
        [
            'key' => 'eksplorasi',
            'title' => 'Eksplorasi Fakultas',
            'from' => 3,
            'to' => 5,
            'tagline' => 'Masuk ke jantung fakultas',
            'narrative' => 'Laboratorium, dosen wali, dan unit kegiatan mahasiswa. Di sinilah Anda mulai mengenali alat dan orang yang akan menemani studi Anda.',
            'icon' => 'layers',
        ],
        [
            'key' => 'aksi',
            'title' => 'Aksi & Penutupan',
            'from' => 6,
            'to' => 8,
            'tagline' => 'Tunjukkan karyamu',
            'narrative' => 'Bekali diri dengan etika digital, rancang solusi nyata untuk kampus, lalu tutup perjalanan dengan sertifikat orientasi.',
            'icon' => 'flag',
        ],
    ],

    'levels' => [
        1 => ['title' => 'Maba Pemula', 'min_xp' => 0],
        2 => ['title' => 'Penjelajah Kampus', 'min_xp' => 300],
        3 => ['title' => 'Pengembara Siswa', 'min_xp' => 750],
        4 => ['title' => 'Perintis Kampus', 'min_xp' => 1400],
        5 => ['title' => 'Mentor Muda', 'min_xp' => 2200],
        6 => ['title' => 'Mahasiswa Legenda', 'min_xp' => 3200],
        7 => ['title' => 'Duta Orientasi', 'min_xp' => 4500],
    ],

    /*
    |--------------------------------------------------------------------------
    | Aturan Kuis
    |--------------------------------------------------------------------------
    */

    'quiz' => [
        'max_attempts' => 3,
        'time_limit_seconds' => 300,
        'grace_seconds' => 20,
        'pass_percentage' => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Akun Demo
    |--------------------------------------------------------------------------
    |
    | Tombol "Masuk sebagai Demo" hanya aktif ketika `enabled` bernilai true.
    | Matikan pada build produksi agar tidak ada jalur autentikasi pintas.
    |
    */

    'demo' => [
        'enabled' => env('OSPEK_DEMO_LOGIN', true),
        'nim' => '202653033',
        'password' => 'password',
    ],

    /*
    |--------------------------------------------------------------------------
    | Engagement Hook (Gugus Wars, Kotak Misteri, Misi Rahasia)
    |--------------------------------------------------------------------------
    |
    | Semua hadiah di bawah diberikan lewat XpService dengan idempotency key,
    | jadi tidak bisa di-farm dengan mengulang klik atau membuka modal dua kali.
    |
    */

    'engagement' => [

        // Hadiah Kotak Misteri Harian. Deterministik per user+tanggal agar
        // hasilnya bisa diaudit dan tidak bisa diulang dengan reload.
        'daily_box' => [
            'min' => 40,
            'max' => 120,
            'step' => 20,
        ],

        // Misi Rahasia: WIRA harus diklik 7x dalam jendela waktu singkat.
        'secret_mission' => [
            'clicks' => 7,
            'window_ms' => 2600,
            'xp' => 150,
        ],

        // Gelar "Digital Flexing" di papan peringkat, urut dari atas.
        // `min_xp` dan `rank_max` dua-duanya harus terpenuhi (atau salah satu
        // bila nilainya null) supaya gelar tidak diberikan cuma karena XP besar
        // di kampus yang pesertanya sedikit.
        'titles' => [
            ['label' => 'Sang Legenda', 'min_xp' => 4500, 'rank_max' => null, 'frame' => 'legend'],
            ['label' => 'Sang Ambis', 'min_xp' => 2200, 'rank_max' => null, 'frame' => 'ember'],
            ['label' => 'Pendekar XP', 'min_xp' => 1400, 'rank_max' => null, 'frame' => 'gold'],
            ['label' => 'Penjelajah', 'min_xp' => 750, 'rank_max' => null, 'frame' => 'silver'],
            ['label' => 'Si Rajin', 'min_xp' => 300, 'rank_max' => null, 'frame' => 'plain'],
        ],

        // FOMO live feed. Seluruh baris berasal dari XP asli milik mahasiswa
        // lain di kampus yang sama - tidak ada pencapaian fiktif.
        'live_feed' => [
            'max_items' => 4,
            'window_minutes' => 180,
            'rotate_ms' => 9000,
        ],
    ],

];
