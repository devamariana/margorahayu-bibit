# TODO - Perombakan Sistem Musim (Periode Admin -> Transaksi Petani)

## Database
- [ ] Buat migration baru: `add_musim_to_periodes_table.php` dengan kolom `musim` enum('kemarau','penghujan') (default/nullable sesuai kebutuhan).
- [ ] Jalankan `php artisan migrate` dan pastikan kolom `periodes.musim` tersedia.

## Model
- [ ] Update `app/Models/Periode.php`: tambahkan `musim` ke `$fillable`.

## Admin Periode
- [ ] Update `app/Http/Controllers/Admin/PeriodeController.php`:
  - Validasi `musim` pada store/update.
  - Pastikan `musim` ikut tersimpan.
- [ ] Update `resources/views/layouts/admin/data_periode.blade.php`:
  - Tambahkan dropdown `musim` di modal tambah & edit.
  - Pastikan nilai edit terisi dari `$p->musim`.

## Petani: Ambil periode aktif
- [ ] Update `resources/views/petani/beli_bibit.blade.php`:
  - Tambahkan alert statis "Periode Aktif Saat Ini: ...".
  - Ambil `$musimAktif` dari controller.
- [ ] Update controller yang mengirim view `beli_bibit` (kemungkinan `PetaniController@beliBibit`) untuk:
  - ambil `Periode::where('status','aktif')->first()`
  - set `$musimAktif` dan kirim ke view.

## Gating bibit berdasarkan musim aktif
- [ ] Di `beli_bibit.blade.php`:
  - Jika `kategori_musim` bibit tidak sesuai `$musimAktif`:
    - render card tapi input dropdown "Pilih Lokasi Lahan" disabled
    - tombol konfirmasi bayar dimatikan
    - tampilkan teks peringatan merah di bawah kartu
  - Pastikan perhitungan nominal & JS tidak rusak.

## Testing
- [ ] Jalankan flow end-to-end:
  1) Set periode aktif + musim di admin.
  2) Masuk halaman beli bibit petani.
  3) Pastikan bibit salah musim hanya bisa dilihat (disabled + warning).
  4) Bibit sesuai musim bisa dipesan seperti biasa.

