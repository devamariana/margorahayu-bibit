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
                <div class="text-sm">
                    @if($transaksi->metode_pembayaran == 'midtrans')
                        <p>Silakan lakukan pembayaran sejumlah <span class="bg-blue-200 px-2 py-0.5 rounded text-blue-800">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span> melalui Midtrans Gateway.</p>
                    @elseif($transaksi->metode_pembayaran == 'transfer_manual')
                        <p>Silakan transfer sejumlah <span class="bg-blue-200 px-2 py-0.5 rounded text-blue-800">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span> ke rekening berikut:</p>
                        <div class="mt-2 p-3 bg-white rounded-lg border border-blue-200">
                            <p class="text-xs uppercase text-gray-500 font-black">Bank BRI (Margo Rahayu)</p>
                            <p class="text-lg font-black text-blue-900">0123-4567-8910-111</p>
                            <p class="text-[10px] text-gray-400">A/N KELOMPOK TANI MARGO RAHAYU</p>
                        </div>
                        <p class="mt-2 text-xs">Setelah transfer, silakan upload bukti bayar di bawah.</p>
                    @else
                        <p>Pembayaran dilakukan secara <span class="bg-blue-200 px-2 py-0.5 rounded text-blue-800">TUNAI</span> langsung di lokasi (Sistem Kasir).</p>
                        <p class="mt-2 text-xs">Silakan tunjukkan ID Trx: <span class="font-bold">{{ $transaksi->order_id }}</span> kepada petugas untuk diverifikasi.</p>
                    @endif
                </div>
            </div>

            <div class="space-y-4 mb-8">
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-gray-500 font-bold text-xs uppercase tracking-widest">Metode</span>
                    <span class="font-bold text-gray-800 uppercase text-xs">{{ str_replace('_', ' ', $transaksi->metode_pembayaran) }}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-gray-500 font-bold text-xs uppercase tracking-widest">Produk</span>
                    <span class="font-bold text-gray-800">{{ $transaksi->bibit->nama_bibit }}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-gray-500 font-bold text-xs uppercase tracking-widest">Jumlah</span>
                    <span class="font-bold text-gray-800">{{ $transaksi->jumlah_beli }} Kg</span>
                </div>
                <div class="flex justify-between items-end pt-2">
                    <span class="text-gray-400 font-bold text-xs uppercase tracking-widest">Total Tagihan</span>
                    <span class="font-black text-2xl text-[#1B4332]">Rp {{ number_format($transaksi->total_harga, 0, ',', '.') }}</span>
                </div>
            </div>

            @if($transaksi->metode_pembayaran == 'midtrans')
                <button id="pay-button" class="w-full bg-[#007BFF] hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg transition-all duration-300 uppercase tracking-widest text-sm flex items-center justify-center gap-3">
                    <i class="fas fa-lock"></i> Bayar Sekarang (Midtrans)
                </button>
            @elseif($transaksi->metode_pembayaran == 'transfer_manual')
                @if(!$transaksi->bukti_pembayaran)
                    <form action="{{ route('petani.upload_bukti', $transaksi->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-widest">Upload Bukti Transfer</label>
                            <input type="file" name="bukti_pembayaran" class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all border-2 border-dashed border-gray-200 p-2 rounded-2xl" required>
                        </div>
                        <button type="submit" class="w-full bg-[#2D6A4F] hover:bg-[#1B4332] text-white font-black py-4 rounded-xl shadow-lg transition-all duration-300 uppercase tracking-widest text-sm">
                            Kirim Bukti Pembayaran
                        </button>
                    </form>
                @else
                    <div class="bg-green-50 p-4 rounded-xl text-center border border-green-100">
                        <i class="fas fa-clock text-green-600 text-2xl mb-2"></i>
                        <p class="text-green-800 font-bold">Bukti sudah diupload.</p>
                        <p class="text-[10px] text-green-600">Mohon tunggu verifikasi dari Admin.</p>
                    </div>
                @endif
            @else
                <div class="bg-orange-50 p-8 rounded-2xl text-center border-2 border-dashed border-orange-200">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-store text-orange-600 text-2xl"></i>
                    </div>
                    <p class="text-orange-900 font-black uppercase tracking-widest text-lg mb-2">Sistem Pembayaran Kasir</p>
                    <p class="text-gray-600 text-xs mb-6">Silakan tunjukkan kode di bawah ini kepada petugas di kantor kelompok tani untuk melakukan pembayaran tunai.</p>
                    
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-orange-100 inline-block w-full mb-6">
                        <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mb-2">Kode Pembayaran</p>
                        <h3 class="text-3xl font-black text-gray-800 tracking-tighter">{{ $transaksi->order_id }}</h3>
                        <div class="mt-4 flex justify-center">
                            {{-- Simulasi QR Code --}}
                            <div class="p-2 bg-gray-50 rounded-xl border border-gray-100">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $transaksi->order_id }}" alt="QR Code" class="w-32 h-32 opacity-80">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <a href="{{ route('petani.invoice', $transaksi->id) }}" target="_blank" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-black py-4 rounded-xl shadow-lg transition-all duration-300 uppercase tracking-widest text-[10px] flex items-center justify-center gap-2">
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                        <a href="{{ route('petani.struk', $transaksi->id) }}" target="_blank" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-black py-4 rounded-xl shadow-lg transition-all duration-300 uppercase tracking-widest text-[10px] flex items-center justify-center gap-2">
                            <i class="fas fa-print"></i> Cetak Struk
                        </a>
                
                    </div>
                    <p class="text-[10px] text-orange-400 italic">Simpan atau screenshot layar ini untuk ditunjukkan ke petugas.</p>
                </div>
            @endif
            @if($transaksi->metode_pembayaran == 'tunai')
                <a href="{{ route('petani.sukses_bayar', $transaksi->id) }}" class="w-full bg-[#28A745] hover:bg-[#218838] text-white font-black py-4 rounded-xl shadow-lg transition-all duration-300 uppercase tracking-widest text-sm flex items-center justify-center gap-3 mt-4">
                    <i class="fas fa-check-circle"></i> SAYA SUDAH MEMBAYAR (Menunggu Verifikasi Admin)
                </a>
            @elseif($transaksi->metode_pembayaran != 'midtrans')
                <a href="{{ route('petani.riwayat') }}" class="w-full bg-[#28A745] hover:bg-[#218838] text-white font-black py-4 rounded-xl shadow-lg transition-all duration-300 uppercase tracking-widest text-sm flex items-center justify-center gap-3 mt-4">
                    <i class="fas fa-receipt"></i> Lihat Riwayat Pembayaran
                </a>
            @endif

            <form action="{{ route('petani.batal_bayar', $transaksi->id) }}" method="POST" class="mt-6">
                @csrf
                @method('DELETE')
                <button type="button" onclick="confirmAction(this, 'Batalkan pembayaran ini? \nStok akan dikembalikan.', 'warning')" class="w-full bg-white hover:bg-red-50 text-red-500 border border-red-200 font-bold py-3 rounded-xl shadow-sm transition-all duration-300 uppercase tracking-widest text-xs flex items-center justify-center gap-2">
                    <i class="fas fa-times-circle"></i> Batalkan Pesanan
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Script Midtrans --}}
@if($transaksi->metode_pembayaran == 'midtrans')
<script src="{{ env('MIDTRANS_IS_PRODUCTION', false) ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
<script type="text/javascript">
    document.getElementById('pay-button').onclick = function () {
        snap.pay('{{ $transaksi->snap_token }}', {
            onSuccess: function (result) { window.location.href = "{{ route('petani.sukses_bayar', $transaksi->id) }}"; },
            onPending: function (result) { Swal.fire({ icon: 'info', title: 'Menunggu Pembayaran', text: 'Silakan selesaikan pembayaran.', confirmButtonColor: '#2D6A4F' }); },
            onError: function (result) { Swal.fire({ icon: 'error', title: 'Gagal', text: 'Pembayaran gagal!', confirmButtonColor: '#2D6A4F' }); },
            onClose: function () { Swal.fire({ icon: 'warning', title: 'Batal?', text: 'Layar ditutup.', confirmButtonColor: '#2D6A4F' }); }
        });
    };
</script>
@endif
@endsection
