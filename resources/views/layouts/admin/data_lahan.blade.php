@extends('layouts.admin_layout')

@section('title', 'Kelola Data Lahan Petani')

@section('content')
<div class="flex flex-col h-full overflow-hidden">
    {{-- Notifikasi Sukses via Layout (Global SweetAlert2) --}}
    <div class="flex-none">
        <div class="flex justify-end items-center mb-4">
            <div class="flex items-center gap-3">
                {{-- Filter Tahun --}}
                <form action="{{ route('admin.data_lahan') }}" method="GET" class="flex items-center">
                    <select name="tahun" onchange="this.form.submit()" class="bg-white border border-gray-300 text-gray-700 text-xs rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#2D6A4F] outline-none font-bold shadow-sm">
                        @foreach($tahunTersedia as $thn)
                            <option value="{{ $thn }}" {{ $selectedTahun == $thn ? 'selected' : '' }}>Tahun {{ $thn }}</option>
                        @endforeach
                        @if(!$tahunTersedia->contains(date('Y')))
                            <option value="{{ date('Y') }}" {{ $selectedTahun == date('Y') ? 'selected' : '' }}>Tahun {{ date('Y') }}</option>
                        @endif
                    </select>
                </form>

                <form action="{{ route('admin.data_lahan') }}" method="GET" class="relative w-full md:w-80">
                    <input type="text" 
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari lahan (blok/petani)..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D6A4F] focus:outline-none shadow-sm bg-white text-xs">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </form>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col flex-1 min-h-0">
        <div class="overflow-x-auto overflow-y-auto flex-1 relative custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">No</th>
                        <th class="p-4 border-b">Nama Pemilik</th>
                        <th class="p-4 border-b">Lokasi/Blok Lahan</th>
                        <th class="p-4 border-b">Luas Lahan</th>
                        <th class="p-4 border-b">Pengajuan Bibit</th>
                        <th class="p-4 border-b">Bibit yang Dibeli</th>
                        <th class="p-4 border-b">Status Lahan</th>
                        <th class="p-4 border-b text-center">Aksi Lahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lahans as $index => $l)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600">{{ $index + 1 }}</td>
                        <td class="p-4 font-bold text-gray-800">{{ $l->petani->nama_lengkap ?? 'Petani Dihapus' }}</td>
                        <td class="p-4 text-gray-600 uppercase">{{ $l->nama_blok }}</td>
                        <td class="p-4 font-medium text-gray-700">{{ $l->luas_lahan }} m²</td>
                        <td class="p-4">
                            @php
                                // Ambil pengajuan terakhir untuk lahan ini pada TAHUN TERPILIH
                                $pengajuan = $l->pengajuans->filter(function($p) use ($selectedTahun) {
                                    return $p->created_at->year == $selectedTahun;
                                })->last(); 
                            @endphp
                            
                            @if($pengajuan)
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1">
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-[10px] font-black uppercase border border-blue-200">
                                            {{ $pengajuan->bibit->nama_bibit ?? 'Bibit Dihapus' }}
                                        </span>
                                    </div>
                                    <span class="text-[9px] uppercase font-bold {{ $pengajuan->status === 'disetujui' ? 'text-green-600' : ($pengajuan->status === 'ditolak' ? 'text-red-500' : 'text-amber-500') }}">
                                        {{ $pengajuan->status }}
                                    </span>
                                </div>
                            @else
                                <span class="text-[10px] text-gray-400 italic">Tidak ada pengajuan di {{ $selectedTahun }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @php
                                // Ambil bibit terakhir yang dibeli SUKSES pada tahun ini
                                $lastTrx = $l->transaksi
                                    ->whereIn('status_pembayaran', ['sukses', 'lunas'])
                                    ->filter(function($t) use ($selectedTahun) {
                                        return $t->bibit && $t->created_at->year == $selectedTahun;
                                    })
                                    ->last();
                            @endphp
                            
                            @if($lastTrx)
                                <div class="inline-flex flex-col items-center">
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[9px] font-bold uppercase shadow-sm">
                                        {{ $lastTrx->bibit->nama_bibit }}
                                    </span>
                                    <span class="text-[8px] text-gray-400 mt-1">{{ $lastTrx->created_at->format('d/m/Y') }}</span>
                                </div>
                            @else
                                <span class="text-[10px] text-gray-400 italic">Belum ada pembelian di {{ $selectedTahun }}</span>
                            @endif
                        </td>
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
                                        <button type="button" onclick="confirmActionLahan(this, 'Setujui data lahan ini?')" title="Setujui Lahan" class="w-8 h-8 bg-[#2D6A4F] hover:bg-green-700 text-white rounded shadow-sm flex items-center justify-center transition">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.verifikasi_lahan', $l->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="ditolak">
                                        <button type="button" onclick="confirmActionLahan(this, 'Tolak data lahan ini?', 'warning')" title="Tolak Lahan" class="w-8 h-8 bg-red-500 hover:bg-red-700 text-white rounded shadow-sm flex items-center justify-center transition">
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
        <div class="p-4 bg-gray-50 border-t border-gray-100">
            {{ $lahans->appends(['search' => request('search')])->links() }}
        </div>
    </div>
</div>
@push('scripts')
<script>
    function confirmActionLahan(button, message, type = 'question') {
        const form = button.closest('form');
        const status = form.querySelector('input[name="status"]').value;

        if (status === 'ditolak') {
            Swal.fire({
                title: 'Alasan Penolakan Lahan',
                input: 'textarea',
                inputLabel: 'Berikan alasan mengapa lahan ini ditolak',
                inputPlaceholder: 'Contoh: Luas lahan tidak valid...',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Tolak Lahan',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) return 'Alasan harus diisi!'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const extraInput = document.createElement('input');
                    extraInput.type = 'hidden';
                    extraInput.name = 'catatan_admin';
                    extraInput.value = result.value;
                    form.appendChild(extraInput);
                    form.submit();
                }
            });
        } else {
            Swal.fire({
                title: 'Konfirmasi',
                text: message,
                icon: type,
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Proses Sekarang'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }
    }

</script>
@endpush
@endsection