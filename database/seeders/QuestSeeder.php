<?php

namespace Database\Seeders;

use App\Enums\QuestActionType;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Katalog modul orientasi yang disusun sebagai tiga babak tematik.
     *
     * Babak bukan sekadar pengelompokan visual: rentang hari tiap babak
     * ditentukan oleh `unlock_day`, sehingga peta misi punya 4-5 node per
     * babak alih-alih satu node per hari. Susunannya:
     *
     *   BAB 1  Pengenalan Kampus      Hari 1-2  (4 modul)
     *   BAB 2  Eksplorasi Fakultas    Hari 3-5  (5 modul)
     *   BAB 3  Aksi & Penutupan       Hari 6-8  (4 modul)
     *
     * Beberapa modul sengaja memakai token/GPS/foto karena inilah yang
     * menutup celah curang: mahasiswa tidak bisa menandai selesai dari kamar.
     *
     * CATATAN PENTING untuk nomor langkah:
     * - step 3  wajib Reflection (dipakai tes untuk validasi catatan refleksi)
     * - step 4  wajib Quiz berkode MOD-04 (QuizQuestionSeeder + QuizTest)
     * - step 8  wajib GPS (AntiCheatVerificationTest memakainya sebagai modul
     *           jauh yang masih terkunci)
     * Jangan menggeser ketiganya tanpa memperbarui tes terkait.
     */
    public function run(): void
    {
        $quests = [
            // ============================================================
            // BAB 1 - PENGENALAN KAMPUS (Hari 1-2)
            // ============================================================
            [
                'code' => 'MOD-01',
                'step_number' => 1,
                'unlock_day' => 1,
                'title' => 'Registrasi Ulang & Verifikasi Berkas',
                'category' => 'Administrasi Akademik',
                'description' => 'Unggah bukti registrasi ulang pada portal SIAKAD, lalu lampirkan tangkapan layar yang memperlihatkan NIM dan status aktif Anda.',
                'objective' => 'Pastikan status registrasi aktif dan NIM terhubung ke portal akademik kampus.',
                'location' => 'Portal SIAKAD UMK',
                'xp_reward' => 100,
                'estimated_minutes' => 10,
                'icon' => 'certificate',
                'deadline' => 'Hari ke-1',
                'action_type' => QuestActionType::PhotoProof,
                'verification_method' => VerificationMethod::Photo,
                'status' => 'published',
                'difficulty' => 1,
                'target_scope' => 'all',
            ],
            [
                'code' => 'MOD-02',
                'step_number' => 2,
                'unlock_day' => 2,
                'title' => 'Jelajah Perpustakaan & Repositori Digital',
                'category' => 'Sarana Pembelajaran',
                'description' => 'Kunjungi Perpustakaan Pusat, temui petugas, dan minta kode token yang ditempel pada meja layanan digital.',
                'objective' => 'Mahasiswa baru mengenal kanal literatur resmi kampus secara langsung.',
                'location' => 'Perpustakaan Pusat UMK',
                'xp_reward' => 120,
                'estimated_minutes' => 15,
                'icon' => 'book',
                'deadline' => 'Hari ke-2',
                'action_type' => QuestActionType::Token,
                'verification_method' => VerificationMethod::Token,
                'status' => 'published',
                'difficulty' => 1,
                'target_scope' => 'all',
                'token_code' => 'PERPUS-UMK-26',
                'token_quota' => 500,
            ],
            [
                'code' => 'MOD-03',
                'step_number' => 3,
                'unlock_day' => 1,
                'title' => 'Tur Keliling Kampus & Titik Layanan',
                'category' => 'Orientasi Lingkungan',
                'description' => 'Telusuri lima titik layanan wajib: gedung rektorat, perpustakaan, laboratorium, kantin, dan klinik. Catat kesan pertama Anda di setiap titik.',
                'objective' => 'Mengenal tata letak kampus agar tidak tersesat pada hari perkuliahan pertama.',
                'location' => 'Lingkungan Kampus UMK',
                'xp_reward' => 110,
                'estimated_minutes' => 25,
                'icon' => 'compass',
                'deadline' => 'Hari ke-1',
                'action_type' => QuestActionType::Reflection,
                'verification_method' => VerificationMethod::None,
                'status' => 'published',
                'difficulty' => 1,
                'target_scope' => 'all',
            ],
            [
                'code' => 'MOD-04',
                'step_number' => 4,
                'unlock_day' => 2,
                'title' => 'Kuis Sistem Kredit Semester & Etika Akademik',
                'category' => 'Akademik & Integritas',
                'description' => 'Kerjakan kuis lima soal tentang beban SKS, syarat kehadiran 75%, ambang nilai, dan aturan integritas akademik.',
                'objective' => 'Memastikan pemahaman aturan akademik sebelum menyusun rencana studi.',
                'location' => 'Daring · Kuis Interaktif',
                'xp_reward' => 250,
                'estimated_minutes' => 12,
                'icon' => 'quiz',
                'deadline' => 'Hari ke-2',
                'action_type' => QuestActionType::Quiz,
                'verification_method' => VerificationMethod::None,
                'quiz_module_code' => 'MOD-04',
                'status' => 'published',
                'difficulty' => 2,
                'target_scope' => 'all',
            ],

            // ============================================================
            // BAB 2 - EKSPLORASI FAKULTAS (Hari 3-5)
            // ============================================================
            [
                'code' => 'MOD-05',
                'step_number' => 5,
                'unlock_day' => 3,
                'title' => 'Pertemuan Kelompok & Mentor Pendamping',
                'category' => 'Kehidupan Kampus',
                'description' => 'Bertemu mentor pendamping dan anggota kelompok. Sepakati jadwal belajar bersama serta kanal komunikasi kelompok.',
                'objective' => 'Membentuk kelompok belajar yang saling mendukung sejak hari pertama.',
                'location' => 'Gedung PKM · Ruang Kelompok',
                'xp_reward' => 150,
                'estimated_minutes' => 45,
                'icon' => 'users',
                'deadline' => 'Hari ke-3',
                'action_type' => QuestActionType::Checkin,
                'verification_method' => VerificationMethod::None,
                'status' => 'published',
                'difficulty' => 1,
                'target_scope' => 'all',
            ],
            [
                'code' => 'MOD-06',
                'step_number' => 6,
                'unlock_day' => 3,
                'title' => 'Kunjungan Laboratorium Fakultas Teknik',
                'category' => 'Sarana Pembelajaran',
                'description' => 'Saksikan demonstrasi perangkat di Laboratorium Jaringan dan Laboratorium Rekayasa Perangkat Lunak. Minta token dari asisten laboratorium.',
                'objective' => 'Mengenalkan fasilitas praktikum yang akan dipakai selama studi.',
                'location' => 'Gedung Teknik · Laboratorium',
                'xp_reward' => 180,
                'estimated_minutes' => 30,
                'icon' => 'layers',
                'deadline' => 'Hari ke-3',
                'action_type' => QuestActionType::Token,
                'verification_method' => VerificationMethod::Token,
                'status' => 'published',
                'difficulty' => 2,
                'target_scope' => 'all',
                'token_code' => 'LAB-FT-UMK-26',
                'token_quota' => 300,
            ],
            [
                'code' => 'MOD-07',
                'step_number' => 7,
                'unlock_day' => 4,
                'title' => 'Kenalan dengan Dosen Wali & Program Studi',
                'category' => 'Akademik & Integritas',
                'description' => 'Temui dosen wali program studi Sistem Informasi. Diskusikan minat awal dan rencana mata kuliah semester pertama.',
                'objective' => 'Membangun relasi akademik pertama dengan dosen pembimbing.',
                'location' => 'Gedung Teknik · Ruang Prodi SI',
                'xp_reward' => 160,
                'estimated_minutes' => 20,
                'icon' => 'user',
                'deadline' => 'Hari ke-4',
                'action_type' => QuestActionType::Checkin,
                'verification_method' => VerificationMethod::None,
                'status' => 'published',
                'difficulty' => 1,
                'target_scope' => 'all',
            ],
            [
                'code' => 'MOD-08',
                'step_number' => 8,
                'unlock_day' => 4,
                'title' => 'Apel Orientasi Akbar di Auditorium UMK',
                'category' => 'Majelis Resmi',
                'description' => 'Hadiri apel orientasi akbar. Kehadiran diverifikasi lewat koordinat GPS perangkat Anda terhadap radius auditorium.',
                'objective' => 'Menjamin kehadiran fisik pada agenda wajib, bukan sekadar klaim.',
                'location' => 'Auditorium Utama UMK',
                'xp_reward' => 350,
                'estimated_minutes' => 90,
                'icon' => 'pin',
                'deadline' => 'Hari ke-4',
                'action_type' => QuestActionType::Geolocation,
                'verification_method' => VerificationMethod::Gps,
                'status' => 'published',
                'difficulty' => 2,
                'target_scope' => 'all',
                'latitude' => -6.8048000,
                'longitude' => 110.8405000,
                'radius_meters' => 150,
            ],
            [
                'code' => 'MOD-09',
                'step_number' => 9,
                'unlock_day' => 5,
                'title' => 'Eksplorasi Stan Muria Computing Club (MCC)',
                'category' => 'Organisasi Mahasiswa',
                'description' => 'Kunjungi stan MCC di Gedung PKM, saksikan demo proyek, lalu minta Kode Token Rahasia dari panitia stan.',
                'objective' => 'Mendorong interaksi langsung dengan unit kegiatan mahasiswa.',
                'location' => 'Gedung PKM · Stan MCC',
                'xp_reward' => 250,
                'estimated_minutes' => 30,
                'icon' => 'flag',
                'deadline' => 'Hari ke-5',
                'action_type' => QuestActionType::Token,
                'verification_method' => VerificationMethod::Token,
                'status' => 'published',
                'difficulty' => 2,
                'target_scope' => 'all',
                'sponsor_name' => 'Muria Computing Club',
                'bonus_reward' => 'Stiker Hologram + Pin Coding',
                'token_code' => 'MCC-UMK-99',
                'token_quota' => 400,
            ],

            // ============================================================
            // BAB 3 - AKSI & PENUTUPAN (Hari 6-8)
            // ============================================================
            [
                'code' => 'MOD-10',
                'step_number' => 10,
                'unlock_day' => 6,
                'title' => 'Workshop Etika Digital & Jejak Online',
                'category' => 'Praktik & Inovasi',
                'description' => 'Ikuti workshop singkat tentang jejak digital, keamanan akun, dan etika berkomunikasi di ruang daring kampus.',
                'objective' => 'Membekali mahasiswa dengan kesadaran keamanan digital sejak awal.',
                'location' => 'Gedung Teknik · Ruang Multimedia',
                'xp_reward' => 200,
                'estimated_minutes' => 60,
                'icon' => 'shield',
                'deadline' => 'Hari ke-6',
                'action_type' => QuestActionType::Checkin,
                'verification_method' => VerificationMethod::None,
                'status' => 'published',
                'difficulty' => 2,
                'target_scope' => 'all',
            ],
            [
                'code' => 'MOD-11',
                'step_number' => 11,
                'unlock_day' => 6,
                'title' => 'Proyek Mini: Solusi Digital untuk Kampus',
                'category' => 'Praktik & Inovasi',
                'description' => 'Rancang satu ide solusi digital untuk masalah nyata di kampus. Jelaskan masalah, pengguna, dan dampaknya.',
                'objective' => 'Menghubungkan tema Smart Ecosystems dengan praktik nyata.',
                'location' => 'Daring · Lembar Kerja',
                'xp_reward' => 280,
                'estimated_minutes' => 60,
                'icon' => 'sparkle',
                'deadline' => 'Hari ke-6',
                'action_type' => QuestActionType::Reflection,
                'verification_method' => VerificationMethod::None,
                'status' => 'published',
                'difficulty' => 3,
                'target_scope' => 'all',
            ],
            [
                'code' => 'MOD-12',
                'step_number' => 12,
                'unlock_day' => 7,
                'title' => 'Presentasi Kelompok & Umpan Balik Mentor',
                'category' => 'Praktik & Inovasi',
                'description' => 'Presentasikan hasil proyek mini kelompok Anda di depan mentor dan kelompok lain, lalu catat umpan balik yang masuk.',
                'objective' => 'Melatih keberanian menyampaikan gagasan dan menerima kritik.',
                'location' => 'Gedung PKM · Aula Kecil',
                'xp_reward' => 240,
                'estimated_minutes' => 50,
                'icon' => 'podium',
                'deadline' => 'Hari ke-7',
                'action_type' => QuestActionType::Checkin,
                'verification_method' => VerificationMethod::None,
                'status' => 'published',
                'difficulty' => 2,
                'target_scope' => 'all',
            ],
            [
                'code' => 'MOD-13',
                'step_number' => 13,
                'unlock_day' => 8,
                'title' => 'Upacara Penutupan & Sertifikat Orientasi',
                'category' => 'Majelis Resmi',
                'description' => 'Hadiri penutupan orientasi di Auditorium. Konfirmasi kehadiran lewat GPS, lalu sertifikat digital Anda terbuka otomatis.',
                'objective' => 'Menutup rangkaian orientasi dan menerbitkan sertifikat resmi.',
                'location' => 'Auditorium Universitas Muria Kudus',
                'xp_reward' => 300,
                'estimated_minutes' => 90,
                'icon' => 'certificate',
                'deadline' => 'Hari ke-8',
                'action_type' => QuestActionType::Geolocation,
                'verification_method' => VerificationMethod::Gps,
                'status' => 'published',
                'difficulty' => 2,
                'target_scope' => 'all',
                'latitude' => -6.8048000,
                'longitude' => 110.8405000,
                'radius_meters' => 150,
            ],
        ];

        foreach ($quests as $row) {
            $tokenCode = $row['token_code'] ?? null;
            unset($row['token_code']);

            $quest = Quest::updateOrCreate(['code' => $row['code']], $row);

            // Token disimpan sebagai hash; plaintext hanya ada di seeder ini
            // sebagai kode awal yang nanti dibagikan panitia di lokasi.
            if ($tokenCode !== null) {
                $quest->setPlainToken($tokenCode);
                $quest->save();
            }
        }
    }
}
