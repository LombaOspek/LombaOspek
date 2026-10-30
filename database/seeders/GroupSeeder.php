<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Gugus nyata + satu LO per gugus. `mentor_id` dipakai untuk menegakkan
     * aturan "LO hanya memverifikasi mahasiswa gugusnya sendiri".
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Kelompok 01 — Merpati', 'code' => 'G-01', 'study_program' => 'Sistem Informasi', 'badge_color' => 'sky'],
            ['name' => 'Kelompok 02 — Elang', 'code' => 'G-02', 'study_program' => 'Sistem Informasi', 'badge_color' => 'teal'],
            ['name' => 'Kelompok 03 — Kancil', 'code' => 'G-03', 'study_program' => 'Informatika', 'badge_color' => 'amber'],
            ['name' => 'Kelompok 04 — Garuda', 'code' => 'G-04', 'study_program' => 'Sistem Informasi', 'badge_color' => 'vermilion'],
        ];

        $officers = [
            ['name' => 'Kak Nissa', 'nim' => 'LO-2022-001', 'group' => 'G-04'],
            ['name' => 'Kak Syakirah', 'nim' => 'LO-2022-002', 'group' => 'G-01'],
            ['name' => 'Kak Dimas', 'nim' => 'LO-2022-003', 'group' => 'G-02'],
            ['name' => 'Kak Rahul', 'nim' => 'LO-2022-004', 'group' => 'G-03'],
        ];

        $created = [];

        foreach ($groups as $row) {
            $created[$row['code']] = Group::updateOrCreate(
                ['code' => $row['code']],
                $row + [
                    'capacity' => 75,
                    'invitation_code' => Group::generateInvitationCode($row['name']),
                ],
            );
        }

        // Superadmin — pengelola akun & Quest Builder.
        User::updateOrCreate(
            ['nim' => 'ADMIN-01'],
            [
                'name' => 'Dr. Rian Ardianto',
                'email' => 'admin@umk.ac.id',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
                'faculty' => 'Fakultas Teknik',
                'major' => 'Sistem Informasi',
                'xp' => 0,
                'level' => 1,
            ],
        );

        foreach ($officers as $row) {
            $group = $created[$row['group']];

            User::updateOrCreate(
                ['nim' => $row['nim']],
                [
                    'name' => $row['name'],
                    'email' => strtolower(str_replace(' ', '.', $row['name'])).'@lo.umk.ac.id',
                    'password' => bcrypt('password'),
                    'role' => UserRole::LiaisonOfficer,
                    'faculty' => 'Fakultas Teknik',
                    'major' => $group->study_program,
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'mentor_name' => null,
                    'xp' => 0,
                    'level' => 1,
                ],
            );

            // Kolom mentor_id pada groups menunjuk LO pembimbingnya.
            $officer = User::query()->where('nim', $row['nim'])->first();
            $group->forceFill([
                'mentor_id' => $officer->id,
                'mentor_name' => $officer->name,
            ])->save();
        }
    }
}