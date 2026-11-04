<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kelola Bursa Hadiah dari UI.
 *
 * Sebelum ini hadiah hanya bisa dibuat lewat seeder dan diserahkan lewat CLI
 * (`reward:fulfill`). Panitia tidak bisa menambah merchandise baru, mengubah
 * harga, atau menandai siapa yang sudah mengambil hadiah tanpa menyentuh kode.
 *
 * Hanya ADMIN yang boleh mengubah hadiah. Harga dan stok harus SATU untuk
 * seluruh kampus - kalau LO bisa mengubahnya, akan muncul gugus dengan harga
 * berbeda dan papan peringkat/Bursa kehilangan kredibilitas di depan juri.
 * LO tetap boleh MENYERAHKAN hadiah untuk gugusnya sendiri.
 */
class AdminRewardController extends Controller
{
    /** Ukuran gambar hadiah: cukup tajam di kartu, tidak membebani koneksi kampus. */
    private const IMAGE_MAX_KB = 2048;

    public function index(Request $request): View
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $rewards = Reward::query()
            ->withCount([
                'redemptions as claimed_count',
                'redemptions as fulfilled_count' => fn ($q) => $q->where('status', 'fulfilled'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $claims = RewardRedemption::query()
            ->with(['user:id,name,nim,group_name', 'reward:id,name,code'])
            ->latest()
            ->limit(30)
            ->get();

        return view('pages.admin.rewards', [
            'rewards' => $rewards,
            'claims' => $claims,
            'stats' => [
                'total' => $rewards->count(),
                'active' => $rewards->where('is_active', true)->count(),
                'claimed' => $rewards->sum('claimed_count'),
                'fulfilled' => $rewards->sum('fulfilled_count'),
            ],
        ]);
    }

    /** Tambah hadiah baru. */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $validated = $this->validateReward($request);

        $reward = Reward::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'cost_coins' => $validated['cost_coins'],
            // NULL = stok tak terbatas. Jangan diisi 0, itu berarti habis.
            'stock' => $validated['stock'] ?? null,
            'quota_per_user' => $validated['quota_per_user'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
            'image_path' => $this->storeImage($request),
        ]);

        Log::info('Hadiah dibuat.', [
            'oleh_admin' => $request->user()->getKey(),
            'reward_id' => $reward->getKey(),
            'code' => $reward->code,
        ]);

        return back()->with('toast', [
            'title' => 'Hadiah ditambahkan',
            'message' => $reward->name.' tersedia di Bursa.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /** Ubah hadiah. */
    public function update(Request $request, Reward $reward): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $validated = $this->validateReward($request, $reward);

        $image = $this->storeImage($request);

        // Gambar lama dihapus HANYA setelah gambar baru tersimpan, supaya
        // kegagalan unggah tidak meninggalkan hadiah tanpa gambar sama sekali.
        if ($image !== null && $reward->image_path) {
            Storage::disk('public')->delete($reward->image_path);
        }

        $reward->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'cost_coins' => $validated['cost_coins'],
            'stock' => $validated['stock'] ?? null,
            'quota_per_user' => $validated['quota_per_user'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
            ...($image !== null ? ['image_path' => $image] : []),
        ]);

        Log::info('Hadiah diubah.', [
            'oleh_admin' => $request->user()->getKey(),
            'reward_id' => $reward->getKey(),
        ]);

        return back()->with('toast', [
            'title' => 'Hadiah diperbarui',
            'message' => $reward->name.' tersimpan.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Nonaktifkan hadiah, bukan hapus.
     *
     * Klaim lama menunjuk reward_id; menghapusnya membuat riwayat klaim
     * mahasiswa kehilangan relasi dan sulit diaudit.
     */
    public function deactivate(Request $request, Reward $reward): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $reward->forceFill(['is_active' => false])->save();

        return back()->with('toast', [
            'title' => 'Hadiah dinonaktifkan',
            'message' => $reward->name.' disembunyikan dari Bursa. Klaim lama tetap utuh.',
            'icon' => 'check',
            'tone' => 'warning',
        ]);
    }

    public function activate(Request $request, Reward $reward): RedirectResponse
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $reward->forceFill(['is_active' => true])->save();

        return back()->with('toast', [
            'title' => 'Hadiah diaktifkan',
            'message' => $reward->name.' muncul lagi di Bursa.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Serahkan hadiah dari UI (pengganti `reward:fulfill` untuk panitia).
     *
     * Hanya menandai status; tidak menyentuh koin - koin sudah terpotong saat
     * penukaran. Menyerahkan dua kali harus ditolak, kalau tidak panitia bisa
     * memberikan barang dua untuk satu klaim.
     */
    public function fulfill(Request $request, RewardRedemption $redemption): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role->canManageAccounts() || $user->role === UserRole::LiaisonOfficer, 403);

        // LO hanya boleh menyerahkan hadiah mahasiswa gugusnya sendiri.
        if ($user->role === UserRole::LiaisonOfficer) {
            $target = $redemption->user;

            abort_unless(
                $target !== null && $target->group_id !== null && $target->group_id === $user->group_id,
                403,
                'Anda hanya bisa menyerahkan hadiah mahasiswa gugus Anda.',
            );
        }

        if ($redemption->status === 'fulfilled') {
            return back()->withErrors(['redemption' => 'Klaim ini sudah diserahkan sebelumnya.']);
        }

        if ($redemption->status === 'cancelled') {
            return back()->withErrors(['redemption' => 'Klaim ini sudah dibatalkan.']);
        }

        $redemption->forceFill([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ])->save();

        Log::info('Hadiah diserahkan.', [
            'oleh' => $user->getKey(),
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

    /** @return array<string, mixed> */
    private function validateReward(Request $request, ?Reward $reward = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:32', 'regex:/^[A-Z0-9-]+$/',
                Rule::unique('rewards', 'code')->ignore($reward?->getKey()),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['required', Rule::in(['merchandise', 'voucher', 'digital', 'lainnya'])],
            'cost_coins' => ['required', 'integer', 'min:1', 'max:100000'],
            // Kosong = tak terbatas. 0 bukan "tidak ada", tetapi "habis".
            'stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'quota_per_user' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::IMAGE_MAX_KB],
        ], [
            'code.regex' => 'Kode hanya boleh huruf besar, angka, dan tanda hubung.',
            'code.unique' => 'Kode hadiah ini sudah dipakai.',
            'image.max' => 'Gambar maksimal 2 MB.',
        ]);
    }

    /**
     * Simpan gambar hadiah ke disk public, kembalikan path relatifnya.
     *
     * Nama file memakai kode hadiah + hash isi, sehingga mengganti gambar
     * tidak menabrak file lain dan browser tidak memakai cache lama.
     */
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');

        $name = Str::slug((string) $request->input('code'))
            .'-'.substr(md5_file($file->getRealPath()), 0, 8)
            .'.'.$file->getClientOriginalExtension();

        return $file->storeAs('rewards', $name, 'public');
    }
}
