<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\QuestSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Manajemen akun mahasiswa & gugus (superadmin).
 */
class AccountController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $role = $request->query('role');
        $major = $request->query('major');

        $users = User::query()
            ->with('group:id,name,badge_color')
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.trim((string) $request->query('q')).'%';

                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('nim', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when(filled($role), fn ($q) => $q->where('role', $role))
            ->when(filled($major), fn ($q) => $q->where('major', $major))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $groups = Group::query()->withCount('members')->orderBy('name')->get();

        $stats = [
            'students' => User::query()->where('role', UserRole::Student)->count(),
            'groups' => $groups->count(),
            'officers' => User::query()->where('role', UserRole::LiaisonOfficer)->count(),
            'verified_today' => QuestSubmission::query()
                ->whereDate('reviewed_at', now()->toDateString())
                ->where('status', 'approved')
                ->count(),
        ];

        return view('pages.admin.accounts', [
            'users' => $users,
            'groups' => $groups,
            'stats' => $stats,
            'roles' => UserRole::cases(),
            'majors' => User::query()->whereNotNull('major')->distinct()->orderBy('major')->pluck('major'),
            'filters' => ['q' => $request->query('q'), 'role' => $role, 'major' => $major],
            'availableOfficers' => User::query()
                ->where('role', UserRole::LiaisonOfficer)
                ->orderBy('name')
                ->get(['id', 'name', 'nim']),
        ]);
    }

    /**
     * Buat gugus baru + kode undangan resmi.
     */
    public function storeGroup(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:groups,name'],
            'study_program' => ['required', 'string', 'max:120'],
            'mentor_id' => ['required', 'exists:users,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'badge_color' => ['required', Rule::in(['amber', 'teal', 'sky', 'vermilion'])],
        ], [
            'name.unique' => 'Nama gugus sudah dipakai.',
        ]);

        $mentor = User::query()->findOrFail($validated['mentor_id']);

        if ($mentor->role !== UserRole::LiaisonOfficer) {
            return back()->withErrors(['mentor_id' => 'User yang dipilih bukan Liaison Officer.'])->withInput();
        }

        /*
         * Satu LO hanya boleh membimbing gugus SEJURUSAN.
         *
         * Tanpa aturan ini, LO Akuntansi bisa membimbing gugus Teknik Mesin:
         * ia tidak memahami jadwal dan dosen prodi itu, dan Gugus Wars jadi
         * tidak adil karena gugus dengan prodi campur akan menang mudah.
         */
        if ($mentor->major !== null && $mentor->major !== $validated['study_program']) {
            return back()->withErrors([
                'mentor_id' => sprintf(
                    'LO %s berasal dari %s, tidak bisa membimbing gugus %s.',
                    $mentor->name,
                    $mentor->major,
                    $validated['study_program'],
                ),
            ])->withInput();
        }

        // Satu LO = satu gugus. Kalau jurusan punya banyak gugus, pakai LO
        // berbeda per gugus supaya beban peninjauan bukti tetap terbagi.
        if (Group::query()->where('mentor_id', $mentor->getKey())->exists()) {
            return back()->withErrors([
                'mentor_id' => $mentor->name.' sudah membimbing gugus lain. Pilih LO lain untuk jurusan ini.',
            ])->withInput();
        }

        $group = Group::create([
            'name' => $validated['name'],
            'code' => 'G-'.str_pad((string) (Group::query()->count() + 1), 2, '0', STR_PAD_LEFT),
            'study_program' => $validated['study_program'],
            'capacity' => $validated['capacity'],
            'badge_color' => $validated['badge_color'],
            'invitation_code' => Group::generateInvitationCode($validated['name']),
            'mentor_name' => $mentor->name,
            'mentor_id' => $mentor->id,
        ]);

        // LO otomatis masuk ke gugus yang dibimbingnya agar bisa meninjau bukti.
        $mentor->forceFill(['group_id' => $group->id, 'group_name' => $group->name])->save();

        return back()->with('toast', [
            'title' => 'Gugus dibuat',
            'message' => $group->name.' dengan kode undangan '.$group->invitation_code,
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Import massal dari CSV. Baris yang gagal dilaporkan, bukan dibatalkan
     * seluruhnya — supaya satu NIM duplikat tidak menggagalkan 3.000 baris.
     */
    public function bulkImport(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:25600'],
            'auto_generate_password' => ['nullable', 'boolean'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'rb');

        if ($handle === false) {
            return back()->withErrors(['file' => 'File tidak dapat dibaca.']);
        }

        $header = fgetcsv($handle);
        $created = 0;
        $skipped = [];
        $row = 1;

        // Kredensial yang dibuat pada import ini. WAJIB dikembalikan ke admin:
        // sebelumnya sandi acak di-hash lalu dibuang, sehingga akun hasil import
        // tidak bisa login sama sekali dan hanya bisa dipulihkan lewat database.
        $credentials = [];
        $autoPassword = $request->boolean('auto_generate_password');

        DB::beginTransaction();

        try {
            while (($line = fgetcsv($handle)) !== false) {
                $row++;

                if ($line === [null] || count(array_filter($line, fn ($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                // Urutan kolom sesuai template: NIM, Nama, Jurusan, Email, Kode Gugus
                [$nim, $name, $major, $email, $groupCode] = array_pad($line, 5, null);

                $nim = trim((string) $nim);
                $name = trim((string) $name);

                if ($nim === '' || $name === '') {
                    $skipped[] = "Baris {$row}: NIM atau nama kosong";

                    continue;
                }

                if (User::query()->where('nim', $nim)->exists()) {
                    $skipped[] = "Baris {$row}: NIM {$nim} sudah terdaftar";

                    continue;
                }

                $group = filled($groupCode)
                    ? Group::query()
                        ->where(fn ($g) => $g->where('invitation_code', trim((string) $groupCode))
                            ->orWhere('code', trim((string) $groupCode))
                            ->orWhere('name', trim((string) $groupCode)))
                        ->first()
                    : null;

                // Kode gugus yang tidak dikenal tidak boleh membuat mahasiswa
                // masuk tanpa gugus secara diam-diam — laporkan agar panitia
                // bisa memperbaiki CSV-nya.
                if (filled($groupCode) && $group === null) {
                    $skipped[] = "Baris {$row}: gugus \"".trim((string) $groupCode).'" tidak ditemukan';
                }

                $plainPassword = $autoPassword ? Str::password(10) : 'password';

                User::create([
                    'nim' => $nim,
                    'name' => $name,
                    'email' => filled($email) ? trim((string) $email) : $nim.'@students.umk.ac.id',
                    'password' => Hash::make($plainPassword),
                    'role' => UserRole::Student,
                    // Fakultas diturunkan dari jurusan lewat config, bukan
                    // di-hardcode: OspekQuest dipakai satu universitas dengan
                    // 6 fakultas, bukan hanya Fakultas Teknik.
                    'faculty' => $this->facultyFor($major),
                    'major' => $this->normalizeMajor($major),
                    'group_id' => $group?->id,
                    'group_name' => $group?->name,
                    'mentor_name' => $group?->mentor_name,
                    'xp' => 0,
                    'level' => 1,
                ]);

                $credentials[] = [
                    'nim' => $nim,
                    'name' => $name,
                    'password' => $plainPassword,
                ];

                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->withErrors(['file' => 'Import gagal: '.$e->getMessage()]);
        }

        fclose($handle);

        return back()->with('toast', [
            'title' => 'Import selesai',
            'message' => $created.' akun dibuat'.($skipped === [] ? '.' : ', '.count($skipped).' baris dilewati.'),
            'icon' => 'check',
            'tone' => $skipped === [] ? 'success' : 'warning',
        ])->with('import_skipped', array_slice($skipped, 0, 15))
            // Ditampilkan SEKALI di halaman admin dalam bentuk CSV agar panitia
            // bisa mencetak/membagikan. Tidak pernah disimpan plaintext di DB.
            ->with('import_credentials', array_slice($credentials, 0, 1200));
    }

    /**
     * Nama jurusan dari CSV dinormalkan ke ejaan resmi. Kalau tidak dikenali,
     * dipakai apa adanya (di-trim) supaya data panitia tidak hilang diam-diam.
     */
    private function normalizeMajor(?string $major): string
    {
        $input = trim((string) $major);

        if ($input === '') {
            return 'Sistem Informasi';
        }

        foreach (config('ospek.faculties', []) as $programs) {
            foreach ($programs as $program) {
                if (strcasecmp($program, $input) === 0) {
                    return $program;
                }
            }
        }

        return $input;
    }

    /**
     * Fakultas pemilik sebuah jurusan. Jurusan yang tidak terdaftar jatuh ke
     * "Fakultas Teknik" hanya bila memang prodi teknik; kalau tidak, dibiarkan
     * null agar tidak salah klaim.
     */
    private function facultyFor(?string $major): ?string
    {
        $normalized = $this->normalizeMajor($major);

        foreach (config('ospek.faculties', []) as $faculty => $programs) {
            if (in_array($normalized, $programs, true)) {
                return $faculty;
            }
        }

        return null;
    }

    /**
     * Tambah satu akun (LO atau mahasiswa) tanpa lewat CSV.
     *
     * Untuk kasus susulan: mahasiswa telat daftar, mahasiswa pindahan, atau LO
     * baru yang ditunjuk setelah import massal selesai.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nim' => ['required', 'string', 'max:30', 'unique:users,nim'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in([UserRole::Student->value, UserRole::LiaisonOfficer->value])],
            'major' => ['required', 'string', 'max:120'],
            'group_id' => ['nullable', 'exists:groups,id'],
        ], [
            'nim.unique' => 'NIM ini sudah terdaftar.',
            'major.required' => 'Jurusan wajib dipilih — dipakai untuk memetakan fakultas.',
        ]);

        // ?? null wajib: group_id opsional, jadi kuncinya bisa tidak ada sama
        // sekali di payload, bukan hanya bernilai null.
        $groupId = $validated['group_id'] ?? null;

        $group = $groupId !== null
            ? Group::query()->find($groupId)
            : null;

        // Anggota gugus harus sejurusan. Kalau tidak, Gugus Wars dan batas
        // wewenang LO jadi tidak bermakna.
        if ($group !== null && $group->study_program !== null
            && $group->study_program !== $validated['major']) {
            return back()->withErrors([
                'group_id' => 'Gugus '.$group->name.' untuk jurusan '.$group->study_program
                    .', bukan '.$validated['major'].'.',
            ])->withInput();
        }

        $plainPassword = Str::password(10);

        $user = User::create([
            'name' => $validated['name'],
            'nim' => $validated['nim'],
            'email' => filled($validated['email'] ?? null)
                ? $validated['email']
                : $validated['nim'].'@students.umk.ac.id',
            'password' => Hash::make($plainPassword),
            'role' => UserRole::from($validated['role']),
            'faculty' => $this->facultyFor($validated['major']),
            'major' => $this->normalizeMajor($validated['major']),
            'group_id' => $group?->id,
            'group_name' => $group?->name,
            'mentor_name' => $group?->mentor_name,
            'xp' => 0,
            'coins' => 0,
            'level' => 1,
        ]);

        Log::info('Akun dibuat manual oleh admin.', [
            'oleh_admin' => $request->user()->getKey(),
            'target_user' => $user->getKey(),
            'role' => $user->role->value,
        ]);

        return back()->with('toast', [
            'title' => 'Akun dibuat',
            'message' => $user->name.' · sandi sementara: '.$plainPassword.' (catat sekarang)',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Ubah data dasar akun: nama, email, jurusan, gugus.
     *
     * NIM tidak diubah karena ia identitas login dan sudah tercetak di
     * banyak tempat; perubahan NIM ditangani sebagai hapus + buat baru.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->getKey())],
            'major' => ['required', 'string', 'max:120'],
            'group_id' => ['nullable', 'exists:groups,id'],
        ]);

        $groupId = $validated['group_id'] ?? null;

        $group = $groupId !== null
            ? Group::query()->find($groupId)
            : null;

        if ($group !== null && $group->study_program !== null
            && $group->study_program !== $validated['major']) {
            return back()->withErrors([
                'group_id' => 'Gugus '.$group->name.' bukan untuk jurusan '.$validated['major'].'.',
            ])->withInput();
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'major' => $this->normalizeMajor($validated['major']),
            'faculty' => $this->facultyFor($validated['major']),
            'group_id' => $group?->id,
            'group_name' => $group?->name,
            'mentor_name' => $group?->mentor_name,
        ])->save();

        Log::info('Akun diubah oleh admin.', [
            'oleh_admin' => $request->user()->getKey(),
            'target_user' => $user->getKey(),
        ]);

        return back()->with('toast', [
            'title' => 'Akun diperbarui',
            'message' => $user->name.' tersimpan.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Setel ulang sandi dan tampilkan sekali.
     *
     * Diperlukan karena tidak ada fitur "lupa sandi" maupun email reset:
     * tanpa ini, mahasiswa yang lupa sandi hanya bisa dipulihkan lewat DB.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $plainPassword = Str::password(10);

        $user->forceFill(['password' => Hash::make($plainPassword)])->save();

        Log::info('Sandi disetel ulang oleh admin.', [
            'oleh_admin' => $request->user()->getKey(),
            'target_user' => $user->getKey(),
        ]);

        return back()->with('toast', [
            'title' => 'Sandi disetel ulang',
            'message' => $user->name.' · sandi baru: '.$plainPassword.' (catat sekarang)',
            'icon' => 'check',
            'tone' => 'warning',
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'group_id' => ['nullable', 'exists:groups,id'],
        ]);

        // Jangan sampai admin menurunkan perannya sendiri dan terkunci di luar.
        if ($user->is($request->user()) && $validated['role'] !== UserRole::Admin->value) {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan peran akun sendiri.']);
        }

        $group = $validated['group_id'] !== null ? Group::query()->find($validated['group_id']) : null;

        $user->forceFill([
            'role' => UserRole::from($validated['role']),
            'group_id' => $group?->id,
            'group_name' => $group?->name,
            'mentor_name' => $group?->mentor_name,
        ])->save();

        return back()->with('toast', [
            'title' => 'Akun diperbarui',
            'message' => $user->name.' sekarang berperan '.$user->role->label().'.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        // Konfirmasi ketik NIM/nama: hapus akun memindahkan seluruh riwayat
        // mahasiswa dari pandangan, jadi tidak boleh terjadi karena salah klik.
        $confirm = trim((string) $request->input('confirm_identity'));

        if ($confirm !== $user->nim && $confirm !== $user->name) {
            return back()->withErrors([
                'confirm_identity' => 'Ketik NIM atau nama akun untuk mengonfirmasi penghapusan.',
            ]);
        }

        // Soft delete: riwayat XP, bukti modul, dan klaim hadiah TETAP ADA.
        // Hard delete akan menghapusnya permanen lewat cascadeOnDelete, dan
        // saat sengketa tidak tersisa bukti apa pun.
        $user->delete();

        Log::info('Akun dihapus (soft delete).', [
            'oleh_admin' => $request->user()->getKey(),
            'target_user' => $user->getKey(),
            'target_nim' => $user->nim,
        ]);

        return back()->with('toast', [
            'title' => 'Akun dihapus',
            'message' => $user->name.' dipindahkan ke arsip. Riwayatnya tetap tersimpan dan bisa dipulihkan.',
            'icon' => 'x',
            'tone' => 'warning',
        ]);
    }

    /**
     * Template CSV untuk diunduh panitia.
     */
    /**
     * Pulihkan akun yang dihapus (soft delete).
     */
    public function restore(Request $request, int $user): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $target = User::withTrashed()->findOrFail($user);

        if (! $target->trashed()) {
            return back()->withErrors(['user' => 'Akun ini tidak sedang dihapus.']);
        }

        $target->restore();

        Log::info('Akun dipulihkan.', [
            'oleh_admin' => $request->user()->getKey(),
            'target_user' => $target->getKey(),
            'target_nim' => $target->nim,
        ]);

        return back()->with('toast', [
            'title' => 'Akun dipulihkan',
            'message' => $target->name.' kembali aktif dengan seluruh riwayatnya.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    public function template()
    {
        abort_unless(request()->user()->role->canManageAccounts(), 403);

        $rows = [
            ['NIM', 'Nama Penuh', 'Jurusan', 'Email Kampus', 'Kode Gugus'],
            ['202653099', 'Contoh Mahasiswa', 'Sistem Informasi', '202653099@students.umk.ac.id', 'G-01'],
        ];

        $callback = function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'template-mahasiswa-ospekquest.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}