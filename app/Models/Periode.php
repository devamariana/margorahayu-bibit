<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    protected $table = 'periodes';
    protected $fillable = ['tahun', 'tanggal_mulai', 'tanggal_selesai', 'status'];
}
