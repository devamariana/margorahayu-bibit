<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek apakah sudah login
        if (!Auth::check()) {
            return redirect('/login');
        }

        // 2. Ambil data user yang sedang login
        $user = Auth::user();

        // 3. Cek apakah role user ada di dalam daftar role yang diizinkan di web.php
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // 4. Jika tidak punya akses (misal petani mau masuk dashboard admin)
        return abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
    }
}