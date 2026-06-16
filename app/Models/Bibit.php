<?php

namespace App\Models; // PERBAIKAN: Cukup gunakan satu namespace Models saja

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bibit extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan di database
     */
    protected $table = 'bibits';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignable)
     */
    protected $fillable = [
        'nama_bibit',
        'jenis',
        'kategori_musim',
        'sumber_pasokan',
        'stok',
        'stok_awal',
        'harga_subsidi',
        'deskripsi',
        'gambar',
        'status',
        'is_buka',
        'tanggal_buka',
        'total_luas_snapshot',
        'periode_id',
    ];

    /**
     * Relasi ke Tabel Periode
     */
    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    /**
     * Menjamin tipe data stok dan harga selalu angka
     */
    protected $casts = [
        'stok' => 'float',
        'stok_awal' => 'float',
        'harga_subsidi' => 'integer',
    ];

    /**
     * Primary Key
     */
    protected $primaryKey = 'id';

    /**
     * Aktifkan timestamps untuk mencatat Tanggal Masuk otomatis
     */
    public $timestamps = true;

    /**
     * Relasi ke Tabel Transaksi
     */
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class, 'bibit_id');
    }

    public function petanis()
    {
        return $this->belongsToMany(Petani::class, 'bibit_petani')
                    ->withPivot('kuota_maksimal')
                    ->withTimestamps();
    }

    public function pengajuans()
    {
        return $this->hasMany(Pengajuan::class, 'bibit_id');
    }

    /**
     * Hitung stok awal yang sebenarnya.
     * Jika stok_awal tersimpan di DB, gunakan itu.
     * Jika tidak, rekonstruksi:
     *   stok_awal_real = stok_saat_ini + total_dibeli - total_dikembalikan_ke_admin
     *
     * Mengapa dikurangi total_dikembalikan_ke_admin?
     * Karena saat petani mengembalikan jatah, bibit->stok bertambah (stok admin naik kembali).
     * Tanpa pengurangan ini, stok_awal_real akan ikut naik setiap ada pengembalian,
     * yang menyebabkan hakLahanIni petani tampak membesar padahal harusnya tetap.
     */
    public function getStokAwalRealAttribute()
    {
        if (!empty($this->stok_awal) && $this->stok_awal > 0) {
            return $this->stok_awal;
        }

        $totalDibeli = $this->transaksis()
            ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak', 'cancel', 'expire'])
            ->sum('jumlah_beli');

        // Jatah yang dikembalikan petani ke admin (penerima_id = null di PindahJatah)
        // menambah stok saat ini tapi BUKAN bagian dari stok awal asli.
        $totalDikembalikanKeAdmin = \App\Models\PindahJatah::where('bibit_id', $this->id)
            ->whereNull('penerima_id')
            ->sum('jumlah_kg');

        return $this->stok + $totalDibeli - $totalDikembalikanKeAdmin;
    }
}