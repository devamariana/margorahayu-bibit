@extends('layouts.petani_layout')

@section('title', 'Pengajuan Bibit')

@section('content')
<div class="p-8 bg-[#F0F7F2] min-h-screen">
    {{-- Header --}}
    <div class="sticky top-0 z-20 bg-[#F0F7F2]/95 backdrop-blur-sm pt-2 pb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-center md:text-left">
            <div>
                <h1 class="text-3xl font-extrabold text-[#1B4332] tracking-tight uppercase">PENGAJUAN BIBIT</h1>
                <p class="text-gray-500 text-sm">Ajukan bibit yang ingin Anda tanam untuk mendapatkan jatah proporsional.</p>
            </div>
            <button onclick="toggleModal()" class="bg-[#2D6A4F] hover:bg-[#1B4332] text-white px-6 py-3 rounded-2xl font-bold shadow-lg transition transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i> Buat Pengajuan Baru
            </button>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-2xl shadow-sm mb-8">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                <i class="fas fa-info-circle text-xl"></i>
            </div>
            <div>
                <h4 class="font-bold text-blue-900">Mengapa harus mengajukan?</h4>
                <p class="text-sm text-blue-700 leading-relaxed mt-1">
                    Berdasarkan kebijakan terbaru, jatah bibit dihitung secara adil berdasarkan total luas lahan petani yang <b>mengajukan</b> bibit tertentu. 
                    Pastikan Anda mengajukan bibit sesuai rencana tanam Anda agar sistem dapat menghitung pembagi jatah dengan akurat.
                </p>
                @if(isset($periodeAktif))
                    <div class="mt-3 inline-block px-4 py-1.5 bg-blue-600 text-white text-[10px] font-black rounded-lg uppercase tracking-widest">
                        Periode Aktif: {{ $periodeAktif->tahun }} - Musim {{ $periodeAktif->musim }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat Pengajuan --}}
    <div class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-gray-50">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#2D6A4F] text-white">
                        <th class="px-6 py-4 text-xs font-bold uppercase">No</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase">Tanggal</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase">Lahan / Blok</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Varietas Bibit</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pengajuans as $index => $p)
                    <tr class="hover:bg-green-50 transition">
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $p->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-[#1B4332] block">{{ $p->lahan->nama_blok }}</span>
                            <span class="text-[10px] text-gray-400 capitalize tracking-wider italic">{{ $p->lahan->luas_lahan }} m²</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-green-50 text-green-700 rounded-full text-[10px] font-bold uppercase border border-green-200">
                                {{ $p->bibit->nama_bibit }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($p->status == 'disetujui')
                                <span class="px-3 py-1 bg-green-100 text-green-700 font-bold rounded-lg text-xs">DISETUJUI</span>
                            @elseif($p->status == 'ditolak')
                                <div class="flex flex-col items-center gap-1">
                                    <span class="px-3 py-1 bg-red-100 text-red-700 font-bold rounded-lg text-xs">DITOLAK</span>
                                    @if($p->catatan)
                                        <span class="text-[9px] text-red-500 italic">"{{ $p->catatan }}"</span>
                                    @endif
                                </div>
                            @else
                                <span class="px-3 py-1 bg-orange-100 text-orange-700 font-bold rounded-lg text-xs">MENUNGGU</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($p->status == 'menunggu')
                                <form action="{{ route('petani.hapus_pengajuan', $p->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmAction(this, 'Batalkan pengajuan ini?', 'warning')" class="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-300"><i class="fas fa-lock text-xs"></i></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">Belum ada data pengajuan. Silakan buat pengajuan pertama Anda.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH PENGAJUAN --}}
<div id="modalPengajuan" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl">
        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
            <h2 class="text-2xl font-black text-[#1B4332] uppercase">BUAT PENGAJUAN</h2>
            <button onclick="toggleModal()" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>

        <form action="{{ route('petani.store_pengajuan') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Pilih Lahan</label>
                    <select name="lahan_id" required class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] outline-none transition text-sm">
                        <option value="">-- Pilih Lahan --</option>
                        @foreach($lahans as $l)
                            <option value="{{ $l->id }}">{{ $l->nama_blok }} ({{ $l->luas_lahan }} m²)</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Varietas Bibit</label>
                    <select name="bibit_id" required class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] outline-none transition text-sm">
                        <option value="">-- Pilih Bibit --</option>
                        @foreach($bibits as $b)
                            <option value="{{ $b->id }}">{{ $b->nama_bibit }} (Stok: {{ $b->stok }} kg)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full mt-8 bg-[#2D6A4F] text-white p-4 rounded-2xl font-black shadow-lg hover:bg-[#1B4332] transition tracking-widest uppercase">
                KIRIM PENGAJUAN
            </button>
        </form>
    </div>
</div>

<script>
    function toggleModal() {
        const modal = document.getElementById('modalPengajuan');
        modal.classList.toggle('hidden');
    }
</script>
@endsection
