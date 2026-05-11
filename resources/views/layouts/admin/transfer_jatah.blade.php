@extends('layouts.admin_layout')
@section('title', 'Fitur Pengalihan Jatah')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold mb-4">Input Pengalihan Jatah</h3>
        <form id="pindahForm" action="{{ route('admin.proses_transfer') }}" method="POST">
            @csrf
            <div class="space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Pilih Bibit</label>
                    <div class="relative">
                        <select name="bibit_id" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm pr-10" required>
                            <option value="">-- Pilih Jenis Bibit --</option>
                            @foreach($bibitsAktif as $ba)
                                <option value="{{ $ba->id }}">{{ $ba->nama_bibit }} ({{ $ba->jenis }})</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Pilih Petani Pengirim (Kurangi Jatah)</label>
                    <div class="relative">
                        <select name="pengirim_id" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm pr-10" required>
                            <option value="">-- Pilih Pengirim --</option>
                            @foreach($petanis as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Pilih Petani Penerima (Tambah Jatah)</label>
                    <div class="relative">
                        <select name="penerima_id" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm pr-10" required>
                            <option value="">-- Pilih Penerima --</option>
                            @foreach($petanis as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Jumlah Jatah (Kg)</label>
                    <input type="number" step="0.1" name="jumlah_kg" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm" placeholder="Misal: 5" required>
                </div>
                <div class="pt-2 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-[#2D6A4F] hover:bg-[#1B4332] text-white text-xs font-bold rounded-lg shadow-sm transition uppercase tracking-widest w-full">Alihkan Sekarang</button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold mb-4">Riwayat Pengalihan</h3>
        <div class="overflow-x-auto overflow-y-auto max-h-[400px] relative">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-gray-50">
                        <th class="p-2">Bibit</th>
                        <th class="p-2">Pengirim</th>
                        <th class="p-2">Penerima</th>
                        <th class="p-2">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riwayatPindah as $r)
                    <tr class="border-b">
                        <td class="p-2 text-xs font-bold uppercase tracking-tighter">{{ $r->bibit->nama_bibit ?? '-' }}</td>
                        <td class="p-2">{{ $r->pengirim->nama_lengkap }}</td>
                        <td class="p-2">{{ $r->penerima->nama_lengkap }}</td>
                        <td class="p-2 text-blue-600 font-bold">{{ $r->jumlah_kg }} Kg</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('pindahForm').addEventListener('submit', function(e) {
        const jumlahKg = document.getElementById('jumlah_kg').value;
        if (!jumlahKg || parseInt(jumlahKg) <= 0) {
            alert('Perhatian: Jumlah jatah yang dialihkan tidak boleh kosong atau 0!');
            e.preventDefault();
            return;
        }
        document.getElementById('jumlah_kg_real').value = jumlahKg;
    });
</script>
@endsection