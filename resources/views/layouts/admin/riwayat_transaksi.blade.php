@extends('layouts.admin_layout')

@section('title', 'Monitor Riwayat Transaksi')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-2 text-green-600 font-medium text-sm">
            <i class="fas fa-check-circle"></i>
            <span>Status Integrasi Midtrans: Connected</span>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <select class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] cursor-pointer">
                <option>Filter Periode</option>
            </select>
            <select class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] cursor-pointer">
                <option>Filter Status Pembayaran</option>
            </select>
            <button class="bg-[#007BFF] hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-lg shadow-md flex items-center gap-2 transition duration-300 text-xs">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-4 border-b">Tanggal</th>
                        <th class="p-4 border-b">Nama Petani</th>
                        <th class="p-4 border-b">Bibit Dibeli</th>
                        <th class="p-4 border-b">Total Harga</th>
                        <th class="p-4 border-b text-center">Status</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksis as $t)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                        <td class="p-4 font-bold text-gray-800 uppercase">{{ $t->petani->nama_lengkap ?? 'Petani Dihapus' }}</td>
                        <td class="p-4 text-gray-600">
                            {{ $t->bibit->nama_bibit ?? 'Bibit Dihapus' }} ({{ $t->jumlah_beli }}kg)<br>
                            <span class="text-[10px] text-gray-400">Lahan: {{ $t->lahan->nama_blok ?? 'Dihapus' }}</span>
                        </td>
                        <td class="p-4 font-bold text-[#2D6A4F] tracking-tight">Rp {{ number_format($t->total_harga, 0, ',', '.') }}</td>
                        <td class="p-4 text-center">
                            @if($t->status_pembayaran == 'menunggu_persetujuan')
                                <span class="px-3 py-1 bg-yellow-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">MENUNGGU PERSETUJUAN</span>
                            @elseif($t->status_pembayaran == 'menunggu_pembayaran' || $t->status_pembayaran == 'pending')
                                <span class="px-3 py-1 bg-blue-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">BELUM DIBAYAR</span>
                            @elseif($t->status_pembayaran == 'sukses')
                                <span class="px-3 py-1 bg-green-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">LUNAS</span>
                            @elseif($t->status_pembayaran == 'kadaluarsa')
                                <span class="px-3 py-1 bg-gray-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">KADALUARSA</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">DITOLAK/BATAL</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @if($t->status_pembayaran == 'menunggu_persetujuan')
                                <div class="flex flex-col sm:flex-row gap-2 justify-center">
                                    <form action="{{ route('admin.verifikasi_transaksi', $t->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status_pembayaran" value="menunggu_pembayaran">
                                        <button type="button" onclick="confirmAction(this, 'Setujui pesanan bibit ini? \n(Petani akan menerima notifikasi pembayaran)')" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-xs font-bold transition shadow-sm w-full"><i class="fas fa-check mr-1"></i>Acc</button>
                                    </form>
                                    <form action="{{ route('admin.verifikasi_transaksi', $t->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status_pembayaran" value="ditolak">
                                        <button type="button" onclick="confirmAction(this, 'Tolak pesanan ini dan kembalikan stok bibit?', 'warning')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-bold transition shadow-sm w-full"><i class="fas fa-times mr-1"></i>Tolak</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-gray-400 italic text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-400 italic">Belum ada data transaksi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection