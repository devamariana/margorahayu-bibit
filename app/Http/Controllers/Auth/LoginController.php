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

        // 2. Cari user untuk menentukan role
        $user = User::where('username', $request->username)->first();

        // 3. Proses Login menggunakan Guard yang sesuai
        if ($user && Auth::guard($user->role)->attempt($credentials)) {
            
            $request->session()->regenerate();

            // Logika Pengalihan berdasarkan ROLE
            if ($user->role === 'superadmin') {
                return redirect()->route('superadmin.dashboard');
            } elseif ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } 
            
            return redirect()->route('petani.dashboard');
        }

        // --- TAMBAHAN DEBUG UNTUK NOVAN ---
        if (!$user) {
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
        // Logout dari semua guard yang mungkin aktif
        Auth::guard('superadmin')->logout();
        Auth::guard('admin')->logout();
        Auth::guard('petani')->logout();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'Anda telah berhasil keluar.');
    }
}