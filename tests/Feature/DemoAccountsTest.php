<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Akun demo untuk juri: satu mahasiswa, satu LO, satu admin.
 *
 * Perintah `demo:accounts` harus bisa dijalankan ulang di server juri tanpa
 * menggandakan XP/koin, dan saldo awalnya HARUS lewat ledger — kalau tidak,
 * rekonsiliasi yang justru dipamerkan ke juri langsung tidak seimbang.
 */
class DemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'OspekJuri2026';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function run3(): void
    {
        $this->artisan('demo:accounts', ['--password' => self::PASSWORD])->assertSuccessful();
    }

    public function test_creates_one_account_per_role(): void
    {
        $this->run3();

        $student = User::query()->where('nim', 'DEMO-MHS')->first();
        $lo = User::query()->where('nim', 'DEMO-LO')->first();
        $admin = User::query()->where('nim', 'DEMO-ADMIN')->first();

        $this->assertNotNull($student);
        $this->assertNotNull($lo);
        $this->assertNotNull($admin);

        $this->assertSame(UserRole::Student, $student->role);
        $this->assertSame(UserRole::LiaisonOfficer, $lo->role);
        $this->assertSame(UserRole::Admin, $admin->role);
    }

    public function test_the_password_actually_works_for_login(): void
    {
        $this->run3();

        foreach (['DEMO-MHS', 'DEMO-LO', 'DEMO-ADMIN'] as $nim) {
            $user = User::query()->where('nim', $nim)->firstOrFail();

            // Hash::check, bukan sekadar "kolom password terisi": sandi yang
            // tersimpan sebagai hash dari string lain tetap lolos pemeriksaan
            // kolom, tetapi tidak bisa dipakai masuk.
            $this->assertTrue(
                Hash::check(self::PASSWORD, $user->password),
                "Sandi akun {$nim} tidak cocok dengan yang dilaporkan perintah.",
            );
        }
    }

    public function test_each_account_can_actually_authenticate_over_http(): void
    {
        $this->run3();

        foreach (['DEMO-MHS', 'DEMO-LO', 'DEMO-ADMIN'] as $nim) {
            $this->post('/login', [
                'identity' => $nim,
                'password' => self::PASSWORD,
            ])->assertRedirect('/dashboard');

            $this->assertAuthenticated();
            $this->post('/logout');
        }
    }

    public function test_student_and_lo_share_a_group_of_the_same_major(): void
    {
        $this->run3();

        $student = User::query()->where('nim', 'DEMO-MHS')->firstOrFail();
        $lo = User::query()->where('nim', 'DEMO-LO')->firstOrFail();

        /*
         * Juri harus bisa mencoba alur anti-curang: mahasiswa mengunggah bukti,
         * LO menilainya. Itu hanya mungkin kalau keduanya berada di gugus yang
         * sama DAN sejurusan — aturan satu gugus satu prodi.
         */
        $this->assertNotNull($student->group_id);
        $this->assertSame($student->group_id, $lo->group_id);
        $this->assertSame($student->major, $lo->major);
    }

    public function test_reseeding_does_not_duplicate_xp_or_coins(): void
    {
        $this->run3();
        $student = User::query()->where('nim', 'DEMO-MHS')->firstOrFail();
        $xpAfterFirst = $student->xp;
        $coinsAfterFirst = $student->coins;

        $this->run3();
        $student->refresh();

        $this->assertSame($xpAfterFirst, $student->xp, 'XP bertambah saat perintah diulang.');
        $this->assertSame($coinsAfterFirst, $student->coins, 'Koin bertambah saat perintah diulang.');

        $this->assertSame(
            1,
            DB::table('xp_transactions')->where('user_id', $student->getKey())->count(),
            'Baris XP ganda terbentuk saat perintah dijalankan dua kali.',
        );
    }

    public function test_balances_match_the_ledger(): void
    {
        $this->run3();

        foreach (['DEMO-MHS', 'DEMO-LO', 'DEMO-ADMIN'] as $nim) {
            $user = User::query()->where('nim', $nim)->firstOrFail();

            $xpLedger = (int) DB::table('xp_transactions')->where('user_id', $user->getKey())->sum('amount');
            $coinLedger = (int) DB::table('coin_transactions')->where('user_id', $user->getKey())->sum('amount');

            $this->assertSame($user->xp, $xpLedger, "XP {$nim} tidak sama dengan jumlah transaksi.");
            $this->assertSame($user->coins, $coinLedger, "Koin {$nim} tidak sama dengan jumlah transaksi.");
        }
    }

    public function test_a_new_user_starts_at_zero_xp(): void
    {
        /*
         * Regresi: skema awal memberi `xp` default 1320 dan `level` default 4,
         * sehingga SETIAP akun baru langsung ber-XP tanpa satu pun baris ledger
         * dan papan peringkat menampilkan mahasiswa baru sebagai level 4.
         */
        $fresh = User::query()->create([
            'nim' => 'BARU-001',
            'name' => 'Mahasiswa Baru',
            'email' => 'baru@students.umk.ac.id',
            'password' => Hash::make('rahasia123'),
            'role' => UserRole::Student,
        ]);

        $fresh->refresh();

        $this->assertSame(0, $fresh->xp, 'Akun baru tidak boleh punya XP dari default kolom.');
        $this->assertSame(1, $fresh->level, 'Akun baru harus mulai dari level 1.');
    }

    public function test_admin_can_reach_the_management_pages(): void
    {
        $this->run3();

        $admin = User::query()->where('nim', 'DEMO-ADMIN')->firstOrFail();

        $this->actingAs($admin)->get('/admin/akun')->assertOk();
        $this->actingAs($admin)->get('/admin/bursa')->assertOk();
        $this->actingAs($admin)->get('/admin/misi')->assertOk();
    }

    public function test_student_cannot_reach_admin_pages(): void
    {
        $this->run3();

        $student = User::query()->where('nim', 'DEMO-MHS')->firstOrFail();

        $this->actingAs($student)->get('/admin/akun')->assertForbidden();
        $this->actingAs($student)->get('/admin/bursa')->assertForbidden();
    }

    public function test_lo_can_open_the_verification_queue_but_not_admin_pages(): void
    {
        $this->run3();

        $lo = User::query()->where('nim', 'DEMO-LO')->firstOrFail();

        $this->actingAs($lo)->get('/lo/verifikasi')->assertOk();
        $this->actingAs($lo)->get('/admin/akun')->assertForbidden();
    }

    public function test_it_refuses_a_password_shorter_than_eight_characters(): void
    {
        $this->artisan('demo:accounts', ['--password' => 'pendek'])
            ->assertFailed();

        $this->assertNull(User::query()->where('nim', 'DEMO-MHS')->first());
    }
}