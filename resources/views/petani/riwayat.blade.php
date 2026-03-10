@extends('layouts.petani_layout')

@section('title', 'Riwayat Pembelian')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h2 class="text-2xl font-bold text-gray-800">Riwayat Pembelian</h2>
        
        <div class="relative">
            <select class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] cursor-pointer shadow-sm">
                <option>Filter Periode Tanam</option>
                <option>Januari - Juni 2026</option>
                <option>Juli - Desember 2026</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
            </div>
        </div>
    </div>

    {{-- Notifikasi Sukses --}}
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded shadow-sm mb-4">
        <p class="text-green-700 font-bold"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="p-4 text-sm font-bold border-b">Tanggal Transaksi</th>
                        <th class="p-4 text-sm font-bold border-b">Lokasi Lahan</th> {{-- Tambahan Kolom Sesuai Revisi --}}
                        <th class="p-4 text-sm font-bold border-b">Nama Bibit</th>
                        <th class="p-4 text-sm font-bold border-b text-center">Jumlah Beli</th>
                        <th class="p-4 text-sm font-bold border-b">Total Harga</th>
                        <th class="p-4 text-sm font-bold border-b text-center">Status Pembayaran</th>
                        <th class="p-4 text-sm font-bold border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-sm text-gray-600">{{ $r->created_at->format('d-m-Y') }}</td>
                        <td class="p-4">
                            <span class="text-sm font-bold text-[#2D6A4F] block">{{ $r->lahan->nama_blok ?? 'Lahan Dihapus' }}</span>
                            <span class="text-[10px] text-gray-400">Luas: {{ $r->lahan->luas_lahan ?? 0 }} m²</span>
                        </td>
                        <td class="p-4 text-sm font-medium text-gray-800">{{ $r->bibit->nama_bibit ?? 'Bibit Dihapus' }}</td>
                        <td class="p-4 text-sm text-gray-600 text-center">{{ $r->jumlah_beli }} kg</td>
                        <td class="p-4 text-sm font-bold text-gray-800">Rp {{ number_format($r->total_harga, 0, ',', '.') }}</td>
                        <td class="p-4 text-center">
                            @if($r->status_pembayaran == 'sukses' || $r->status_pembayaran == 'lunas')
                                <span class="px-3 py-1 bg-green-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">Lunas</span>
                            @elseif($r->status_pembayaran == 'menunggu_persetujuan')
                                <span class="px-3 py-1 bg-yellow-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">Menunggu Persetujuan</span>
                            @elseif($r->status_pembayaran == 'menunggu_pembayaran' || $r->status_pembayaran == 'pending')
                                <span class="px-3 py-1 bg-blue-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">Belum Bayar</span>
                            @elseif($r->status_pembayaran == 'kadaluarsa')
                                <span class="px-3 py-1 bg-gray-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">Kadaluarsa</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter">Ditolak/Batal</span>
                            @endif
                        </td>
                        <td class="p-4 text-center text-xs">
                            @if($r->status_pembayaran == 'menunggu_pembayaran' || $r->status_pembayaran == 'pending')
                                <a href="{{ route('petani.bayar_bibit', $r->id) }}" class="inline-block px-4 py-2 bg-[#D97706] hover:bg-[#B45309] text-white font-bold rounded-lg shadow-sm transition">Bayar Sekarang</a>
                            @elseif($r->status_pembayaran == 'menunggu_persetujuan')
                                <form action="{{ route('petani.batal_bayar', $r->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan permintaan pesanan ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-bold rounded-lg shadow-sm transition">Batalkan</button>
                                </form>
                            @else
                                <span class="text-gray-400 italic">Selesai</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400 italic text-sm">
                            Belum ada riwayat pembelian. Silakan pilih bibit di menu Beli Bibit.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection