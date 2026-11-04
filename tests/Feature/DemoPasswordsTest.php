<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Command demo:passwords — menyeragamkan sandi akun demo.
 *
 * Kenapa perlu diuji: `lo:generate` membuat sandi acak yang hanya ada di CSV.
 * Tanpa command ini, demo di depan juri mustahil dilakukan sebagai LO. Tetapi
 * command yang sama juga berbahaya di server nyata, jadi penolakan produksi
 * ikut dikunci.
 */
class DemoPasswordsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_it_sets_a_working_password_for_liaison_officers(): void
    {
        $lo = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();

        // Pastikan sandi awal bukan yang akan dipakai.
        $lo->forceFill(['password' => 'sandi-lama-acak'])->save();

        $this->artisan('demo:passwords', ['--role' => 'lo', '--password' => 'password'])
            ->assertSuccessful();

        $this->assertTrue(
            Hash::check('password', $lo->fresh()->password),
            'Sandi LO harus benar-benar berlaku setelah command dijalankan.',
        );
    }

    public function test_it_only_touches_the_requested_role(): void
    {
        $admin = User::query()->where('role', UserRole::Admin)->firstOrFail();
        $admin->forceFill(['password' => 'sandi-admin-asli'])->save();

        $this->artisan('demo:passwords', ['--role' => 'lo', '--password' => 'password'])
            ->assertSuccessful();

        $this->assertTrue(
            Hash::check('sandi-admin-asli', $admin->fresh()->password),
            'Sandi admin tidak boleh berubah saat hanya --role=lo dijalankan.',
        );
    }

    public function test_it_covers_every_officer(): void
    {
        $total = User::query()->where('role', UserRole::LiaisonOfficer)->count();

        $this->artisan('demo:passwords', ['--role' => 'lo', '--password' => 'password'])
            ->assertSuccessful();

        $matching = User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->get()
            ->filter(fn (User $u) => Hash::check('password', $u->password))
            ->count();

        $this->assertSame($total, $matching, 'Semua LO harus memakai sandi baru.');
    }

    public function test_it_rejects_an_unknown_role(): void
    {
        $this->artisan('demo:passwords', ['--role' => 'kepala-suku'])
            ->assertFailed();
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('demo:passwords', ['--role' => 'lo', '--password' => 'abc'])
            ->assertFailed();
    }

    public function test_it_refuses_to_run_in_production_without_force(): void
    {
        // Menyeragamkan sandi di server nyata membuka semua akun.
        $this->app['env'] = 'production';

        $lo = User::query()->where('role', UserRole::LiaisonOfficer)->firstOrFail();
        $lo->forceFill(['password' => 'sandi-asli'])->save();

        $this->artisan('demo:passwords', ['--role' => 'lo', '--password' => 'password'])
            ->assertFailed();

        $this->assertTrue(
            Hash::check('sandi-asli', $lo->fresh()->password),
            'Sandi tidak boleh berubah saat command menolak berjalan.',
        );
    }

    public function test_force_overrides_the_production_guard(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('demo:passwords', ['--role' => 'lo', '--password' => 'password', '--force' => true])
            ->assertSuccessful();
    }
}