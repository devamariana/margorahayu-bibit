<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 1. PASTIKAN INI KE TABEL USERS
    protected $table = 'users';

    // 2. PERBAIKAN: Tambahkan 'email' agar bisa disimpan saat register
    protected $fillable = [
        'username', 
        'email',    // Wajib ada untuk revisi dosen
        'password', 
        'role',
    ];

    // 3. RELASI KE TABEL PETANI (Untuk ambil profil)
    public function petani()
    {
        return $this->hasOne(Petani::class, 'user_id');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}