@extends('layouts.petani_layout')

@section('title', 'Dashboard Petani')

@section('content')
<div class="space-y-6">

    {{-- ALERT MESSAGE UNTUK VERIFIKASI DITOLAK --}}
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl relative flex items-center gap-3">
            <i class="fas fa-hand-paper text-xl"></i>
            <span class="block sm:inline font-bold">{{ session('error') }}</span>
        </div>
    @endif

    {{-- NOTIFIKASI BIBIT TERBARU --}}
    @if(isset($bibitTerbaru))
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg shadow-md animate-pulse">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-bullhorn text-blue-500 text-xl mr-3"></i>
                <p class="text-sm text-blue-800 font-bold">
                    INFO STOK: Bibit <span class="text-blue-600 underline">{{ $bibitTerbaru->nama_bibit }}</span> tersedia! Segera hubungi pengurus.
                </p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-blue-500 hover:text-blue-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center">
            <p class="text-gray-500 text-sm mb-2 font-medium">Status Verifikasi Profil</p>
            @if($petani && $petani->status == 'disetujui')
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase mb-2">
                    <i class="fas fa-check-circle mr-1"></i> Terverifikasi
                </div>
                <p class="text-xl font-black text-gray-800 uppercase">{{ $petani->nama_lengkap }}</p>
            @else
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-bold uppercase mb-2">
                    <i class="fas fa-hourglass-half mr-1"></i> Pending
                </div>
                <p class="text-sm text-gray-400">Menunggu Validasi Admin</p>
            @endif
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center">
            <p class="text-gray-500 text-sm mb-2 font-medium">Estimasi Jatah Bibit</p>
            @if($petani && $petani->status == 'disetujui')
                <p class="text-4xl font-black text-[#2D6A4F]">{{ $jatahBibit }} <span class="text-sm font-normal text-gray-400">Kg</span></p>
                <p class="text-[10px] text-gray-400 mt-1 italic">*Berdasarkan luas lahan {{ $totalLuas ?? 0 }} m²</p>
            @else
                <p class="text-2xl font-bold text-gray-300 italic">Belum Tersedia</p>
                <p class="text-[10px] text-orange-400 mt-1">Lengkapi profil & tunggu verifikasi</p>
            @endif
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center">
            <p class="text-gray-500 text-sm mb-2 font-medium">Luas Lahan Terdaftar</p>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($totalLuas, 0, ',', '.') ?? '0' }} <span class="text-sm font-normal text-gray-400">m²</span></p>
        </div>
    </div>

    {{-- CHART (MASIH DUMMY) --}}
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center">
            <i class="fas fa-chart-area mr-2 text-green-600"></i> Statistik Pengambilan Bibit
        </h3>
        <div style="height: 250px; position: relative;">
            <canvas id="pembelianChart"></canvas>
        </div>
    </div>

    {{-- RIWAYAT TRANSAKSI ASLI --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">3 Riwayat Terakhir</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white text-gray-400 text-[10px] font-bold uppercase tracking-widest">
                    <tr>
                        <th class="p-4 border-b">Tanggal</th>
                        <th class="p-4 border-b">Komoditas</th>
                        <th class="p-4 border-b">Jumlah</th>
                        <th class="p-4 border-b">Status</th>
                    </tr>
                </thead>
                <tbody class="text-sm font-medium text-gray-600">
                    {{-- PERBAIKAN: Loop data dari database --}}
                    @forelse($riwayat as $r)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4">{{ $r->created_at->format('d M Y') }}</td>
                        <td class="p-4 text-gray-800">{{ $r->bibit->nama_bibit ?? 'Bibit' }}</td>
                        <td class="p-4">{{ $r->jumlah }} Kg</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-md bg-green-100 text-green-700 text-[10px] font-bold uppercase">
                                {{ $r->status ?? 'DIAMBIL' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-400 italic">
                            Belum ada riwayat pengambilan bibit.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('pembelianChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Okt', 'Nov', 'Des', 'Jan', 'Feb', 'Mar'], 
                datasets: [{
                    label: 'Pengambilan (kg)',
                    data: [10, 25, 15, 30, 20, 45], 
                    borderColor: '#2D6A4F', 
                    backgroundColor: 'rgba(45, 106, 79, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#1B4332',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    });
</script>
@endsection