<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Simpan ID Gudang / Toko penanggung jawab ke Session
            if ($user->role === 'warehouse_supervisor' && $user->warehouse_id) {
                session(['warehouse_id' => $user->warehouse_id]);
            }

            if ($user->role === 'cashier' && $user->store_id) {
                session(['store_id' => $user->store_id]);
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, '.$user->username);
        }

        throw ValidationException::withMessages([
            'username' => 'Username atau password yang dimasukkan salah.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar.');
    }
}
