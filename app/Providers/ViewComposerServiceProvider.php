<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Petani;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Kode ini akan membagikan data $petani ke seluruh VIEW (halaman)
        View::composer('*', function ($view) {
            if (Auth::check()) {
                // Mencari data di tabel petanis berdasarkan siapa yang sedang login
                $petani = Petani::where('user_id', Auth::id())->first();
                $view->with('petani', $petani);
            }
        });
    }
}