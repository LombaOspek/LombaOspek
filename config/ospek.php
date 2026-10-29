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

];
