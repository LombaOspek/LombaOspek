<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Faq;
use App\Models\Quest;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserQuest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Main MABA User (Karan Mehra from design)
        $karan = User::updateOrCreate(
            ['email' => 'karan.mehra@maba.ac.id'],
            [
                'name' => 'Karan Mehra',
                'nim' => 'MABA-2025-089',
                'password' => Hash::make('password'),
                'faculty' => 'Fakulti Sains Komputer & Teknologi Maklumat',
                'major' => 'Sains Komputer (Kejuruteraan Perisian)',
                'group_name' => 'Alpha Harimau 🐯',
                'mentor_name' => 'Kak Syakirah • Bilik Kaunseling 4B',
                'phone' => '+62 812-3456-7890',
                'xp' => 1320,
                'level' => 4,
                'streak_days' => 9,
                'avatar' => null,
                'sound_effects' => true,
                'email_notifications' => true,
                'whatsapp_notifications' => true,
                'dark_mode' => false,
            ]
        );

        // Additional Rival Students for Leaderboard
        $rivals = [
            [
                'name' => 'Aisyah Humaira',
                'nim' => 'MABA-2025-012',
                'email' => 'aisyah.humaira@maba.ac.id',
                'faculty' => 'Fakulti Perubatan & Sains Kesihatan',
                'major' => 'Doktor Perubatan',
                'group_name' => 'Garuda Emas 🦅',
                'xp' => 1580,
                'level' => 5,
                'streak_days' => 12,
            ],
            [
                'name' => 'Budi Santoso',
                'nim' => 'MABA-2025-045',
                'email' => 'budi.santoso@maba.ac.id',
                'faculty' => 'Fakulti Kejuruteraan & Alam Bina',
                'major' => 'Kejuruteraan Mekanikal',
                'group_name' => 'Alpha Harimau 🐯',
                'xp' => 1460,
                'level' => 4,
                'streak_days' => 10,
            ],
            [
                'name' => 'Farhan Daniel',
                'nim' => 'MABA-2025-088',
                'email' => 'farhan.daniel@maba.ac.id',
                'faculty' => 'Fakulti Ekonomi & Pengurusan',
                'major' => 'Perakaunan & Kewangan',
                'group_name' => 'Badak Perkasa 🦏',
                'xp' => 1390,
                'level' => 4,
                'streak_days' => 9,
            ],
            [
                'name' => 'Siti Sarah',
                'nim' => 'MABA-2025-101',
                'email' => 'siti.sarah@maba.ac.id',
                'faculty' => 'Fakulti Sains Sosial & Kemanusiaan',
                'major' => 'Komunikasi Media',
                'group_name' => 'Garuda Emas 🦅',
                'xp' => 1280,
                'level' => 4,
                'streak_days' => 8,
            ],
            [
                'name' => 'Kevin Wijaya',
                'nim' => 'MABA-2025-033',
                'email' => 'kevin.wijaya@maba.ac.id',
                'faculty' => 'Fakulti Sains Komputer & Teknologi Maklumat',
                'major' => 'Sistem Maklumat',
                'group_name' => 'Singa Juara 🦁',
                'xp' => 1190,
                'level' => 3,
                'streak_days' => 7,
            ],
        ];

        foreach ($rivals as $rivalData) {
            User::updateOrCreate(
                ['email' => $rivalData['email']],
                array_merge($rivalData, [
                    'password' => Hash::make('password'),
                    'mentor_name' => 'Senior Koordinator Orientasi',
                    'phone' => '+62 811-9876-5432',
                    'sound_effects' => true,
                    'email_notifications' => true,
                    'whatsapp_notifications' => true,
                    'dark_mode' => false,
                ])
            );
        }

        // 2. Create Orientation Quests (7 Modules)
        $questsData = [
            [
                'code' => 'MOD-01',
                'step_number' => 1,
                'title' => 'Sumpah Mahasiswa & Ikrar Integriti',
                'category' => 'Etika & Integriti',
                'description' => 'Membaca dan menandatangani ikrar akur janji integriti akademik dan tatatertib universiti di hadapan dekan.',
                'location' => 'Dewan Canselor Utama',
                'xp_reward' => 100,
                'icon' => '📜',
                'deadline' => 'Hari 1 • Selesai',
                'action_type' => 'checkin',
                'is_default_completed' => true,
            ],
            [
                'code' => 'MOD-02',
                'step_number' => 2,
                'title' => 'Jelajah Fasiliti Perpustakaan Digital',
                'category' => 'Kemudahan Kampus',
                'description' => 'Mengaktifkan akaun EZProxy perpustakaan dan meminjam e-buku panduan orientasi pertama secara digital.',
                'location' => 'Perpustakaan Tun Sri Lanang',
                'xp_reward' => 150,
                'icon' => '📚',
                'deadline' => 'Hari 2 • Selesai',
                'action_type' => 'checkin',
                'is_default_completed' => true,
            ],
            [
                'code' => 'MOD-03',
                'step_number' => 3,
                'title' => 'Pertemuan Mentor & Kumpulan Harimau',
                'category' => 'Aktiviti Pelajar',
                'description' => 'Sesi suai kenal bersama senior pembimbing kumpulan Alpha Harimau dan penetapan pelan sokongan semester 1.',
                'location' => 'Bilik Kaunseling 4B',
                'xp_reward' => 200,
                'icon' => '🤝',
                'deadline' => 'Hari 3 • Selesai',
                'action_type' => 'checkin',
                'is_default_completed' => true,
            ],
            [
                'code' => 'MOD-04',
                'step_number' => 4,
                'title' => 'Kuiz Pengenalan Fakulti & Sistem SKS',
                'category' => 'Akademik & SKS',
                'description' => 'Ujian interaktif 5 soalan pemahaman struktur kredit kursus (SKS), syarat kehadiran 80%, dan syarat kelulusan.',
                'location' => 'Makmal Komputer Al-Khawarizmi',
                'xp_reward' => 250,
                'icon' => '⚡',
                'deadline' => 'Hari Ini • Buka',
                'action_type' => 'quiz',
                'is_default_completed' => false,
            ],
            [
                'code' => 'MOD-05',
                'step_number' => 5,
                'title' => 'Pendaftaran Kelab & Persatuan Siswa',
                'category' => 'Kepimpinan & Kelab',
                'description' => 'Pilih dan daftar sekurang-kurangnya satu persatuan fakulti, kelab sukan, atau unit beruniform kampus.',
                'location' => 'Pusat Kegiatan Pelajar',
                'xp_reward' => 200,
                'icon' => '🏛️',
                'deadline' => 'Esok • Terkunci',
                'action_type' => 'checkin',
                'is_default_completed' => false,
            ],
            [
                'code' => 'MOD-06',
                'step_number' => 6,
                'title' => 'Jelajah Digital Sistem MyCampus',
                'category' => 'Teknologi & Portal',
                'description' => 'Pengesahan kad matrik maya dan pengaktifan kad diskaun konsesi pengangkutan awam pelajar.',
                'location' => 'Portal Mahasiswa Online',
                'xp_reward' => 220,
                'icon' => '💻',
                'deadline' => 'Hari 5 • Terkunci',
                'action_type' => 'checkin',
                'is_default_completed' => false,
            ],
            [
                'code' => 'MOD-07',
                'step_number' => 7,
                'title' => 'Majlis Penutup & Lencana Mahasiswa Sejati',
                'category' => 'Majlis Rasmi',
                'description' => 'Penyerahan sijil tamat orientasi berkanun dan pembukaan lencana rasmi graduan harapan universiti.',
                'location' => 'Stadium Tertutup Kampus',
                'xp_reward' => 300,
                'icon' => '🎖️',
                'deadline' => 'Hari 6 • Terkunci',
                'action_type' => 'checkin',
                'is_default_completed' => false,
            ],
        ];

        foreach ($questsData as $questRow) {
            $quest = Quest::updateOrCreate(['code' => $questRow['code']], $questRow);

            // Seed user quest status for Karan Mehra
            $status = $questRow['step_number'] <= 3 ? 'completed' : ($questRow['step_number'] === 4 ? 'in_progress' : 'locked');
            UserQuest::updateOrCreate(
                ['user_id' => $karan->id, 'quest_id' => $quest->id],
                [
                    'status' => $status,
                    'completed_at' => $questRow['step_number'] <= 3 ? now()->subDays(4 - $questRow['step_number']) : null,
                ]
            );
        }

        // 3. Create Quiz Questions for Module 4 (Interactive Quiz)
        $questionsData = [
            [
                'module_code' => 'MOD-04',
                'module_title' => 'Pengenalan Fakulti & Sistem Kredit Semester (SKS)',
                'question_number' => 1,
                'category' => 'Akademik Fakulti',
                'question' => 'Berapakah had maksimum beban Sistem Kredit Semester (SKS) yang boleh diambil oleh pelajar tahun pertama pada semester ganjil?',
                'option_a' => '16 Kredit Semester',
                'option_b' => '20 hingga 24 Kredit Semester mengikut pakej orientasi fakulti',
                'option_c' => 'Tiada had maksimum selagi yuran pengajian telah dijelaskan',
                'option_d' => '10 Kredit Semester sahaja untuk tempoh percubaan',
                'correct_option' => 'B',
                'hint' => 'Pakej tahun 1 biasanya telah ditetapkan secara berstruktur antara 20-24 SKS.',
                'explanation' => 'Pelajar tahun 1 diberi pakej asas terancang antara 20 hingga 24 SKS untuk memastikan penyesuaian beban pengajian optimum.',
                'xp_reward' => 30,
            ],
            [
                'module_code' => 'MOD-04',
                'module_title' => 'Pengenalan Fakulti & Sistem Kredit Semester (SKS)',
                'question_number' => 2,
                'category' => 'Kehadiran & Peperiksaan',
                'question' => 'Apakah syarat minimum peratusan kehadiran kuliah bagi melayakkan mahasiswa menduduki Peperiksaan Akhir Semester?',
                'option_a' => 'Sekurang-kurangnya 80% kehadiran aktif',
                'option_b' => '50% sekiranya menghantar surat tunjuk sebab',
                'option_c' => 'Cukup sekadar hadir semasa kuiz pertengahan semester',
                'option_d' => '70% untuk kursus teras dan 50% untuk kursus elektif',
                'correct_option' => 'A',
                'hint' => 'Peraturan am universiti menetapkan kehadiran minimum 80% untuk layak ke dewan peperiksaan.',
                'explanation' => 'Kehadiran minimum 80% adalah mandatori bagi semua kursus bergrad. Kegagalan mencapai 80% tanpa sebab sah mengakibatkan halangan menduduki peperiksaan.',
                'xp_reward' => 30,
            ],
            [
                'module_code' => 'MOD-04',
                'module_title' => 'Pengenalan Fakulti & Sistem Kredit Semester (SKS)',
                'question_number' => 3,
                'category' => 'Kemudahan Kampus',
                'question' => 'Di manakah lokasi Pusat Khidmat Kesihatan dan Kaunseling Pelajar kampus utama?',
                'option_a' => 'Tingkat Bawah Blok Pentadbiran Canseleri',
                'option_b' => 'Sebelah Kompleks Sukan & Stadium Mahasiswa',
                'option_c' => 'Bangunan Pusat Pelajar Aras 2, Sayap Timur',
                'option_d' => 'Kolej Kediaman Siswi Blok Melati',
                'correct_option' => 'C',
                'hint' => 'Pusat Kesihatan dan Kaunseling terletak di Bangunan Pusat Pelajar Aras 2 bersebelahan dewan serbaguna.',
                'explanation' => 'Pusat Kesihatan dan Kaunseling ditempatkan di Bangunan Pusat Pelajar Aras 2 untuk akses mesra mahasiswa dan kerahsiaan terpelihara.',
                'xp_reward' => 30,
            ],
            [
                'module_code' => 'MOD-04',
                'module_title' => 'Pengenalan Fakulti & Sistem Kredit Semester (SKS)',
                'question_number' => 4,
                'category' => 'Tatatertib & Keselamatan',
                'question' => 'Apakah tindakan wajib sekiranya kad pintar kampus (Smart Card ID) hilang atau rosak?',
                'option_a' => 'Menunggu sehingga tamat minggu peperiksaan baru membuat laporan',
                'option_b' => 'Membuat laporan segera di Portal Keselamatan & Pejabat Hal Ehwal Pelajar (HEP)',
                'option_c' => 'Meminjam kad matrik rakan sekuliah untuk akses perpustakaan',
                'option_d' => 'Mencetak sendiri salinan kertas tanpa tandatangan rasmi',
                'correct_option' => 'B',
                'hint' => 'Laporkan segera ke Bahagian Hal Ehwal Pelajar (HEP) bagi menyahaktifkan akses pintu pintar.',
                'explanation' => 'Laporan segera membolehkan sekuriti menyekat penyalahgunaan kemudahan dan memproses kad penggantian dalam tempoh 24 jam.',
                'xp_reward' => 30,
            ],
            [
                'module_code' => 'MOD-04',
                'module_title' => 'Pengenalan Fakulti & Sistem Kredit Semester (SKS)',
                'question_number' => 5,
                'category' => 'Gred & Prestasi',
                'question' => 'Berapakah mata GPA / PNGS minimum yang perlu dikekalkan oleh mahasiswa untuk mengelakkan status Amaran Akademik?',
                'option_a' => '1.50',
                'option_b' => '2.00',
                'option_c' => '2.50',
                'option_d' => '3.00',
                'correct_option' => 'B',
                'hint' => 'Status Kepujian Memuaskan memerlukan sekurang-kurangnya PNGS 2.00 ke atas.',
                'explanation' => 'Mata nilai PNGS 2.00 adalah garis aras lulus ijazah sarjana muda. Jika di bawah 2.00, pelajar akan diberikan bimbingan penasihat akademik.',
                'xp_reward' => 30,
            ],
        ];

        foreach ($questionsData as $q) {
            QuizQuestion::updateOrCreate(
                ['module_code' => $q['module_code'], 'question_number' => $q['question_number']],
                $q
            );
        }

        // 4. Create Achievements & Badges (Matching design)
        $achievementsData = [
            [
                'code' => 'ach-01',
                'title' => 'Pemburu Misi',
                'description' => 'Selesaikan 5 misi orientasi pertama dengan jayanya.',
                'icon' => '🎯',
                'category' => 'Misi',
                'xp_reward' => 150,
                'total_steps' => 5,
                'badge_name' => 'Lencana Pemburu Misi',
                'badge_color' => 'neo-yellow',
            ],
            [
                'code' => 'ach-02',
                'title' => 'Kutu Buku Kampus',
                'description' => 'Pinjam dan semak imbas 3 e-buku modul panduan fakulti.',
                'icon' => '📚',
                'category' => 'Akademik',
                'xp_reward' => 120,
                'total_steps' => 3,
                'badge_name' => 'Lencana Kutu Buku',
                'badge_color' => 'neo-blue',
            ],
            [
                'code' => 'ach-03',
                'title' => 'Juara Kuiz Fakulti',
                'description' => 'Raih skor sempurna 5/5 dalam Kuiz Interaktif SKS.',
                'icon' => '⚡',
                'category' => 'Akademik',
                'xp_reward' => 200,
                'total_steps' => 1,
                'badge_name' => 'Lencana Cendekiawan',
                'badge_color' => 'neo-orange',
            ],
            [
                'code' => 'ach-04',
                'title' => 'Sosialisasi Siswa',
                'description' => 'Berkenalan dan catat kenalan dari sekurang-kurangnya 10 rakan baharu.',
                'icon' => '🤝',
                'category' => 'Sosial',
                'xp_reward' => 180,
                'total_steps' => 10,
                'badge_name' => 'Lencana Sahabat Kampus',
                'badge_color' => 'neo-peach',
            ],
            [
                'code' => 'ach-05',
                'title' => 'Peneroka Kampus',
                'description' => 'Lakukan daftar masuk di 6 mercu tanda penting universiti.',
                'icon' => '🏛️',
                'category' => 'Penerokaan',
                'xp_reward' => 250,
                'total_steps' => 6,
                'badge_name' => 'Lencana Penjelajah',
                'badge_color' => 'neo-cream',
            ],
            [
                'code' => 'ach-06',
                'title' => 'Wira Mahasiswa Sejati',
                'description' => 'Selesaikan seluruh 7 modul orientasi OspekQuest 2025.',
                'icon' => '🏆',
                'category' => 'Misi',
                'xp_reward' => 500,
                'total_steps' => 7,
                'badge_name' => 'Lencana Mahasiswa Sejati',
                'badge_color' => 'neo-amber',
            ],
        ];

        foreach ($achievementsData as $achRow) {
            $achievement = Achievement::updateOrCreate(['code' => $achRow['code']], $achRow);

            // Preset progress for Karan Mehra matching UI design
            $currentSteps = match ($achRow['code']) {
                'ach-01' => 5, // Unlocked
                'ach-02' => 3, // Unlocked
                'ach-03' => 0, // In progress
                'ach-04' => 10, // Unlocked
                'ach-05' => 6, // Unlocked
                'ach-06' => 3, // In progress (3/7)
                default => 0,
            };
            $isUnlocked = $currentSteps >= $achRow['total_steps'];

            UserAchievement::updateOrCreate(
                ['user_id' => $karan->id, 'achievement_id' => $achievement->id],
                [
                    'current_steps' => $currentSteps,
                    'is_unlocked' => $isUnlocked,
                    'unlocked_at' => $isUnlocked ? now()->subDays(2) : null,
                ]
            );
        }

        // 5. Create FAQs (Pusat Bantuan)
        $faqsData = [
            [
                'category' => 'Pendaftaran & Kad Matrik',
                'question' => 'Bagaimanakah cara untuk mengambil kad matrik fizikal pelajar?',
                'answer' => 'Kad matrik fizikal boleh diambil di Kaunter Khidmat Pelajar, Bangunan Canseleri mulai hari ketiga orientasi dengan menunjukkan slip pendaftaran maya.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Orientasi & Kehadiran',
                'question' => 'Adakah kehadiran dalam semua modul OspekQuest diwajibkan?',
                'answer' => 'Ya, penyelesaian seluruh modul orientasi dan pengesahan kehadiran menerusi kod QR / kuiz adalah prasyarat untuk memperolehi Sijil Tamat Orientasi berkanun.',
                'sort_order' => 2,
            ],
            [
                'category' => 'Kesihatan & Kebajikan',
                'question' => 'Apa yang perlu dilakukan jika saya jatuh sakit atau tidak sihat?',
                'answer' => 'Sila maklumkan segera kepada mentor kumpulan anda (Kak Syakirah) dan kunjungi Pusat Kesihatan Mahasiswa di Bangunan Pusat Pelajar Aras 2.',
                'sort_order' => 3,
            ],
            [
                'category' => 'Akademik & SKS',
                'question' => 'Di manakah saya boleh menyemak jadual kuliah dan senarai pensyarah?',
                'answer' => 'Jadual kuliah semester 1 boleh disemak menerusi portal MyCampus atau tab Akademik sejurus selepas modul Kuiz SKS disahkan selesai.',
                'sort_order' => 4,
            ],
            [
                'category' => 'Bimbingan & Kaunseling',
                'question' => 'Bagaimana cara berhubung secara peribadi dengan kaunselor atau pembimbing senior?',
                'answer' => 'Anda boleh menggunakan borang tiket bantuan terus di portal ini atau berkunjung ke Bilik Kaunseling 4B pada hari bekerja (8:30 pagi - 4:30 petang).',
                'sort_order' => 5,
            ],
        ];

        foreach ($faqsData as $faqRow) {
            Faq::updateOrCreate(
                ['question' => $faqRow['question']],
                $faqRow
            );
        }
    }
}
