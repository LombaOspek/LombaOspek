<?php

namespace Database\Seeders;

use App\Models\OrientationSchedule;
use App\Models\Quest;
use Illuminate\Database\Seeder;

/**
 * Jadwal orientasi 8 hari yang tersambung ke modul peta misi.
 *
 * Prinsipnya: setiap acara punya tindak lanjut di OspekQuest. Seminar selesai,
 * kuis terbuka. Tur kampus selesai, refleksi terbuka. Hadiah tidak diberikan
 * untuk menonton seminar, tetapi untuk membuktikan pemahaman setelahnya.
 *
 * `quest_id` adalah pengaitnya. Modul yang tidak tertaut agenda tetap ada di
 * peta (mis. tugas mandiri di luar jam acara), jadi kolomnya nullable.
 */
class OrientationScheduleSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Kode modul -> kode agenda. Ditulis terpisah dari isi jadwal supaya
         * tautan modul mudah diperiksa: kalau sebuah kode modul tidak ada,
         * agenda itu tetap dibuat tanpa tautan (bukan gagal senyap).
         */
        $schedule = [
            // ================= HARI 1 — Pembukaan & Pengenalan =================
            [
                'day' => 1,
                'title' => 'Registrasi Ulang & Pembagian Gugus',
                'description' => 'Verifikasi berkas, pengambilan atribut, dan pengumuman gugus masing-masing.',
                'starts_at' => '07:00',
                'ends_at' => '09:00',
                'location' => 'Gedung Rektorat UMK',
                'kind' => 'opening',
                'quest' => 'MOD-01',
                'sort_order' => 1,
            ],
            [
                'day' => 1,
                'title' => 'Upacara Pembukaan Orientasi',
                'description' => 'Sambutan Rektor dan pengenalan panitia PKKMB.',
                'starts_at' => '09:00',
                'ends_at' => '11:00',
                'location' => 'Auditorium Utama UMK',
                'speaker' => 'Rektor Universitas Muria Kudus',
                'kind' => 'ceremony',
                'quest' => null,
                'sort_order' => 2,
            ],
            [
                'day' => 1,
                'title' => 'Tur Keliling Kampus & Titik Layanan',
                'description' => 'Menyusuri lima titik layanan wajib bersama kakak pendamping gugus.',
                'starts_at' => '13:00',
                'ends_at' => '15:30',
                'location' => 'Lingkungan Kampus UMK',
                'kind' => 'tour',
                'quest' => 'MOD-03',
                'sort_order' => 3,
            ],

            // ================= HARI 2 — Akademik =================
            [
                'day' => 2,
                'title' => 'Seminar Sistem Kredit Semester & Etika Akademik',
                'description' => 'Beban SKS, syarat kehadiran 75%, ambang nilai, dan aturan integritas akademik.',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'location' => 'Auditorium Utama UMK',
                'speaker' => 'Wakil Dekan I Fakultas Teknik',
                'kind' => 'seminar',
                // Inilah pola yang diminta: seminar selesai -> kuis terbuka.
                'quest' => 'MOD-04',
                'sort_order' => 1,
            ],
            [
                'day' => 2,
                'title' => 'Jelajah Perpustakaan & Repositori Digital',
                'description' => 'Pengenalan kanal literatur resmi dan cara mengakses repositori kampus.',
                'starts_at' => '10:30',
                'ends_at' => '12:00',
                'location' => 'Perpustakaan Pusat UMK',
                'speaker' => 'Kepala Perpustakaan',
                'kind' => 'tour',
                'quest' => 'MOD-02',
                'sort_order' => 2,
            ],

            // ================= HARI 3 — Kehidupan Kampus =================
            [
                'day' => 3,
                'title' => 'Pertemuan Gugus & Mentor Pendamping',
                'description' => 'Perkenalan anggota, kesepakatan belajar bersama, dan kanal komunikasi gugus.',
                'starts_at' => '08:00',
                'ends_at' => '10:00',
                'location' => 'Gedung PKM · Ruang Gugus',
                'kind' => 'session',
                'quest' => 'MOD-05',
                'sort_order' => 1,
            ],
            [
                'day' => 3,
                'title' => 'Kunjungan Laboratorium Fakultas Teknik',
                'description' => 'Demonstrasi perangkat Laboratorium Jaringan dan Rekayasa Perangkat Lunak.',
                'starts_at' => '10:30',
                'ends_at' => '13:00',
                'location' => 'Gedung Teknik · Laboratorium',
                'speaker' => 'Asisten Laboratorium',
                'kind' => 'tour',
                'quest' => 'MOD-06',
                'sort_order' => 2,
            ],

            // ================= HARI 4 — Apel Akbar =================
            [
                'day' => 4,
                'title' => 'Apel Orientasi Akbar',
                'description' => 'Apel seluruh mahasiswa baru. Kehadiran diverifikasi lewat GPS di lokasi.',
                'starts_at' => '07:00',
                'ends_at' => '09:00',
                'location' => 'Auditorium Utama UMK',
                'kind' => 'ceremony',
                'quest' => 'MOD-08',
                'sort_order' => 1,
            ],
            [
                'day' => 4,
                'title' => 'Kenalan dengan Dosen Wali & Program Studi',
                'description' => 'Diskusi minat awal dan rencana mata kuliah semester pertama.',
                'starts_at' => '10:00',
                'ends_at' => '12:00',
                'location' => 'Gedung Teknik · Ruang Prodi',
                'speaker' => 'Dosen Wali Program Studi',
                'kind' => 'session',
                'quest' => 'MOD-07',
                'sort_order' => 2,
            ],

            // ================= HARI 5 — Stan Organisasi =================
            [
                'day' => 5,
                'title' => 'Pameran Unit Kegiatan Mahasiswa',
                'description' => 'Stan organisasi dan komunitas kampus. Minta token dari panitia stan.',
                'starts_at' => '08:00',
                'ends_at' => '14:00',
                'location' => 'Gedung PKM · Area Pameran',
                'kind' => 'fair',
                'quest' => 'MOD-09',
                'sort_order' => 1,
            ],

            // ================= HARI 6 — Workshop =================
            [
                'day' => 6,
                'title' => 'Workshop Etika Digital & Jejak Online',
                'description' => 'Jejak digital, keamanan akun, dan etika berkomunikasi di ruang daring kampus.',
                'starts_at' => '08:00',
                'ends_at' => '11:00',
                'location' => 'Gedung Teknik · Ruang Multimedia',
                'speaker' => 'Tim Keamanan Informasi Kampus',
                'kind' => 'workshop',
                'quest' => 'MOD-10',
                'sort_order' => 1,
            ],
            [
                'day' => 6,
                'title' => 'Pendampingan Proyek Mini Solusi Digital',
                'description' => 'Sesi kerja terbimbing merancang solusi digital untuk masalah nyata di kampus.',
                'starts_at' => '13:00',
                'ends_at' => '15:30',
                'location' => 'Gedung Teknik · Ruang Kelas',
                'kind' => 'workshop',
                'quest' => 'MOD-11',
                'sort_order' => 2,
            ],

            // ================= HARI 7 — Presentasi =================
            [
                'day' => 7,
                'title' => 'Presentasi Kelompok & Umpan Balik Mentor',
                'description' => 'Presentasi hasil proyek mini di depan mentor dan kelompok lain.',
                'starts_at' => '08:00',
                'ends_at' => '12:00',
                'location' => 'Gedung PKM · Aula Kecil',
                'speaker' => 'Mentor Pendamping',
                'kind' => 'session',
                'quest' => 'MOD-12',
                'sort_order' => 1,
            ],

            // ================= HARI 8 — Penutupan =================
            [
                'day' => 8,
                'title' => 'Upacara Penutupan & Penerbitan Sertifikat',
                'description' => 'Penutupan orientasi. Konfirmasi kehadiran lewat GPS, sertifikat terbuka otomatis.',
                'starts_at' => '08:00',
                'ends_at' => '11:00',
                'location' => 'Auditorium Universitas Muria Kudus',
                'speaker' => 'Rektor Universitas Muria Kudus',
                'kind' => 'closing',
                'quest' => 'MOD-13',
                'sort_order' => 1,
            ],
        ];

        foreach ($schedule as $row) {
            $questCode = $row['quest'];
            unset($row['quest']);

            $questId = null;

            if ($questCode !== null) {
                $questId = Quest::query()->where('code', $questCode)->value('id');

                if ($questId === null) {
                    // Jangan menautkan ke modul yang tidak ada: agenda tetap
                    // dibuat, tetapi tanpa tombol yang menuju 404.
                    $this->command?->warn("Agenda \"{$row['title']}\" menunjuk {$questCode} yang tidak ada.");
                }
            }

            OrientationSchedule::updateOrCreate(
                ['day' => $row['day'], 'title' => $row['title']],
                $row + ['quest_id' => $questId],
            );
        }
    }
}