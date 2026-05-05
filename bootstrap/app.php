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
        
        // --- PENGECUALIAN CSRF ---
        $middleware->validateCsrfTokens(except: [
            '/api/midtrans-callback',
            '/logout',
        ]);

        // --- TAMBAHKAN INI AGAR 'checkRole' DI RECOGNIZED ---
        $middleware->alias([
            'checkRole' => \App\Http\Middleware\CheckRole::class,
        ]);

        // Mengarahkan tamu yang tidak terautentikasi kembali ke login
        $middleware->redirectGuestsTo('/login');

        // Biarkan user bisa buka halaman login meskipun sudah login salah satu role
        // Agar bisa login role kedua
        $middleware->redirectUsersTo(fn() => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();