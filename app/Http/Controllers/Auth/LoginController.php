<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Tambahkan ini
use Illuminate\Support\Facades\Hash; // Tambahkan ini

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('username', 'password');

        // 2. Proses Login
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();

            // 3. Logika Pengalihan berdasarkan ROLE
            if ($user->role === 'superadmin') {
                return redirect()->intended(route('superadmin.dashboard'));
            } elseif ($user->role === 'admin') {
                return redirect()->intended(route('admin.dashboard'));
            } 
            
            return redirect()->intended(route('petani.dashboard'));
        }

        // --- TAMBAHAN DEBUG UNTUK NOVAN ---
        // Jika gagal, kita cek apakah usernamenya ada di DB tapi passwordnya yang salah
        $userCheck = User::where('username', $request->username)->first();
        if (!$userCheck) {
            $errorMessage = 'Username tidak terdaftar di database.';
        } else {
            $errorMessage = 'Password yang Anda masukkan salah.';
        }

        return back()->withErrors([
            'username' => $errorMessage,
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'Anda telah berhasil keluar.');
    }
}