<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Endpoint yang dipanggil modal anti-curang. Semua keputusan (token cocok,
 * jarak GPS, kuota, sekali-pakai) dibuat di server, bukan di JavaScript.
 */
class VerificationController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    /**
     * Klausa token rahasia stan.
     */
    public function token(Request $request, Quest $quest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'token' => ['required', 'string', 'min:4', 'max:40'],
        ]);

        try {
            $result = $this->verification->redeemToken(
                $user,
                $quest,
                $validated['token'],
                $request->ip(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json($result);
    }

    /**
     * Klausa lokasi GPS. Koordinat dikirim klien, jarak dihitung server.
     */
    public function location(Request $request, Quest $quest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $result = $this->verification->verifyLocation(
                $user,
                $quest,
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json($result);
    }

    /**
     * Unggah bukti foto. Tidak menghasilkan XP — hanya membuat pengajuan
     * berstatus pending untuk ditinjau LO.
     */
    public function photo(Request $request, Quest $quest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'proof.image' => 'Bukti harus berupa gambar.',
            'proof.max' => 'Ukuran bukti maksimal 5 MB.',
        ]);

        try {
            $result = $this->verification->submitPhotoProof(
                $user,
                $quest,
                $request->file('proof'),
                $validated['note'] ?? null,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json($result);
    }
}