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
        <div onclick="pilihBibit('{{ $b->id }}', '{{ $b->nama_bibit }}', {{ $b->harga_subsidi }}, {{ $b->pivot->kuota_maksimal ?? 0 }})" 
             class="bibit-card bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center hover:border-green-500 hover:shadow-md transition cursor-pointer relative overflow-hidden">
            
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
                <span class="inline-block px-4 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full border border-green-200">Tersedia: {{ $b->stok }} kg</span>
            @else
                <span class="inline-block px-4 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full border border-red-200">Stok Habis</span>
            @endif
            
            @if(isset($b->pivot->kuota_maksimal) && $b->pivot->kuota_maksimal > 0)
                <p class="text-[10px] text-orange-600 mt-3 font-bold uppercase tracking-widest">Maksimal Beli: {{ $b->pivot->kuota_maksimal }} kg</p>
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
            <input type="hidden" name="jumlah_beli" id="input-jumlah-beli">
            <input type="hidden" name="total_harga" id="input-total-harga">

            <div class="space-y-4">
                <div id="placeholder-text" class="flex justify-between text-gray-600 border-b pb-4 italic">
                    <span>Silahkan pilih lahan & bibit di atas untuk mulai memesan</span>
                    <span class="font-bold text-gray-800">Rp 0</span>
                </div>

                <div id="detail-pesanan" class="hidden space-y-4 border-b pb-4">
                    <div class="flex justify-between text-gray-700">
                        <span id="label-bibit" class="font-bold text-lg text-[#2D6A4F]">Nama Bibit</span>
                        <span id="harga-item" class="font-bold">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Terpilih untuk Lahan:</span>
                        <span id="info-lahan-terpilih" class="font-bold text-gray-700">-</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500 italic">
                        <span>Jatah Lahan (10% dari luas)</span>
                        <span id="berat-estimasi" class="font-bold text-orange-600">0 Kg</span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b pb-4">
                    <span class="text-lg font-bold text-gray-800">Total Pembayaran</span>
                    <span id="total-harga" class="text-2xl font-bold text-[#2D6A4F]">Rp 0</span>
                </div>

                <p class="text-center text-[10px] text-gray-400 mt-6 italic">Permintaan pembelian memerlukan persetujuan Admin sebelum lanjut ke pembayaran</p>

                <div class="flex justify-end mt-6">
                    <button type="submit" id="btn-bayar" class="bg-gray-400 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition duration-300 cursor-not-allowed" disabled>
                        Ajukan Permintaan Pembelian
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function resetPilihanBibit() {
        document.querySelectorAll('.bibit-card').forEach(card => {
            card.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
        });
        document.getElementById('placeholder-text').classList.remove('hidden');
        document.getElementById('detail-pesanan').classList.add('hidden');
        document.getElementById('btn-bayar').classList.add('bg-gray-400', 'cursor-not-allowed');
        document.getElementById('btn-bayar').disabled = true;
        document.getElementById('total-harga').innerText = 'Rp 0';
        
        // Kosongkan input hidden
        document.getElementById('input-lahan-id').value = '';
        document.getElementById('input-bibit-id').value = '';
    }

    function pilihBibit(id, nama, harga, kuota = 0) {
        const selectLahan = document.getElementById('pilih-lahan');
        const selectedOption = selectLahan.options[selectLahan.selectedIndex];
        
        if (!selectedOption.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Silakan pilih lahan yang akan ditanami terlebih dahulu!',
                confirmButtonColor: '#2D6A4F'
            });
            return;
        }

        const luasLahan = parseFloat(selectedOption.getAttribute('data-luas'));
        const jatahTambahan = parseFloat(selectedOption.getAttribute('data-tambahan'));
        
        let estimasiBerat = (luasLahan / 100) * 10 + jatahTambahan;
        
        if (kuota > 0 && estimasiBerat > kuota) {
            estimasiBerat = parseFloat(kuota);
            Swal.fire({
                icon: 'info',
                title: 'Informasi Jatah',
                text: `Jatah lahan Anda melebihi batas kuota bibit ini. Pesanan disesuaikan ke maksimal: ${kuota} kg.`,
                confirmButtonColor: '#2D6A4F'
            });
        }

        const total = estimasiBerat * harga;

        // Update Input Hidden untuk Form
        document.getElementById('input-lahan-id').value = selectedOption.value;
        document.getElementById('input-bibit-id').value = id;
        document.getElementById('input-jumlah-beli').value = estimasiBerat;
        document.getElementById('input-total-harga').value = total;

        // Update Tampilan Ringkasan
        document.getElementById('placeholder-text').classList.add('hidden');
        document.getElementById('detail-pesanan').classList.remove('hidden');
        
        document.getElementById('label-bibit').innerText = 'Bibit ' + nama;
        document.getElementById('info-lahan-terpilih').innerText = selectedOption.text;
        document.getElementById('berat-estimasi').innerText = estimasiBerat + ' Kg';
        document.getElementById('harga-item').innerText = 'Rp ' + harga.toLocaleString('id-ID') + ' /kg';
        document.getElementById('total-harga').innerText = 'Rp ' + total.toLocaleString('id-ID');

        // Efek visual pada card
        document.querySelectorAll('.bibit-card').forEach(card => {
            card.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
        });
        event.currentTarget.classList.add('border-green-500', 'ring-2', 'ring-green-200');

        checkButtonState(); // Update button state after bibit selection
    }

    function checkButtonState() {
        const jumlah = parseFloat(document.getElementById('input-jumlah-beli').value) || 0;
        const btnBayar = document.getElementById('btn-bayar');
        const isLahanSelected = document.getElementById('pilih-lahan').value !== "";

        if (jumlah > 0 && isLahanSelected) {
            btnBayar.disabled = false;
            btnBayar.classList.remove('bg-gray-400', 'cursor-not-allowed');
            btnBayar.classList.add('bg-[#D97706]', 'hover:bg-[#B45309]');
        } else {
            btnBayar.disabled = true;
            btnBayar.classList.add('bg-gray-400', 'cursor-not-allowed');
            btnBayar.classList.remove('bg-[#D97706]', 'hover:bg-[#B45309]');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        checkButtonState(); 
    });
</script>
@endsection