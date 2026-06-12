<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    protected $fillable = [
        'petani_id',
        'lahan_id',
        'bibit_id',
        'periode_id',
        'status',
        'catatan',
    ];

    public function petani()
    {
        return $this->belongsTo(Petani::class);
    }

    public function lahan()
    {
        return $this->belongsTo(Lahan::class);
    }

    public function bibit()
    {
        return $this->belongsTo(Bibit::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }
}
