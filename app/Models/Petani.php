<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Petani extends Model
{
    use HasFactory;

    // Nama tabel di SQLyog kamu
    protected $table = 'petanis';

    // Daftar kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'no_hp',         // TAMBAHKAN INI agar no telp masuk database
        'nik',
        'nama_lengkap',
        'alamat',
        'luas_lahan',
        'foto_ktp',
        'foto_kk',
        'status',
        'jatah_tambahan', // WAJIB ADA agar fitur pindah jatah Admin tersimpan
    ];

    // Relasi balik ke User (Satu profil ini punya satu akun login)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}