@extends('layouts.admin_layout')

@section('title', 'Rekap Laporan Kelompok Tani')

@section('content')
<div class="space-y-8 animate-fade-in">
    {{-- Header Page --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-black text-[#1B4332] uppercase tracking-tighter">Rekap Laporan Aktivitas</h2>
            <p class="text-xs text-gray-500 font-medium">
                @if($periodeTerpilih)
                    Menampilkan data periode <span class="font-bold text-[#2D6A4F]">{{ $periodeTerpilih->tahun }}</span> ({{ \Carbon\Carbon::parse($periodeTerpilih->tanggal_mulai)->format('d M') }} - {{ \Carbon\Carbon::parse($periodeTerpilih->tanggal_selesai)->format('d M Y') }})
                @else
                    Monitoring perolehan dana dan distribusi bibit kelompok tani (Semua Waktu).
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            {{-- Filter Periode --}}
            <form action="{{ route('admin.laporan') }}" method="GET" class="flex items-center gap-2">
                <select name="periode_id" onchange="this.form.submit()" class="pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-2xl text-xs font-bold text-gray-700 shadow-sm focus:ring-2 focus:ring-[#2D6A4F] outline-none appearance-none cursor-pointer min-w-[180px]">
                    <option value="">-- Semua Periode --</option>
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>
                            Periode {{ $p->tahun }} ({{ $p->status }})
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="flex gap-2">
                <a href="{{ route('admin.export.excel', ['periode_id' => request('periode_id')]) }}" class="inline-flex items-center px-5 py-2.5 bg-[#D97706] hover:bg-[#B45309] text-white rounded-2xl font-bold shadow-lg shadow-orange-100 transition-all transform hover:-translate-y-1 gap-2 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-file-excel"></i>
                    <span>Excel</span>
                </a>
                <a href="{{ route('admin.export.pdf', ['periode_id' => request('periode_id')]) }}" target="_blank" class="inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-bold shadow-lg shadow-red-100 transition-all transform hover:-translate-y-1 gap-2 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-file-pdf"></i>
                    <span>PDF</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Statistik Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center text-[#2D6A4F]">
                <i class="fas fa-wallet text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Dana Masuk</p>
                <h3 class="text-xl font-black text-gray-800">Rp {{ number_format($totalDanaMasuk, 0, ',', '.') }}</h3>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                <i class="fas fa-seedling text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Distribusi Bibit</p>
                <h3 class="text-xl font-black text-gray-800">{{ number_format($totalBibitKeluar, 0, ',', '.') }} Kg</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Transaksi Selesai</p>
                <h3 class="text-xl font-black text-gray-800">{{ $totalTransaksiSelesai }} Pesanan</h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Tabel Barang Terlaris --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                <h4 class="font-black text-gray-800 uppercase text-xs tracking-widest">Peringkat Bibit Terlaris</h4>
                <i class="fas fa-trophy text-orange-400"></i>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($terlaris as $idx => $item)
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="flex items-center gap-4">
                            <span class="w-8 h-8 rounded-full bg-white flex items-center justify-center font-black text-xs text-gray-400 shadow-sm border border-gray-100">{{ $idx + 1 }}</span>
                            <span class="font-bold text-gray-700">{{ $item->nama_bibit }}</span>
                        </div>
                        <span class="text-sm font-black text-[#2D6A4F]">{{ number_format($item->total_kg, 0, ',', '.') }} Kg</span>
                    </div>
                    @empty
                    <p class="text-center text-gray-400 text-xs italic">Belum ada data distibusi bibit.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Info Box --}}
        <div class="bg-gradient-to-br from-[#1B4332] to-[#40916C] p-8 rounded-3xl text-white flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10">
                <i class="fas fa-file-excel text-[200px]"></i>
            </div>
            <div>
                <h4 class="text-xl font-black mb-3">Sistem Laporan Otomatis</h4>
                <p class="text-sm text-green-100 leading-relaxed opacity-80">
                    Laporan ini mencakup seluruh transaksi petani yang sudah berstatus <span class="font-bold underline">LUNAS</span>. Data Excel yang diunduh dapat digunakan sebagai lampiran laporan pertanggungjawaban tahunan kelompok tani Margo Rahayu II.
                </p>
            </div>
            <div class="mt-8 flex gap-3">
                <div class="px-4 py-2 bg-white/10 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">Update Realtime</div>
                <div class="px-4 py-2 bg-white/10 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">Excel Format</div>
            </div>
        </div>
    </div>
</div>
@endsection
