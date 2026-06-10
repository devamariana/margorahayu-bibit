<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    protected $table = 'periodes';
    
    // PERBAIKAN: Hanya menambahkan 'musim' di bagian akhir array fillable
    protected $fillable = ['tahun', 'tanggal_mulai', 'tanggal_selesai', 'status', 'musim'];
    
    public function bibits()
    {
        return $this->hasMany(Bibit::class, 'periode_id');
    }
}