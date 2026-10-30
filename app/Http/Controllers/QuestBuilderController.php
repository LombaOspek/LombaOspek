<?php

namespace App\Http\Controllers;

use App\Enums\QuestActionType;
use App\Enums\VerificationMethod;
use App\Models\Quest;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Quest Builder: admin merancang misi baru, termasuk mekanisme verifikasinya.
 */
class QuestBuilderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role->canBuildQuests(), 403);

        $quests = Quest::query()
            ->withCount('submissions')
            ->orderByDesc('id')
            ->get();

        return view('pages.admin.quest-builder', [
            'quests' => $quests,
            'drafts' => $quests->where('status', 'draft')->count(),
            'actionTypes' => QuestActionType::cases(),
            'methods' => VerificationMethod::cases(),
            'majors' => User::query()->whereNotNull('major')->distinct()->orderBy('major')->pluck('major'),
            'nextStep' => (int) (Quest::query()->max('step_number') ?? 0) + 1,
            'editing' => $request->filled('edit')
                ? Quest::query()->find($request->integer('edit'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->canBuildQuests(), 403);

        $validated = $this->validated($request);

        $quest = new Quest($validated);
        $quest->code = 'MOD-'.str_pad((string) ($validated['step_number']), 2, '0', STR_PAD_LEFT);

        // Token disimpan sebagai hash; plaintext tidak pernah menyentuh database.
        if ($validated['verification_method'] === VerificationMethod::Token->value && filled($request->input('token_code'))) {
            $quest->setPlainToken((string) $request->input('token_code'));
        }

        $quest->save();

        return redirect()->route('admin.quests.index')->with('toast', [
            'title' => $quest->status === 'draft' ? 'Draf disimpan' : 'Misi dipublikasikan',
            'message' => $quest->title.' tersimpan sebagai '.$quest->status.'.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    public function update(Request $request, Quest $quest): RedirectResponse
    {
        abort_unless($request->user()->role->canBuildQuests(), 403);

        $validated = $this->validated($request, $quest);

        $quest->fill($validated);

        // Kode modul diturunkan dari urutan, jadi harus ikut berubah saat
        // panitia memindahkan modul ke urutan lain. Kode kuis hanya diubah bila
        // belum ada percobaan yang mengunci kode lama.
        $newCode = 'MOD-'.str_pad((string) $validated['step_number'], 2, '0', STR_PAD_LEFT);

        if ($quest->code !== $newCode) {
            $previousCode = $quest->code;

            // Kode kuis hanya boleh ikut berubah bila belum ada percobaan yang
            // mencatat kode lama — kalau tidak, riwayat kuis jadi yatim.
            $hasAttempts = QuizAttempt::query()
                ->where('module_code', $quest->quiz_module_code ?? $previousCode)
                ->exists();

            $quest->code = $newCode;

            if ($quest->action_type === QuestActionType::Quiz && ! $hasAttempts) {
                $quest->quiz_module_code = $newCode;
            }
        }

        if ($validated['verification_method'] === VerificationMethod::Token->value && filled($request->input('token_code'))) {
            $quest->setPlainToken((string) $request->input('token_code'));
        }

        $quest->save();

        return redirect()->route('admin.quests.index')->with('toast', [
            'title' => 'Misi diperbarui',
            'message' => $quest->title.' berhasil disimpan.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Quest $quest = null): array
    {
        $method = (string) $request->input('verification_method', VerificationMethod::None->value);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'objective' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:150'],
            'xp_reward' => ['required', 'integer', 'min:10', 'max:1000'],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'step_number' => [
                'required', 'integer', 'min:1', 'max:99',
                // Urutan modul menentukan kode MOD-xx, jadi harus unik. Tanpa
                // aturan ini dua modul bisa mendapat kode sama dan meledak di
                // tingkat database.
                Rule::unique('quests', 'step_number')->ignore($quest?->getKey()),
            ],
            'unlock_day' => ['required', 'integer', 'min:1', 'max:30'],
            'action_type' => ['required', Rule::enum(QuestActionType::class)],
            'verification_method' => ['required', Rule::enum(VerificationMethod::class)],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'difficulty' => ['required', 'integer', 'min:1', 'max:3'],
            'target_scope' => ['required', Rule::in(['all', 'faculty', 'major'])],
            'target_study_programs' => ['nullable', 'array'],
            'target_study_programs.*' => ['string', 'max:120'],
            'sponsor_name' => ['nullable', 'string', 'max:120'],
            'bonus_reward' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:40'],

            // Konfigurasi token
            'token_code' => ['nullable', 'string', 'min:4', 'max:40'],
            'token_quota' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'token_expires_at' => ['nullable', 'date'],

            // Konfigurasi GPS
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
        ]);

        // Validasi silang: metode verifikasi menuntut konfigurasi yang lengkap,
        // kalau tidak modul akan mustahil diselesaikan mahasiswa.
        if ($method === VerificationMethod::Token->value) {
            $hasExistingToken = $quest?->token_hash !== null;

            if (! $hasExistingToken && blank($request->input('token_code'))) {
                throw ValidationException::withMessages([
                    'token_code' => 'Kode token wajib diisi untuk metode Token Rahasia.',
                ]);
            }
        }

        if ($method === VerificationMethod::Gps->value) {
            if ($request->input('latitude') === null || $request->input('longitude') === null) {
                throw ValidationException::withMessages([
                    'latitude' => 'Koordinat lokasi wajib diisi untuk metode GPS.',
                ]);
            }
        }

        // Kode modul harus sama formatnya dengan seeder: MOD-01, MOD-09, MOD-20.
        $moduleCode = 'MOD-'.str_pad((string) $data['step_number'], 2, '0', STR_PAD_LEFT);

        $data['quiz_module_code'] = $data['action_type'] === QuestActionType::Quiz->value
            ? ($quest?->quiz_module_code ?? $moduleCode)
            : null;

        $data['target_study_programs'] = $data['target_scope'] === 'major'
            ? ($data['target_study_programs'] ?? [])
            : null;

        unset($data['token_code']);

        return $data;
    }
}