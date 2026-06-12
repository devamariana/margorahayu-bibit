@extends('layouts.petani_layout')

@section('title', 'Katalog Informasi Bibit')

@section('content')
<div class="space-y-8">

    {{-- ALERT UTAMA: INFORMASI PERIODE MUSIM AKTIF DARI ADMIN --}}
    @if(isset($periodeAktif) && $periodeAktif)
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                <i class="fas fa-cloud-sun text-lg"></i>
            </div>
            <div>
                <h4 class="font-bold text-blue-900 text-sm">Periode Aktif Saat Ini: {{ $periodeAktif->nama_periode ?? 'Masa Tanam Kelompok' }}</h4>
                <p class="text-xs text-blue-700">Sistem mendeteksi saat ini sedang berjalan <span class="font-black underline uppercase">Musim {{ $periodeAktif->musim }}</span>. Pilihan pengambilan komoditas bibit disesuaikan otomatis demi asas keadilan.</p>
            </div>
        </div>
    @else
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-xl shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <div>
                <h4 class="font-bold text-amber-900 text-sm">Peringatan: Tidak Ada Periode Aktif</h4>
                <p class="text-xs text-amber-700">Admin belum mengaktifkan atau membuka jadwal musim tanam resmi. Transaksi penguncian sementara akan disetel ke mode default.</p>
            </div>
        </div>
    @endif

    {{-- TAMPILAN PRODUK KATALOG DARI DATABASE --}}
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#2D6A4F] rounded-full flex items-center justify-center text-white">
                <i class="fas fa-store text-sm"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-xl">Katalog Bibit Tersedia</h3>
                <p class="text-sm text-gray-500">Lihat varietas bibit unggul yang tersedia untuk musim ini.</p>
            </div>
        </div>

        {{-- FILTER MUSIM: DROPDOWN STYLE --}}
        <div class="relative w-full md:w-max">
            <select id="filter-musim" onchange="changeSeasonFilter(this.value, this)" class="appearance-none w-full md:w-64 p-3 bg-green-50 border border-green-200 rounded-xl focus:ring-2 focus:ring-green-500 outline-none font-bold text-green-700 pr-10">
                <option value="all" selected>-- Pilih Musim --</option>
                <option value="kemarau">Musim Kemarau</option>
                <option value="penghujan">Musim Penghujan</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-green-600">
                <i class="fas fa-filter text-xs"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6" id="bibit-grid">
        @forelse($semuaBibit as $b)
        @php
            $isSalahMusim = ($b->kategori_musim !== $currentMusimAktif);
            $isTutup = !$b->is_buka;
            
            $extraClasses = '';
            if ($b->stok <= 0) {
                $extraClasses = 'opacity-50 grayscale';
            } elseif ($isSalahMusim || $isTutup) {
                $extraClasses = 'border-red-100 bg-red-50/20 grayscale-[0.3] opacity-80';
            }
        @endphp
        
        <div class="bibit-card bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center hover:border-green-500 hover:shadow-xl transition-all duration-300 relative overflow-hidden {{ $extraClasses }}" 
             data-musim="{{ $b->kategori_musim }}"
             data-id="{{ $b->id }}">
            
            <div class="absolute top-0 right-0 flex flex-col items-end">
                <span class="bg-gray-100 text-[8px] font-bold px-2 py-1 text-gray-400">Buka: {{ \Carbon\Carbon::parse($b->tanggal_buka)->format('d/m/y') }}</span>
            </div>

            <div class="w-full h-40 border-2 border-gray-50 rounded-xl flex items-center justify-center mb-4 overflow-hidden bg-gray-50 group">
                @if($b->gambar)
                    <img src="{{ asset('uploads/bibit/' . $b->gambar) }}" class="object-cover w-full h-full zoomable-image hover:scale-110 transition duration-500">
                @else
                    <div class="text-gray-300 flex flex-col items-center">
                        <i class="fas fa-seedling text-5xl mb-2"></i>
                        <span class="text-xs">Tanpa Gambar</span>
                    </div>
                @endif
            </div>

            <h4 class="font-bold text-gray-800 uppercase text-lg mb-1">{{ $b->nama_bibit }}</h4>
            <div class="flex justify-center gap-1.5 mb-3">
                <span class="text-[10px] text-gray-500">Varietas: {{ $b->jenis ?? '-' }}</span>
                <span class="text-[10px] px-2 py-0.5 font-bold rounded {{ $b->kategori_musim === 'kemarau' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                     {{ $b->kategori_musim === 'kemarau' ? '☀ Kemarau' : '🌧 Penghujan' }}
                </span>
            </div>
            
            <div class="bg-green-50 p-3 rounded-xl mb-4">
                <p class="text-[#2D6A4F] font-black text-xl">Rp {{ number_format($b->harga_subsidi, 0, ',', '.') }}<span class="text-xs font-normal text-gray-500">/kg</span></p>
                <p class="text-[10px] text-green-700 font-bold uppercase tracking-tighter">Harga Subsidi Anggota</p>
            </div>
            
            <div class="flex items-center justify-between gap-2 mb-4">
                @if($b->stok > 0)
                    <div class="flex-1 px-3 py-2 bg-green-100 text-green-700 text-xs font-bold rounded-lg border border-green-200">
                        Stok: {{ $b->stok }} kg
                    </div>
                @else
                    <div class="flex-1 px-3 py-2 bg-red-100 text-red-700 text-xs font-bold rounded-lg border border-red-200">
                        Stok Habis
                    </div>
                @endif
            </div>

            {{-- ACTION BUTTON --}}
            @if(!$isSalahMusim && !$isTutup && $b->stok > 0)
                <a href="{{ route('petani.beli_bibit', ['bibit_id' => $b->id]) }}" class="block w-full py-3 bg-[#2D6A4F] text-white rounded-xl font-bold text-sm hover:bg-[#1B4332] transition shadow-md shadow-green-100">
                    <i class="fas fa-shopping-basket mr-2"></i>Beli Bibit
                </a>
            @else
                <button disabled class="w-full py-3 bg-gray-200 text-gray-400 rounded-xl font-bold text-sm cursor-not-allowed">
                    @if($isSalahMusim)
                        Belum Musimnya
                    @elseif($isTutup)
                        Distribusi Tutup
                    @else
                        Stok Habis
                    @endif
                </button>
            @endif
            
            @if($isSalahMusim)
                <p class="text-[10px] text-red-600 mt-3 font-black bg-red-100 py-1 px-2 rounded border border-red-200 tracking-tight">
                    ⚠ Khusus Musim {{ ucfirst($b->kategori_musim) }}
                </p>
            @elseif($isTutup)
                 <p class="text-[10px] text-amber-600 mt-3 font-black bg-amber-50 py-1 px-2 rounded border border-amber-200 tracking-tight">
                    🔒 Distribusi Masa Tunggu
                </p>
            @endif
        </div>
        @empty
        <div class="col-span-full bg-white p-20 rounded-3xl text-center border-2 border-dashed border-gray-200">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-box-open text-4xl text-gray-300"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Tidak Ada Bibit</h3>
            <p class="text-gray-500 italic max-w-xs mx-auto">Kami belum memiliki stok bibit yang tersedia untuk ditampilkan di katalog saat ini.</p>
        </div>
        @endforelse
    </div>

</div>

<script>
    function changeSeasonFilter(season) {
        // Filter Cards
        document.querySelectorAll('.bibit-card').forEach(card => {
            if (season === 'all' || card.getAttribute('data-musim') === season) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    // Default: Show all on load
    document.addEventListener('DOMContentLoaded', () => {
        changeSeasonFilter('all');
    });
</script>
@endsection
