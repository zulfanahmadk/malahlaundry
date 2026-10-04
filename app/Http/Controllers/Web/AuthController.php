<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if ($request->user()->active && ($request->user()->isOwner() || $request->user()->isAdmin())) {
                return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attemptWhen(['username' => $credentials['username'], 'password' => $credentials['password'], 'active' => true], fn ($user) => $user->isOwner() || $user->isAdmin(), $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->user()->forceFill(['last_login_at' => now()])->save();
            return $request->user()->isAdmin()
                ? redirect()->route('admin.dashboard') : redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'username' => 'Login gagal. Gunakan akun owner atau admin yang aktif dengan username dan password yang sesuai.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->attributes->set('audit_actor', $request->user()?->only(['id', 'role']));
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
