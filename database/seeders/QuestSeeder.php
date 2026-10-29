<?php

namespace Database\Seeders;

use App\Enums\QuestActionType;
use App\Models\Quest;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Delapan modul orientasi. `unlock_day` menentukan gerbang waktu sehingga
     * modul tidak bisa dilompati walau pemanggil mengirim request langsung.
     */
    public function run(): void
    {
        $quests = [
            [
                'code' => 'MOD-01',
                'step_number' => 1,
                'unlock_day' => 1,
                'title' => 'Registrasi Ulang & Verifikasi Berkas',
                'category' => 'Administrasi Akademik',
                'description' => 'Unggah bukti registrasi ulang, verifikasi Kartu Tanda Mahasiswa digital, dan lengkapi data akademik pada portal Sistem Informasi Akademik.',
                'objective' => 'Pastikan status registrasi Anda aktif dan NIM sudah terhubung ke portal akademik kampus.',
                'location' => 'Portal SIAKAD UMK',
                'xp_reward' => 100,
                'estimated_minutes' => 10,
                'icon' => 'certificate',
                'deadline' => 'Hari ke-1',
                'action_type' => QuestActionType::Checkin,
                'quiz_module_code' => null,
            ],
            [
                'code' => 'MOD-02',
                'step_number' => 2,
                'unlock_day' => 2,
                'title' => 'Jelajah Perpustakaan & Repositori Digital',
                'category' => 'Sarana Pembelajaran',
                'description' => 'Aktifkan akun perpustakaan digital, telusuri repositori skripsi Program Studi Sistem Informasi, dan simpan satu referensi untuk mata kuliah semester pertama.',
                'objective' => 'Mahasiswa baru mengenal kanal literatur resmi kampus sebelum masa perkuliahan dimulai.',
                'location' => 'Perpustakaan Pusat UMK',
                'xp_reward' => 120,
                'estimated_minutes' => 15,
                'icon' => 'book',
                'deadline' => 'Hari ke-2',
                'action_type' => QuestActionType::Checkin,
                'quiz_module_code' => null,
            ],
            [
                'code' => 'MOD-03',
                'step_number' => 3,
                'unlock_day' => 3,
                'title' => 'Pertemuan Kelompok & Mentor Pendamping',
                'category' => 'Kehidupan Kampus',
                'description' => 'Hadiri sesi perkenalan bersama mentor pendamping kelompok, sepakati jadwal belajar bersama, dan catat kontak dua rekan satu kelompok.',
                'objective' => 'Membangun jejaring awal agar mahasiswa baru tidak merasa berjalan sendiri di minggu pertama.',
                'location' => 'Gedung Fakultas Teknik, Ruang 4B',
                'xp_reward' => 150,
                'estimated_minutes' => 45,
                'icon' => 'users',
                'deadline' => 'Hari ke-3',
                'action_type' => QuestActionType::Reflection,
                'quiz_module_code' => null,
            ],
            [
                'code' => 'MOD-04',
                'step_number' => 4,
                'unlock_day' => 4,
                'title' => 'Kuis Sistem Kredit Semester & Etika Akademik',
                'category' => 'Akademik & Integritas',
                'description' => 'Kerjakan kuis lima soal tentang beban SKS, syarat kehadiran 75%, ambang nilai, dan aturan integritas akademik Program Studi Sistem Informasi.',
                'objective' => 'Memastikan mahasiswa baru memahami aturan akademik sebelum menyusun rencana studi bersama dosen pembimbing.',
                'location' => 'Daring · Kuis Interaktif',
                'xp_reward' => 250,
                'estimated_minutes' => 12,
                'icon' => 'quiz',
                'deadline' => 'Hari ke-4',
                'action_type' => QuestActionType::Quiz,
                'quiz_module_code' => 'MOD-04',
            ],
            [
                'code' => 'MOD-05',
                'step_number' => 5,
                'unlock_day' => 5,
                'title' => 'Pendaftaran Organisasi & Unit Kegiatan Mahasiswa',
                'category' => 'Kepemimpinan',
                'description' => 'Pilih minimal satu organisasi mahasiswa atau unit kegiatan, lalu tuliskan alasan memilihnya serta peran yang ingin Anda ambil.',
                'objective' => 'Mendorong keterlibatan aktif di luar kelas sebagai bagian dari pengembangan soft skill.',
                'location' => 'Pusat Kegiatan Mahasiswa',
                'xp_reward' => 180,
                'estimated_minutes' => 20,
                'icon' => 'flag',
                'deadline' => 'Hari ke-5',
                'action_type' => QuestActionType::Reflection,
                'quiz_module_code' => null,
            ],
            [
                'code' => 'MOD-06',
                'step_number' => 6,
                'unlock_day' => 6,
                'title' => 'Literasi Data & Etika Penggunaan Kecerdasan Buatan',
                'category' => 'Teknologi & Etika',
                'description' => 'Pelajari panduan penggunaan AI dalam tugas akademik, lalu tunjukkan pemahaman melalui absensi modul literasi digital kampus.',
                'objective' => 'Menyiapkan mahasiswa Sistem Informasi menghadapi perkuliahan berbasis teknologi secara bertanggung jawab.',
                'location' => 'Laboratorium Komputer Sistem Informasi',
                'xp_reward' => 220,
                'estimated_minutes' => 25,
                'icon' => 'shield',
                'deadline' => 'Hari ke-6',
                'action_type' => QuestActionType::Checkin,
                'quiz_module_code' => null,
            ],
            [
                'code' => 'MOD-07',
                'step_number' => 7,
                'unlock_day' => 7,
                'title' => 'Proyek Mini: Solusi Digital untuk Kampus',
                'category' => 'Praktik & Inovasi',
                'description' => 'Rancang satu ide solusi digital sederhana untuk masalah nyata di kampus, lalu jelaskan masalah, pengguna, dan dampaknya dalam catatan refleksi.',
                'objective' => 'Menghubungkan tema Smart Ecosystems dengan praktik nyata di lingkungan kampus.',
                'location' => 'Daring · Lembar Kerja',
                'xp_reward' => 280,
                'estimated_minutes' => 60,
                'icon' => 'sparkle',
                'deadline' => 'Hari ke-7',
                'action_type' => QuestActionType::Reflection,
                'quiz_module_code' => null,
            ],
            [
                'code' => 'MOD-08',
                'step_number' => 8,
                'unlock_day' => 8,
                'title' => 'Upacara Penutupan & Sertifikat Orientasi',
                'category' => 'Majelis Resmi',
                'description' => 'Hadiri penutupan orientasi, konfirmasi kehadiran, lalu sertifikat digital Anda terbuka otomatis setelah seluruh modul selesai.',
                'objective' => 'Menutup rangkaian orientasi dan menerbitkan sertifikat resmi yang dapat diverifikasi publik.',
                'location' => 'Auditorium Universitas Muria Kudus',
                'xp_reward' => 300,
                'estimated_minutes' => 90,
                'icon' => 'certificate',
                'deadline' => 'Hari ke-8',
                'action_type' => QuestActionType::Checkin,
                'quiz_module_code' => null,
            ],
        ];

        foreach ($quests as $quest) {
            Quest::updateOrCreate(['code' => $quest['code']], $quest);
        }
    }
}
