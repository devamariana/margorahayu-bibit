@extends('layouts.petani_layout')

@section('title', 'Informasi & Pembelian Bibit')

@section('content')
<div class="space-y-8">

    {{-- Alert Messages --}}
    {{-- Notifikasi Error via Layout (Global SweetAlert2) --}}

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

    {{-- TAMPILAN PRODUK DARI DATABASE --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($semuaBibit as $b)
        <div onclick="pilihBibit('{{ $b->id }}', '{{ $b->nama_bibit }}', {{ $b->harga_subsidi }}, {{ $b->stok_awal }}, {{ $b->total_luas_snapshot }}, false, {{ $b->stok }}, {{ $b->sisa_jatah_global }})" 
             class="bibit-card bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center hover:border-green-500 hover:shadow-md transition cursor-pointer relative overflow-hidden">
            
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
            <p class="text-xs text-gray-500 mb-2">Varietas: {{ $b->jenis ?? '-' }}</p>
            <p class="text-[#2D6A4F] font-black mb-3">Rp {{ number_format($b->harga_subsidi, 0, ',', '.') }}/kg</p>
            
            @if($b->stok > 0)
                <span class="inline-block px-4 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full border border-green-200">Stok: {{ $b->stok }} kg</span>
            @else
                <span class="inline-block px-4 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full border border-red-200">Stok Habis</span>
            @endif
            
            <p class="text-[9px] text-gray-400 mt-3 font-medium italic">Dibagi proporsional sesuai luas lahan</p>
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
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest mb-1">Lahan Terpilih</p>
                            <p id="info-lahan-terpilih" class="font-bold text-gray-700">-</p>
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
                            <span class="text-sm font-bold text-gray-500 block mb-1">Subtotal Pembayaran</span>
                            <span id="total-harga" class="text-4xl font-black text-[#2D6A4F]">Rp 0</span>
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
    let currentQuota = 0;

    function resetPilihanBibit() {
        document.querySelectorAll('.bibit-card').forEach(card => {
            card.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
        });
        document.getElementById('placeholder-text').classList.remove('hidden');
        document.getElementById('detail-pesanan').classList.add('hidden');
        document.getElementById('btn-bayar').classList.add('bg-gray-400', 'cursor-not-allowed');
        document.getElementById('btn-bayar').disabled = true;
        document.getElementById('total-harga').innerText = 'Rp 0';
        
        // Kosongkan input
        document.getElementById('input-lahan-id').value = '';
        document.getElementById('input-bibit-id').value = '';
        document.getElementById('input-jumlah-beli').value = 0;
    }

    function pilihBibit(id, nama, harga, stokAwal = 0, luasRef = 0, isTerbuka = false, currentStok = 0, sisaJatahGlobal = 0) {
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
        const jatahTambahan = parseFloat(selectedOption.getAttribute('data-tambahan'));
        
        // RUMUS PROPORSIONAL LAHAN TERPILIH
        let pembagi = luasRef > 0 ? luasRef : 1;
        let hakLahanIni = ((luasLahan / pembagi) * stokAwal) + jatahTambahan;
        
        // VALIDASI: Hak Lahan Ini tidak boleh melebihi Sisa Jatah Global (Sisa kuota seluruh lahan)
        let hakFinal = Math.min(hakLahanIni, sisaJatahGlobal);
        
        // Pembulatan
        hakFinal = Math.round(hakFinal * 10) / 10;

        currentQuota = hakFinal;

        // Update Input Hidden & Display
        document.getElementById('input-lahan-id').value = selectedOption.value;
        document.getElementById('input-bibit-id').value = id;
        document.getElementById('input-jumlah-beli').value = hakFinal;
        document.getElementById('input-jumlah-beli').max = hakFinal;
        
        // Display Info
        document.getElementById('placeholder-text').classList.add('hidden');
        document.getElementById('detail-pesanan').classList.remove('hidden');
        document.getElementById('label-bibit').innerText = 'Bibit ' + nama;
        document.getElementById('info-lahan-terpilih').innerText = selectedOption.text;
        document.getElementById('berat-estimasi').innerText = hakFinal + ' Kg';
        document.getElementById('harga-item').innerText = 'Rp ' + harga.toLocaleString('id-ID') + ' /kg';
        
        // Tambahkan info jika jatah sudah habis
        if (sisaJatahGlobal <= 0) {
             Swal.fire({
                icon: 'info',
                title: 'Jatah Sudah Habis',
                text: 'Anda sudah mengambil seluruh jatah untuk varietas bibit ini di transaksi sebelumnya.',
                confirmButtonColor: '#2D6A4F'
            });
            resetPilihanBibit();
            return;
        }

        // Efek visual pada card
        document.querySelectorAll('.bibit-card').forEach(card => {
            card.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
        });
        event.currentTarget.classList.add('border-green-500', 'ring-2', 'ring-green-200');

        hitungTotalManual();
    }

    function hitungTotalManual() {
        let qty = parseFloat(document.getElementById('input-jumlah-beli').value) || 0;
        const btnBayar = document.getElementById('btn-bayar');

        // Validasi: Tidak boleh melebihi jatah
        if (qty > currentQuota) {
            qty = currentQuota;
            document.getElementById('input-jumlah-beli').value = qty;
            Swal.fire({
                icon: 'error',
                title: 'Melebihi Jatah',
                text: 'Anda tidak diperbolehkan mengambil bibit melebihi hak jatah maksimal (' + currentQuota + ' Kg).',
                confirmButtonColor: '#2D6A4F'
            });
        }

        const total = qty * currentHarga;
        document.getElementById('input-total-harga').value = total;
        document.getElementById('total-harga').innerText = 'Rp ' + total.toLocaleString('id-ID');

        // Control Button State
        if (qty > 0 && document.getElementById('input-bibit-id').value) {
            btnBayar.disabled = false;
            btnBayar.classList.remove('bg-gray-400', 'cursor-not-allowed');
            btnBayar.classList.add('bg-[#D97706]', 'hover:bg-[#B45309]', 'shadow-orange-200');
        } else {
            btnBayar.disabled = true;
            btnBayar.classList.add('bg-gray-400', 'cursor-not-allowed');
            btnBayar.classList.remove('bg-[#D97706]', 'hover:bg-[#B45309]', 'shadow-orange-200');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Init state
    });
</script>
@endsection