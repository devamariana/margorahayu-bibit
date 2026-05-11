@extends('layouts.petani_layout')

@section('title', 'Riwayat Pembelian')

@section('content')
<div class="space-y-6">
    {{-- PAGE HEADER STICKY --}}
    <div class="sticky top-0 z-20 bg-[#F0F7F2]/95 backdrop-blur-sm pt-2 pb-4">
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
    </div>

    {{-- Notifikasi Sukses --}}
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded shadow-sm mb-4">
        <p class="text-green-700 font-bold"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- CONTAINER SCROLL - DIBUAT PAS DENGAN LAYAR AGAR TIDAK "LOSS" --}}
        <div class="w-full overflow-x-auto overflow-y-auto max-h-[calc(100vh-250px)] relative">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 z-10 bg-gray-50">
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="p-3 text-[10px] font-black uppercase tracking-wider border-b bg-gray-50 whitespace-nowrap">Tanggal</th>
                        <th class="p-3 text-[10px] font-black uppercase tracking-wider border-b bg-gray-50">Lokasi Lahan</th>
                        <th class="p-3 text-[10px] font-black uppercase tracking-wider border-b bg-gray-50">Nama Bibit</th>
                        <th class="p-3 text-[10px] font-black uppercase tracking-wider border-b text-center bg-gray-50 whitespace-nowrap">Jumlah</th>
                        <th class="p-3 text-[10px] font-black uppercase tracking-wider border-b bg-gray-50 whitespace-nowrap">Total Harga</th>
                        <th class="p-3 text-[10px] font-black uppercase tracking-wider border-b text-center bg-gray-50 whitespace-nowrap">Status</th>
                        <th class="p-3 text-[10px] font-black uppercase tracking-wider border-b text-center bg-gray-50">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                    <tr class="hover:bg-green-50/30 transition group">
                        <td class="p-3 text-xs text-gray-600 font-medium whitespace-nowrap">{{ $r->created_at->format('d-m-Y') }}</td>
                        <td class="p-3">
                            <span class="text-xs font-black text-[#2D6A4F] block group-hover:text-green-700 transition-colors leading-tight">{{ $r->lahan->nama_blok ?? 'Lahan Dihapus' }}</span>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">Luas: {{ $r->lahan->luas_lahan ?? 0 }} m²</span>
                        </td>
                        <td class="p-3 text-xs font-bold text-gray-800 leading-tight">{{ $r->bibit->nama_bibit ?? 'Bibit Dihapus' }}</td>
                        <td class="p-3 text-xs text-gray-600 text-center font-bold whitespace-nowrap">{{ number_format($r->jumlah_beli, 0, ',', '.') }} kg</td>
                        <td class="p-3 text-xs font-black text-gray-800 whitespace-nowrap">Rp {{ number_format($r->total_harga, 0, ',', '.') }}</td>
                        <td class="p-3 text-center whitespace-nowrap">
                            @if($r->status_pembayaran == 'sukses' || $r->status_pembayaran == 'lunas')
                                <span class="px-2 py-0.5 bg-green-500 text-white text-[9px] font-black rounded-md uppercase tracking-tighter shadow-sm">Lunas</span>
                            @elseif($r->status_pembayaran == 'menunggu_persetujuan')
                                <span class="px-2 py-0.5 bg-yellow-500 text-white text-[9px] font-black rounded-md uppercase tracking-tighter shadow-sm">Verif</span>
                            @elseif($r->status_pembayaran == 'menunggu_pembayaran' || $r->status_pembayaran == 'pending')
                                <span class="px-2 py-0.5 bg-blue-500 text-white text-[9px] font-black rounded-md uppercase tracking-tighter shadow-sm">Bayar</span>
                            @elseif($r->status_pembayaran == 'kadaluarsa')
                                <span class="px-2 py-0.5 bg-gray-500 text-white text-[9px] font-black rounded-md uppercase tracking-tighter shadow-sm">Expired</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-500 text-white text-[9px] font-black rounded-md uppercase tracking-tighter shadow-sm">Batal</span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            <div class="flex flex-row gap-1 justify-center items-center">
                                @if($r->status_pembayaran == 'sukses' || $r->status_pembayaran == 'lunas')
                                    <a href="{{ route('petani.invoice', $r->id) }}" target="_blank" class="p-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm transition-all hover:-translate-y-0.5 flex items-center justify-center" title="PDF">
                                        <i class="fas fa-file-pdf text-[10px]"></i>
                                    </a>
                                    <a href="{{ route('petani.struk', $r->id) }}" target="_blank" class="p-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-md shadow-sm transition-all hover:-translate-y-0.5 flex items-center justify-center" title="STRUK">
                                        <i class="fas fa-print text-[10px]"></i>
                                    </a>
                                @else
                                    <span class="text-[9px] text-gray-300 font-bold uppercase">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center text-gray-400 italic text-sm">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-box-open text-4xl mb-2 opacity-20"></i>
                                <span>Belum ada riwayat pembelian.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection