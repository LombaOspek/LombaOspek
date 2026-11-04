<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kelola anggota gugus tanpa import CSV ulang.
 *
 * Kenapa perlu: `storeGroup()` dulu hanya memasukkan LO sebagai anggota, dan
 * satu-satunya cara menambah mahasiswa adalah import CSV yang mencocokkan
 * kolom "Kode Gugus". Untuk kasus susulan (mahasiswa pindahan, telat daftar,
 * salah gugus) itu berat dan rawan salah.
 *
 * Aturan yang ditegakkan di sini: anggota gugus WAJIB sejurusan dengan gugus.
 * Tanpa itu Gugus Wars membandingkan kelompok yang tidak setara, dan LO tidak
 * bisa menilai bukti mahasiswa dari prodi yang tidak ia pahami.
 */
class GroupMemberController extends Controller
{
    /**
     * Daftar mahasiswa yang bisa ditambahkan: sejurusan, belum punya gugus,
     * atau sudah di gugus lain (untuk dipindahkan).
     */
    public function candidates(Request $request, Group $group): JsonResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $term = trim((string) $request->query('q', ''));

        $query = User::query()
            ->where('role', UserRole::Student)
            ->where('major', $group->study_program)
            ->where(function ($q) use ($group): void {
                // Belum bergugus, atau sudah di gugus lain (bisa dipindah).
                $q->whereNull('group_id')->orWhere('group_id', '!=', $group->id);
            })
            ->when($term !== '', function ($q) use ($term): void {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('nim', 'like', $like));
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'nim', 'name', 'group_id', 'group_name']);

        return response()->json([
            'ok' => true,
            'major' => $group->study_program,
            'candidates' => $query->map(fn (User $u) => [
                'id' => $u->id,
                'nim' => $u->nim,
                'name' => $u->name,
                'group' => $u->group_name,
            ])->values(),
        ]);
    }

    /**
     * Tambahkan satu atau beberapa mahasiswa ke gugus.
     *
     * Massal karena panitia biasanya memindahkan satu kelompok sekaligus
     * (mis. mahasiswa yang salah masuk gugus saat import).
     */
    public function store(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $members = User::query()
            ->whereIn('id', $validated['user_ids'])
            ->get();

        // Validasi jurusan: satu mahasiswa beda prodi akan merusak keadilan
        // Gugus Wars dan wewenang LO, jadi ditolak seluruhnya - bukan sebagian.
        $mismatch = $members->filter(
            fn (User $u) => $u->major !== null && $u->major !== $group->study_program
        );

        if ($mismatch->isNotEmpty()) {
            return back()->withErrors([
                'user_ids' => sprintf(
                    '%d mahasiswa bukan dari %s: %s',
                    $mismatch->count(),
                    $group->study_program,
                    $mismatch->take(3)->map(fn (User $u) => $u->name.' ('.$u->major.')')->implode(', '),
                ),
            ]);
        }

        $capacity = $group->capacity ?? 0;
        $current = $group->members()->count();

        if ($capacity > 0 && $current + $members->count() > $capacity) {
            return back()->withErrors([
                'user_ids' => sprintf(
                    'Kapasitas gugus %d, sudah terisi %d. Tidak bisa menambah %d lagi.',
                    $capacity,
                    $current,
                    $members->count(),
                ),
            ]);
        }

        DB::transaction(function () use ($members, $group): void {
            User::query()
                ->whereIn('id', $members->pluck('id')->all())
                ->update([
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'mentor_name' => $group->mentor_name,
                ]);
        });

        Log::info('Anggota gugus ditambahkan.', [
            'oleh_admin' => $request->user()->getKey(),
            'group_id' => $group->getKey(),
            'jumlah' => $members->count(),
        ]);

        return back()->with('toast', [
            'title' => 'Anggota ditambahkan',
            'message' => $members->count().' mahasiswa masuk ke '.$group->name.'.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Keluarkan anggota dari gugus.
     *
     * Akun TIDAK dihapus - hanya `group_id` dikosongkan. Menghapus akun di
     * sini akan membuang riwayat XP mahasiswa hanya karena salah pilih gugus.
     */
    public function destroy(Request $request, Group $group, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        if ($user->group_id !== $group->id) {
            return back()->withErrors(['user' => 'Mahasiswa ini bukan anggota gugus tersebut.']);
        }

        $user->forceFill([
            'group_id' => null,
            'group_name' => null,
            'mentor_name' => null,
        ])->save();

        Log::info('Anggota dikeluarkan dari gugus.', [
            'oleh_admin' => $request->user()->getKey(),
            'group_id' => $group->getKey(),
            'target_user' => $user->getKey(),
        ]);

        return back()->with('toast', [
            'title' => 'Anggota dikeluarkan',
            'message' => $user->name.' kini tanpa gugus. Akun dan riwayatnya tetap utuh.',
            'icon' => 'check',
            'tone' => 'warning',
        ]);
    }
}
