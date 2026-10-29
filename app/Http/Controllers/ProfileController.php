<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\HelpTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $faqs = Faq::orderBy('sort_order')->get();
        $tickets = $user->helpTickets()->latest()->get();

        $activeTab = $request->query('tab', 'profile');

        return view('pages.profile', compact('user', 'faqs', 'tickets', 'activeTab'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'faculty' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($validated);

        return redirect()->route('profile', ['tab' => 'profile'])->with('toast', [
            'title' => 'Profil Dikemaskini!',
            'message' => 'Maklumat rasmi pendaftaran anda berjaya disimpan.',
            'icon' => '✅',
        ]);
    }

    public function updateSettings(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        if ($request->has('current_password') && $request->filled('password')) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'min:6', 'confirmed'],
            ]);

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with('toast', [
                'title' => 'Kata Laluan Dikemaskini!',
                'message' => 'Kata laluan akaun anda telah ditukar dengan selamat.',
                'icon' => '🔑',
            ]);
        }

        $user->sound_effects = $request->boolean('sound_effects', $user->sound_effects);
        $user->email_notifications = $request->boolean('email_notifications', $user->email_notifications);
        $user->whatsapp_notifications = $request->boolean('whatsapp_notifications', $user->whatsapp_notifications);
        $user->dark_mode = $request->boolean('dark_mode', $user->dark_mode);
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tetapan pilihan berjaya disimpan.',
                'sound_effects' => $user->sound_effects,
                'dark_mode' => $user->dark_mode,
            ]);
        }

        return redirect()->route('profile', ['tab' => 'settings'])->with('toast', [
            'title' => 'Tetapan Disimpan',
            'message' => 'Keutamaan akaun anda telah dikemaskini.',
            'icon' => '⚙️',
        ]);
    }

    public function submitHelpTicket(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
        ]);

        $ticket = HelpTicket::create([
            'user_id' => $user->id,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'message' => $validated['message'],
            'status' => 'answered',
            'reply' => 'Salam sejahtera '.$user->name.'. Pihak senior pembimbing telah menerima pertanyaan anda berkenaan "'.$validated['subject'].'". Kami akan membantu anda dan anda juga dialu-alukan datang ke Bilik Kaunseling 4B untuk bimbingan langsung.',
        ]);

        return redirect()->route('profile', ['tab' => 'helpdesk'])->with('toast', [
            'title' => 'Tiket Bantuan Dihantar!',
            'message' => 'Senior pembimbing telah menerima mesej anda.',
            'icon' => '📩',
        ]);
    }
}
