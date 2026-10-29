<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identity = $credentials['identity'];
        $user = User::where('email', $identity)
            ->orWhere('nim', $identity)
            ->first();

        if ($user && Auth::attempt(['email' => $user->email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))->with('toast', [
                'title' => 'Selamat Datang!',
                'message' => 'Berjaya masuk sebagai '.$user->name,
                'icon' => '👋',
            ]);
        }

        return back()->withErrors([
            'identity' => 'NIM atau Kata Laluan tidak sah. Sila cuba lagi atau gunakan butang demo.',
        ])->onlyInput('identity');
    }

    public function quickLogin(Request $request): RedirectResponse
    {
        $user = User::where('email', 'karan.mehra@maba.ac.id')->first()
            ?? User::first();

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('toast', [
                'title' => 'Log Masuk Pantas Berjaya!',
                'message' => 'Selamat kembali ke OspekQuest, '.$user->name.'!',
                'icon' => '🚀',
            ]);
        }

        return redirect()->route('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('toast', [
            'title' => 'Log Keluar Berjaya',
            'message' => 'Jumpa lagi di sesi orientasi seterusnya!',
            'icon' => '👋',
        ]);
    }
}
