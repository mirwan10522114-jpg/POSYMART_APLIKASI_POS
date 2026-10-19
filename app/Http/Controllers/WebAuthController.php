<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    /**
     * Tampilkan formulir login admin.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('pages.login');
    }

    /**
     * Proses autentikasi login staf / admin.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email atau Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $identifier = trim($credentials['email']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // Cari pengguna berdasarkan email, username (name), atau ID
        $user = User::where('email', $identifier)
            ->orWhere('name', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        $valid = false;
        if ($user) {
            // Cocokkan password hashed atau demo password fallback
            if (Hash::check($password, $user->password) || $password === 'posymart-demo' || $password === 'admin123') {
                $valid = true;
            }
        }

        if (!$user || !$valid) {
            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        if (!$user->is_active) {
            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => 'Akun ini sedang dinonaktifkan. Hubungi administrator.',
            ]);
        }

        // Login session
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->update(['last_active_at' => now()]);

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Selamat datang kembali, ' . $user->name . '!');
    }

    /**
     * Logout pengguna dari sesi web.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Anda telah berhasil keluar dari sistem.');
    }
}
