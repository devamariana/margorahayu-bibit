<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Petani;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
class RegisterController extends Controller
{
    public function index()
    {
        return view('auth.register');
    }

    public function store(Request $request)
{
    // 1. Validasi Input
    $request->validate([
        'username' => 'required|string|max:255|unique:users,username',
        'no_hp'    => 'required|string|min:10|max:15|unique:petanis,no_hp',
        'password' => [
            'required',
            'string',
            'min:8',
            'regex:/[a-z]/',
            'regex:/[0-9]/',
            'confirmed'
        ],
    ], [
        'password.regex' => 'Password harus mengandung kombinasi huruf (minimal 1) dan angka (minimal 1).',
        'no_hp.unique'   => 'Nomor WhatsApp sudah didaftarkan sebelumnya.', 
        'username.unique'=> 'Username telah digunakan, silakan pilih yang lain.',
        'password.min'   => 'Password minimal 8 karakter.'
    ]);
    
    // 2. Buat Kode OTP Acak 6 Digit
    $otpCode = mt_rand(100000, 999999);
    
    // 3. Simpan data kedalam Laravel Session untuk diteruskan ke halaman Verifikasi
    Session::put('register_data', [
        'username' => $request->username,
        'no_hp'    => $request->no_hp,
        'password' => $request->password, // Password asli akan di hash saat verifikasi benar
    ]);
    Session::put('register_otp', $otpCode);
    Session::put('register_time', time());
    
    // 4. Kirim OTP via WhatsApp
    $fonnteToken = env('FONNTE_TOKEN', 'uSwGNgmjw2wNtXU8CxmN'); // Default token fallback just in case env is cached null
    if (!empty($fonnteToken) && $fonnteToken != 'your_fonnte_token_here') {
        try {
            $pesan = "Halo *" . $request->username . "* 👋\n\n"
                   . "Kode Verifikasi (OTP) untuk pendaftaran akun *Si Margo Rahayu II* Anda adalah:\n\n"
                   . "*" . $otpCode . "*\n\n"
                   . "⚠️ _Pastikan Anda tidak membagikan kode rahasia ini kepada siapa pun!_\n\n"
                   . "Jika Anda merasa tidak melakukan pendaftaran, abaikan pesan ini.";

            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => $fonnteToken,
            ])->post('https://api.fonnte.com/send', [
                'target' => $request->no_hp,
                'message' => $pesan,
                'delay' => '2'
            ]);
            
            // Check body response from Fonnte
            $resBody = $response->json();
            if ($resBody && isset($resBody['status']) && $resBody['status'] === false) {
                return back()->withInput()->withErrors(['no_hp' => 'WhatsApp Gagal Terkirim: ' . ($resBody['reason'] ?? 'Unknown Error API Fonnte')]);
            }
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['no_hp' => 'Sistem gagal menghubungi server WhatsApp. ' . $e->getMessage()]);
        }
    } else {
        return back()->withInput()->withErrors(['no_hp' => 'Token WhatsApp (Fonnte) belum disetting secara benar.']);
    }
    
    return redirect()->route('register.verify')->with('success', 'Kode OTP telah berhasil dikirim ke WhatsApp Anda! Silakan masukkan 6 digit kode tersebut.');
}

// METHOD KE 2: Tampilkan form verifikasi
public function showVerificationForm()
{
    if (!Session::has('register_otp')) {
        return redirect()->route('register')->withErrors(['Sesi pendaftaran Anda telah kedaluwarsa, silakan coba lagi.']);
    }
    
    return view('auth.verify-otp');
}

// METHOD KE 3: Verifikasi OTP dan Masukkan ke Database
public function verifyOtp(Request $request)
{
    // Validasi input
    $request->validate([
        'otp' => 'required|numeric|digits:6',
    ], [
        'otp.required' => 'Kode OTP wajib diisi.',
        'otp.digits' => 'Kode OTP harus 6 angka.'
    ]);
    
    $sessOtp  = Session::get('register_otp');
    $sessData = Session::get('register_data');

    // Jika sesi habis di tengah jalan
    if (!$sessOtp || !$sessData) {
        return redirect()->route('register')->withErrors(['Sesi keamanan telah berakhir. Silakan ulangi mendaftar.']);
    }

    // Jika salah kode OTPnya
    if ((int)$request->otp !== (int)$sessOtp) {
        return back()->withErrors(['otp' => 'Kode Verifikasi OTP salah! Silakan periksa kembali pesan dari Fonnte.']);
    }
    
    // Jika BENAR -> Masukkan ke DB
    DB::beginTransaction();

    try {
        // A. Insert Users table
        $user = User::create([
            'username' => $sessData['username'],
            'password' => Hash::make($sessData['password']),
            'role'     => 'petani',
        ]);

        // B. Insert Petanis table
        Petani::create([
            'user_id'      => $user->id,
            'no_hp'        => $sessData['no_hp'], 
            'nama_lengkap' => $sessData['username'],
            'nik'          => '-', 
            'alamat'       => '-',
            'luas_lahan'   => 0,
            'status'       => 'pending',
            'foto_ktp'     => '', 
            'foto_kk'      => '',
        ]);

        DB::commit(); 
        
        // C. Bersihkan Sesi
        Session::forget('register_data');
        Session::forget('register_otp');

        // D. Pesan Notifikasi Berhasil Jadi Member
        $fonnteToken = env('FONNTE_TOKEN', 'uSwGNgmjw2wNtXU8CxmN');
        if (!empty($fonnteToken) && $fonnteToken != 'your_fonnte_token_here') {
            try {
                $pesan = "🎉 *PENDAFTARAN BERHASIL* 🎉\n\n"
                       . "Halo *" . $sessData['username'] . "*,\n\n"
                       . "Akun Anda di *Si Margo Rahayu II* berhasil terverifikasi.\n"
                       . "Silakan login menggunakan Username & Password Anda.\n\n"
                       . "Selamat menjalankan hari Anda! 🌱🌤️";
                Http::withoutVerifying()->withHeaders([
                    'Authorization' => $fonnteToken,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $sessData['no_hp'],
                    'message' => $pesan,
                    'delay' => '2' // Delay sedikit agar tidak bertubrukan dgn pesan otp sebelumnya jika cepat
                ]);
            } catch (\Exception $e) {}
        }
        
        // E. Alihkan ke Login
        return redirect()->route('login')->with('success', 'Akun berhasil diverifikasi! Silakan login.');

    } catch (\Exception $e) {
        DB::rollback();
        return redirect()->route('register')->withErrors(['username' => 'Terjadi kendala pada database: ' . $e->getMessage()]);
    }
}

// METHOD KE 4: Resend/Kirim Ulang OTP
public function resendOtp()
{
    $sessData = Session::get('register_data');
    if (!$sessData) {
        return redirect()->route('register')->withErrors(['Sesi telah berakhir, silakan mendaftar ulang.']);
    }

    // Hindari Spam: Cek apakah sudah 1 menit berlalu
    $lastTime = Session::get('register_time', 0);
    $now = time();
    $diff = $now - $lastTime;

    if ($diff < 60) {
        $wait = 60 - $diff;
        return back()->withErrors(['otp' => 'Mohon tunggu ' . $wait . ' detik sebelum meminta OTP ulang.']);
    }

    // Buat kode OTP baru
    $newOtp = mt_rand(100000, 999999);
    Session::put('register_otp', $newOtp);
    Session::put('register_time', time()); // Reset timer 1 menit

    // Kirim ulang
    $fonnteToken = env('FONNTE_TOKEN', 'uSwGNgmjw2wNtXU8CxmN');
    if (!empty($fonnteToken) && $fonnteToken != 'your_fonnte_token_here') {
        try {
            $pesan = "Halo *" . $sessData['username'] . "* 👋\n\n"
                   . "Anda baru saja meminta kode OTP baru. Kode Verifikasi Anda adalah:\n\n"
                   . "*" . $newOtp . "*\n\n"
                   . "⚠️ _Pastikan Anda tidak membagikan kode rahasia ini kepada siapa pun!_\n\n";

            Http::withoutVerifying()->withHeaders([
                'Authorization' => $fonnteToken,
            ])->post('https://api.fonnte.com/send', [
                'target' => $sessData['no_hp'],
                'message' => $pesan,
            ]);
        } catch (\Exception $e) {}
    }

    return back()->with('success', 'Kode OTP terbaru telah dikirim ulang ke WhatsApp Anda.');
}

}