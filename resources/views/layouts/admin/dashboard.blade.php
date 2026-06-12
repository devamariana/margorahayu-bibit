@extends('layouts.admin_layout') {{-- PERBAIKAN: Pastikan path ke layout admin benar --}}

@section('title', 'Dashboard Admin Overview')

@section('content')
<div class="h-full overflow-y-auto custom-scrollbar p-1">
    {{-- ALERT STOK KRITIS DIHAPUS SESUAI PERMINTAAN USER --}}

    {{-- Bagian Statistik Utama --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5">
            <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center text-[#2D6A4F]">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Petani</p>
                <h3 class="text-2xl font-black text-gray-800">{{ $totalPetani }}</h3>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5">
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                <i class="fas fa-seedling text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Stok Bibit Aktif</p>
                <h3 class="text-2xl font-black text-gray-800">{{ number_format($totalStok) }} <span class="text-xs text-gray-400 font-medium">Kg</span></h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5">
            <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600">
                <i class="fas fa-user-clock text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Antrean Verifikasi</p>
                <h3 class="text-2xl font-black text-orange-600">{{ $totalPending }}</h3>
            </div>
        </div>
    </div>



    {{-- Bagian Grafik --}}
    @if(!empty($hasPengambilan) && $hasPengambilan)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Distribusi Penjualan Bibit</h3>
                <div class="h-64 relative border-2 border-gray-50 rounded-lg">
                    <canvas id="adminSalesChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider text-center">Status Pembayaran</h3>
                <div class="h-64 relative border-2 border-gray-50 rounded-lg flex justify-center">
                    <canvas id="adminStatusChart"></canvas>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Distribusi Penjualan Bibit</h3>
            <p class="text-xs text-gray-500 mt-2">Belum ada pengambilan bibit (transaksi sukses). Grafik akan muncul setelah ada data.</p>
        </div>
    @endif


    {{-- Tabel Transaksi --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-700">Petani Baru Terdaftar (Panel Admin)</h3>
        </div>
        <div class="overflow-x-auto overflow-y-auto max-h-[400px] text-xs relative">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase font-bold sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">Tanggal Daftar</th>
                        <th class="p-4 border-b">Nama Petani</th>
                        <th class="p-4 border-b">Username</th>
                        <th class="p-4 border-b">Luas Lahan</th>
                        <th class="p-4 border-b">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    {{-- LOOPING DATA PETANI TERBARU --}}
                    @forelse($petaniTerbaru as $p)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600">{{ $p->created_at->format('Y-m-d') }}</td>
                        <td class="p-4 font-medium text-gray-800 uppercase">{{ $p->nama_lengkap }}</td>
                        <td class="p-4 text-blue-600">{{ $p->user->username ?? '-' }}</td>
                        <td class="p-4 font-bold text-gray-800">{{ number_format(App\Models\Lahan::where('petani_id', $p->id)->where('status', 'disetujui')->sum('luas_lahan'), 0, ',', '.') }} m²</td>
                        <td class="p-4 italic font-bold {{ $p->status == 'pending' ? 'text-orange-500' : 'text-green-600' }}">
                            {{ strtoupper($p->status) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500">Belum ada data petani terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Script Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@if(!empty($hasPengambilan) && $hasPengambilan)
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new Chart(document.getElementById('adminSalesChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: 'Penjualan Bibit (kg)',
                    data: {!! json_encode($chartData) !!},
                    borderColor: '#2D6A4F',
                    backgroundColor: 'rgba(45, 106, 79, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        new Chart(document.getElementById('adminStatusChart'), {
            type: 'pie',
            data: {
                labels: ['Lunas', 'Pending'],
                datasets: [{
                    data: [{{ $totalLunas }}, {{ $totalPendingTx }}],
                    backgroundColor: ['#2D6A4F', '#F59E0B']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    });
</script>
@endif
@endsection