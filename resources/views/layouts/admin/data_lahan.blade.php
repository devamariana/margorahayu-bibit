@extends('layouts.admin_layout')

@section('title', 'Kelola Data Lahan Petani')

@section('content')
<div class="space-y-6">
    {{-- Notifikasi Sukses --}}
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded shadow-sm">
        <p class="text-green-700 font-bold"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
    </div>
    @endif
    <div class="flex justify-end items-center">
        <div class="relative w-full md:w-80">
            <input type="text" 
                   placeholder="Cari nama pemilik lahan..." 
                   class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D6A4F] focus:outline-none shadow-sm bg-white text-sm">
            <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-4 border-b">No</th>
                        <th class="p-4 border-b">Nama Pemilik</th>
                        <th class="p-4 border-b">Lokasi/Blok Lahan</th>
                        <th class="p-4 border-b">Luas Lahan (m²)</th>
                        <th class="p-4 border-b">Rencana Bibit</th>
                        <th class="p-4 border-b">Status</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lahans as $index => $l)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600">{{ $index + 1 }}</td>
                        <td class="p-4 font-bold text-gray-800">{{ $l->petani->nama_lengkap ?? 'Petani Dihapus' }}</td>
                        <td class="p-4 text-gray-600 uppercase">{{ $l->nama_blok }}</td>
                        <td class="p-4 font-medium text-gray-700">{{ $l->luas_lahan }} m²</td>
                        <td class="p-4 text-gray-600 font-bold">{{ $l->rencana_bibit }}</td>
                        <td class="p-4">
                            @if($l->status == 'disetujui')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[10px] font-bold">DISETUJUI</span>
                            @elseif($l->status == 'ditolak')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-[10px] font-bold">DITOLAK</span>
                            @else
                                <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-[10px] font-bold">PENDING</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center gap-2">
                                @if($l->status == 'pending')
                                    <form action="{{ route('admin.verifikasi_lahan', $l->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="disetujui">
                                        <button type="submit" title="Setujui Lahan" class="w-8 h-8 bg-[#2D6A4F] hover:bg-green-700 text-white rounded shadow-sm flex items-center justify-center transition">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.verifikasi_lahan', $l->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="ditolak">
                                        <button type="submit" title="Tolak Lahan" class="w-8 h-8 bg-red-500 hover:bg-red-700 text-white rounded shadow-sm flex items-center justify-center transition">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </form>
                                @else
                                    <button disabled class="w-8 h-8 bg-gray-300 text-white rounded cursor-not-allowed flex items-center justify-center">
                                        <i class="fas fa-lock text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-gray-400 italic">Belum ada data lahan yang terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection