@extends('layouts.admin_layout')

@section('title', 'Kelola Pengajuan Bibit Petani')

@section('content')
<div class="flex flex-col h-full overflow-hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col flex-1 min-h-0">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-lg font-black text-gray-800 uppercase tracking-tight">Daftar Pengajuan Bibit</h3>
            <p class="text-xs text-gray-500 mt-1">Gunakan halaman ini untuk memverifikasi rencana tanam petani agar jatah dapat dihitung secara proporsional.</p>
        </div>
        
        <div class="overflow-x-auto overflow-y-auto flex-1 relative custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">No</th>
                        <th class="p-4 border-b">Nama Petani</th>
                        <th class="p-4 border-b">Lahan / Blok</th>
                        <th class="p-4 border-b">Bibit Diajukan</th>
                        <th class="p-4 border-b text-center">Status</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pengajuans as $index => $p)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600">{{ $index + 1 }}</td>
                        <td class="p-4">
                            <span class="font-bold text-gray-800 block">{{ $p->petani->nama_lengkap ?? 'Petani Dihapus' }}</span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-widest italic">{{ $p->petani->nik ?? '-' }}</span>
                        </td>
                        <td class="p-4">
                            <span class="text-gray-700 block">{{ $p->lahan->nama_blok }}</span>
                            <span class="text-[10px] font-bold text-[#2D6A4F]">{{ $p->lahan->luas_lahan }} m²</span>
                        </td>
                        <td class="p-4">
                            <span class="px-2 py-1 bg-green-50 text-green-700 rounded-lg text-[10px] font-bold uppercase border border-green-100">
                                {{ $p->bibit->nama_bibit }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            @if($p->status == 'disetujui')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[10px] font-bold">DISETUJUI</span>
                            @elseif($p->status == 'ditolak')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-[10px] font-bold">DITOLAK</span>
                            @else
                                <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-[10px] font-bold">MENUNGGU</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center gap-2">
                                @if($p->status == 'menunggu')
                                    <form action="{{ route('admin.verifikasi_pengajuan', $p->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="disetujui">
                                        <button type="button" onclick="confirmActionPengajuan(this, 'Setujui pengajuan bibit ini?')" title="Setujui" class="w-8 h-8 bg-[#2D6A4F] hover:bg-green-700 text-white rounded shadow-sm flex items-center justify-center transition">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.verifikasi_pengajuan', $p->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="ditolak">
                                        <button type="button" onclick="confirmActionPengajuan(this, 'Tolak pengajuan ini?', 'warning')" title="Tolak" class="w-8 h-8 bg-red-500 hover:bg-red-700 text-white rounded shadow-sm flex items-center justify-center transition">
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
                        <td colspan="6" class="p-10 text-center text-gray-400 italic">Belum ada pengajuan bibit dari petani.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmActionPengajuan(button, message, type = 'question') {
        const form = button.closest('form');
        const status = form.querySelector('input[name="status"]').value;

        if (status === 'ditolak') {
            Swal.fire({
                title: 'Alasan Penolakan',
                input: 'textarea',
                inputLabel: 'Mengapa pengajuan ini ditolak?',
                inputPlaceholder: 'Contoh: Bibit tidak sesuai musim atau lahan sedang bermasalah...',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Tolak Sekarang',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Alasan penolakan wajib diisi!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'catatan';
                    input.value = result.value;
                    form.appendChild(input);
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
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    }
</script>
@endpush
@endsection
