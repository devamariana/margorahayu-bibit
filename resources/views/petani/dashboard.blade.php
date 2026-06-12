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

    {{-- INFORMASI UTAMA: LAHAN (SELALU MUNCUL) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- CARD 1: TOTAL LUAS LAHAN --}}
        <div class="bg-gradient-to-br from-green-600 to-green-700 p-6 rounded-2xl shadow-lg border border-green-500/20 text-white flex flex-col justify-between">
            <div>
                <p class="text-green-100 text-[10px] mb-1 font-bold uppercase tracking-widest leading-none">Total Luas Lahan Anda</p>
                <p class="text-4xl font-black leading-tight">{{ number_format($totalLuas, 0, ',', '.') }} <span class="text-xs font-medium text-green-200 uppercase">m²</span></p>
            </div>
            <p class="text-[10px] text-green-200 mt-4 uppercase font-bold tracking-tighter opacity-80 italic">Data Lahan Terverifikasi</p>
        </div>

        {{-- CARD 2: JUMLAH BLOK LAHAN --}}
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5 hover:shadow-md transition-all duration-300 group">
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                <i class="fas fa-layer-group text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest leading-none mb-1">Blok Lahan</p>
                <div class="flex items-baseline gap-1">
                    <p class="text-2xl font-black text-gray-800 leading-none">{{ $jumlahLahan ?? 0 }}</p>
                    <span class="text-[10px] font-bold text-gray-500 uppercase">Blok Terdaftar</span>
                </div>
            </div>
        </div>

        {{-- CARD 3: STATUS PROFIL --}}
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5 hover:shadow-md transition-all duration-300 group">
            <div class="w-14 h-14 {{ $petani && $petani->status == 'disetujui' ? 'bg-indigo-50 text-indigo-600' : 'bg-orange-50 text-orange-600' }} rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas {{ $petani && $petani->status == 'disetujui' ? 'fa-id-badge' : 'fa-user-clock' }} text-2xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest leading-none mb-1">Status Akun</p>
                @if($petani && $petani->status == 'disetujui')
                    <p class="text-sm font-black text-gray-800 uppercase leading-none truncate mb-1">{{ $petani->nama_lengkap }}</p>
                    <div class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[9px] font-black text-green-600 uppercase tracking-tighter">Terverifikasi</span>
                    </div>
                @else
                    <p class="text-sm font-black text-gray-800 uppercase leading-none mb-1">Review Berkas</p>
                    <div class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-bounce"></span>
                        <span class="text-[9px] font-black text-orange-600 uppercase tracking-tighter">Sedang Dicek Admin</span>
                    </div>
                @endif
            </div>
        </div>
    </div>




    {{-- CHART: kosongkan jika belum ada pengambilan sukses --}}
@php
        // Riwayat $riwayat diambil hanya transaksi sukses.
        // Jika belum ada, grafik harus kosong.
        $hasPengambilan = (isset($riwayat) ? $riwayat->count() : 0) > 0;
    @endphp


    @if($hasPengambilan)
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <i class="fas fa-chart-line text-8xl text-green-600"></i>
            </div>
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <h3 class="text-lg font-black text-gray-800 flex items-center gap-3">
                        <span class="w-2 h-8 bg-green-500 rounded-full"></span>
                        Statistik Pengambilan Bibit
                    </h3>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-green-600"></span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Realisasi (Kg)</span>
                        </div>
                    </div>
                </div>
                <div style="height: 300px; position: relative;" class="rounded-2xl">
                    <canvas id="pembelianChart"></canvas>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <i class="fas fa-chart-line text-8xl text-green-600"></i>
            </div>
            <div class="relative z-10">
                <h3 class="text-lg font-black text-gray-800 flex items-center gap-3 mb-6">
                    <span class="w-2 h-8 bg-green-500 rounded-full"></span>
                    Statistik Pengambilan Bibit
                </h3>
                <div style="height: 300px;" class="rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-gray-400 text-sm italic">Belum ada pengambilan bibit sukses. Grafik kosong.</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- CUSTOM STYLES FOR ANIMATIONS --}}
    <style>
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 8s linear infinite;
        }
    </style>

    {{-- RIWAYAT TRANSAKSI ASLI --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">3 Riwayat Terakhir</h3>
        </div>
        <div class="overflow-x-auto overflow-y-auto max-h-[300px] relative">
            @if(count($riwayat) > 0)
                <table class="w-full text-left">
                    <thead class="bg-white text-gray-400 text-[10px] font-bold uppercase tracking-widest sticky top-0 z-10">
                        <tr>
                            <th class="p-4 border-b">Tanggal</th>
                            <th class="p-4 border-b">Komoditas</th>
                            <th class="p-4 border-b">Jumlah</th>
                            <th class="p-4 border-b">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm font-medium text-gray-600">
                        @foreach($riwayat as $r)
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="p-4">{{ $r->created_at->format('d M Y') }}</td>
                                <td class="p-4 text-gray-800">{{ $r->bibit->nama_bibit ?? 'Bibit' }}</td>
                                <td class="p-4 font-bold text-gray-700">{{ $r->jumlah_beli }} Kg</td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-md {{ $r->status_pembayaran == 'sukses' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }} text-[10px] font-bold uppercase">
                                        {{ str_replace('_', ' ', $r->status_pembayaran) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-50 rounded-full mb-3">
                        <i class="fas fa-history text-gray-300"></i>
                    </div>
                    <p class="text-xs text-gray-400 italic font-medium uppercase tracking-widest">Belum Ada Riwayat Pengambilan Bibit</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const canvas = document.getElementById('pembelianChart');
        if (!canvas) return; // jika grafik kosong (canvas tidak dirender)

        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!}, 
                datasets: [{
                    label: 'Pengambilan (kg)',
                    data: {!! json_encode($chartData) !!}, 
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