<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'demoEnabled' => (bool) config('ospek.demo.enabled'),
            'demoNim' => (string) config('ospek.demo.nim'),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $identity = (string) $request->validated('identity');

        $user = User::query()
            ->where('nim', $identity)
            ->orWhere('email', $identity)
            ->first();

        if ($user === null || ! Hash::check((string) $request->validated('password'), $user->password)) {
            // Pesan seragam: jangan membocorkan apakah NIM atau sandi yang salah.
            return back()
                ->withErrors(['identity' => 'NIM/email atau kata sandi tidak cocok.'])
                ->onlyInput('identity');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('toast', [
            'title' => 'Berhasil masuk',
            'message' => 'Selamat datang, '.$user->name.'.',
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('toast', [
            'title' => 'Sesi diakhiri',
            'message' => 'Anda telah keluar dari portal orientasi.',
            'icon' => 'logout',
            'tone' => 'neutral',
        ]);
    }
}
