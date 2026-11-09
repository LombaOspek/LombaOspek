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
 * Dua bug nyata pada station LO, dilaporkan sebagai "masih tidak bisa di-redeem".
 *
 * BUG 1 — "0" diketik sebagai "O"
 * Alfabet kode klaim (RewardService::CLAIM_ALPHABET =
 * 'ABCDEFGHJKMNPQRSTUVWXYZ23456789') sengaja menghilangkan I, O, 0, dan 1
 * supaya tidak ambigu. Tetapi yang terjadi justru sebaliknya: karena O tidak
 * pernah muncul sebagai huruf dalam kode... kecuali sebagai bagian dari prefix
 * "OQ-", orang mengetik "0Q-" dan pencarian gagal.
 *
 * Kasus nyata 10 November 2026: layar menampilkan `OQ-R97N-J4D8`, petugas
 * mengetik `0Q-R97N-J4D8`, hasil "0 klaim · Tidak ada klaim yang cocok",
 * padahal klaim itu ada di database.
 *
 * BUG 2 — klaim ada, tetapi milik gugus lain
 * Kartu ringkasan berkata "MENUNGGU: 1" sementara hasil pencarian kosong. Dua
 * angka itu benar sekaligus: ringkasan menghitung gugus LO, sedangkan klaim
 * yang dicari milik gugus sebelah. Tanpa penjelasan, layar hanya berkata
 * "tidak ada yang cocok" dan petugas menyimpulkan sistemnya rusak.
 */
class LoStationLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function officerOf(Group $group): User
    {
        return User::query()->firstOrCreate(
            ['nim' => 'LO-LOOK-'.$group->getKey()],
            [
                'name' => 'LO Gudang '.$group->getKey(),
                'email' => 'lo.look.'.$group->getKey().'@example.test',
                'password' => bcrypt('rahasia-tes-123'),
                'role' => UserRole::LiaisonOfficer,
                'group_id' => $group->getKey(),
            ],
        );
    }

    private function studentOf(Group $group, string $nim): User
    {
        return User::query()->firstOrCreate(
            ['nim' => $nim],
            [
                'name' => 'Mahasiswa '.$nim,
                'email' => $nim.'@example.test',
                'password' => bcrypt('rahasia-tes-123'),
                'role' => UserRole::Student,
                'group_id' => $group->getKey(),
            ],
        );
    }

    private function claimFor(User $student, string $code): RewardRedemption
    {
        $reward = Reward::query()->firstOrFail();

        return RewardRedemption::query()->create([
            'user_id' => $student->getKey(),
            'reward_id' => $reward->getKey(),
            'claim_code' => $code,
            'coins_spent' => $reward->cost_coins,
            'status' => 'pending',
        ]);
    }

    public function test_a_zero_typed_for_letter_o_still_finds_the_claim(): void
    {
        $group = Group::query()->firstOrFail();
        $officer = $this->officerOf($group);
        $student = $this->studentOf($group, '2099000011');

        $claim = $this->claimFor($student, 'OQ-R97N-J4D8');

        // Persis keluhan: petugas mengetik angka nol, bukan huruf O.
        $html = $this->actingAs($officer)
            ->get('/lo/station?q=0Q-R97N-J4D8')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            $claim->claim_code,
            $html,
            'Kode dengan angka 0 di depan tidak menemukan klaim yang sama '
            .'dengan huruf O. Petugas akan melihat "tidak ada klaim".',
        );
    }

    public function test_lowercase_and_spaces_are_tolerated(): void
    {
        $group = Group::query()->firstOrFail();
        $officer = $this->officerOf($group);
        $student = $this->studentOf($group, '2099000022');

        $claim = $this->claimFor($student, 'OQ-AB2C-D3EF');

        $html = $this->actingAs($officer)
            ->get('/lo/station?q=oq ab2c d3ef')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($claim->claim_code, $html);
    }

    public function test_a_claim_from_another_group_explains_why_it_is_missing(): void
    {
        /*
         * Inilah yang membingungkan: kartu "Menunggu" menunjukkan ada klaim,
         * tetapi pencarian kosong. Penyebabnya klaim itu milik gugus lain.
         */
        $groups = Group::query()->limit(2)->get();
        $this->assertCount(2, $groups, 'Butuh dua gugus untuk uji ini.');

        $mine = $this->officerOf($groups[0]);
        $studentOther = $this->studentOf($groups[1], '2099000033');

        $claim = $this->claimFor($studentOther, 'OQ-OTHR-GRPX');

        $html = $this->actingAs($mine)
            ->get('/lo/station?q='.$claim->claim_code)
            ->assertOk()
            ->getContent();

        // Klaimnya TIDAK boleh muncul sebagai baris yang bisa diserahkan.
        $this->assertStringNotContainsString('action="'.route('lo.station.fulfill', $claim).'"', $html);

        // Tetapi harus ada penjelasan, bukan sekadar "tidak ada yang cocok".
        $this->assertStringContainsString('bukan gugus Anda', $html);
        $this->assertStringContainsString($claim->claim_code, $html);
        $this->assertStringContainsString('Sistem Informasi', $html);
    }

    public function test_an_unknown_code_suggests_checking_the_letter_o(): void
    {
        $group = Group::query()->firstOrFail();
        $officer = $this->officerOf($group);

        $html = $this->actingAs($officer)
            ->get('/lo/station?q=OQ-ZZZZ-9999')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tidak ada klaim yang cocok', $html);
        $this->assertStringContainsString('bukan angka nol', $html);
    }

    public function test_an_officer_can_fulfil_after_looking_up_with_a_zero(): void
    {
        /*
         * Ujung ke ujung: cari dengan angka nol, lalu serahkan. Tanpa
         * normalisasi, langkah pertama sudah gagal dan tombol Serahkan tidak
         * pernah muncul - persis keluhan "tidak bisa di-redeem".
         */
        $group = Group::query()->firstOrFail();
        $officer = $this->officerOf($group);
        $student = $this->studentOf($group, '2099000044');

        $claim = $this->claimFor($student, 'OQ-FIND-MEZZ');

        $html = $this->actingAs($officer)
            ->get('/lo/station?q=0Q-FIND-MEZZ')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'action="'.route('lo.station.fulfill', $claim).'"',
            $html,
            'Tombol Serahkan tidak muncul setelah pencarian dengan angka nol.',
        );

        $this->actingAs($officer)
            ->post("/lo/station/klaim/{$claim->getKey()}/serahkan")
            ->assertRedirect();

        $this->assertSame('fulfilled', $claim->fresh()->status);
    }
}