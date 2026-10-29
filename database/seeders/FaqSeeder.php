<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'category' => 'Akademik & SKS',
                'question' => 'Bagaimana cara mengubah rencana studi setelah disetujui dosen pembimbing?',
                'answer' => 'Perubahan rencana studi hanya dapat dilakukan pada masa revisi yang ditetapkan bagian akademik. Ajukan permohonan tertulis ke dosen Pembimbing Akademik, lalu bawa persetujuan ke Administrasi Akademik Fakultas sebelum masa revisi berakhir.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Kartu Mahasiswa',
                'question' => 'Kartu mahasiswa saya hilang, apa langkah pertamanya?',
                'answer' => 'Buat surat keterangan kehilangan dari kepolisian, lalu ajukan penggantian di bagian Administrasi Akademik Fakultas dengan membawa surat tersebut dan foto terbaru. Kartu pengganti umumnya selesai dalam lima hari kerja.',
                'sort_order' => 2,
            ],
            [
                'category' => 'Orientasi',
                'question' => 'Apakah seluruh modul OspekQuest wajib diselesaikan?',
                'answer' => 'Ya. Sertifikat orientasi hanya diterbitkan bila kedelapan modul selesai. Setiap modul memiliki gerbang waktu, jadi modul berikutnya baru terbuka sesuai jadwal dan setelah modul sebelumnya tuntas.',
                'sort_order' => 3,
            ],
            [
                'category' => 'Orientasi',
                'question' => 'Saya tidak bisa hadir pada salah satu sesi luring. Apakah bisa diganti?',
                'answer' => 'Sampaikan kepada mentor pendamping kelompok maksimal satu hari sebelum sesi. Mentor akan mencatat alasan Anda dan memberi tugas pengganti bila alasannya dapat diterima.',
                'sort_order' => 4,
            ],
            [
                'category' => 'Fasilitas Kampus',
                'question' => 'Bagaimana cara mengakses jaringan internet kampus?',
                'answer' => 'Gunakan akun akademik Anda pada jaringan Wi-Fi kampus. Bila belum aktif, ajukan aktivasi ke Unit Sistem Informasi dengan menyertakan NIM dan Kartu Tanda Mahasiswa.',
                'sort_order' => 5,
            ],
            [
                'category' => 'Kesehatan',
                'question' => 'Ke mana saya harus melapor jika merasa tidak sehat selama orientasi?',
                'answer' => 'Hubungi mentor pendamping dan kunjungi Klinik Kampus. Untuk keadaan darurat di luar jam kerja, hubungi nomor keamanan kampus yang tertera pada papan informasi gedung fakultas.',
                'sort_order' => 6,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq + ['is_published' => true],
            );
        }
    }
}
