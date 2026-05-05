@extends('layouts.admin_layout') {{-- PERBAIKAN: Pastikan path ke layout admin benar --}}

@section('title', 'Dashboard Admin Overview')

@section('content')
<div class="space-y-6">
    {{-- ALERT STOK KRITIS (Hanya Muncul jika ada stok < 50) --}}
    @php $stokKritis = \App\Models\Bibit::where('stok', '<', 50)->get(); @endphp
    @if($stokKritis->count() > 0)
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-sm animate-pulse">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                <div class="text-sm">
                    <p class="text-red-800 font-black uppercase tracking-tighter">Peringatan: Stok Bibit Menipis!</p>
                    <p class="text-red-700 text-xs mt-1">
                        Segera lakukan pengadaan untuk: 
                        @foreach($stokKritis as $s)
                            <span class="font-bold underline">{{ $s->nama_bibit }} (Sisa {{ $s->stok }} Kg)</span>{{ !$loop->last ? ',' : '' }}
                        @endforeach
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Bagian Statistik --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 text-center">
            <p class="text-gray-500 text-xs font-bold uppercase mb-2">Total Petani</p>
            {{-- DATA ASLI DARI DATABASE --}}
            <p class="text-3xl font-bold text-gray-800 tracking-tight">{{ $totalPetani }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 text-center">
            <p class="text-gray-500 text-xs font-bold uppercase mb-2">Stok Bibit</p>
            {{-- DATA ASLI DARI DATABASE --}}
            <p class="text-3xl font-bold text-gray-800 tracking-tight">{{ number_format($totalStok) }} kg</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 text-center">
            <p class="text-gray-500 text-xs font-bold uppercase mb-2">Menunggu Verifikasi</p>
            {{-- DATA ASLI DARI DATABASE (Tadinya Transaksi Berhasil) --}}
            <p class="text-3xl font-bold text-orange-600 tracking-tight">{{ $totalPending }}</p>
        </div>
    </div>

    {{-- Bagian Grafik --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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

    {{-- Tabel Transaksi --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-700">Petani Baru Terdaftar (Panel Admin)</h3>
        </div>
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase font-bold">
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
@endsection