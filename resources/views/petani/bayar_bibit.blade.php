@extends('layouts.petani_layout')

@section('title', 'Selesaikan Pembayaran')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        
        <div class="bg-[#1B4332] p-8 text-white text-center rounded-t-2xl relative overflow-hidden">
            <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 0h20v20H0V0zm10 10l10 10H0L10 10z\' fill=\'%23ffffff\' fill-rule=\'evenodd\'/%3E%3C/svg%3E');"></div>
            <div class="w-20 h-20 mx-auto rounded-full bg-white/20 flex flex-col items-center justify-center mb-4 relative z-10 backdrop-blur-sm border border-white/30">
                <i class="fas fa-wallet text-3xl text-white"></i>
            </div>
            <h2 class="text-2xl font-black mb-2 uppercase tracking-wide relative z-10">Konfirmasi Pembayaran</h2>
            <p class="text-green-100 text-sm opacity-90 relative z-10 tracking-widest uppercase">ID Trx: {{ $transaksi->order_id }}</p>
        </div>

        <div class="p-8">
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex gap-4 text-blue-700 font-bold mb-6">
                <i class="fas fa-info-circle text-xl flex-shrink-0 mt-0.5"></i>
                <p class="text-sm">Silakan lakukan pembayaran sejumlah <span class="bg-blue-200 px-2 py-0.5 rounded text-blue-800">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span> untuk pesanan bibit Anda melalui metode apapun via Midtrans Gateway.</p>
            </div>

            <div class="space-y-4 mb-8">
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-gray-500 font-bold text-xs uppercase tracking-widest">Produk</span>
                    <span class="font-bold text-gray-800">{{ $transaksi->bibit->nama_bibit }}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-gray-500 font-bold text-xs uppercase tracking-widest">Jumlah</span>
                    <span class="font-bold text-gray-800">{{ $transaksi->jumlah_beli }} Kg</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b-2 border-dashed border-gray-200">
                    <span class="text-gray-500 font-bold text-xs uppercase tracking-widest">Tujuan Lahan</span>
                    <span class="font-bold text-gray-800">{{ $transaksi->lahan->nama_blok }}</span>
                </div>
                <div class="flex justify-between items-end pt-2">
                    <span class="text-gray-400 font-bold text-xs uppercase tracking-widest">Total Tagihan</span>
                    <span class="font-black text-2xl text-[#1B4332]">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span>
                </div>
            </div>

            <button id="pay-button" class="w-full bg-[#007BFF] hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg transition-all duration-300 uppercase tracking-widest text-sm flex items-center justify-center gap-3">
                <i class="fas fa-lock"></i> Bayar Sekarang Secara Aman
            </button>
            <form action="{{ route('petani.batal_bayar', $transaksi->id) }}" method="POST" class="mt-4">
                @csrf
                @method('DELETE')
                <button type="button" onclick="confirmAction(this, 'Batalkan pembayaran ini? \nStok akan dikembalikan dan Anda harus memesan ulang.', 'warning')" class="w-full bg-white hover:bg-red-50 text-red-500 border border-red-200 font-bold py-3 rounded-xl shadow-sm transition-all duration-300 uppercase tracking-widest text-xs flex items-center justify-center gap-2">
                    <i class="fas fa-times-circle"></i> Batalkan Pembayaran
                </button>
            </form>
            <p class="text-center text-xs text-gray-400 mt-4 flex items-center justify-center gap-2"><i class="fas fa-shield-alt"></i> Pembayaran diamankan oleh Midtrans.</p>
        </div>
    </div>
</div>

{{-- Script Midtrans --}}
<script src="{{ env('MIDTRANS_IS_PRODUCTION', false) ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
<script type="text/javascript">
    // Mencegah Tombol Back Browser
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };

    document.getElementById('pay-button').onclick = function () {
        // Pop-up Midtrans otomatis dengan snap token dari Controller
        snap.pay('{{ $transaksi->snap_token }}', {
            // Ketika Sukses Bayar
            onSuccess: function (result) {
                // Di dunia nyata kirim result ke backend, di sini kita redirect ke rute konfirmasi
                window.location.href = "{{ route('petani.sukses_bayar', $transaksi->id) }}";
            },
            // Ketika Pending
            onPending: function (result) {
                Swal.fire({
                    icon: 'info',
                    title: 'Menunggu Pembayaran',
                    text: 'Silakan selesaikan pembayaran sesuai instruksi di Midtrans.',
                    confirmButtonColor: '#2D6A4F'
                });
            },
            // Ketika Gagal
            onError: function (result) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Pembayaran gagal! Silakan coba lagi atau hubungi admin.',
                    confirmButtonColor: '#2D6A4F'
                });
            },
            // Ketika Close tanpa bayar
            onClose: function () {
                Swal.fire({
                    icon: 'warning',
                    title: 'Batal Membayar?',
                    text: 'Anda menutup layar pembayaran. Silakan bayar melalui menu Riwayat Pembelian.',
                    confirmButtonColor: '#2D6A4F'
                }).then(() => {
                    window.location.href = "{{ route('petani.riwayat') }}";
                });
            }
        });
    };
</script>
@endsection
