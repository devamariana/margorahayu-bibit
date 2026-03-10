<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // --- TAMBAHKAN INI AGAR 'checkRole' DI RECOGNIZED ---
        $middleware->alias([
            'checkRole' => \App\Http\Middleware\CheckRole::class,
        ]);

        // Mengarahkan tamu yang tidak terautentikasi kembali ke login
        $middleware->redirectGuestsTo('/login');

        // Mengarahkan user yang SUDAH login agar tidak bisa buka halaman login lagi
        $middleware->redirectUsersTo(function (Request $request) {
            if (auth()->user()->role === 'admin') {
                return route('admin.dashboard');
            }
            return route('petani.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();