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
        // 1. Cari user di guard yang sesuai dengan role yang diminta
        $user = null;
        
        // Kita cek guard mana yang sedang aktif DAN punya role yang cocok
        foreach ($roles as $role) {
            if (Auth::guard($role)->check()) {
                $user = Auth::guard($role)->user();
                break;
            }
        }

        // 2. Jika tidak ketemu di guard spesifik, coba cek guard 'web' atau guard mana saja yang aktif sebagai fallback
        if (!$user) {
            $guards = ['superadmin', 'admin', 'petani', 'web'];
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    break;
                }
            }
        }

        // 3. Jika tetap tidak ada user yang login
        if (!$user) {
            return redirect('/login');
        }

        // 4. Cek apakah role user ada di dalam daftar role yang diizinkan
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // 5. Jika role tidak cocok (misal admin mau masuk ke halaman petani tapi tidak login sebagai petani)
        return abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
    }
}