@extends('layouts.petani_layout')

@section('title', 'Riwayat Pembelian')

@section('content')
<style>
    /* Custom Scrollbar for the table container */
    .custom-scroll::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: rgba(45, 106, 79, 0.2);
        border-radius: 10px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(45, 106, 79, 0.4);
    }
</style>
<div class="space-y-6">
    {{-- PAGE HEADER STICKY --}}
    <div class="sticky top-0 z-20 bg-[#F0F7F2]/95 backdrop-blur-sm pt-2 pb-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Pembelian</h2>
            
            <div class="relative w-full md:w-64">
                <form action="{{ route('petani.riwayat') }}" method="GET" id="filterForm">
                    <select name="periode" onchange="this.form.submit()" class="appearance-none w-full bg-white border border-gray-300 rounded-lg px-4 py-2 pr-10 text-xs focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] cursor-pointer shadow-sm transition-all hover:border-[#2D6A4F]">
                        <option value="">Semua Periode</option>
                        @php
                            $periods = \DB::table('transaksis')
                                ->where('petani_id', $riwayat->first()->petani_id ?? 0)
                                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as val, DATE_FORMAT(created_at, '%M %Y') as label")
                                ->distinct()
                                ->orderBy('val', 'desc')
                                ->get();
                        @endphp
                        @foreach($periods as $p)
                            <option value="{{ $p->val }}" {{ request('periode') == $p->val ? 'selected' : '' }}>{{ $p->label }}</option>
                        @endforeach
                    </select>
                </form>
                <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none">
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
        <div class="w-full overflow-x-auto overflow-y-auto max-h-[calc(100vh-280px)] relative custom-scroll">
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
                            <button onclick="showDetail({{ json_encode($r) }}, '{{ $r->lahan->nama_blok ?? 'Dihapus' }}', '{{ $r->bibit->nama_bibit ?? 'Dihapus' }}')" 
                                    class="px-4 py-1.5 bg-white text-[#2D6A4F] border border-[#2D6A4F] rounded-lg text-[10px] font-black uppercase hover:bg-[#2D6A4F] hover:text-white transition-all flex items-center justify-center gap-2 mx-auto">
                                <i class="fas fa-info-circle"></i>
                                <span>Detail</span>
                            </button>
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

{{-- MODAL DETAIL TRANSAKSI --}}
<div id="modalDetail" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-3xl overflow-hidden shadow-2xl transform transition-all">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-lg font-black text-gray-800">Detail Transaksi</h3>
                <p id="detOrderId" class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"></p>
            </div>
            <button onclick="closeDetail()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-[9px] font-black text-gray-400 uppercase">Tanggal</p>
                    <p id="detTanggal" class="text-sm font-bold text-gray-800"></p>
                </div>
                <div class="space-y-1">
                    <p class="text-[9px] font-black text-gray-400 uppercase">Status</p>
                    <div id="detStatus"></div>
                </div>
            </div>
            <div class="p-4 bg-[#F0F7F2] rounded-2xl border border-green-50/50">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-500 uppercase">Bibit</span>
                        <span id="detBibit" class="text-sm font-black text-[#2D6A4F]"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-500 uppercase">Lahan</span>
                        <span id="detLahan" class="text-sm font-bold text-gray-700"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-500 uppercase">Jumlah</span>
                        <span id="detJumlah" class="text-sm font-black text-gray-800"></span>
                    </div>
                    <div class="pt-3 border-t border-green-100 flex justify-between items-center">
                        <span class="text-[10px] font-black text-gray-400 uppercase">Total Bayar</span>
                        <span id="detTotal" class="text-lg font-black text-gray-800"></span>
                    </div>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-[9px] font-black text-gray-400 uppercase">Metode Pembayaran</p>
                <p id="detMetode" class="text-xs font-bold text-blue-600 uppercase"></p>
            </div>
            
            <div id="detBuktiContainer" class="space-y-2 hidden">
                <p class="text-[9px] font-black text-gray-400 uppercase">Bukti Pembayaran</p>
                <div class="rounded-xl overflow-hidden border-2 border-dashed border-gray-200">
                    <img id="detBuktiImg" src="" alt="Bukti" class="w-full h-auto max-h-48 object-contain bg-gray-50">
                </div>
            </div>
        </div>
        <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3">
            <button onclick="closeDetail()" class="flex-1 py-3 bg-gray-800 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-black transition-colors">
                Tutup
            </button>
            <a href="https://wa.me/6282228154201" target="_blank" class="flex items-center justify-center px-4 bg-green-500 text-white rounded-xl hover:bg-green-600 transition-colors shadow-lg shadow-green-100">
                <i class="fab fa-whatsapp"></i>
            </a>
        </div>
    </div>
</div>

<script>
function showDetail(data, namaLahan, namaBibit) {
    const modal = document.getElementById('modalDetail');
    document.getElementById('detOrderId').innerText = data.order_id;
    document.getElementById('detTanggal').innerText = new Date(data.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    document.getElementById('detBibit').innerText = namaBibit;
    document.getElementById('detLahan').innerText = namaLahan;
    document.getElementById('detJumlah').innerText = data.jumlah_beli + ' Kg';
    document.getElementById('detTotal').innerText = 'Rp ' + new Number(data.total_harga).toLocaleString('id-ID');
    document.getElementById('detMetode').innerText = data.metode_pembayaran.replace('_', ' ');

    // Status Badge
    const statusContainer = document.getElementById('detStatus');
    let statusHtml = '';
    if (data.status_pembayaran === 'sukses' || data.status_pembayaran === 'lunas') {
        statusHtml = '<span class="px-2 py-0.5 bg-green-500 text-white text-[9px] font-black rounded uppercase shadow-sm">Lunas</span>';
    } else {
        statusHtml = '<span class="px-2 py-0.5 bg-orange-500 text-white text-[9px] font-black rounded uppercase shadow-sm">' + data.status_pembayaran + '</span>';
    }
    statusContainer.innerHTML = statusHtml;

    // Bukti Gambar
    const buktiContainer = document.getElementById('detBuktiContainer');
    if (data.bukti_pembayaran) {
        document.getElementById('detBuktiImg').src = '/uploads/bukti_bayar/' + data.bukti_pembayaran;
        buktiContainer.classList.remove('hidden');
    } else {
        buktiContainer.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDetail() {
    const modal = document.getElementById('modalDetail');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection