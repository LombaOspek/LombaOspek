<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Setel sandi yang seragam untuk akun demo.
 *
 * Kenapa perlu: `lo:generate` membuat 102 LO dengan sandi ACAK yang hanya
 * tercatat di CSV. Itu benar untuk akun produksi, tetapi membuat demo mustahil
 * - panitia harus membuka CSV dan mencari satu baris untuk bisa masuk sebagai
 * LO di depan juri.
 *
 * Perintah ini hanya menyentuh peran yang diminta, dan menolak berjalan tanpa
 * --force di lingkungan produksi: menyeragamkan sandi di server nyata sama
 * dengan membuka semua akun.
 */
class DemoPasswordsCommand extends Command
{
    protected $signature = 'demo:passwords
                            {--password=password : Sandi yang dipakai}
                            {--role=lo : Peran sasaran (lo, student, admin, all)}
                            {--force : Izinkan di lingkungan produksi}';

    protected $description = 'Setel sandi seragam untuk akun demo (LO/mahasiswa)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Menolak berjalan di produksi tanpa --force.');
            $this->line('Menyeragamkan sandi di server nyata membuka semua akun.');

            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        $role = (string) $this->option('role');

        if (strlen($password) < 6) {
            $this->error('Sandi minimal 6 karakter.');

            return self::FAILURE;
        }

        $roles = match ($role) {
            'lo' => [UserRole::LiaisonOfficer],
            'student' => [UserRole::Student],
            'admin' => [UserRole::Admin],
            'all' => [UserRole::LiaisonOfficer, UserRole::Student, UserRole::Admin],
            default => null,
        };

        if ($roles === null) {
            $this->error('--role harus salah satu: lo, student, admin, all.');

            return self::FAILURE;
        }

        $affected = 0;

        User::query()
            ->whereIn('role', $roles)
            ->orderBy('id')
            ->chunk(200, function ($users) use ($password, &$affected): void {
                foreach ($users as $user) {
                    // forceFill + save: melewati cast 'hashed' tidak diperlukan,
                    // cast itu yang melakukan hashing.
                    $user->forceFill(['password' => $password])->save();
                    $affected++;
                }
            });

        $this->info(sprintf(
            'Sandi %d akun (%s) disetel ke "%s".',
            $affected,
            implode(', ', array_map(fn (UserRole $r) => $r->value, $roles)),
            $password,
        ));

        if (in_array(UserRole::LiaisonOfficer, $roles, true)) {
            $sample = User::query()
                ->where('role', UserRole::LiaisonOfficer)
                ->orderBy('nim')
                ->take(3)
                ->get(['nim', 'name', 'major']);

            $this->newLine();
            $this->line('Contoh akun LO untuk demo:');
            $this->table(
                ['NIM', 'Nama', 'Jurusan'],
                $sample->map(fn (User $u) => [$u->nim, $u->name, $u->major])->all(),
            );
        }

        return self::SUCCESS;
    }
}