<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * groups:rebuild — satu gugus harus berisi satu program studi.
 *
 * Bug yang dikunci: slug kode gugus hanya 6 huruf sehingga "Teknik Informatika"
 * dan "Teknik Industri" bertabrakan, dan anggota dua prodi tercampur ke gugus
 * yang sama.
 */
class RebuildGroupsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function makeStudents(string $major, int $count, string $prefix): void
    {
        for ($i = 1; $i <= $count; $i++) {
            User::create([
                'nim' => $prefix.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => $major.' '.$i,
                'email' => $prefix.$i.'@example.test',
                'password' => 'password',
                'role' => UserRole::Student,
                'major' => $major,
            ]);
        }
    }

    public function test_rebuild_puts_every_student_in_a_group_of_their_own_major(): void
    {
        $this->artisan('groups:rebuild', ['--size' => 20])->assertSuccessful();

        $mismatch = User::query()
            ->join('groups', 'users.group_id', '=', 'groups.id')
            ->whereColumn('users.major', '!=', 'groups.study_program')
            ->count();

        $this->assertSame(0, $mismatch, 'Tidak boleh ada mahasiswa di gugus jurusan lain.');
    }

    public function test_group_codes_are_unique_across_similar_major_names(): void
    {
        /*
         * Nama prodi ini dulu bertabrakan pada 6 huruf pertama ("PENDID",
         * "TEKNIK"), sehingga anggota dua prodi masuk ke satu gugus.
         */
        $this->makeStudents('Pendidikan Bahasa Inggris', 25, '9101');
        $this->makeStudents('Pendidikan Bahasa dan Sastra Indonesia', 25, '9102');
        $this->makeStudents('Teknik Informatika', 25, '9103');
        $this->makeStudents('Teknik Industri', 25, '9104');

        $this->artisan('groups:rebuild', ['--size' => 20])->assertSuccessful();

        $codes = Group::query()->pluck('code');

        $this->assertSame(
            $codes->count(),
            $codes->unique()->count(),
            'Kode gugus tidak boleh duplikat: prodi berbeda tidak boleh berbagi gugus.',
        );

        // Dan setiap gugus benar-benar satu prodi.
        $this->assertSame(0, User::query()
            ->join('groups', 'users.group_id', '=', 'groups.id')
            ->whereColumn('users.major', '!=', 'groups.study_program')
            ->count());
    }

    public function test_a_large_major_is_split_into_several_groups(): void
    {
        $this->makeStudents('Psikologi', 45, '9201');

        $this->artisan('groups:rebuild', ['--size' => 20])->assertSuccessful();

        $psikologi = Group::query()->where('study_program', 'Psikologi')->get();

        $this->assertSame(3, $psikologi->count(), '45 mahasiswa dengan batas 20 harus menjadi 3 gugus.');

        foreach ($psikologi as $group) {
            $this->assertLessThanOrEqual(20, $group->members()->count());
        }
    }

    public function test_liaison_officers_are_only_assigned_to_their_own_major(): void
    {
        $this->artisan('groups:rebuild', ['--size' => 30])->assertSuccessful();

        $violations = Group::query()->whereNotNull('mentor_id')->get()
            ->filter(function (Group $group) {
                $officer = User::query()->find($group->mentor_id);

                return $officer && $officer->major !== null && $officer->major !== $group->study_program;
            });

        $this->assertCount(0, $violations, 'LO tidak boleh membimbing gugus dari jurusan lain.');
    }

    public function test_rebuild_is_idempotent(): void
    {
        $this->artisan('groups:rebuild', ['--size' => 40])->assertSuccessful();

        $groupsAfterFirst = Group::query()->count();
        $assignment = User::query()->orderBy('id')->pluck('group_id', 'id')->all();

        $this->artisan('groups:rebuild', ['--size' => 40])->assertSuccessful();

        $this->assertSame($groupsAfterFirst, Group::query()->count(), 'Menjalankan ulang tidak boleh menambah gugus.');
        $this->assertSame($assignment, User::query()->orderBy('id')->pluck('group_id', 'id')->all(), 'Penempatan harus stabil.');
    }

    public function test_dry_run_changes_nothing(): void
    {
        $before = Group::query()->count();

        $this->artisan('groups:rebuild', ['--dry-run' => true, '--size' => 30])->assertSuccessful();

        $this->assertSame($before, Group::query()->count(), '--dry-run tidak boleh mengubah data.');
    }
}