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
use App\Http\Controllers\WebhookController;

// --- WEBHOOK MIDTRANS (TANPA LOGIN, TANPA CSRF) ---
Route::post('/api/midtrans-callback', [WebhookController::class, 'midtransCallback']);

// --- HALAMAN PUBLIK (Bisa diakses tanpa login) ---
Route::get('/', function () {
    return redirect('/login');
});

// Halaman login dan register sekarang bisa dibuka kapan saja (untuk multi-role)
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']); 
Route::get('/register', [RegisterController::class, 'index'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
Route::get('/register/verify', [RegisterController::class, 'showVerificationForm'])->name('register.verify');
Route::post('/register/verify', [RegisterController::class, 'verifyOtp'])->name('register.verify.post');
Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp'])->name('register.resend_otp');

// Logout kita buat any agar bisa diakses via link (GET) maupun form (POST)
Route::any('/logout', [LoginController::class, 'logout'])->name('logout');


// --- HALAMAN TERPROTEKSI (Wajib Login) ---
// Kita tidak pakai middleware 'auth' global agar guard tidak tabrakan

// 1. KHUSUS ROLE: SUPERADMIN
Route::middleware(['auth:superadmin', 'checkRole:superadmin'])->group(function () {
    Route::get('/superadmin/dashboard', [SuperadminController::class, 'dashboard'])->name('superadmin.dashboard');
    Route::get('/superadmin/data-admin', [SuperadminController::class, 'dataAdmin'])->name('superadmin.data_admin');
    Route::post('/superadmin/store-admin', [SuperadminController::class, 'storeAdmin'])->name('superadmin.store_admin');
    Route::delete('/superadmin/hapus-admin/{id}', [SuperadminController::class, 'hapusAdmin'])->name('superadmin.hapus_admin');
});

// 2. KHUSUS ROLE: ADMIN
Route::middleware(['auth:admin', 'checkRole:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    // Kelola Data Petani
    Route::get('/admin/data-petani', [AdminController::class, 'dataPetani'])->name('admin.data_petani'); // Menampilkan data petani
    Route::get('/admin/data-petani/pdf', [AdminController::class, 'cetakPetaniPdf'])->name('admin.petani.pdf'); // Print PDF
    Route::post('/admin/verifikasi-petani/{id}', [AdminController::class, 'verifikasiPetani'])->name('admin.verifikasi_petani');
    Route::delete('/admin/petani/hapus/{id}', [AdminController::class, 'hapusPetani'])->name('admin.hapus_petani');
    Route::post('/transaksi/store', [TransaksiController::class, 'store'])->name('petani.transaksi.store');

    // Kelola Data Bibit
    Route::get('/admin/data-bibit', [BibitController::class, 'index'])->name('admin.data_bibit');
    Route::post('/admin/data-bibit/store', [BibitController::class, 'store'])->name('admin.store_bibit');
    Route::put('/admin/data-bibit/update/{id}', [BibitController::class, 'update'])->name('admin.update_bibit');
    Route::delete('/admin/data-bibit/destroy/{id}', [BibitController::class, 'destroy'])->name('admin.data_bibit.destroy');
    Route::post('/admin/data-bibit/buka/{id}', [BibitController::class, 'bukaDistribusi'])->name('admin.buka_bibit');
    Route::post('/admin/data-bibit/tutup/{id}', [BibitController::class, 'tutupDistribusi'])->name('admin.tutup_bibit');
    Route::get('/admin/data-bibit/detail/{id}', [BibitController::class, 'detailDistribusi'])->name('admin.detail_bibit');

    // Kelola Pengalihan Jatah (Admin)
    Route::get('/admin/transfer-jatah', [AdminController::class, 'pindahJatah'])->name('admin.transfer_jatah');
    Route::post('/admin/transfer-jatah/proses', [AdminController::class, 'prosesPindahJatah'])->name('admin.proses_transfer');

    // Menu Admin Lainnya
    Route::get('/admin/data-periode', [PeriodeController::class, 'index'])->name('admin.data_periode');
    Route::post('/admin/data-periode/store', [PeriodeController::class, 'store'])->name('admin.data_periode.store');
    Route::put('/admin/data-periode/update/{id}', [PeriodeController::class, 'update'])->name('admin.data_periode.update');
    Route::delete('/admin/data-periode/hapus/{id}', [PeriodeController::class, 'destroy'])->name('admin.data_periode.destroy');
    Route::get('/admin/data-lahan', [AdminController::class, 'dataLahan'])->name('admin.data_lahan');
    Route::post('/admin/verifikasi-lahan/{id}', [AdminController::class, 'verifikasiLahan'])->name('admin.verifikasi_lahan');
    Route::get('/admin/riwayat-transaksi', [AdminController::class, 'riwayatTransaksi'])->name('admin.riwayat_transaksi');
    Route::post('/admin/verifikasi-transaksi/{id}', [AdminController::class, 'verifikasiTransaksi'])->name('admin.verifikasi_transaksi');
    Route::get('/admin/notifikasi/baca-semua', [AdminController::class, 'bacaSemuaNotifikasi']);

    // Kelola Laporan Excel & PDF (Di-nonaktifkan atas permintaan user)
    // Route::get('/admin/laporan', [AdminController::class, 'halamanLaporan'])->name('admin.laporan');
    Route::get('/admin/export/excel', [AdminController::class, 'exportExcel'])->name('admin.export.excel');
    Route::get('/admin/export/pdf', [AdminController::class, 'exportPdf'])->name('admin.export.pdf');
});

// 3. KHUSUS ROLE: PETANI
Route::middleware(['auth:petani', 'checkRole:petani'])->group(function () {
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
    Route::post('/beli-bibit/upload-bukti/{id}', [PetaniController::class, 'uploadBukti'])->name('petani.upload_bukti');
    Route::get('/beli-bibit/bayar/{id}', [PetaniController::class, 'bayarBibit'])->name('petani.bayar_bibit');
    Route::delete('/beli-bibit/batal/{id}', [PetaniController::class, 'batalBayar'])->name('petani.batal_bayar');
    Route::get('/beli-bibit/sukses-bayar/{id}', [PetaniController::class, 'suksesBayarBibit'])->name('petani.sukses_bayar');
    Route::get('/riwayat-pembelian', [PetaniController::class, 'riwayat'])->name('petani.riwayat');
    Route::get('/riwayat-pembelian/sync/{id}', [PetaniController::class, 'syncStatus'])->name('petani.riwayat.sync');
    
    // Pengalihan Jatah (Transfer Hak ke Petani Lain)
    Route::get('/petani/transfer-jatah', [PetaniController::class, 'transferJatah'])->name('petani.transfer_jatah');
    Route::post('/petani/transfer-jatah/proses', [PetaniController::class, 'prosesTransferJatah'])->name('petani.proses_transfer');

    // Notifikasi
    Route::get('/petani/notifikasi/baca-semua', [PetaniController::class, 'bacaSemuaNotifikasi']);
});

// Route Umum (Bisa diakses Admin & Petani) - Kita pakai auth:superadmin,admin,petani agar salah satu saja login sudah cukup
Route::middleware(['auth:admin,petani'])->group(function () {
    Route::get('/notifikasi/baca/{id}', [PetaniController::class, 'bacaDanArahkan'])->name('notifikasi.baca');
    
    // Fitur Cetak (Bisa diakses Petani & Admin/Ketua)
    Route::get('/pembelian/invoice/{id}', [PetaniController::class, 'cetakInvoice'])->name('petani.invoice');
    Route::get('/pembelian/struk/{id}', [PetaniController::class, 'cetakStruk'])->name('petani.struk');
});