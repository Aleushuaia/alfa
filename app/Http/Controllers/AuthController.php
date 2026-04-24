<?php

namespace App\Http\Controllers;

use App\Services\UnidadActivaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('pdf-analyzer.form');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        // ── Modo dev: login solo con email, cualquier contraseña o sin ella ──
        if (config('app.dev_login')) {
            $request->validate(['email' => ['required', 'email']]);

            $user = \App\Models\User::where('email', $request->input('email'))->first();

            if (!$user) {
                return back()->withErrors([
                    'email' => 'No se encontró un usuario con ese correo electrónico.',
                ])->onlyInput('email');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            app(UnidadActivaService::class)->initAfterLogin($user);

            return redirect()->intended(route('pdf-analyzer.form'));
        }

        // ── Modo normal ────────────────────────────────────────────────────────
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            app(UnidadActivaService::class)->initAfterLogin(Auth::user());
            return redirect()->intended(route('pdf-analyzer.form'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no son válidas.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        app(UnidadActivaService::class)->clear();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

