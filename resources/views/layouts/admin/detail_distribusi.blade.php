@extends('layouts.admin_layout')

@section('title', 'Detail Transparansi Distribusi')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.data_bibit') }}" class="text-[#2D6A4F] font-bold text-xs uppercase hover:underline flex items-center gap-2 mb-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Data Bibit
            </a>
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Detail Distribusi: {{ $bibit->nama_bibit }}</h2>
            <p class="text-xs text-gray-400 mt-1">Laporan transparansi pembagian jatah proporsional petani.</p>
        </div>
        <div class="text-right">
             <span class="block text-[10px] text-gray-400 font-bold uppercase">Tanggal Dibuka</span>
             <span class="font-black text-gray-800">{{ \Carbon\Carbon::parse($bibit->tanggal_buka)->format('d F Y H:i') }}</span>
        </div>
    </div>

    {{-- STATISTIK SINGKAT --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Stok Awal</p>
            <p class="text-3xl font-black text-[#2D6A4F]">{{ number_format($bibit->stok_awal, 0) }} <span class="text-xs text-gray-400">Kg</span></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Referensi Luas Lahan</p>
            <p class="text-3xl font-black text-blue-600">{{ number_format($bibit->total_luas_snapshot, 0, ',', '.') }} <span class="text-xs text-gray-400">m²</span></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Stok Tersisa di Gudang</p>
            <p class="text-3xl font-black text-orange-600">{{ number_format($bibit->stok, 0) }} <span class="text-xs text-gray-400">Kg</span></p>
        </div>
    </div>

    {{-- TABEL TRANSPARANSI --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto relative">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-tighter sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">Nama Petani</th>
                        <th class="p-4 border-b">Luas Lahan (m²)</th>
                        <th class="p-4 border-b">Hasil Kalkulasi Jatah (Kg)</th>
                        <th class="p-4 border-b">Total Sudah Diambil (Kg)</th>
                        <th class="p-4 border-b">Sisa Jatah Belum Diambil</th>
                        <th class="p-4 border-b">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($dataDistribusi as $d)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-bold text-gray-800 uppercase">{{ $d['nama'] }}</td>
                        <td class="p-4 text-gray-600">{{ number_format($d['luas'], 0, ',', '.') }} m²</td>
                        <td class="p-4 font-black text-[#2D6A4F] text-sm">{{ $d['hak'] }} Kg</td>
                        <td class="p-4 font-bold text-blue-600">{{ $d['diambil'] }} Kg</td>
                        <td class="p-4 font-bold {{ $d['sisa'] > 0 ? 'text-orange-600' : 'text-gray-400' }}">{{ $d['sisa'] }} Kg</td>
                        <td class="p-4">
                            @if($d['status'] == 'Lengkap')
                                <span class="bg-green-100 text-green-600 px-2 py-1 rounded-lg font-black text-[9px] uppercase border border-green-200">Lunas/Lengkap</span>
                            @elseif($d['status'] == 'Sebagian')
                                <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded-lg font-black text-[9px] uppercase border border-blue-200">Sebagian</span>
                            @else
                                <span class="bg-gray-100 text-gray-400 px-2 py-1 rounded-lg font-black text-[9px] uppercase border border-gray-200">Belum Ada Pengambilan</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex items-start gap-4">
        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
        <div class="text-xs text-blue-800 leading-relaxed font-medium">
            <strong>Catatan Transparansi:</strong> Rumus perhitungan jatah adalah <code>(Luas Lahan Petani / Total Referensi Luas) * Stok Awal Pembukaan</code>. 
            Data di atas adalah data hidup yang mencerminkan realisasi pengambilan bibit oleh masing-masing petani pada distribusi batch ini.
        </div>
    </div>
</div>
@endsection
