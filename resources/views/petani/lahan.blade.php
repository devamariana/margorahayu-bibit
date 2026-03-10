@extends('layouts.petani_layout')

@section('title', 'Data Lahan Pertanian')

@section('content')
<div class="p-8 bg-[#F0F7F2] min-h-screen">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 text-center md:text-left">
        <div>
            <h1 class="text-3xl font-extrabold text-[#1B4332] tracking-tight text-uppercase">DATA LAHAN PERTANIAN</h1>
            <p class="text-gray-500 text-sm">Kelola semua aset lahan yang Anda miliki di sini.</p>
        </div>
        <button onclick="toggleModal()" class="bg-[#2D6A4F] hover:bg-[#1B4332] text-white px-6 py-3 rounded-2xl font-bold shadow-lg transition transform hover:scale-105">
            <i class="fas fa-plus mr-2"></i> Tambah Lahan Baru
        </button>
    </div>

    {{-- Alert Success --}}
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-500 text-white rounded-2xl shadow-lg flex items-center">
        <i class="fas fa-check-circle mr-3"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- Statistik Ringkas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
            <p class="text-gray-400 text-xs font-bold uppercase">Jumlah Lahan</p>
            <h3 class="text-2xl font-black text-[#2D6A4F]">{{ $lahans->count() }} Lokasi</h3>
        </div>
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
            <p class="text-gray-400 text-xs font-bold uppercase">Total Luas Keseluruhan</p>
            <h3 class="text-2xl font-black text-[#2D6A4F]">{{ $lahans->sum('luas_lahan') }} m²</h3>
        </div>
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
            <p class="text-gray-400 text-xs font-bold uppercase">Estimasi Total Jatah</p>
            <h3 class="text-2xl font-black text-[#2D6A4F]">{{ ($lahans->sum('luas_lahan') / 100) * 10 }} kg</h3>
        </div>
    </div>

    {{-- Tabel Daftar Lahan --}}
    <div class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-gray-50">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#2D6A4F] text-white">
                    <th class="px-6 py-4 text-xs font-bold uppercase">No</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase">Nama/Blok Lahan</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Luas (m²)</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Rencana Bibit</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Status</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($lahans as $index => $lahan)
                <tr class="hover:bg-green-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $index + 1 }}</td>
                    <td class="px-6 py-4">
                        <span class="font-bold text-[#1B4332] block">{{ $lahan->nama_blok }}</span>
                        <span class="text-[10px] text-gray-400 uppercase tracking-widest italic">Lokasi Pertanian</span>
                    </td>
                    <td class="px-6 py-4 text-center font-black text-[#2D6A4F]">{{ $lahan->luas_lahan }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase">
                            {{ $lahan->rencana_bibit }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($lahan->status == 'disetujui')
                            <span class="px-3 py-1 bg-green-100 text-green-700 font-bold rounded-lg text-xs">DISETUJUI</span>
                        @elseif($lahan->status == 'ditolak')
                            <span class="px-3 py-1 bg-red-100 text-red-700 font-bold rounded-lg text-xs">DITOLAK</span>
                        @else
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 font-bold rounded-lg text-xs">PENDING</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center gap-3">
                            <button class="text-blue-500 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('petani.hapus_lahan', $lahan->id) }}" method="POST" onsubmit="return confirm('Hapus lahan ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">Belum ada data lahan. Silakan tambah lahan baru.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL TAMBAH LAHAN --}}
<div id="modalLahan" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl">
        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
            <h2 class="text-2xl font-black text-[#1B4332] uppercase">TAMBAH LAHAN</h2>
            <button onclick="toggleModal()" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>

        <form id="lahanForm" action="{{ route('petani.store_lahan') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Nama / Blok Lahan</label>
                    <input type="text" name="nama_blok" required placeholder="Contoh: Sawah Blok Utara" 
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Luas Lahan (m²)</label>
                    <input type="text" id="luas_lahan" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required placeholder="Contoh: 500" 
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                    <input type="hidden" name="luas_lahan" id="luas_lahan_real">
                </div>
                <div class="space-y-1.5 relative" id="bibit-dropdown-container">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Rencana Bibit</label>
                    <input type="hidden" name="rencana_bibit" id="rencana_bibit_input" required>
                    
                    <button type="button" onclick="toggleDropdown()" id="dropdownToggle" class="relative w-full text-left px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm font-bold text-[#1B4332] flex justify-between items-center shadow-sm h-[46px]">
                        <span id="selectedBibitText" class="truncate font-normal text-gray-500">Cari & Pilih jenis bibit...</span>
                        <i class="fas fa-search text-[#2D6A4F] text-xs transition-transform duration-200" id="dropdownIcon"></i>
                    </button>

                    <div id="dropdownMenu" class="absolute z-20 w-full top-[105%] mt-1 bg-white rounded-xl shadow-[0_10px_25px_-5px_rgba(0,0,0,0.2)] border border-gray-100 hidden flex-col overflow-hidden transition-all duration-200 origin-top transform scale-95 opacity-0">
                        <div class="p-3 border-b border-gray-100 bg-gray-50/50">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-filter text-gray-400 text-xs"></i>
                                </div>
                                <input type="text" id="searchBibit" onkeyup="filterBibitList()" class="block w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-[#2D6A4F] focus:border-[#2D6A4F] outline-none" placeholder="Ketik nama atau jenis bibit..." autocomplete="off">
                            </div>
                        </div>

                        <ul id="bibitList" class="max-h-56 overflow-y-auto w-full pb-2 custom-scrollbar">
                            @forelse($rencanaBibits->groupBy('jenis') as $jenis => $bibits)
                                <li>
                                    <div class="px-4 py-2 mt-1 text-[10px] font-black text-[#2D6A4F] uppercase tracking-widest bg-green-50/50 block border-y border-green-100">
                                        {{ $jenis ?? 'Belum Dikategorikan' }}
                                    </div>
                                </li>
                                @foreach($bibits as $bibit)
                                    <li class="cursor-pointer search-item group">
                                        <div onclick="selectBibit('{{ $bibit->nama_bibit }}')" class="px-4 py-2 hover:bg-[#F0F7F2] transition flex flex-col justify-center border-l-2 border-transparent hover:border-[#2D6A4F]">
                                            <span class="text-sm font-bold text-gray-800 group-hover:text-[#1B4332] item-nama">{{ $bibit->nama_bibit }}</span>
                                            <span class="text-[10px] text-gray-400 mt-0.5 item-jenis hidden">{{ $jenis }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            @empty
                                <li class="px-4 py-5 text-sm text-center text-gray-500 italic flex flex-col items-center gap-2">
                                    <i class="fas fa-box-open text-2xl text-gray-300"></i> Data kosong (Hubungi Admin)
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <button type="submit" class="w-full mt-8 bg-[#2D6A4F] text-white p-4 rounded-2xl font-black shadow-lg hover:bg-[#1B4332] transition tracking-widest uppercase">
                SIMPAN DATA LAHAN
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('lahanForm').addEventListener('submit', function(e) {
        const luas = document.getElementById('luas_lahan').value;
        if (!luas || parseInt(luas) <= 0) {
            alert('Perhatian: Luas lahan tidak boleh 0 atau kosong!');
            e.preventDefault();
            return;
        }
        
        const bibitInput = document.getElementById('rencana_bibit_input').value;
        if (!bibitInput) {
            alert('Silakan pilih rencana bibit terlebih dahulu!');
            e.preventDefault();
            return;
        }

        document.getElementById('luas_lahan_real').value = luas;
    });

    function toggleModal() {
        const modal = document.getElementById('modalLahan');
        modal.classList.toggle('hidden');
    }

    // --- LOGIKA DROPDOWN PENCARIAN BIBIT ---
    let isDropdownOpen = false;
    const dropdownMenu = document.getElementById('dropdownMenu');
    const searchInput = document.getElementById('searchBibit');

    function toggleDropdown() {
        isDropdownOpen = !isDropdownOpen;
        if (isDropdownOpen) {
            dropdownMenu.classList.remove('hidden');
            dropdownMenu.classList.add('flex');
            setTimeout(() => {
                dropdownMenu.classList.remove('scale-95', 'opacity-0');
                dropdownMenu.classList.add('scale-100', 'opacity-100');
                searchInput.focus();
            }, 10);
        } else {
            dropdownMenu.classList.remove('scale-100', 'opacity-100');
            dropdownMenu.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                dropdownMenu.classList.add('hidden');
                dropdownMenu.classList.remove('flex');
            }, 200);
        }
    }

    function selectBibit(bibitName) {
        // Hilangkan font normal agar text lebih bold sesudah dipilih
        const textSpan = document.getElementById('selectedBibitText');
        textSpan.innerText = bibitName;
        textSpan.classList.remove('font-normal', 'text-gray-500');
        
        document.getElementById('rencana_bibit_input').value = bibitName;
        
        // Ganti icon search ke centang
        document.getElementById('dropdownIcon').className = 'fas fa-check-circle text-green-500 text-sm';
        
        toggleDropdown(); // Tutup dropdown
        
        // Reset kolom pencarian
        searchInput.value = '';
        filterBibitList();
    }

    function filterBibitList() {
        const filter = searchInput.value.toLowerCase();
        const items = document.querySelectorAll('.search-item');

        items.forEach(item => {
            const nama = item.querySelector('.item-nama').innerText.toLowerCase();
            const jenis = item.querySelector('.item-jenis').innerText.toLowerCase();
            
            if (nama.includes(filter) || jenis.includes(filter)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Tutup dropdown otomatis jika klik di luar area
    document.addEventListener('click', function(event) {
        const container = document.getElementById('bibit-dropdown-container');
        if (isDropdownOpen && !container.contains(event.target)) {
            toggleDropdown();
        }
    });

</script>
@endsection