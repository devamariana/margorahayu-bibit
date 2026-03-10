@extends('layouts.superadmin_layout')

@section('title', 'Master Dashboard Overview')

@section('content')
<div class="space-y-6">
    {{-- Bagian Statistik --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] p-6 rounded-lg shadow-md text-center text-white">
            <p class="text-green-100 text-xs font-bold uppercase mb-2">Total Admin</p>
            <p class="text-4xl font-extrabold tracking-tight">{{ $totalAdmin }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 text-center">
            <p class="text-gray-500 text-xs font-bold uppercase mb-2">Total Petani Terdaftar</p>
            <p class="text-3xl font-bold text-gray-800 tracking-tight">{{ $totalPetani }}</p>
        </div>
    </div>

    {{-- Tabel Admin Terbaru --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-sm font-bold text-gray-700">Daftar Admin Aktif</h3>
            <a href="{{ route('superadmin.data_admin') }}" class="text-xs font-bold text-[#2D6A4F] hover:underline">Kelola Semua &rarr;</a>
        </div>
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase font-bold">
                    <tr>
                        <th class="p-4 border-b">Username</th>
                        <th class="p-4 border-b">Dibuat Pada</th>
                        <th class="p-4 border-b">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($admins as $admin)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-medium text-gray-800">{{ $admin->username }}</td>
                        <td class="p-4 text-gray-600">{{ $admin->created_at->format('d M Y') }}</td>
                        <td class="p-4 text-blue-600 font-bold uppercase">{{ $admin->role }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="p-4 text-center text-gray-500">Belum ada data admin terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
