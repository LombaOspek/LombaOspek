<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Reward;
use App\Models\User;
use App\Services\CoinService;
use App\Services\XpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Menyiapkan tiga akun demo untuk juri: mahasiswa, LO, admin.
 *
 * Kenapa perintah, bukan data yang ditempel di seeder:
 *  - Akun demo harus bisa dibuat ULANG di server juri tanpa mengubah kode.
 *  - Sandi tidak boleh ada di seeder; kalau ada, ia ikut ter-commit dan
 *    tercetak di setiap `db:seed`.
 *  - Harus idempoten: menjalankan dua kali tidak boleh menggandakan XP/koin
 *    atau menimpa progres yang sudah dimainkan juri.
 *
 * Akun dibuat dengan `updateOrCreate` supaya aman diulang, dan saldo awal
 * diberikan LEWAT LEDGER (XpService/CoinService), bukan with `UPDATE users SET
 * xp = ...`. Tanpa itu, rekonsiliasi `users.coins == SUM(coin_transactions)`
 * tidak akan pernah seimbang.
 */
class DemoAccountsCommand extends Command
{
    protected $signature = 'demo:accounts
                            {--password= : Sandi untuk ketiga akun (default: dibuat acak)}
                            {--fresh : Setel ulang XP/koin ke nilai awal (hapus ledger demo lama)}
                            {--show : Hanya tampilkan akun demo yang sudah ada}';

    protected $description = 'Siapkan tiga akun demo (mahasiswa, LO, admin) untuk pengujian juri.';

    /**
     * NIM sengaja tidak berbentuk NIM mahasiswa asli supaya tidak bertabrakan
     * dengan data hasil import, dan jelas terlihat sebagai akun demo.
     */
    private const STUDENT_NIM = 'DEMO-MHS';

    private const LO_NIM = 'DEMO-LO';

    private const ADMIN_NIM = 'DEMO-ADMIN';

    /** Saldo awal: cukup untuk mencoba Bursa, cukup untuk terlihat "hidup". */
    private const STUDENT_XP = 900;

    private const STUDENT_COINS = 700;

    public function handle(XpService $xp, CoinService $coins): int
    {
        if ($this->option('show')) {
            return $this->showAccounts();
        }

        $password = (string) ($this->option('password') ?: $this->randomPassword());

        $validator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        $group = $this->pickGroup();

        if ($group === null) {
            $this->error('Belum ada gugus. Jalankan `php artisan groups:rebuild` lebih dulu.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->wipeDemoState();
        }

        DB::transaction(function () use ($password, $group, $xp, $coins): void {
            $admin = $this->upsert(
                self::ADMIN_NIM,
                'Admin Demo',
                'admin-demo@umk.ac.id',
                UserRole::Admin,
                $password,
                major: $group->study_program,
            );

            $lo = $this->upsert(
                self::LO_NIM,
                'LO Demo',
                'lo-demo@lo.umk.ac.id',
                UserRole::LiaisonOfficer,
                $password,
                major: $group->study_program,
                groupId: $group->getKey(),
            );

            $student = $this->upsert(
                self::STUDENT_NIM,
                'Mahasiswa Demo',
                'mahasiswa-demo@students.umk.ac.id',
                UserRole::Student,
                $password,
                major: $group->study_program,
                groupId: $group->getKey(),
            );

            $this->seedStudentProgress($student, $xp, $coins);
        });

        $this->newLine();
        $this->info('Akun demo siap dipakai.');
        $this->table(
            ['Peran', 'NIM', 'Sandi', 'Gugus'],
            [
                ['Mahasiswa', self::STUDENT_NIM, $password, $group->code],
                ['LO', self::LO_NIM, $password, $group->code],
                ['Admin', self::ADMIN_NIM, $password, '-'],
            ],
        );

        $this->line('  Halaman masuk: '.url('/login'));
        $this->line('  Isi kolom "NIM / Email" dengan NIM di atas (bukan email).');
        $this->newLine();

        if (! $this->option('password')) {
            $this->warn('Sandi ini dibuat acak. Catat sekarang - tidak ditampilkan lagi.');
        }

        return self::SUCCESS;
    }

    /**
     * Pilih gugus yang punya LO sejurusan, supaya alur verifikasi bukti bisa
     * dicoba juri: mahasiswa demo mengunggah bukti, LO demo menilainya.
     */
    private function pickGroup(): ?Group
    {
        return Group::query()
            ->whereNotNull('mentor_id')
            ->orderBy('id')
            ->first() ?? Group::query()->orderBy('id')->first();
    }

    private function upsert(
        string $nim,
        string $name,
        string $email,
        UserRole $role,
        string $password,
        ?string $major = null,
        ?int $groupId = null,
    ): User {
        /** @var User $user */
        $user = User::withTrashed()->firstOrNew(['nim' => $nim]);

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'major' => $major,
            'group_id' => $groupId,
            'password' => Hash::make($password),
            // Supaya riwayat XP/koin yang sudah dimainkan juri tidak hilang
            // saat perintah dijalankan ulang.
            'deleted_at' => null,
            // Menandai akun sudah aktif hari ini supaya gerbang hari pertama
            // tidak mengunci seluruh peta saat juri mencoba.
            'last_active_on' => $user->last_active_on ?? now()->toDateString(),
        ])->save();

        return $user;
    }

    /**
     * Beri saldo awal lewat ledger, bukan dengan menulis kolom langsung.
     *
     * `--fresh` menghapus baris ledger demo lama lebih dulu supaya
     * idempotency key tidak menolak award berikutnya dan saldo tetap
     * konsisten dengan jumlah transaksi.
     */
    private function seedStudentProgress(User $student, XpService $xp, CoinService $coins): void
    {
        $key = 'demo-seed-'.$student->getKey();

        $alreadySeeded = DB::table('xp_transactions')
            ->where('idempotency_key', $key)
            ->exists();

        if ($alreadySeeded) {
            return;
        }

        $xp->award($student, self::STUDENT_XP, 'admin_grant', null, 'Saldo awal akun demo', $key);
        $coins->award($student, self::STUDENT_COINS, 'admin_grant', null, 'Saldo awal akun demo', $key);
    }

    private function wipeDemoState(): void
    {
        foreach ([self::STUDENT_NIM, self::LO_NIM, self::ADMIN_NIM] as $nim) {
            $user = User::withTrashed()->where('nim', $nim)->first();

            if ($user === null) {
                continue;
            }

            DB::table('xp_transactions')->where('user_id', $user->getKey())->delete();
            DB::table('coin_transactions')->where('user_id', $user->getKey())->delete();

            $user->forceFill(['xp' => 0, 'coins' => 0, 'level' => 1])->save();
        }

        $this->line('  Saldo akun demo direset ke nol.');
    }

    private function showAccounts(): int
    {
        $rows = [];

        foreach ([self::STUDENT_NIM, self::LO_NIM, self::ADMIN_NIM] as $nim) {
            $user = User::withTrashed()->where('nim', $nim)->first();

            $rows[] = $user
                ? [$user->role->label(), $user->nim, $user->name, $user->xp, $user->coins]
                : ['-', $nim, '(belum dibuat)', '-', '-'];
        }

        $this->table(['Peran', 'NIM', 'Nama', 'XP', 'Koin'], $rows);

        return self::SUCCESS;
    }

    /**
     * Sandi acak yang mudah dibaca ulang saat presentasi: hindari karakter
     * yang sering tertukar (0/O, 1/l/I).
     */
    private function randomPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $out = 'Ospek-';

        for ($i = 0; $i < 6; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }
}