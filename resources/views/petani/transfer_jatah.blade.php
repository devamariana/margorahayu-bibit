@extends('layouts.petani_layout')

@section('title', 'Transfer Jatah Bibit')

@section('content')
<div class="space-y-6">

    {{-- HEADER INFORMASI --}}
    <div class="bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] p-8 rounded-3xl shadow-xl text-white relative overflow-hidden">
        <div class="relative z-10">
            <p class="text-green-100 text-sm opacity-90 max-w-2xl">
                Fitur ini memungkinkan Anda untuk mengembalikan hak jatah bibit subsidi Anda langsung ke Admin.
            </p>
        </div>
        <i class="fas fa-exchange-alt absolute -right-10 -bottom-10 text-[180px] text-white/10 rotate-12"></i>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- FORM HIBAH --}}
        <div class="md:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                <i class="fas fa-paper-plane text-green-600"></i> Kembalikan Jatah ke Admin
            </h3>
            
            <form action="{{ route('petani.proses_transfer') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- DROPDOWN LAHAN --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest ml-1">Pilih Blok Lahan</label>
                        @php $lahans = \App\Models\Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->get(); @endphp
                        <select id="lahan_id" name="lahan_id" onchange="updateLahanURL(this.value)" class="appearance-none block w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-green-500 focus:bg-white rounded-2xl transition-all outline-none font-bold text-gray-800" required>
                            <option value="">-- Pilih Lokasi Lahan --</option>
                            @foreach($lahans as $l)
                                <option value="{{ $l->id }}" {{ request('lahan_id') == $l->id ? 'selected' : '' }}>
                                    {{ $l->nama_blok }} ({{ $l->luas_lahan }} m²)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- DROPDOWN BIBIT --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest ml-1">Pilih Bibit</label>
                        <select name="bibit_id" onchange="updateBibitURL(this.value)" class="appearance-none block w-full px-5 py-4 bg-gray-50 border-2 border-transparent focus:border-green-500 focus:bg-white rounded-2xl transition-all outline-none font-bold text-gray-800 {{ !request('lahan_id') ? 'opacity-50' : '' }}" required {{ !request('lahan_id') ? 'disabled' : '' }}>
                            <option value="">-- Pilih Bibit Aktif --</option>
                            @foreach($bibitsTerbuka as $bt)
                                <option value="{{ $bt->id }}" {{ isset($selectedBibit) && $selectedBibit->id == $bt->id ? 'selected' : '' }}>
                                    [{{ strtoupper($bt->kategori_musim) }}] {{ $bt->nama_bibit }} ({{ $bt->jenis }})
                                </option>
                            @endforeach
                        </select>
                        @if(!request('lahan_id'))
                            <p class="text-[10px] text-orange-500 font-bold italic ml-1">Pilih lahan terlebih dahulu.</p>
                        @endif
                    </div>
                </div>


                @php
                    $isSalahMusimTransfer = isset($selectedBibit) && isset($currentMusimAktif) && ($selectedBibit->kategori_musim !== $currentMusimAktif);
                @endphp

                @if($isSalahMusimTransfer)
                    <div class="bg-red-50 border border-red-200 p-4 rounded-2xl text-sm text-red-700 font-black shadow-sm">
                        <i class="fas fa-lock mr-2"></i>
                        Transaksi Terkunci: Bibit ini hanya bisa dikembalikan pada Musim {{ strtoupper($selectedBibit->kategori_musim) }}. Saat ini sedang berjalan Musim {{ strtoupper($currentMusimAktif) }}.
                    </div>
                @else
                    <div class="bg-green-50 border border-green-100 p-4 rounded-2xl text-sm text-green-700 font-bold">
                        <i class="fas fa-user-shield mr-2"></i>
                        Jatah akan dikembalikan langsung ke Admin.
                    </div>
                @endif

                <div class="space-y-2">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest ml-1">Jumlah Jatah (Kg) 
                        @if(isset($selectedBibit)) 
                            <span class="text-[10px] text-green-600 font-bold lowercase italic">(Max: {{ number_format($sisaJatah, 1) }} Kg)</span>
                        @endif
                    </label>
                    <div class="relative">
                        <input type="number" name="jumlah_kg" step="0.1" 
                               min="{{ $sisaJatah > 0 ? '0.1' : '0' }}" 
                               max="{{ $sisaJatah }}" 
                               {{ $sisaJatah <= 0 || $isSalahMusimTransfer ? 'readonly disabled' : '' }}
                               class="block w-full px-5 py-4 {{ $sisaJatah <= 0 || $isSalahMusimTransfer ? 'bg-gray-100 text-gray-400' : 'bg-gray-50 text-green-700' }} border-2 border-transparent focus:border-green-500 focus:bg-white rounded-2xl transition-all outline-none font-black text-2xl" 
                               placeholder="0" required>
                        <div class="absolute inset-y-0 right-5 flex items-center pointer-events-none text-gray-400 font-bold italic text-xs">
                            Kg
                        </div>
                    </div>
                    @if(isset($selectedBibit))
                        @if($isSalahMusimTransfer)
                            <p class="text-[10px] text-red-500 font-bold italic mt-1 uppercase">
                                <i class="fas fa-times-circle"></i> Pengembalian ditutup karena bukan musimnya.
                            </p>
                        @elseif($sisaJatah <= 0)
                            <p class="text-[10px] text-red-500 font-bold italic mt-1">
                                <i class="fas fa-exclamation-triangle"></i> Anda tidak memiliki sisa jatah untuk bibit ini sehingga tidak dapat melakukan transfer.
                            </p>
                        @endif
                    @endif
                </div>

                {{-- BAGIAN PESAN DIHAPUS SESUAI PERMINTAAN USER --}}

                @if(isset($selectedBibit))
                <div class="bg-orange-50 p-4 rounded-2xl border border-orange-100 flex items-start gap-3">
                    <i class="fas fa-info-circle text-orange-500 mt-1"></i>
                    <p class="text-xs text-orange-700 leading-relaxed font-medium">
                        <strong>Perhatian:</strong> Dengan memproses ini, jatah <strong>{{ $selectedBibit->nama_bibit ?? 'Bibit' }}</strong> Anda akan berkurang secara permanen dan dikembalikan ke Admin. Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                @endif

                <button type="submit" {{ !isset($selectedBibit) || $sisaJatah <= 0 || $isSalahMusimTransfer ? 'disabled' : '' }} class="w-full {{ !isset($selectedBibit) || $sisaJatah <= 0 || $isSalahMusimTransfer ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#2D6A4F] hover:bg-[#1B4332]' }} text-white font-black py-4 rounded-2xl shadow-lg shadow-green-100 transition-all uppercase tracking-widest text-sm flex items-center justify-center gap-2">
                    @if(!isset($selectedBibit))
                        PILIH BIBIT TERLEBIH DAHULU
                    @elseif($isSalahMusimTransfer)
                        TRANSAKSI MUSIM DIKUNCI
                    @elseif($sisaJatah <= 0)
                        SALDO JATAH KOSONG
                    @else
                        KEMBALIKAN JATAH KE ADMIN
                        <i class="fas fa-check-circle"></i>
                    @endif
                </button>
            </form>
        </div>

        {{-- INFO SALDO JATAH --}}
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 text-center relative overflow-hidden">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Saldo Jatah Tersedia</p>
                <div class="relative z-10">
                    @if(isset($selectedBibit) && request('lahan_id'))
                        <span class="text-5xl font-black text-green-600 leading-none">{{ number_format($sisaJatah, 1) }}</span>
                        <span class="text-sm font-bold text-gray-400 ml-1">Kg</span>
                    @elseif(request('lahan_id'))
                        <div class="py-2">
                             <i class="fas fa-seedling text-gray-200 text-3xl mb-2 block mx-auto"></i>
                             <span class="text-2xl font-black text-gray-300 leading-none italic uppercase tracking-tighter">Pilih Bibit</span>
                        </div>
                    @else
                        <div class="py-2">
                             <i class="fas fa-map-marked-alt text-gray-200 text-3xl mb-2 block mx-auto"></i>
                             <span class="text-2xl font-black text-gray-300 leading-none italic uppercase tracking-tighter">Pilih Lahan</span>
                        </div>
                    @endif
                </div>
                <p class="text-[9px] font-bold text-gray-500 mt-2 uppercase tracking-widest">{{ $selectedBibit->nama_bibit ?? 'Menunggu Pilihan' }}</p>
                <div class="mt-4 pt-4 border-t border-gray-50">
                    @if(isset($selectedBibit) && request('lahan_id'))
                        <p class="text-[10px] text-gray-400 italic">Sisa jatah untuk lahan ini.</p>
                    @elseif(request('lahan_id'))
                        <p class="text-[10px] text-gray-400 italic">Pilih jenis bibit untuk melihat saldo.</p>
                    @else
                        <p class="text-[10px] text-orange-500 font-bold italic">Tentukan lahan terlebih dahulu.</p>
                    @endif
                </div>
            </div>

            {{-- RIWAYAT SINGKAT --}}
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest mb-4 border-b pb-3">Riwayat Transfer Saya</h4>
                <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                    @forelse($riwayatTransfer as $rh)
                    <div class="flex items-center justify-between gap-3 p-3 rounded-2xl hover:bg-gray-50 transition border border-transparent hover:border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-[10px]">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-800 leading-none mb-1">{{ $rh->penerima->nama_lengkap ?? 'Admin' }}</p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">{{ $rh->bibit->nama_bibit ?? 'Bibit' }}</p>
                                <p class="text-[8px] text-gray-400">{{ $rh->created_at->format('d/m H:i') }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-black text-gray-700">-{{ $rh->jumlah_kg }} Kg</span>
                    </div>
                    @empty
                    <div class="text-center py-6 text-gray-400 italic text-[10px]">
                        Belum ada riwayat transfer.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }
</style>
<script>
    function updateLahanURL(lahanId) {
        window.location.href = '?lahan_id=' + lahanId;
    }

    function updateBibitURL(bibitId) {
        const lahanId = new URLSearchParams(window.location.search).get('lahan_id');
        if (lahanId && bibitId) {
            window.location.href = '?lahan_id=' + lahanId + '&bibit_id=' + bibitId;
        }
    }
</script>
@endsection
