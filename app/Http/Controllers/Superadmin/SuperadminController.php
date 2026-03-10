<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Petani;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperadminController extends Controller
{
    /**
     * Menampilkan Dashboard Master Superadmin
     */
    public function dashboard()
    {
        $totalAdmin = User::where('role', 'admin')->count();
        $totalPetani = Petani::count();
        $admins = User::where('role', 'admin')->latest()->take(10)->get();

        return view('layouts.superadmin.dashboard', compact('totalAdmin', 'totalPetani', 'admins'));
    }

    /**
     * Menampilkan Data Admin
     */
    public function dataAdmin()
    {
        $admins = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        return view('layouts.superadmin.data_admin', compact('admins'));
    }

    /**
     * Menyimpan Akun Admin Baru
     */
    public function storeAdmin(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        return back()->with('success', 'Admin ' . $request->username . ' berhasil ditambahkan!');
    }

    /**
     * Menghapus Akun Admin
     */
    public function hapusAdmin($id)
    {
        $admin = User::findOrFail($id);
        
        // Proteksi sederhana agar tidak bingung admin mendelete dirinya sendiri atau admin terakhir
        if ($admin->role !== 'admin') {
            return back()->with('error', 'Hanya role Admin yang bisa dihapus lewat sini.');
        }

        $admin->delete();

        return back()->with('success', 'Akun admin berhasil dihapus!');
    }
}
