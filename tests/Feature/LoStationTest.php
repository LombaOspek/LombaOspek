<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Station penyerahan hadiah untuk Liaison Officer.
 *
 * Bug yang diperbaiki: menu sidebar LO menautkan ke `admin.rewards`, yang
 * berada di grup middleware `role:admin`. Setiap LO yang mengklik menu itu
 * mendapat 403 "Anda tidak memiliki akses ke halaman ini" - padahal
 * `AdminRewardController::fulfill()` sudah mengizinkan peran LO. Izin di
 * controller tidak pernah tercapai karena middleware menolak lebih dulu.
 *
 * Kebutuhan nyatanya: panitia membuka beberapa station di hari penyerahan,
 * dan setiap station dijaga satu LO yang menyerahkan hadiah mahasiswa
 * gugusnya sendiri.
 */
class LoStationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function officer(): User
    {
        return User::query()
            ->where('role', UserRole::LiaisonOfficer)
            ->whereNotNull('group_id')
            ->firstOrFail();
    }

    private function studentInGroupOf(User $officer): User
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->where('group_id', $officer->group_id)
            ->firstOrFail();
    }

    private function makeClaim(User $student, string $status = 'pending'): RewardRedemption
    {
        $reward = Reward::query()->firstOrFail();

        return RewardRedemption::query()->create([
            'user_id' => $student->getKey(),
            'reward_id' => $reward->getKey(),
            /*
             * Kode uji WAJIB memakai alfabet yang sama dengan produksi
             * (RewardService::CLAIM_ALPHABET = ABCDEFGHJKMNPQRSTUVWXYZ23456789),
             * yang tidak memuat I, O, 0, maupun 1. Memakai md5 mentah
             * menghasilkan kode seperti `OQ-TEST-0A3F`, dan normalisasi
             * pencarian (yang menukar 0->O) tidak bisa mengembalikannya -
             * membuat tes gagal pada kode yang di produksi tidak mungkin ada.
             */
            'claim_code' => (function () use ($student): string {
                $h = strtoupper(strtr(md5((string) $student->getKey()), '01IO', 'WXYZ'));

                // Format produksi: OQ-XXXX-XXXX (dua kelompok empat karakter).
                return 'OQ-'.substr($h, 0, 4).'-'.substr($h, 4, 4);
            })(),

            'coins_spent' => $reward->cost_coins,
            'status' => $status,
        ]);
    }

    public function test_an_officer_can_open_the_station_page(): void
    {
        /*
         * Inilah keluhan aslinya: 403. Rute harus berada di grup `role:lo,admin`,
         * bukan `role:admin`.
         */
        $this->actingAs($this->officer())
            ->get('/lo/station')
            ->assertOk()
            ->assertSee('Station Penyerahan Hadiah');
    }

    public function test_the_station_lists_only_claims_from_the_officers_group(): void
    {
        $officer = $this->officer();
        $mine = $this->studentInGroupOf($officer);

        // Mahasiswa dari gugus lain.
        $other = User::query()
            ->where('role', UserRole::Student)
            ->where('group_id', '!=', $officer->group_id)
            ->firstOrFail();

        $claimMine = $this->makeClaim($mine);
        $claimOther = $this->makeClaim($other);

        $html = $this->actingAs($officer)->get('/lo/station')->assertOk()->getContent();

        $this->assertStringContainsString($claimMine->claim_code, $html);
        $this->assertStringNotContainsString(
            $claimOther->claim_code,
            $html,
            'Klaim mahasiswa gugus lain tidak boleh muncul di station LO.',
        );
    }

    public function test_an_officer_can_fulfil_a_claim_from_their_group(): void
    {
        $officer = $this->officer();
        $claim = $this->makeClaim($this->studentInGroupOf($officer));

        $this->actingAs($officer)
            ->post("/lo/station/klaim/{$claim->getKey()}/serahkan")
            ->assertRedirect();

        $this->assertSame('fulfilled', $claim->fresh()->status);
        $this->assertNotNull($claim->fresh()->fulfilled_at);
    }

    public function test_an_officer_cannot_fulfil_a_claim_from_another_group(): void
    {
        $officer = $this->officer();

        $other = User::query()
            ->where('role', UserRole::Student)
            ->where('group_id', '!=', $officer->group_id)
            ->firstOrFail();

        $claim = $this->makeClaim($other);

        $this->actingAs($officer)
            ->post("/lo/station/klaim/{$claim->getKey()}/serahkan")
            ->assertForbidden();

        $this->assertSame('pending', $claim->fresh()->status, 'Klaim gugus lain tidak boleh berubah.');
    }

    public function test_a_claim_cannot_be_fulfilled_twice(): void
    {
        /*
         * Menyerahkan dua kali berarti memberikan dua barang untuk satu klaim.
         * Ini kesalahan paling mahal di meja station.
         */
        $officer = $this->officer();
        $claim = $this->makeClaim($this->studentInGroupOf($officer), 'fulfilled');

        $this->actingAs($officer)
            ->post("/lo/station/klaim/{$claim->getKey()}/serahkan")
            ->assertSessionHasErrors('redemption');
    }

    public function test_a_cancelled_claim_cannot_be_fulfilled(): void
    {
        $officer = $this->officer();
        $claim = $this->makeClaim($this->studentInGroupOf($officer), 'cancelled');

        $this->actingAs($officer)
            ->post("/lo/station/klaim/{$claim->getKey()}/serahkan")
            ->assertSessionHasErrors('redemption');
    }

    public function test_a_student_cannot_reach_the_station(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $this->actingAs($student)->get('/lo/station')->assertForbidden();
    }

    public function test_an_officer_still_cannot_manage_the_reward_catalog(): void
    {
        /*
         * Harga dan stok tetap wewenang admin: satu harga untuk seluruh kampus.
         * Station hanya menyerahkan, tidak mengubah katalog.
         */
        $this->actingAs($this->officer())->get('/admin/bursa')->assertForbidden();
    }

    public function test_the_sidebar_links_officers_to_the_station_not_admin(): void
    {
        /*
         * Tautan lama menunjuk `admin.rewards` dan pasti 403. Dikunci di sini
         * supaya tidak kembali tanpa ketahuan.
         */
        $html = $this->actingAs($this->officer())->get('/lo/station')->assertOk()->getContent();

        $this->assertStringContainsString('/lo/station', $html);
        $this->assertStringNotContainsString(
            'href="'.route('admin.rewards').'"',
            $html,
            'Sidebar LO masih menaut ke /admin/bursa yang akan menolak dengan 403.',
        );
    }

    public function test_the_station_can_search_by_claim_code(): void
    {
        $officer = $this->officer();
        $claim = $this->makeClaim($this->studentInGroupOf($officer));

        $html = $this->actingAs($officer)
            ->get('/lo/station?q='.$claim->claim_code)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($claim->claim_code, $html);
    }

    public function test_the_station_filters_by_status(): void
    {
        $officer = $this->officer();
        $student = $this->studentInGroupOf($officer);

        $pending = $this->makeClaim($student, 'pending');

        $html = $this->actingAs($officer)
            ->get('/lo/station?status=fulfilled')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            $pending->claim_code,
            $html,
            'Filter status=fulfilled tidak boleh menampilkan klaim pending.',
        );
    }
}