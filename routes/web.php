<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Petani\PetaniController;
use App\Http\Controllers\Admin\BibitController;
use App\Http\Controllers\Petani\TransaksiController; 
use App\Http\Controllers\Superadmin\SuperadminController;
use App\Http\Controllers\Admin\PeriodeController;

// --- HALAMAN PUBLIK (Bisa diakses tanpa login) ---
Route::get('/', function () {
    return redirect('/login');
});

// Guest Middleware: Kalau sudah login, tidak bisa buka halaman login/register lagi
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']); 
    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/register/verify', [RegisterController::class, 'showVerificationForm'])->name('register.verify');
    Route::post('/register/verify', [RegisterController::class, 'verifyOtp'])->name('register.verify.post');
    Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp'])->name('register.resend_otp');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// --- HALAMAN TERPROTEKSI (Wajib Login) ---
Route::middleware(['auth'])->group(function () {

    // 1. KHUSUS ROLE: SUPERADMIN
    Route::middleware(['checkRole:superadmin'])->group(function () {
        Route::get('/superadmin/dashboard', [SuperadminController::class, 'dashboard'])->name('superadmin.dashboard');
        Route::get('/superadmin/data-admin', [SuperadminController::class, 'dataAdmin'])->name('superadmin.data_admin');
        Route::post('/superadmin/store-admin', [SuperadminController::class, 'storeAdmin'])->name('superadmin.store_admin');
        Route::delete('/superadmin/hapus-admin/{id}', [SuperadminController::class, 'hapusAdmin'])->name('superadmin.hapus_admin');
    });

    // 2. KHUSUS ROLE: ADMIN
    Route::middleware(['checkRole:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

        // Kelola Data Petani
        Route::get('/admin/data-petani', [AdminController::class, 'dataPetani'])->name('admin.data_petani');
        Route::post('/admin/verifikasi-petani/{id}', [AdminController::class, 'verifikasiPetani'])->name('admin.verifikasi_petani');
        Route::delete('/admin/petani/hapus/{id}', [AdminController::class, 'hapusPetani'])->name('admin.hapus_petani');
        Route::post('/transaksi/store', [TransaksiController::class, 'store'])->name('petani.transaksi.store');

        // Kelola Data Bibit
        Route::get('/admin/data-bibit', [BibitController::class, 'index'])->name('admin.data_bibit');
        Route::post('/admin/data-bibit/store', [BibitController::class, 'store'])->name('admin.store_bibit');
        Route::put('/admin/data-bibit/update/{id}', [BibitController::class, 'update'])->name('admin.update_bibit');
        Route::delete('/admin/data-bibit/destroy/{id}', [BibitController::class, 'destroy'])->name('admin.data_bibit.destroy');

        // Pindah Jatah (Admin)
        Route::get('/admin/pindah-jatah', [AdminController::class, 'pindahJatah'])->name('admin.pindah_jatah');
        Route::post('/admin/pindah-jatah/proses', [AdminController::class, 'prosesPindahJatah'])->name('admin.proses_pindah');

        // Menu Admin Lainnya
        Route::get('/admin/data-periode', [PeriodeController::class, 'index'])->name('admin.data_periode');
        Route::post('/admin/data-periode/store', [PeriodeController::class, 'store'])->name('admin.data_periode.store');
        Route::delete('/admin/data-periode/hapus/{id}', [PeriodeController::class, 'destroy'])->name('admin.data_periode.destroy');
        Route::get('/admin/data-lahan', [AdminController::class, 'dataLahan'])->name('admin.data_lahan');
        Route::post('/admin/verifikasi-lahan/{id}', [AdminController::class, 'verifikasiLahan'])->name('admin.verifikasi_lahan');
        Route::get('/admin/riwayat-transaksi', [AdminController::class, 'riwayatTransaksi'])->name('admin.riwayat_transaksi');
        Route::post('/admin/verifikasi-transaksi/{id}', [AdminController::class, 'verifikasiTransaksi'])->name('admin.verifikasi_transaksi');
        Route::get('/admin/notifikasi/baca-semua', [AdminController::class, 'bacaSemuaNotifikasi']);
    });

    // 2. KHUSUS ROLE: PETANI
    Route::middleware(['checkRole:petani'])->group(function () {
        Route::get('/dashboard-petani', [PetaniController::class, 'dashboard'])->name('petani.dashboard');
        
        // Profil Petani
        Route::get('/profil-petani', [PetaniController::class, 'index'])->name('petani.profil');
        Route::post('/profil-petani/update', [PetaniController::class, 'updateProfil'])->name('petani.update');

        // REVISI: Kelola Lahan Petani (Banyak Lahan)
        Route::get('/petani/lahan', [PetaniController::class, 'lahan'])->name('petani.lahan');
        Route::post('/petani/lahan/store', [PetaniController::class, 'storeLahan'])->name('petani.store_lahan');
        Route::delete('/petani/lahan/{id}', [PetaniController::class, 'hapusLahan'])->name('petani.hapus_lahan');

        // Informasi & Beli Bibit
        Route::get('/beli-bibit', [PetaniController::class, 'beliBibit'])->name('petani.beli_bibit');
        Route::post('/beli-bibit/proses', [PetaniController::class, 'prosesBeliBibit'])->name('petani.proses_beli');
        Route::get('/beli-bibit/bayar/{id}', [PetaniController::class, 'bayarBibit'])->name('petani.bayar_bibit');
        Route::delete('/beli-bibit/batal/{id}', [PetaniController::class, 'batalBayar'])->name('petani.batal_bayar');
        Route::get('/beli-bibit/sukses-bayar/{id}', [PetaniController::class, 'suksesBayarBibit'])->name('petani.sukses_bayar');
        Route::get('/riwayat-pembelian', [PetaniController::class, 'riwayat'])->name('petani.riwayat');
        
        // Notifikasi
        Route::get('/petani/notifikasi/baca-semua', [PetaniController::class, 'bacaSemuaNotifikasi']);
    });

    // Route Umum (Bisa diakses Admin & Petani)
    Route::get('/notifikasi/baca/{id}', [PetaniController::class, 'bacaDanArahkan'])->name('notifikasi.baca');
});