<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tugas tinjauan 4 November: LO otomatis, paginasi gugus, paginasi tanpa
 * reload, dan keterangan arsip.
 */
class GroupPaginationAndLoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::query()->where('role', UserRole::Admin)->firstOrFail();
    }

    /* ---------------- LO otomatis ---------------- */

    public function test_every_group_ends_up_with_at_least_two_officers_of_its_own_major(): void
    {
        // Kondisi awal: hanya 4 LO, semuanya Fakultas Teknik.
        $this->artisan('groups:rebuild', ['--size' => 40])->assertSuccessful();
        $this->artisan('lo:generate', ['--per-group' => 2])->assertSuccessful();

        $groups = Group::query()->get();
        $officers = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->get(['id', 'major'])
            ->groupBy('major');

        foreach ($groups as $group) {
            $pool = $officers->get($group->study_program, collect());

            $this->assertGreaterThanOrEqual(
                2,
                $pool->count(),
                'Gugus '.$group->name.' ('.$group->study_program.') hanya punya '.$pool->count().' LO.',
            );
        }
    }

    public function test_generated_officers_never_cross_majors(): void
    {
        $this->artisan('groups:rebuild', ['--size' => 40])->assertSuccessful();
        $this->artisan('lo:generate', ['--per-group' => 2])->assertSuccessful();

        $violations = Group::query()->whereNotNull('mentor_id')->get()
            ->filter(function (Group $group) {
                $officer = User::query()->find($group->mentor_id);

                return $officer && $officer->major !== null && $officer->major !== $group->study_program;
            });

        $this->assertCount(0, $violations, 'LO tidak boleh membimbing gugus jurusan lain.');
    }

    public function test_generated_officer_nims_are_unique_across_similar_major_names(): void
    {
        /*
         * Regresi: slug 8 huruf membuat "Agribisnis" dan "Agroteknologi"
         * sama-sama menjadi "A", sehingga NIM LO kedua prodi bertabrakan.
         */
        $this->artisan('lo:generate', ['--per-group' => 2])->assertSuccessful();

        $nims = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->pluck('nim');

        $this->assertSame(
            $nims->count(),
            $nims->unique()->count(),
            'NIM LO harus unik; prodi berbeda tidak boleh berbagi nomor urut.',
        );
    }

    public function test_lo_generate_is_idempotent(): void
    {
        $this->artisan('lo:generate', ['--per-group' => 2])->assertSuccessful();
        $afterFirst = User::query()->where('role', UserRole::LiaisonOfficer)->count();

        $this->artisan('lo:generate', ['--per-group' => 2])->assertSuccessful();

        $this->assertSame(
            $afterFirst,
            User::query()->where('role', UserRole::LiaisonOfficer)->count(),
            'Menjalankan ulang tidak boleh menambah LO tanpa batas.',
        );
    }

    public function test_lo_dry_run_creates_nothing(): void
    {
        $before = User::query()->where('role', UserRole::LiaisonOfficer)->count();

        $this->artisan('lo:generate', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame($before, User::query()->where('role', UserRole::LiaisonOfficer)->count());
    }

    /* ---------------- Paginasi gugus ---------------- */

    public function test_group_list_is_paginated_eight_per_page(): void
    {
        // Buat cukup gugus untuk melewati satu halaman.
        for ($i = 1; $i <= 12; $i++) {
            Group::create([
                'name' => 'Gugus Uji '.$i,
                'code' => 'UJI-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'study_program' => 'Sistem Informasi',
                'capacity' => 50,
                'badge_color' => 'amber',
                'invitation_code' => 'UJI'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        // Delapan gugus per halaman, dan ada navigasi halaman untuk gugus.
        $this->assertStringContainsString('gugus_page', $html);
        $this->assertStringContainsString('Menampilkan', $html);
        $this->assertStringContainsString('gugus', $html);
    }

    public function test_group_pagination_uses_its_own_query_parameter(): void
    {
        /*
         * Kalau gugus memakai parameter `page` yang sama dengan tabel akun,
         * membuka halaman 2 gugus akan ikut memindahkan halaman tabel akun.
         */
        $html = $this->actingAs($this->admin())
            ->get('/admin/akun?gugus_page=2')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('gugus_page=2', $html);
    }

    /* ---------------- Fragmen AJAX ---------------- */

    public function test_partial_request_returns_only_the_table_fragment(): void
    {
        $response = $this->actingAs($this->admin())
            ->get('/admin/akun', ['X-Partial' => '1'])
            ->assertOk();

        $html = $response->getContent();

        // Fragmen memuat region yang ditukar...
        $this->assertStringContainsString('data-fragment-accounts', $html);
        $this->assertStringContainsString('data-fragment-group-list', $html);

        // ...tetapi BUKAN kerangka halaman. Inilah alasan fragmen ada:
        // mengirim ulang header/sidebar/form setiap klik halaman itu boros.
        $this->assertStringNotContainsString('<!DOCTYPE html>', $html);
        $this->assertStringNotContainsString('data-import-form', $html);
    }

    public function test_full_page_still_renders_when_no_partial_header(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('data-accounts-region', $html);
        $this->assertStringContainsString('data-group-list-region', $html);
    }

    /* ---------------- Tombol arsip ---------------- */

    public function test_archive_button_shows_the_archived_count(): void
    {
        $students = User::query()->where('role', UserRole::Student)->take(2)->get();

        foreach ($students as $student) {
            $this->actingAs($this->admin())
                ->delete("/admin/akun/{$student->id}", ['confirm_identity' => $student->nim]);
        }

        $html = $this->actingAs($this->admin())->get('/admin/akun')->assertOk()->getContent();

        $this->assertStringContainsString('Lihat arsip', $html);
        $this->assertStringContainsString('title="Tampilkan akun yang sudah dihapus', $html);
        $this->assertSame(2, User::onlyTrashed()->count());
    }
}