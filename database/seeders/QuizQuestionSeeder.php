<?php

namespace Database\Seeders;

use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

class QuizQuestionSeeder extends Seeder
{
    /**
     * Soal disesuaikan dengan sistem akademik Indonesia: SKS, kehadiran 75%,
     * grade, dan etika akademik — bukan istilah Malaysia seperti versi lama.
     */
    public function run(): void
    {
        $questions = [
            [
                'question_number' => 1,
                'category' => 'Beban Studi',
                'question' => 'Berapa beban studi maksimum yang umumnya diambil mahasiswa semester 1 paket reguler?',
                'option_a' => '9 SKS',
                'option_b' => '18 sampai 20 SKS sesuai paket semester',
                'option_c' => '30 SKS tanpa syarat',
                'option_d' => 'Bebas tanpa batas',
                'correct_option' => 'B',
                'hint' => 'Semester 1 biasanya memakai paket tetap agar mahasiswa baru lebih mudah beradaptasi.',
                'explanation' => 'Semester pertama memakai paket reguler sekitar 18–20 SKS. Penambahan beban baru dimungkinkan setelah mahasiswa memiliki IPK dan berkonsultasi dengan dosen Pembimbing Akademik.',
                'xp_reward' => 30,
            ],
            [
                'question_number' => 2,
                'category' => 'Kehadiran',
                'question' => 'Berapa persentase kehadiran minimum agar mahasiswa boleh mengikuti Ujian Akhir Semester?',
                'option_a' => '50%',
                'option_b' => '60%',
                'option_c' => '75%',
                'option_d' => '90%',
                'correct_option' => 'C',
                'hint' => 'Aturan umum perguruan tinggi menetapkan tiga per empat pertemuan.',
                'explanation' => 'Kehadiran minimal 75% dari total pertemuan adalah syarat mengikuti ujian akhir. Kekurangan kehadiran tanpa alasan sah dapat berakibat tidak memperoleh nilai mata kuliah tersebut.',
                'xp_reward' => 30,
            ],
            [
                'question_number' => 3,
                'category' => 'Penilaian',
                'question' => 'Apa arti nilai huruf E dalam transkrip?',
                'option_a' => 'Lulus dengan predikat sangat baik',
                'option_b' => 'Belum mengambil mata kuliah tersebut',
                'option_c' => 'Tidak lulus dan harus mengulang mata kuliah',
                'option_d' => 'Nilai pengganti dari kegiatan organisasi',
                'correct_option' => 'C',
                'hint' => 'Huruf E berada di bawah rentang nilai yang dinyatakan lulus.',
                'explanation' => 'Nilai E berarti tidak lulus. Mata kuliah tersebut harus ditempuh kembali dan nilainya masuk perhitungan Indeks Prestasi Kumulatif.',
                'xp_reward' => 30,
            ],
            [
                'question_number' => 4,
                'category' => 'Integritas Akademik',
                'question' => 'Anda terlambat mengumpulkan tugas dan melihat solusi milik teman. Tindakan yang benar adalah…',
                'option_a' => 'Menyalin solusi teman lalu mengubah format penulisan',
                'option_b' => 'Mengumpulkan karya sendiri sebisanya dan transparan menanyakan konsekuensi keterlambatan',
                'option_c' => 'Mengumpulkan dua versi agar salah satunya dinilai',
                'option_d' => 'Meminta teman mengumpulkan atas nama Anda',
                'correct_option' => 'B',
                'hint' => 'Integritas akademik menilai kejujuran proses, bukan hanya benar atau salah jawaban.',
                'explanation' => 'Plagiarisme dan mengumpulkan karya orang lain termasuk pelanggaran etika akademik yang dapat berujung sanksi. Menjaga orisinalitas dan bersikap transparan mengenai keterlambatan adalah pilihan yang benar.',
                'xp_reward' => 40,
            ],
            [
                'question_number' => 5,
                'category' => 'Sarana Kampus',
                'question' => 'Di mana mahasiswa baru dapat mengurus Kartu Tanda Mahasiswa yang hilang?',
                'option_a' => 'Dosen Pembimbing Akademik',
                'option_b' => 'Bagian Administrasi Akademik Fakultas',
                'option_c' => 'Unit Kegiatan Mahasiswa',
                'option_d' => 'Perpustakaan Pusat',
                'correct_option' => 'B',
                'hint' => 'Kartu mahasiswa adalah dokumen identitas akademik, bukan dokumen organisasi.',
                'explanation' => 'Pengurusan kartu hilang dilakukan di bagian Administrasi Akademik Fakultas dengan surat kehilangan. Penggantian biasanya membutuhkan waktu beberapa hari kerja.',
                'xp_reward' => 40,
            ],
        ];

        foreach ($questions as $question) {
            QuizQuestion::updateOrCreate(
                ['module_code' => 'MOD-04', 'question_number' => $question['question_number']],
                $question + [
                    'module_code' => 'MOD-04',
                    'module_title' => 'Sistem Kredit Semester & Etika Akademik',
                ],
            );
        }
    }
}
