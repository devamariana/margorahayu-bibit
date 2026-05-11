@extends('layouts.admin_layout')

@section('title', 'Kelola Periode Tanam')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-80">
            <form action="{{ route('admin.data_periode') }}" method="GET" class="flex">
                <input type="text" 
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari tahun periode..." 
                       class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D6A4F] focus:outline-none shadow-sm bg-white">
                <button type="submit" class="absolute right-0 top-0 bottom-0 px-3 text-gray-500 hover:text-[#2D6A4F]">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <button onclick="document.getElementById('modalPeriode').classList.remove('hidden')" class="bg-[#007BFF] hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md flex items-center gap-2 transition duration-300">
            <i class="fas fa-plus text-sm"></i> Tambah Periode
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto max-h-[600px] relative">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">No</th>
                        <th class="p-4 border-b">Tahun</th>
                        <th class="p-4 border-b">Tanggal Mulai</th>
                        <th class="p-4 border-b">Tanggal Selesai</th>
                        <th class="p-4 border-b">Status</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($periodes as $index => $p)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600 font-medium">{{ $index + 1 }}</td>
                        <td class="p-4 font-bold text-gray-800">{{ $p->tahun }}</td>
                        <td class="p-4 text-gray-600">{{ \Carbon\Carbon::parse($p->tanggal_mulai)->format('d M Y') }}</td>
                        <td class="p-4 text-gray-600">{{ \Carbon\Carbon::parse($p->tanggal_selesai)->format('d M Y') }}</td>
                        <td class="p-4">
                            @if($p->status == 'aktif')
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-bold text-[10px] uppercase">AKTIF</span>
                            @else
                                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-bold text-[10px] uppercase">BERAKHIR</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex justify-center gap-2">
                                <button onclick="openEditModal({{ $p->id }}, '{{ $p->tahun }}', '{{ $p->tanggal_mulai }}', '{{ $p->tanggal_selesai }}', '{{ $p->status }}')" title="Edit" class="w-8 h-8 bg-[#FFC107] hover:bg-yellow-600 text-white rounded shadow-sm flex items-center justify-center transition">
                                    <i class="fas fa-edit text-[10px]"></i>
                                </button>
                                <form action="{{ route('admin.data_periode.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus periode ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="w-8 h-8 bg-[#DC3545] hover:bg-red-600 text-white rounded shadow-sm flex items-center justify-center transition">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-400 italic">Belum ada data periode.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">
            {{ $periodes->links() }}
        </div>
    </div>
</div>

{{-- Modal Tambah Periode --}}
<div id="modalPeriode" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Tambah Periode Baru</h3>
            <button onclick="document.getElementById('modalPeriode').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6">
            <form action="{{ route('admin.data_periode.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Tahun Periode</label>
                    <input type="text" name="tahun" required value="{{ date('Y') }}"
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" required 
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" required 
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Status Keaktifan</label>
                    <div class="relative">
                        <select name="status" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm font-bold pr-10">
                            <option value="aktif">Aktif Sedang Berjalan</option>
                            <option value="berakhir">Berakhir (Ditutup)</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <p class="text-[10px] text-blue-600 font-medium mt-1 italic">* Jika diaktifkan, periode lain otomatis akan ditutup.</p>
                </div>

                <div class="pt-6 flex justify-end gap-4 items-center">
                    <button type="button" onclick="document.getElementById('modalPeriode').classList.add('hidden')" class="text-sm font-bold text-gray-500 hover:text-gray-700 transition">Batal</button>
                    <button type="submit" class="px-6 py-2.5 bg-[#007BFF] hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition uppercase tracking-widest">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Periode --}}
<div id="modalEditPeriode" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Edit Periode Tanam</h3>
            <button onclick="document.getElementById('modalEditPeriode').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6">
            <form id="formEditPeriode" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Tahun Periode</label>
                    <input type="text" name="tahun" id="edit_tahun" required
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" required 
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" required 
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Status Keaktifan</label>
                    <div class="relative">
                        <select name="status" id="edit_status" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm font-bold pr-10">
                            <option value="aktif">Aktif Sedang Berjalan</option>
                            <option value="berakhir">Berakhir (Ditutup)</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex justify-end gap-4 items-center">
                    <button type="button" onclick="document.getElementById('modalEditPeriode').classList.add('hidden')" class="text-sm font-bold text-gray-500 hover:text-gray-700 transition">Batal</button>
                    <button type="submit" class="px-6 py-2.5 bg-[#FFC107] hover:bg-yellow-600 text-white text-xs font-bold rounded-lg shadow-sm transition uppercase tracking-widest">
                        Update Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditModal(id, tahun, tanggal_mulai, tanggal_selesai, status) {
        document.getElementById('edit_tahun').value = tahun;
        
        document.getElementById('edit_tanggal_mulai').value = tanggal_mulai.split(' ')[0];
        document.getElementById('edit_tanggal_selesai').value = tanggal_selesai.split(' ')[0];
        
        document.getElementById('edit_status').value = status;
        
        let updateUrl = '{{ route("admin.data_periode.update", ":id") }}';
        updateUrl = updateUrl.replace(':id', id);
        document.getElementById('formEditPeriode').action = updateUrl;
        
        document.getElementById('modalEditPeriode').classList.remove('hidden');
    }
</script>
@endsection