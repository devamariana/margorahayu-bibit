@extends('layouts.petani_layout')

@section('title', 'Informasi & Pembelian Bibit')

@section('content')
<div class="space-y-8">

    {{-- ALERT UTAMA: INFORMASI PERIODE MUSIM AKTIF DARI ADMIN --}}
    @if(isset($periodeAktif) && $periodeAktif)
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                <i class="fas fa-cloud-sun text-lg"></i>
            </div>
            <div>
                <h4 class="font-bold text-blue-900 text-sm">Periode Aktif Saat Ini: {{ $periodeAktif->nama_periode ?? 'Masa Tanam Kelompok' }}</h4>
                <p class="text-xs text-blue-700">Sistem mendeteksi saat ini sedang berjalan <span class="font-black underline uppercase">Musim {{ $periodeAktif->musim }}</span>. Pilihan pengambilan komoditas bibit disesuaikan otomatis demi asas keadilan.</p>
            </div>
        </div>
    @else
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-xl shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <div>
                <h4 class="font-bold text-amber-900 text-sm">Peringatan: Tidak Ada Periode Aktif</h4>
                <p class="text-xs text-amber-700">Admin belum mengaktifkan atau membuka jadwal musim tanam resmi. Transaksi penguncian sementara akan disetel ke mode default.</p>
            </div>
        </div>
    @endif

    {{-- BAGIAN PILIH LAHAN TERLEBIH DAHULU --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-green-100 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-[#2D6A4F]">
                <i class="fas fa-map-marked-alt text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Pilih Lahan yang Akan Ditanami</h3>
                <p class="text-xs text-gray-500">Jatah bibit akan dihitung otomatis sesuai luas lahan yang dipilih.</p>
            </div>
        </div>
        <div class="w-full md:w-1/3 relative">
            <select id="pilih-lahan" onchange="resetPilihanBibit()" class="appearance-none w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2D6A4F] outline-none font-bold text-[#1B4332] pr-10">
                <option value="" data-luas="0" data-tambahan="0">-- Pilih Lokasi Lahan --</option>
                @foreach($lahans as $l)
                    <option value="{{ $l->id }}" data-luas="{{ $l->luas_lahan }}" data-tambahan="{{ $petani->jatah_tambahan ?? 0 }}">
                        {{ $l->nama_blok }} ({{ $l->luas_lahan }} m²)
                    </option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                <i class="fas fa-chevron-down text-[#2D6A4F] text-xs"></i>
            </div>
        </div>
    </div>

    {{-- TAMPILAN PRODUK KATALOG DARI DATABASE --}}
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#2D6A4F] rounded-full flex items-center justify-center text-white">
                <i class="fas fa-store text-sm"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Katalog Bibit Tersedia</h3>
                <p class="text-[10px] text-gray-500">Pilih musim untuk melihat varietas bibit.</p>
            </div>
        </div>

        {{-- FILTER MUSIM: TABS STYLE --}}
        <div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200">
            <button onclick="changeSeasonFilter('kemarau', this)" 
                    class="filter-btn px-6 py-2 rounded-lg text-xs font-bold transition-all {{ $currentMusimAktif === 'kemarau' ? 'bg-white text-[#2D6A4F] shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-sun mr-2"></i>Musim Kemarau
            </button>
            <button onclick="changeSeasonFilter('penghujan', this)" 
                    class="filter-btn px-6 py-2 rounded-lg text-xs font-bold transition-all {{ $currentMusimAktif === 'penghujan' ? 'bg-white text-[#2D6A4F] shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-cloud-showers-heavy mr-2"></i>Musim Penghujan
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="bibit-grid">
        @forelse($semuaBibit as $b)
        @php
            $isSalahMusim = ($b->kategori_musim !== $currentMusimAktif);
            $isTutup = !$b->is_buka;
            
            // Hitung kelas css tambahan
            $extraClasses = '';
            if ($b->stok <= 0) {
                $extraClasses = 'opacity-50 grayscale cursor-not-allowed pointer-events-none';
            } elseif ($isSalahMusim || $isTutup) {
                // Efek visual redup sedikit untuk membedakan, tapi tidak hilang agar bisa dilihat
                $extraClasses = 'border-red-100 bg-red-50/20 grayscale-[0.3] opacity-80';
            }
        @endphp
        
        <div onclick="pilihBibit('{{ $b->id }}', '{{ $b->nama_bibit }}', {{ $b->harga_subsidi }}, {{ $b->stok_awal ?? 0 }}, 0, false, {{ $b->stok }}, {{ $b->sisa_jatah_global ?? 0 }}, '{{ $b->kategori_musim }}', '{{ $currentMusimAktif }}', {{ $isTutup ? 'true' : 'false' }})" 
             class="bibit-card bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center hover:border-green-500 hover:shadow-md transition cursor-pointer relative overflow-hidden {{ $extraClasses }}" 
             data-musim="{{ $b->kategori_musim }}"
             data-id="{{ $b->id }}">
            
            <div class="absolute top-0 right-0 flex flex-col items-end">
                <span class="bg-gray-100 text-[8px] font-bold px-2 py-1 text-gray-400">Buka: {{ \Carbon\Carbon::parse($b->tanggal_buka)->format('d/m/y') }}</span>
            </div>

            <div class="w-full h-32 border-2 border-gray-100 rounded-lg flex items-center justify-center mb-4 overflow-hidden bg-gray-50">
                @if($b->gambar)
                    <img src="{{ asset('uploads/bibit/' . $b->gambar) }}" class="object-cover w-full h-full zoomable-image hover:opacity-80 transition">
                @else
                    <div class="text-gray-300 flex flex-col items-center">
                        <i class="fas fa-seedling text-4xl mb-1"></i>
                        <span class="text-[10px]">Tanpa Gambar</span>
                    </div>
                @endif
            </div>

            <h4 class="font-bold text-gray-800 uppercase">{{ $b->nama_bibit }}</h4>
            <div class="flex justify-center gap-1.5 mb-1">
                <span class="text-[9px] text-gray-500">Varietas: {{ $b->jenis ?? '-' }}</span>
                <span class="text-[9px] px-1.5 font-bold rounded {{ $b->kategori_musim === 'kemarau' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                     {{ $b->kategori_musim === 'kemarau' ? '☀ Kemarau' : '🌧 Penghujan' }}
                </span>
            </div>
            <p class="text-[#2D6A4F] font-black mb-3">Rp {{ number_format($b->harga_subsidi, 0, ',', '.') }}/kg</p>
            
            @if($b->stok > 0)
                <span class="inline-block px-4 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full border border-green-200">Stok: {{ $b->stok }} kg</span>
            @else
                <span class="inline-block px-4 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full border border-red-200">Stok Habis</span>
            @endif
            
            {{-- TEKS PERINGATAN MERAH JIKA SALAH MUSIM ATAU TUTUP --}}
            @if($isSalahMusim)
                <p class="text-[10px] text-red-600 mt-3 font-black bg-red-100 py-1 px-2 rounded border border-red-200 tracking-tight">
                    ⚠ Musim {{ ucfirst($b->kategori_musim) }} (Sistem: {{ ucfirst($currentMusimAktif) }})
                </p>
            @elseif($isTutup)
                 <p class="text-[10px] text-amber-600 mt-3 font-black bg-amber-50 py-1 px-2 rounded border border-amber-200 tracking-tight text-center">
                    🔒 Distribusi Masuk Masa Tunggu
                </p>
            @else
                <p class="text-[9px] text-gray-400 mt-3 font-medium italic">Dibagi proporsional sesuai luas lahan</p>
            @endif
        </div>
        @empty
        <div class="col-span-3 bg-white p-10 rounded-xl text-center border border-dashed border-gray-300">
            <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 italic">Belum ada bibit yang tersedia dari Admin.</p>
        </div>
        @endforelse
    </div>

    {{-- RINGKASAN PESANAN DENGAN FORM --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6 uppercase tracking-wider">Ringkasan Pesanan</h3>
        
        <form id="lahanForm" action="{{ route('petani.proses_beli') }}" method="POST">
            @csrf
            {{-- Input Hidden untuk mengirim data ke TransaksiController --}}
            <input type="hidden" name="lahan_id" id="input-lahan-id">
            <input type="hidden" name="bibit_id" id="input-bibit-id">
            <input type="hidden" name="total_harga" id="input-total-harga">

            <div class="space-y-4">
                <div id="placeholder-text" class="flex justify-between text-gray-600 border-b pb-4 italic">
                    <span>Silahkan pilih lahan & bibit di atas untuk mulai memesan</span>
                    <span class="font-bold text-gray-800">Rp 0</span>
                </div>

                <div id="detail-pesanan" class="hidden space-y-6 border-b pb-6">
                    <div class="flex justify-between items-center text-gray-700">
                        <span id="label-bibit" class="font-black text-2xl text-[#2D6A4F]">Nama Bibit</span>
                        <span id="harga-item" class="text-sm font-bold bg-gray-100 px-3 py-1 rounded-full">Rp 0 /kg</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest mb-1">Analisis Perhitungan</p>
                            <p class="text-[10px] text-gray-600 font-mono italic">(Perhitungan otomatis sesuai sistem)</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-orange-400 uppercase font-black tracking-widest mb-1">Hak Jatah Maksimal</p>
                            <p id="berat-estimasi" class="font-black text-orange-600 text-lg">0 Kg</p>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 py-4 border-t border-dashed border-gray-200">
                        <div class="max-w-xs">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Jumlah Dijemput/Diambil (Kg)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="jumlah_beli" id="input-jumlah-beli" value="0" min="0.1" class="w-full p-4 bg-white border-2 border-green-600 rounded-xl font-black text-[#2D6A4F] text-xl focus:ring-4 focus:ring-green-100 outline-none transition" oninput="hitungTotalManual()">
                                <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
                                    <span class="font-bold text-gray-400 uppercase text-xs italic">Sesuai Kebutuhan</span>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 italic">* Anda boleh mengambil kurang dari atau sama dengan hak jatah maksimal.</p>
                        </div>

                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-500 block mb-1">Subtotal Pembayaran</p>
                            <p id="total-harga" class="text-4xl font-black text-[#2D6A4F]">Rp 0</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 py-6 border-t border-dashed border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Pilih Metode Pembayaran</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Opsi Midtrans --}}
                        <label class="relative flex flex-col p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-green-500 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                            <input type="radio" name="metode_pembayaran" value="midtrans" class="sr-only" checked>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 group-has-[:checked]:bg-green-600 group-has-[:checked]:text-white">
                                    <i class="fas fa-credit-card text-xs"></i>
                                </div>
                                <span class="font-bold text-sm text-gray-800">Otomatis</span>
                            </div>
                            <p class="text-[10px] text-gray-400">VA, QRIS, E-Wallet via Midtrans</p>
                        </label>

                        {{-- Opsi Transfer Manual --}}
                        <label class="relative flex flex-col p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-green-500 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                            <input type="radio" name="metode_pembayaran" value="transfer_manual" class="sr-only">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 group-has-[:checked]:bg-green-600 group-has-[:checked]:text-white">
                                    <i class="fas fa-university text-xs"></i>
                                </div>
                                <span class="font-bold text-sm text-gray-800">Transfer Manual</span>
                            </div>
                            <p class="text-[10px] text-gray-400">Upload bukti transfer bank</p>
                        </label>

                        {{-- Opsi Tunai (Kasir) --}}
                        <label class="relative flex flex-col p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-green-500 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                            <input type="radio" name="metode_pembayaran" value="tunai" class="sr-only">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 group-has-[:checked]:bg-green-600 group-has-[:checked]:text-white">
                                    <i class="fas fa-money-bill-wave text-xs"></i>
                                </div>
                                <span class="font-bold text-sm text-gray-800">Bayar Langsung</span>
                            </div>
                            <p class="text-[10px] text-gray-400">Bayar tunai di lokasi (Kasir)</p>
                        </label>
                    </div>
                </div>

                <p class="text-center text-[10px] text-gray-400 mt-6 italic" id="instruksi-bayar">Klik tombol di bawah untuk memproses pesanan dan melakukan pembayaran</p>

                <div class="flex justify-end mt-6">
                    <button type="submit" id="btn-bayar" class="bg-gray-400 text-white font-bold py-4 px-10 rounded-2xl shadow-lg transition duration-300 cursor-not-allowed uppercase tracking-widest text-sm" disabled>
                        Konfirmasi & Bayar Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let currentHarga = 0;
    let activeSeasonFilter = '{{ $currentMusimAktif }}';

    function changeSeasonFilter(season, btn) {
        activeSeasonFilter = season;
        
        // Update Buttons UI
        document.querySelectorAll('.filter-btn').forEach(b => {
             b.classList.remove('bg-white', 'text-[#2D6A4F]', 'shadow-sm');
             b.classList.add('text-gray-500', 'hover:text-gray-700');
        });
        btn.classList.remove('text-gray-500', 'hover:text-gray-700');
        btn.classList.add('bg-white', 'text-[#2D6A4F]', 'shadow-sm');

        // Filter Cards
        document.querySelectorAll('.bibit-card').forEach(card => {
            if (card.getAttribute('data-musim') === season) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
        
        // Reset selection if the currently selected bibit is now hidden
        const selectedId = document.getElementById('input-bibit-id').value;
        if (selectedId) {
            const selectedCard = document.querySelector(`.bibit-card[data-id="${selectedId}"]`);
            if (selectedCard && selectedCard.classList.contains('hidden')) {
                resetPilihanBibit();
            }
        }
    }

    // Run filter on load
    document.addEventListener('DOMContentLoaded', () => {
        const initialBtn = document.querySelector(`.filter-btn:contains('Musim ${activeSeasonFilter.charAt(0).toUpperCase() + activeSeasonFilter.slice(1)}')`);
        // Note: :contains isn't standard JS, just use logic
        const btns = document.querySelectorAll('.filter-btn');
        btns.forEach(b => {
            if (b.innerText.toLowerCase().includes(activeSeasonFilter)) {
                changeSeasonFilter(activeSeasonFilter, b);
            }
        });
    });


    let currentQuota = 0;
    const totalLuasPetani = {{ $totalLuasPetani ?? 0 }};
    const totalLuasSemuaPetani = {{ \App\Models\Lahan::where('status','disetujui')->sum('luas_lahan') }};
    const purchasesMap = @json($purchases ?? []);

    function resetPilihanBibit() {
        document.querySelectorAll('.bibit-card').forEach(card => {
            card.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
        });
        document.getElementById('placeholder-text').classList.remove('hidden');
        document.getElementById('detail-pesanan').classList.add('hidden');
        document.getElementById('btn-bayar').classList.add('bg-gray-400', 'cursor-not-allowed');
        document.getElementById('btn-bayar').disabled = true;
        document.getElementById('total-harga').innerText = 'Rp 0';
        
        document.getElementById('input-lahan-id').value = '';
        document.getElementById('input-bibit-id').value = '';
        document.getElementById('input-jumlah-beli').value = 0;
    }

    // FIX PERBAIKAN LOGIKA PENGUNCIAN DI JAVASCRIPT SESUAI REVISI DOSEN
    function pilihBibit(id, nama, harga, stokAwal = 0, luasRef = 0, isTerbuka = false, currentStok = 0, sisaJatahGlobal = 0, musimBibit, musimAktif, isTutup = false) {
        
        // 1. CEK DISTRIBUSI: Jika distribusi sedang ditutup oleh Admin
        if (isTutup) {
             Swal.fire({
                icon: 'warning',
                title: 'Distribusi Ditutup',
                text: 'Maaf, distribusi bibit ini sedang ditutup sementara oleh Admin. Anda hanya dapat melihat informasi varietas saat ini.',
                confirmButtonColor: '#D97706'
            });
            resetPilihanBibit();
            return;
        }

        // 2. CEK MUSIM: Jika musim tidak cocok dengan periode aktif, langsung kunci/blokir transaksi
        if (musimBibit !== musimAktif) {
            Swal.fire({
                icon: 'error',
                title: 'Transaksi Dikunci!',
                text: 'Bibit ' + nama + ' hanya dapat ditransaksikan pada Musim ' + musimBibit.toUpperCase() + '. Saat ini sedang berjalan Musim ' + musimAktif.toUpperCase() + '.',
                confirmButtonColor: '#DC3545'
            });
            resetPilihanBibit();
            return; // Gagalkan fungsi, tidak diizinkan masuk ke ringkasan pesanan
        }

        const selectLahan = document.getElementById('pilih-lahan');
        const selectedOption = selectLahan.options[selectLahan.selectedIndex];
        
        if (!selectedOption.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Lahan Belum Dipilih',
                text: 'Silakan pilih lokasi lahan Anda terlebih dahulu untuk melihat jatah hak ambil.',
                confirmButtonColor: '#2D6A4F'
            });
            return;
        }

        currentHarga = harga;
        const luasLahan = parseFloat(selectedOption.getAttribute('data-luas'));
        
        // FORMULA BARU (SESUAI GAMBAR): (luas lahan blok / total seluruh luas lahan global) * stok bibit admin
        let hakLahanIni = totalLuasSemuaPetani > 0 
            ? (luasLahan / totalLuasSemuaPetani) * currentStok 
            : 0;

        const lahanId = selectedOption.value;
        const sudahDariLahan = parseFloat((purchasesMap[id] && purchasesMap[id][lahanId]) ? purchasesMap[id][lahanId] : 0) || 0;

        // Jatah akhir = Hitungan rumus - yang sudah pernah dibeli di lahan ini
        let hakFinal = Math.max(0, hakLahanIni - sudahDariLahan);
        
        // Tetap pastikan tidak melebihi sisa stok global (opsional safety)
        hakFinal = Math.min(hakFinal, sisaJatahGlobal);

        // Pembulatan presisi satu angka di belakang koma
        // Pembulatan presisi satu angka di belakang koma (Sesuai Permintaan)
        hakFinal = Math.round(hakFinal * 10) / 10;

        currentQuota = hakFinal;

        document.getElementById('input-lahan-id').value = selectedOption.value;
        document.getElementById('input-bibit-id').value = id;
        document.getElementById('input-jumlah-beli').value = hakFinal;
        document.getElementById('input-jumlah-beli').max = hakFinal;
        
        document.getElementById('placeholder-text').classList.add('hidden');
        document.getElementById('detail-pesanan').classList.remove('hidden');
        document.getElementById('label-bibit').innerText = 'Bibit ' + nama;
        document.getElementById('berat-estimasi').innerText = hakFinal + ' Kg';
        document.getElementById('harga-item').innerText = 'Rp ' + harga.toLocaleString('id-ID') + ' /kg';
        
        // Cek jika jatah sudah benar-benar nol
        if (hakFinal <= 0) {
             Swal.fire({
                icon: 'info',
                title: 'Jatah Habis Untuk Blok Ini',
                text: 'Anda sudah mengambil jatah maksimal untuk lokasi lahan ini.',
                confirmButtonColor: '#2D6A4F'
            });
            resetPilihanBibit();
            return;
        }

        document.querySelectorAll('.bibit-card').forEach(card => {
            card.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
        });
        event.currentTarget.classList.add('border-green-500', 'ring-2', 'ring-green-200');

        hitungTotalManual();
    }

    function hitungTotalManual() {
        let inputVal = document.getElementById('input-jumlah-beli').value;
        let qty = parseFloat(inputVal) || 0;
        const btnBayar = document.getElementById('btn-bayar');

        // Proteksi Jatah Maksimal: Bulatkan input ke 1 desimal
        qty = Math.round(qty * 10) / 10;

        if (qty > currentQuota) {
            qty = currentQuota;
            document.getElementById('input-jumlah-beli').value = qty;
            Swal.fire({
                icon: 'error',
                title: 'Melebihi Jatah Maksimal',
                text: 'Berdasarkan rumus perhitungan luas lahan, jatah maksimal Anda untuk blok ini adalah ' + currentQuota + ' Kg.',
                confirmButtonColor: '#2D6A4F'
            });
        }

        const total = qty * currentHarga;
        document.getElementById('input-total-harga').value = Math.round(total);
        document.getElementById('total-harga').innerText = 'Rp ' + Math.round(total).toLocaleString('id-ID');

        // Validasi tombol pembayaran
        if (qty > 0 && document.getElementById('input-bibit-id').value && qty <= currentQuota) {
            btnBayar.disabled = false;
            btnBayar.classList.remove('bg-gray-400', 'cursor-not-allowed', 'opacity-50');
            btnBayar.classList.add('bg-[#D97706]', 'hover:bg-[#B45309]', 'shadow-orange-200');
        } else {
            btnBayar.disabled = true;
            btnBayar.classList.add('bg-gray-400', 'cursor-not-allowed', 'opacity-50');
            btnBayar.classList.remove('bg-[#D97706]', 'hover:bg-[#B45309]', 'shadow-orange-200');
        }
    }
</script>
@endsection