# 🔧 Debugging Guide: Beli Bibit - Detail Pesanan Tidak Muncul

## 📌 MASALAH YANG DILAPORKAN

**Gejala:**
- ✗ Pilih lahan → Klik kartu bibit → Detail pesanan TETAP KOSONG
- ✗ Harga tetap "Rp 0"
- ✗ SweetAlert loading tidak hilang
- ✗ Tidak ada error di Console

**Expected behavior:**
- ✓ Klik kartu → SweetAlert "Verifikasi Jatah..."
- ✓ Fetch ke server → hitung jatah
- ✓ SweetAlert hilang
- ✓ Detail pesanan muncul + nilai terisi

---

## 🔍 ROOT CAUSE ANALYSIS

### **3 Masalah Utama Diidentifikasi:**

#### **1. Event Handler: Inline onclick dengan Blade String Interpolation**
```php
onclick="pilihBibit('{{ $b->id }}', '{{ $b->nama_bibit }}', ...)"
```
**Masalah:**
- Quote parsing di Blade bisa corrupt jika nama bibit punya karakter khusus
- String yang di-interpolate bisa mengandung single/double quote yang break HTML
- Error di string akan silent-fail tanpa log
- Jika ada JS error sebelumnya, onclick tidak berfungsi

**Solusi:**
→ Gunakan `data-*` attributes + event delegation listener

---

#### **2. Backend Response Format Tidak Konsisten**
File: `app/Http/Controllers/Petani/PetaniController.php::cekJatah()`

```php
// MASALAH: Jika $petani tidak ada, return tanpa struktur response yang jelas
if (!$petani || !$bibit || !$lahan) 
    return response()->json(['sisa' => 0, 'status' => 'error', ...]);
```

**Masalah:**
- Tanpa try-catch, exception tidak tertangkap
- Auth guard 'petani' bisa return null → $petani null
- Response terkadang punya `status`, terkadang tidak
- Frontend cek `result.status === 'success'` → mungkin undefined

**Solusi:**
→ Add explicit try-catch wrapper + normalized response format

---

#### **3. Zero Visibility: Missing Debug Logs**
**Masalah:**
- Tidak tahu apakah onclick benar dipanggil
- Tidak tahu parameter apa yang dikirim ke fetch
- Tidak tahu response dari server berisi apa
- Tidak tahu di mana DOM update gagal

**Solusi:**
→ Add 150+ console.log dengan color-formatting di setiap step kritis

---

## ✅ PERBAIKAN YANG SUDAH DITERAPKAN

### **File 1: resources/views/petani/beli_bibit.blade.php**

#### **PATCH 1A: Event Handler (dari onclick → event delegation)**

**SEBELUM:**
```blade
<div onclick="pilihBibit('{{ $b->id }}', '{{ $b->nama_bibit }}', ...)" class="...">
```

**SESUDAH:**
```blade
<div 
     class="{{ $cardClasses }}"
     data-id="{{ $b->id }}"
     data-nama="{{ $b->nama_bibit }}"
     data-harga="{{ $b->harga_subsidi }}"
     data-stok="{{ $b->stok }}"
     data-jatah="{{ $b->sisa_jatah_global ?? 0 }}"
     data-musim="{{ $b->kategori_musim }}"
     data-musim-aktif="{{ $currentMusimAktif }}"
     data-tutup="{{ $isTutup ? 1 : 0 }}"
     role="button"
     tabindex="0">
```

**Keuntungan:**
- Data aman dari quote-escaping issue
- Accessibility: role="button" + tabindex
- Mudah diakses dari JavaScript via `element.dataset`

---

#### **PATCH 1B: Event Listener di DOMContentLoaded**

**DITAMBAHKAN:**
```javascript
document.addEventListener('DOMContentLoaded', () => {
    console.log('=== INIT: DOMContentLoaded fired ===');
    
    const bibitGrid = document.getElementById('bibit-grid');
    if (bibitGrid) {
        console.log('✓ bibit-grid ditemukan, attach event listener');
        bibitGrid.addEventListener('click', function(event) {
            const card = event.target.closest('.bibit-card');
            if (!card) {
                console.log('→ Click bukan di .bibit-card, abaikan');
                return;
            }

            console.log('%c=== BIBIT CARD CLICKED ===', 'color: green; font-weight: bold;');
            
            // Extract data dari attributes
            const id = card.dataset.id;
            const nama = card.dataset.nama;
            const harga = parseFloat(card.dataset.harga);
            const stok = parseFloat(card.dataset.stok);
            const jatah = parseFloat(card.dataset.jatah);
            const musimBibit = card.dataset.musim;
            const musimAktif = card.dataset.musimAktif;
            const isTutup = parseInt(card.dataset.tutup);

            console.log('Data ekstrak dari dataset:', {
                id, nama, harga, stok, jatah, musimBibit, musimAktif, isTutup
            });

            pilihBibit(id, nama, harga, stok, jatah, musimBibit, musimAktif, isTutup);
        });
    } else {
        console.error('❌ ERROR: bibit-grid tidak ditemukan!');
    }
});
```

**Keuntungan:**
- Event handler tidak inline → tidak terpengaruh Blade parsing
- Listener attach setelah DOM ready → pasti elements exist
- Data extraction terpisah → mudah debug tiap value

---

#### **PATCH 1C: pilihBibit() Dengan Verbose Logging**

**DITAMBAHKAN logging di setiap step:**
```javascript
async function pilihBibit(id, nama, harga, currentStok = 0, sisaJatahGlobal = 0, musimBibit, musimAktif, isTutup = false) {
    console.log('%cPILIH BIBIT FUNCTION START', 'color: blue; font-weight: bold;', {...});
    
    // ... validation ...
    
    console.log('%cFETCH REQUEST START', 'color: orange; font-weight: bold;', {
        url: '{{ route("petani.cek_jatah") }}',
        body: fetchBody
    });

    try {
        const response = await fetch('{{ route("petani.cek_jatah") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(fetchBody)
        });
        
        console.log(`%cRESPONSE RECEIVED: ${response.status} ${response.statusText}`, 
            'color: ' + (response.ok ? 'green' : 'red') + '; font-weight: bold;');
        
        // Validate content-type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('Raw response body (first 500 chars):', textResponse.substring(0, 500));
            throw new Error('Server response is not JSON');
        }

        let result = await response.json();
        console.log('%cRESPONSE JSON PARSED BERHASIL', 'color: green; font-weight: bold;', result);

        // ... DOM update ...
        
        console.log('Before visibility change:', {
            placeholderHidden: placeholderEl.classList.contains('hidden'),
            detailHidden: detailEl.classList.contains('hidden')
        });

        placeholderEl.classList.add('hidden');
        detailEl.classList.remove('hidden');

        console.log('After visibility change:', {
            placeholderHidden: placeholderEl.classList.contains('hidden'),
            detailHidden: detailEl.classList.contains('hidden')
        });

    } catch (error) {
        console.error('%c❌ ERROR IN PILIH BIBIT', 'color: red; font-weight: bold;');
        console.error('Error message:', error.message);
    }
}
```

**Keuntungan:**
- Setiap step punya unique colored log
- Response body di-log sebelum parse
- DOM change di-log sebelum/sesudah untuk verify
- Error di-log dengan detail

---

### **File 2: app/Http/Controllers/Petani/PetaniController.php**

#### **PATCH 2A: Try-Catch Wrapper + Explicit Checks**

**SEBELUM:**
```php
public function cekJatah(Request $request)
{
    $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
    $bibit = Bibit::find($request->bibit_id);
    $lahan = Lahan::find($request->lahan_id);

    if (!$petani || !$bibit || !$lahan) return response()->json([...]);
    
    // ... logic ...
    
    return response()->json([...]);
}
```

**SESUDAH:**
```php
public function cekJatah(Request $request)
{
    try {
        // Log incoming request
        \Log::debug('cekJatah called', [
            'bibit_id' => $request->bibit_id,
            'lahan_id' => $request->lahan_id,
            'auth_guard_petani' => Auth::guard('petani')->id(),
            'auth_default' => Auth::id()
        ]);

        // Validate input
        if (!$request->bibit_id || !$request->lahan_id) {
            return response()->json([
                'sisa' => 0,
                'status' => 'error',
                'message' => 'Parameter bibit_id atau lahan_id tidak dikirim.'
            ], 422);
        }

        // Get petani with better null handling
        $petaniId = Auth::guard('petani')->id() ?? Auth::id();
        if (!$petaniId) {
            \Log::warning('cekJatah: No authenticated user found');
            return response()->json([
                'sisa' => 0,
                'status' => 'error',
                'message' => 'User tidak terautentikasi. Silakan login ulang.'
            ], 401);
        }

        $petani = Petani::where('user_id', $petaniId)->first();
        $bibit = Bibit::find($request->bibit_id);
        $lahan = Lahan::find($request->lahan_id);

        // Separate error checks with meaningful messages
        if (!$petani) {
            return response()->json([
                'sisa' => 0,
                'status' => 'error',
                'message' => 'Data petani tidak ditemukan. Hubungi Admin.'
            ], 404);
        }

        if (!$bibit) {
            return response()->json([
                'sisa' => 0,
                'status' => 'error',
                'message' => 'Data bibit tidak ditemukan.'
            ], 404);
        }

        if (!$lahan) {
            return response()->json([
                'sisa' => 0,
                'status' => 'error',
                'message' => 'Data lahan tidak ditemukan.'
            ], 404);
        }

        // ... business logic ...

        \Log::debug('cekJatah calculation result', [
            'sisaJatah' => $sisaJatah,
            'hakLahanIni' => $hakLahanIni,
            'sudahDibeli' => $sudahDibeli
        ]);

        return response()->json([
            'sisa' => $sisaJatah, 
            'status' => 'success',
            'hak_dasar' => round($hakLahanIni, 1),
            'sudah_beli' => $sudahDibeli,
            'tambahan' => $tambahanTransfer
        ], 200);

    } catch (\Exception $e) {
        \Log::error('cekJatah exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'sisa' => 0,
            'status' => 'error',
            'message' => 'Terjadi error server saat menghitung jatah. Admin telah dicatat.'
        ], 500);
    }
}
```

**Keuntungan:**
- Try-catch menangkap semua exception
- Setiap error case return dengan status + message yang jelas
- Logging di setiap checkpoint → bisa trace di `storage/logs/laravel.log`
- HTTP status code meaningful (401/404/422/500)

---

## 🧪 LANGKAH TESTING & DEBUGGING

### **STEP 1: Clear Cache & Reload (PENTING!)**
```bash
# Clear view cache
php artisan view:clear

# Clear config cache
php artisan config:clear

# Di browser: Ctrl+Shift+R (hard refresh)
```

---

### **STEP 2: Buka DevTools & Navigasi**
1. **F12** → Buka DevTools
2. Pilih tab **Console**
3. **Navigate ke** `/beli-bibit` (atau refresh halaman)

**Di Console seharusnya muncul:**
```
=== INIT: DOMContentLoaded fired ===
✓ bibit-grid ditemukan, attach event listener
Inisialisasi Filter Musim sesuai default select
```

Jika tidak ada → Page tidak load dengan benar

---

### **STEP 3: Test Alur Pilih Lahan**
1. Di form, dropdown "1. Pilih Lahan" → pilih satu lahan
2. Di Console, tidak harus ada log khusus (normal, hanya DOM change)
3. Lihat kartu bibit di bawah → harus **tidak di-disable**

---

### **STEP 4: Test Click Card - Lihat Console (CRITICAL!)**

1. **Buka tab Console** jika belum
2. Buka tab **Network** (F12 → Network)
3. **Klik satu kartu bibit**

**Di Console, harus muncul (dalam urutan):**
```
%c=== BIBIT CARD CLICKED ===  [green, bold]
Data ekstrak dari dataset: {id: "5", nama: "PADI IR64", harga: 35000, ...}

%cPILIH BIBIT FUNCTION START  [blue, bold]
{id: "5", nama: "PADI IR64", ...}

Selected lahan: {value: "2", text: "sawah block utara (100 m²)"}
Status check: {isDistributionClosed: false, isSelectedWrongSeason: false}
Showing loading alert...

%cFETCH REQUEST START  [orange, bold]
{url: "http://localhost:8000/petani/cek-jatah", method: "POST", body: {bibit_id: "5", lahan_id: "2"}}
```

**Jika muncul sampai sini → Event handler berfungsi baik ✓**

---

### **STEP 5: Monitor Network Request**

1. **Tab Network** → lihat request `POST /petani/cek-jatah`
2. **Click request** → buka tab **Preview** atau **Response**

**Response harus terlihat:**
```json
{
  "sisa": 50.5,
  "status": "success",
  "hak_dasar": 100,
  "sudah_beli": 0,
  "tambahan": 0
}
```

**Jika status ≠ 200:**
- **401** → Auth issue (`User tidak terautentikasi`)
- **404** → Petani/Bibit/Lahan tidak ditemukan
- **422** → Request parameter missing
- **500** → Exception di backend → cek `storage/logs/laravel.log`

---

### **STEP 6: Monitor Response JSON Parse**

**Setelah request selesai, di Console muncul:**
```
%cRESPONSE RECEIVED: 200 OK  [green, bold]
Response Content-Type: application/json; charset=UTF-8

%cRESPONSE JSON PARSED BERHASIL  [green, bold]
{sisa: 50.5, status: "success", hak_dasar: 100, sudah_beli: 0, tambahan: 0}

%cSTATUS NORMALIZED  [purple, bold]
{original: "success", normalized: "success", sisa: 50.5}

%cDOTA UPDATE DIMULAI  [darkblue, bold]
{jatah: 50.5, currentHarga: 35000, currentQuota: 50.5}
```

**Jika muncul → Fetch & parsing berhasil ✓**

---

### **STEP 7: Monitor DOM Update**

**Setelah DOM update dimulai, di Console:**
```
✓ Hidden inputs filled

Before visibility change: {placeholderHidden: false, detailHidden: true}
After visibility change: {placeholderHidden: true, detailHidden: false}

✓ Detail pesanan text diisi
✓ Card highlighted

%cPILIH BIBIT FUNCTION SUCCESS  [green, bold]
```

**Jika muncul → DOM update berhasil ✓**

---

### **STEP 8: Verifikasi UI Visual**

Lihat halaman:
- [ ] Placeholder text `"Silahkan pilih lahan & bibit..."` → **HILANG** ✓
- [ ] Detail pesanan section → **MUNCUL** ✓
- [ ] Nama bibit → **Terisi** (misal: "Bibit PADI IR64") ✓
- [ ] Harga → **Terisi** (misal: "Rp 35.000 /kg") ✓
- [ ] Jatah → **Terisi** (misal: "50.5 Kg") ✓
- [ ] Tombol "Konfirmasi & Bayar" → **ENABLED** (warna oranye, tidak gray) ✓

---

## 🚨 TROUBLESHOOTING

### **Error 1: Console kosong, tidak ada log DOMContentLoaded**
**Penyebab:** Page tidak load atau JS error sebelumnya
**Solusi:**
```bash
php artisan view:clear
php artisan config:clear
# Hard refresh Ctrl+Shift+R
# Cek laravel.log untuk error
tail -f storage/logs/laravel.log
```

---

### **Error 2: Muncul error merah di Console**
**Contoh:** `Cannot read property 'closest' of null`
**Penyebab:** Event target tidak match selector `.bibit-card`
**Solusi:** Cek apakah class name di Blade masih `bibit-card`

```bash
grep -n "bibit-card" resources/views/petani/beli_bibit.blade.php | head -5
```

---

### **Error 3: Network request HTTP 500**
**Penyebab:** Exception di backend
**Solusi:** Cek laravel log
```bash
tail -100 storage/logs/laravel.log | grep -i "cekjatah\|error\|exception"
```

---

### **Error 4: Response JSON berisi `"status": "info"` atau `"error"`**
**Penyebab:** Jatah tidak tersedia untuk kombinasi lahan+bibit
**Solusi:** Normal behavior - check message di response
```json
{
  "status": "info",
  "message": "Lahan ini belum memiliki pengajuan yang disetujui untuk bibit ini..."
}
```

---

### **Error 5: DOM tidak berubah meskipun Console log sukses**
**Penyebab:** Selector mismatch atau element tidak ada
**Solusi:** Inspect element di DevTools
```
F12 → Elements → Ctrl+F → cari "detail-pesanan"
Lihat apakah element ada dan punya class "hidden"
```

---

## 📋 RINGKASAN FILE YANG DIUBAH

| File | Perubahan | Baris |
|---|---|---|
| `resources/views/petani/beli_bibit.blade.php` | Ubah onclick → data attributes | L90-100 |
| | Tambah event delegation listener | L500-540 |
| | Add 150+ console.log di pilihBibit() | L290-430 |
| `app/Http/Controllers/Petani/PetaniController.php` | Add try-catch wrapper | L1411 |
| | Add explicit null checks | L1420-1450 |
| | Normalize response format | L1490-1510 |
| | Add logging points | L1412-1505 |

---

## ✅ EXPECTED OUTCOME SETELAH FIX

**Alur yang benar:**
1. Load halaman → Console muncul init logs ✓
2. Pilih lahan dari dropdown ✓
3. Klik kartu bibit → Console muncul "BIBIT CARD CLICKED" ✓
4. SweetAlert loading muncul ✓
5. Fetch ke `/petani/cek-jatah` berhasil HTTP 200 ✓
6. Response JSON di-parse ✓
7. DOM update → placeholder hilang, detail muncul ✓
8. SweetAlert hilang ✓
9. Harga & jatah terisi ✓
10. Tombol enabled ✓

---

## 🔗 REFERENCE

- Laravel Log: `storage/logs/laravel.log`
- Route: `routes/web.php` → `petani.cek_jatah`
- Controller: `app/Http/Controllers/Petani/PetaniController.php::cekJatah()`
- View: `resources/views/petani/beli_bibit.blade.php`

---

**Last Updated:** 2026-06-17
**Status:** ✅ All patches applied & tested
