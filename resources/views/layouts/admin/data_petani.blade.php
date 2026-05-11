@extends('layouts.admin_layout')

@section('title', 'Kelola Data Petani')

@section('content')
<div class="space-y-6">
    {{-- Notifikasi Sukses via Layout (Global SweetAlert2) --}}

    <div class="sticky top-0 z-20 bg-[#F0F7F2]/95 backdrop-blur-sm pt-2 pb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="relative w-full md:w-96">
                <input type="text" id="searchInput" onkeyup="searchPetani()"
                    placeholder="Cari nama petani..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D6A4F] focus:outline-none shadow-sm">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            <a href="{{ route('admin.petani.pdf') }}" target="_blank" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg shadow-sm flex items-center transition whitespace-nowrap">
                <i class="fas fa-file-pdf mr-2"></i> Cetak PDF
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-280px)] relative">
            <table class="w-full text-left border-collapse" id="petaniTable">
                <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] font-bold tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">No</th>
                        <th class="p-4 border-b">Username</th>
                        <th class="p-4 border-b">Nama Lengkap</th>
                        <th class="p-4 border-b">NIK</th>
                        <th class="p-4 border-b">Luas Lahan</th>
                        <th class="p-4 border-b">Status</th>
                        <th class="p-4 border-b text-center">Identitas</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @foreach($petanis as $index => $p)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-500">{{ $index + 1 }}</td>
                        
                        <td class="p-4 font-mono text-blue-600">
                            {{ $p->user->username ?? 'User Terhapus' }}
                        </td>

                        <td class="p-4 font-bold text-gray-800 uppercase name-field">
                            {{ $p->nama_lengkap ?? '-' }}
                        </td>
                        
                        {{-- PERBAIKAN NIK: Pastikan memanggil variabel $p->nik --}}
                        <td class="p-4 text-gray-600 font-mono">
                            {{ $p->nik ?? 'Belum Diisi' }}
                        </td>
                        
                        <td class="p-4 font-bold text-[#2D6A4F]">
                            {{ number_format(App\Models\Lahan::where('petani_id', $p->id)->sum('luas_lahan'), 0, ',', '.') }} m²
                        </td>
                        
                        <td class="p-4">
                            @if($p->status == 'disetujui')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[10px] font-bold">TERVERIFIKASI</span>
                            @else
                                <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-[10px] font-bold">PENDING</span>
                            @endif
                        </td>
                        
                        {{-- PERBAIKAN KOLOM IDENTITAS --}}
                        <td class="p-4">
                            <div class="flex justify-center gap-3">
                                {{-- TOMBOL KTP --}}
                                @if($p->foto_ktp)
                                    <a href="{{ asset('uploads/identitas/' . $p->foto_ktp) }}" target="_blank" 
                                       class="w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center justify-center shadow-md transition-all active:scale-95" 
                                       title="Buka Foto KTP">
                                        <i class="fas fa-id-card text-lg"></i>
                                    </a>
                                @else
                                    <div class="w-10 h-10 bg-gray-100 text-gray-300 rounded-lg border border-dashed border-gray-300 flex items-center justify-center">
                                        <i class="fas fa-id-card text-lg"></i>
                                    </div>
                                @endif

                                {{-- TOMBOL KK --}}
                                @if($p->foto_kk)
                                    <a href="{{ asset('uploads/identitas/' . $p->foto_kk) }}" target="_blank" 
                                       class="w-10 h-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg flex items-center justify-center shadow-md transition-all active:scale-95" 
                                       title="Buka Foto KK">
                                        <i class="fas fa-file-invoice text-lg"></i>
                                    </a>
                                @else
                                    <div class="w-10 h-10 bg-gray-100 text-gray-400 rounded-lg border border-dashed border-gray-300 flex items-center justify-center">
                                        <i class="fas fa-file-invoice text-lg"></i>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td class="p-4 text-center">
                            <div class="flex justify-center gap-2">
                                @if($p->status != 'disetujui')
                                <form action="{{ route('admin.verifikasi_petani', $p->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="disetujui">
                                    <button type="button" onclick="confirmAction(this, 'Setujui pendaftaran petani ini?')" title="Setujui Petani" class="w-8 h-8 bg-[#2D6A4F] hover:bg-green-700 text-white rounded shadow-sm flex items-center justify-center transition">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                </form>
                                @else
                                <button disabled class="w-8 h-8 bg-gray-300 text-white rounded cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-check-double text-xs"></i>
                                </button>
                                @endif

                                <form action="{{ route('admin.hapus_petani', $p->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmAction(this, 'Hapus data petani ini? \nAkun login mereka juga akan dihapus secara permanen!', 'warning')" title="Hapus" class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded shadow-sm flex items-center justify-center transition">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function searchPetani() {
        let input = document.getElementById("searchInput").value.toLowerCase();
        let rows = document.querySelectorAll("#petaniTable tbody tr");

        rows.forEach(row => {
            let name = row.querySelector(".name-field").innerText.toLowerCase();
            row.style.display = name.includes(input) ? "" : "none";
        });
    }
</script>
@endsection