<?php

namespace Database\Seeders;

use App\Enums\QuestActionType;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use Illuminate\Database\Seeder;

/**
 * Side Quest — misi sosial opsional (proposal: "interaksi secara sosial").
 *
 * Semua di sini memakai `step_number` di atas 100 supaya tidak pernah
 * bertabrakan dengan penomoran modul utama (1-13), walau `mainline()` sudah
 * memisahkan keduanya.
 */
class SideQuestSeeder extends Seeder
{
    public function run(): void
    {
        $quests = [
            [
                'code' => 'SQ-01',
                'step_number' => 101,
                'unlock_day' => 1,
                'title' => 'Sapa Lima Teman Baru',
                'category' => 'Sosial',
                'description' => 'Perkenalkan diri ke lima mahasiswa baru di luar gugus Anda. Tulis satu kalimat tentang apa yang Anda pelajari dari masing-masing.',
                'objective' => 'Refleksi singkat setelah menyapa lima orang.',
                'location' => 'Area kampus',
                'xp_reward' => 80,
                'estimated_minutes' => 15,
                'icon' => 'users',
                'action_type' => QuestActionType::Reflection,
                'verification_method' => VerificationMethod::None,
                'required_completed_modules' => 0,
                'difficulty' => 1,
            ],
            [
                'code' => 'SQ-02',
                'step_number' => 102,
                'unlock_day' => 1,
                'title' => 'Foto Bareng Gugus',
                'category' => 'Sosial',
                'description' => 'Kumpulkan minimal lima anggota gugus Anda dan ambil satu foto bersama di depan gedung fakultas.',
                'objective' => 'Unggah satu foto gugus.',
                'location' => 'Depan Gedung Fakultas Teknik',
                'xp_reward' => 100,
                'estimated_minutes' => 20,
                'icon' => 'camera',
                'action_type' => QuestActionType::PhotoProof,
                'verification_method' => VerificationMethod::Photo,
                'required_completed_modules' => 2,
                'difficulty' => 2,
            ],
            [
                'code' => 'SQ-03',
                'step_number' => 103,
                'unlock_day' => 2,
                'title' => 'Bantu Satu Mahasiswa Baru',
                'category' => 'Sosial',
                'description' => 'Bantu teman yang kebingungan mencari lokasi atau memahami satu modul. Ceritakan apa yang Anda bantu.',
                'objective' => 'Catatan singkat bantuan yang diberikan.',
                'location' => 'Area kampus',
                'xp_reward' => 120,
                'estimated_minutes' => 20,
                'icon' => 'help',
                'action_type' => QuestActionType::Reflection,
                'verification_method' => VerificationMethod::None,
                'required_completed_modules' => 4,
                'difficulty' => 2,
            ],
            [
                'code' => 'SQ-04',
                'step_number' => 104,
                'unlock_day' => 3,
                'title' => 'Tantang Dosen Satu Pertanyaan',
                'category' => 'Akademik',
                'description' => 'Ajukan satu pertanyaan bermutu dalam sesi tanya jawab. Tulis pertanyaan dan jawaban yang Anda terima.',
                'objective' => 'Pertanyaan beserta ringkasan jawaban.',
                'location' => 'Ruang sesi fakultas',
                'xp_reward' => 150,
                'estimated_minutes' => 25,
                'icon' => 'quiz',
                'action_type' => QuestActionType::Reflection,
                'verification_method' => VerificationMethod::None,
                'required_completed_modules' => 6,
                'difficulty' => 3,
            ],
            [
                'code' => 'SQ-05',
                'step_number' => 105,
                'unlock_day' => 4,
                'title' => 'Rekam Pesan untuk Diri Sendiri',
                'category' => 'Refleksi',
                'description' => 'Tulis pesan singkat untuk diri Anda di akhir orientasi. Akan ditampilkan saat sertifikat Anda terbit.',
                'objective' => 'Satu paragraf pesan pribadi.',
                'location' => 'Daring',
                'xp_reward' => 100,
                'estimated_minutes' => 10,
                'icon' => 'scroll',
                'action_type' => QuestActionType::Reflection,
                'verification_method' => VerificationMethod::None,
                'required_completed_modules' => 8,
                'difficulty' => 1,
            ],
        ];

        foreach ($quests as $quest) {
            Quest::updateOrCreate(
                ['code' => $quest['code']],
                $quest + [
                    'is_side_quest' => true,
                    'is_default_completed' => false,
                    'status' => 'published',
                    'target_scope' => 'all',
                ],
            );
        }
    }
}