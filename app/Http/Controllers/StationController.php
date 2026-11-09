<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Station penyerahan hadiah untuk Liaison Officer.
 *
 * Kenapa halaman terpisah, bukan `/admin/bursa`:
 *   Rute admin berada di grup `role:admin`, sehingga LO ditolak 403 oleh
 *   middleware SEBELUM mencapai controller. Padahal `AdminRewardController::
 *   fulfill()` sudah mengizinkan LO - izin itu tidak pernah tercapai.
 *   Menu sidebar LO juga menautkan ke `admin.rewards`, jadi LO melihat menu
 *   yang pasti menghasilkan 403 ketika diklik.
 *
 * Halaman ini hanya memuat pekerjaan LO: daftar klaim mahasiswa gugusnya, dan
 * penyerahan berdasarkan kode klaim. Admin tetap memegang harga/stok di
 * `/admin/bursa` - satu harga untuk seluruh kampus.
 */
class StationController extends Controller
{
    /**
     * Daftar klaim pada gugus LO, dikelompokkan menurut status.
     */
    public function index(Request $request): View
    {
        $officer = $request->user();
        $this->ensureOfficer($officer);

        // Filter status dari query string; default menampilkan yang belum diambil.
        $status = $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'fulfilled', 'cancelled', 'all'], true)) {
            $status = 'pending';
        }

        $cari = trim((string) $request->query('q', ''));

        $memberIds = $this->memberIds($officer);

        $query = RewardRedemption::query()
            ->with(['reward:id,name,code,image_path', 'user:id,name,nim,group_id'])
            ->whereIn('user_id', $memberIds)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($cari !== '', function ($q) use ($cari) {
                // Pencarian kode klaim dipakai di meja station: panitia membaca
                // kode dari layar mahasiswa lalu mengetiknya di sini.
                $q->where(function ($inner) use ($cari) {
                    $inner->where('claim_code', 'like', '%'.$cari.'%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$cari.'%')
                            ->orWhere('nim', 'like', '%'.$cari.'%'));
                });
            })
            /*
             * Urutan status memakai CASE, bukan FIELD().
             *
             * FIELD() adalah fungsi MySQL saja; tes berjalan di SQLite dan
             * langsung gagal "no such function: FIELD". CASE portabel di
             * keduanya, jadi urutan yang sama berlaku di produksi (MariaDB)
             * maupun di suite tes.
             */
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'fulfilled' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');

        // Dihitung SEBELUM paginasi, supaya kartu ringkasan menunjukkan total
        // gugus, bukan total halaman yang sedang dibuka.
        $counts = [
            'pending' => RewardRedemption::query()->whereIn('user_id', $memberIds)->where('status', 'pending')->count(),
            'fulfilled' => RewardRedemption::query()->whereIn('user_id', $memberIds)->where('status', 'fulfilled')->count(),
            'cancelled' => RewardRedemption::query()->whereIn('user_id', $memberIds)->where('status', 'cancelled')->count(),
        ];

        return view('pages.lo.station', [
            'claims' => $query->paginate(15)->withQueryString(),
            'counts' => $counts,
            'status' => $status,
            'cari' => $cari,
            'group' => $officer->group,
            'memberCount' => $memberIds->count(),
        ]);
    }

    /**
     * Serahkan satu klaim. Dipisah dari AdminRewardController::fulfill karena
     * pemeriksaan kepemilikan gugus di sini lebih ketat: LO hanya boleh
     * menyerahkan klaim mahasiswa yang berada di gugusnya SEKARANG.
     */
    public function fulfill(Request $request, RewardRedemption $redemption): RedirectResponse
    {
        $officer = $request->user();
        $this->ensureOfficer($officer);

        $target = $redemption->user;

        abort_unless(
            $target !== null
                && $officer->group_id !== null
                && $target->group_id === $officer->group_id,
            403,
            'Klaim ini milik mahasiswa gugus lain.',
        );

        if ($redemption->status === 'fulfilled') {
            return back()->withErrors([
                'redemption' => 'Klaim '.$redemption->claim_code.' sudah diserahkan. Jangan berikan barang dua kali.',
            ]);
        }

        if ($redemption->status === 'cancelled') {
            return back()->withErrors([
                'redemption' => 'Klaim '.$redemption->claim_code.' sudah dibatalkan, tidak ada barang yang diserahkan.',
            ]);
        }

        $redemption->forceFill([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ])->save();

        Log::info('Hadiah diserahkan via station LO.', [
            'oleh' => $officer->getKey(),
            'officer_nim' => $officer->nim,
            'redemption_id' => $redemption->getKey(),
            'claim_code' => $redemption->claim_code,
        ]);

        return back()->with('toast', [
            'title' => 'Hadiah diserahkan',
            'message' => 'Klaim '.$redemption->claim_code.' sudah ditandai diambil.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Cari klaim berdasarkan kode - dipakai kotak pencarian di meja station.
     * Tujuan: panitia bisa langsung mengetik kode dari QR/teks tanpa menggulir.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $officer = $request->user();
        $this->ensureOfficer($officer);

        $code = strtoupper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return back();
        }

        $redemption = RewardRedemption::query()->where('claim_code', $code)->first();

        if ($redemption === null) {
            return back()->withErrors(['code' => 'Kode klaim '.$code.' tidak ditemukan.']);
        }

        return redirect()->route('lo.station', ['q' => $code]);
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function memberIds(User $officer)
    {
        if ($officer->group_id === null) {
            return collect();
        }

        return User::query()->where('group_id', $officer->group_id)->pluck('id');
    }

    private function ensureOfficer(?User $user): void
    {
        abort_unless(
            $user !== null && $user->role === UserRole::LiaisonOfficer,
            403,
            'Halaman ini hanya untuk Liaison Officer.',
        );
    }
}