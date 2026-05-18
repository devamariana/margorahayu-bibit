@extends('layouts.admin_layout')

@section('title', 'Monitor Riwayat Transaksi')

@section('content')
<div class="flex flex-col h-full overflow-hidden">
    {{-- Row 1: Status & Title --}}
    <div class="z-20 bg-[#F0F7F2]/95 backdrop-blur-sm pt-2 pb-6 mb-4">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tight">Monitor Riwayat Transaksi</h2>
        <div class="flex items-center gap-2 text-green-600 font-medium text-[10px] bg-green-50 px-3 py-1 rounded-full border border-green-100">
            <i class="fas fa-check-circle"></i>
            <span>Midtrans: Connected</span>
        </div>
    </div>

    {{-- Row 2: Search & Filters --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col lg:flex-row justify-between items-center gap-4 mb-4">
        <div class="w-full lg:w-1/3">
            <div class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors"></i>
                <input type="text" id="searchTrx" onkeyup="filterTransaksi()" placeholder="Cari Kode TRX atau Nama..." class="w-full pl-12 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:bg-white transition-all text-xs">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end">
            <select id="filterPeriode" onchange="filterTransaksi()" class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] cursor-pointer">
                <option value="">Semua Periode</option>
                @php
                    $uniquePeriods = collect($transaksis)->map(function($item) {
                        return [
                            'val' => $item->created_at->format('Y-m'),
                            'label' => $item->created_at->translatedFormat('F Y')
                        ];
                    })->unique('val')->sortByDesc('val');
                @endphp
                @foreach($uniquePeriods as $p)
                    <option value="{{ $p['val'] }}">{{ $p['label'] }}</option>
                @endforeach
            </select>

            <select id="filterStatus" onchange="filterTransaksi()" class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] cursor-pointer">
                <option value="">Semua Status</option>
                <option value="menunggu_persetujuan">Menunggu Verifikasi</option>
                <option value="menunggu_pembayaran">Belum Bayar</option>
                <option value="sukses">Lunas</option>
                <option value="kadaluarsa">Kadaluarsa</option>
                <option value="ditolak">Ditolak</option>
            </select>

            <a href="{{ route('admin.export.pdf') }}" target="_blank" class="bg-[#007BFF] hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 transition duration-300 text-[10px] uppercase">
                <i class="fas fa-print"></i> Cetak Laporan
            </a>
        </div>
    </div>
</div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col flex-1 min-h-0">
        <div class="overflow-x-auto overflow-y-auto flex-1 relative custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">Tanggal</th>
                        <th class="p-4 border-b">Nama Petani</th>
                        <th class="p-4 border-b">Bibit Dibeli</th>
                        <th class="p-4 border-b">Total Harga</th>
                        <th class="p-4 border-b text-center">Status</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksis as $t)
                    <tr class="hover:bg-gray-50 transition transaction-row" 
                        data-status="{{ $t->status_pembayaran }}" 
                        data-periode="{{ $t->created_at->format('Y-m') }}"
                        data-order-id="{{ $t->order_id }}"
                        data-nama="{{ strtolower($t->petani->nama_lengkap ?? '') }}">
                        <td class="p-4 text-gray-600">
                            {{ $t->created_at->format('d/m/Y') }}<br>
                            <span class="text-[10px] font-black text-gray-400">{{ $t->order_id }}</span>
                        </td>
                        <td class="p-4 font-bold text-gray-800 uppercase">{{ $t->petani->nama_lengkap ?? 'Petani Dihapus' }}</td>
                        <td class="p-4 text-gray-600">
                            {{ $t->bibit->nama_bibit ?? 'Bibit Dihapus' }} ({{ $t->jumlah_beli }}kg)<br>
                            <span class="text-[10px] text-gray-400">Lahan: {{ $t->lahan->nama_blok ?? 'Dihapus' }}</span>
                        </td>
                        <td class="p-4 font-bold text-[#2D6A4F] tracking-tight">
                            Rp {{ number_format($t->total_harga, 0, ',', '.') }}<br>
                            <span class="text-[9px] text-gray-400 font-medium uppercase">{{ str_replace('_', ' ', $t->metode_pembayaran) }}</span>
                        </td>
                        <td class="p-4 text-center">
                            @if($t->status_pembayaran == 'sukses')
                                <span class="px-3 py-1 bg-green-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter shadow-sm">LUNAS</span>
                            @elseif($t->status_pembayaran == 'menunggu_pembayaran' || $t->status_pembayaran == 'pending')
                                <span class="px-3 py-1 bg-orange-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter shadow-sm">MENUNGGU</span>
                            @elseif($t->status_pembayaran == 'kadaluarsa')
                                <span class="px-3 py-1 bg-gray-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter shadow-sm">KADALUARSA</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 text-white text-[10px] font-bold rounded-md uppercase tracking-tighter shadow-sm">DITOLAK/BATAL</span>
                            @endif

                            @if($t->bukti_pembayaran)
                                <div class="mt-2">
                                    <a href="{{ asset('uploads/bukti_bayar/' . $t->bukti_pembayaran) }}" target="_blank" class="text-[9px] text-blue-600 font-black underline hover:text-blue-800">
                                        <i class="fas fa-image mr-1"></i> LIHAT BUKTI
                                    </a>
                                </div>
                            @endif

                            {{-- Verifikasi Manual oleh Admin --}}
                            @if(in_array($t->status_pembayaran, ['pending', 'menunggu_pembayaran']) || ($t->status_pembayaran == 'sukses' && $t->metode_pembayaran != 'midtrans'))
                                <div class="mt-3 flex justify-center gap-1">
                                    <form action="{{ route('admin.verifikasi_transaksi', $t->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status_pembayaran" value="sukses">
                                        <button type="submit" class="bg-green-100 text-green-700 px-2 py-1 rounded text-[9px] font-bold hover:bg-green-200" title="Verifikasi Lunas">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.verifikasi_transaksi', $t->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status_pembayaran" value="ditolak">
                                        <button type="submit" class="bg-red-100 text-red-700 px-2 py-1 rounded text-[9px] font-bold hover:bg-red-200" title="Tolak">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        <td class="p-4 text-center">
                            <div class="flex justify-center gap-1">
                                @if($t->status_pembayaran == 'sukses')
                                    <a href="{{ route('petani.invoice', $t->id) }}" target="_blank" class="p-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm transition flex items-center justify-center" title="Cetak Invoice (PDF)">
                                        <i class="fas fa-file-invoice text-[10px]"></i>
                                    </a>
                                    <a href="{{ route('petani.struk', $t->id) }}" target="_blank" class="p-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-md shadow-sm transition flex items-center justify-center" title="Cetak Struk (Thermal)">
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
                        <td colspan="5" class="p-6 text-center text-gray-400 italic">Belum ada data transaksi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterTransaksi() {
    let filterPeriode = document.getElementById("filterPeriode").value;
    let filterStatus = document.getElementById("filterStatus").value;
    let searchText = document.getElementById("searchTrx").value.toLowerCase();
    let rows = document.querySelectorAll("tr.transaction-row");

    rows.forEach(row => {
        let status = row.getAttribute("data-status");
        let periode = row.getAttribute("data-periode");
        let orderId = row.getAttribute("data-order-id").toLowerCase();
        let nama = row.getAttribute("data-nama").toLowerCase();
        
        let matchesStatus = (filterStatus === "" || (filterStatus === 'menunggu_pembayaran' && (status === 'pending' || status === 'menunggu_pembayaran')) || status === filterStatus);
        let matchesPeriode = (filterPeriode === "" || periode === filterPeriode);
        let matchesSearch = (orderId.includes(searchText) || nama.includes(searchText));

        if (matchesStatus && matchesPeriode && matchesSearch) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>
@endsection