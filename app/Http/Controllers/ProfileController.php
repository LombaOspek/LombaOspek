<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreHelpTicketRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Faq;
use App\Models\HelpTicket;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\CertificateService;
use App\Services\LeaderboardService;
use App\Services\ProgressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProgressionService $progression,
        private readonly LeaderboardService $leaderboard,
        private readonly CertificateService $certificates,
        private readonly AchievementService $achievements,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $tab = (string) $request->query('tab', 'profile');

        if (! in_array($tab, ['profile', 'settings', 'helpdesk'], true)) {
            $tab = 'profile';
        }

        $faqs = Faq::query()->published()->orderBy('sort_order')->get();

        $tickets = $user->helpTickets()
            ->with('answeredBy:id,name')
            ->latest('id')
            ->get();

        return view('pages.profile', [
            'user' => $user,
            'faqs' => $faqs,
            'tickets' => $tickets,
            'activeTab' => $tab,
            'categories' => StoreHelpTicketRequest::CATEGORIES,
            'completedQuests' => $this->progression->completedCount($user),
            'totalQuests' => $this->progression->questBoard($user)->count(),
            'certificate' => $this->certificates->status($user),
            'rank' => $this->leaderboard->rankOf($user),
            'achievementCount' => $this->achievements->board($user)->where('is_unlocked', true)->count(),
            'pendingTickets' => $tickets->where('status', TicketStatus::Open)->count(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // NIM, email, dan role sengaja tidak dapat diubah dari form profil:
        // ketiganya identitas akademik yang hanya boleh diubah admin.
        $user->update($request->validated());

        return redirect()->route('profile', ['tab' => 'profile'])->with('toast', [
            'title' => 'Profil diperbarui',
            'message' => 'Data akademik Anda berhasil disimpan.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * Toggle preferensi. Nilai selalu ditulis eksplisit (true/false) sehingga
     * checkbox yang dilepas benar-benar mematikan setelan — bug lama membuat
     * setelan tidak pernah bisa dimatikan karena `boolean()` memakai default.
     */
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill($request->toggles())->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Preferensi disimpan.',
                'settings' => [
                    'sound_effects' => $user->sound_effects,
                    'email_notifications' => $user->email_notifications,
                    'whatsapp_notifications' => $user->whatsapp_notifications,
                    'dark_mode' => $user->dark_mode,
                ],
            ]);
        }

        return redirect()->route('profile', ['tab' => 'settings'])->with('toast', [
            'title' => 'Preferensi disimpan',
            'message' => 'Pengaturan akun Anda telah diperbarui.',
            'icon' => 'settings',
            'tone' => 'success',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update(['password' => $request->validated('password')]);

        $request->session()->regenerate();

        return redirect()->route('profile', ['tab' => 'settings'])->with('toast', [
            'title' => 'Kata sandi diganti',
            'message' => 'Gunakan kata sandi baru pada login berikutnya.',
            'icon' => 'key',
            'tone' => 'success',
        ]);
    }

    /**
     * Tiket dibuat berstatus `open` tanpa jawaban palsu. Balasan datang dari
     * mentor sungguhan lewat /mentor/tiket — versi lama langsung mengisi
     * balasan otomatis yang menyesatkan.
     */
    public function submitHelpTicket(StoreHelpTicketRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        HelpTicket::create([
            'user_id' => $user->getKey(),
            'subject' => $request->validated('subject'),
            'category' => $request->validated('category'),
            'message' => $request->validated('message'),
            'status' => TicketStatus::Open,
        ]);

        return redirect()->route('profile', ['tab' => 'helpdesk'])->with('toast', [
            'title' => 'Tiket terkirim',
            'message' => 'Mentor orientasi akan menjawab melalui portal ini.',
            'icon' => 'send',
            'tone' => 'success',
        ]);
    }
}
