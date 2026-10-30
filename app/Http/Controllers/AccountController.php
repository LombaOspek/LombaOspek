<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\QuestSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

                User::create([
                    'nim' => $nim,
                    'name' => $name,
                    'email' => filled($email) ? trim((string) $email) : $nim.'@students.umk.ac.id',
                    'password' => Hash::make($request->boolean('auto_generate_password') ? Str::password(10) : 'password'),
                    'role' => UserRole::Student,
                    'faculty' => 'Fakultas Teknik',
                    'major' => filled($major) ? trim((string) $major) : 'Sistem Informasi',
                    'group_id' => $group?->id,
                    'group_name' => $group?->name,
                    'mentor_name' => $group?->mentor_name,
                    'xp' => 0,
                    'level' => 1,
                ]);

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
        ])->with('import_skipped', array_slice($skipped, 0, 15));
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

        $name = $user->name;
        $user->delete();

        return back()->with('toast', [
            'title' => 'Akun dihapus',
            'message' => $name.' telah dihapus dari sistem.',
            'icon' => 'x',
            'tone' => 'warning',
        ]);
    }

    /**
     * Template CSV untuk diunduh panitia.
     */
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