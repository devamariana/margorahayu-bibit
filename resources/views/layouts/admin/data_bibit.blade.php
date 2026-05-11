@extends('layouts.admin_layout')

@section('title', 'Manajemen Distribusi Bibit')

@section('content')
<div class="space-y-6">
    {{-- Notifikasi via Layout (Global) --}}

    {{-- ROW 1: STATISTIK RINGKAS --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 shadow-sm">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Stok Aktif</p>
                <p class="text-xl font-bold text-gray-800 leading-none">{{ number_format($bibits->where('is_buka', true)->sum('stok')) }} <span class="text-xs text-gray-400 font-medium">Kg</span></p>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 shadow-sm">
                <i class="fas fa-archive text-xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Stok Sisa (Tutup)</p>
                <p class="text-xl font-bold text-gray-800 leading-none">{{ number_format($bibits->where('is_buka', false)->whereNotNull('tanggal_buka')->sum('stok')) }} <span class="text-xs text-gray-400 font-medium">Kg</span></p>
            </div>
        </div>
        {{-- Spacer atau Info Tambahan bisa di sini --}}
        <div class="md:col-span-2"></div>
    </div>

    {{-- ROW 2: ACTIONS --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-50">
        {{-- Fitur Cari --}}
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                <i class="fas fa-search text-sm"></i>
            </div>
            <input type="text" id="searchInput" onkeyup="searchTable()"
                   placeholder="Cari berdasarkan nama bibit..." 
                   class="w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2D6A4F] focus:border-transparent focus:outline-none shadow-sm transition-all text-sm">
        </div>
        
        {{-- Tombol Tambah Bibit --}}
        <button onclick="openModal('tambah')" class="w-full md:w-auto bg-[#007BFF] hover:bg-blue-700 text-white font-black py-3 px-8 rounded-xl shadow-lg shadow-blue-100 flex items-center justify-center gap-3 transition-all transform hover:scale-[1.02] active:scale-95 uppercase tracking-widest text-xs">
            <i class="fas fa-truck-loading"></i> Input Kedatangan Bibit
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-280px)] text-xs relative">
            <table class="w-full text-left border-collapse" id="bibitTable">
                <thead class="bg-gray-50 text-gray-500 font-bold sticky top-0 z-10">
                    <tr>
                        <th class="p-4 border-b">No</th>
                        <th class="p-4 border-b">Foto Bibit</th>
                        <th class="p-4 border-b">Nama Bibit</th>
                        <th class="p-4 border-b">Jenis/Varietas</th>
                        <th class="p-4 border-b">Stok (Kg)</th>
                        <th class="p-4 border-b">Harga per Kg</th>
                        <th class="p-4 border-b">Status Distribusi</th>
                        <th class="p-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bibits as $index => $b)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600">{{ $index + 1 }}</td>
                        <td class="p-4">
                            <div class="w-16 h-16 border-2 border-gray-300 rounded flex items-center justify-center relative bg-gray-50 overflow-hidden">
                                @if($b->gambar)
                                    <img src="{{ asset('/uploads/bibit/' . $b->gambar) }}" alt="Foto" class="w-full h-full object-cover zoomable-image cursor-pointer hover:opacity-80 transition">
                                @else
                                    <i class="fas fa-image text-gray-300 text-2xl"></i>
                                @endif
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="font-bold text-gray-800 uppercase bibit-name">{{ $b->nama_bibit }}</div>
                            <span class="text-[10px] text-gray-400 font-medium">{{ $b->jenis ?? '-' }}</span>
                        </td>
                        <td class="p-4 text-gray-600 font-medium">{{ $b->jenis ?? '-' }}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full font-bold {{ $b->stok < 10 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                                {{ $b->stok }} Kg
                            </span>
                        </td>
                        <td class="p-4 font-bold text-gray-800">Rp {{ number_format($b->harga_subsidi, 0, ',', '.') }}</td>
                        <td class="p-4">
                            @if($b->is_buka)
                                <div class="space-y-1">
                                    <span class="bg-green-500 text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-tighter">DISTRIBUSI DIBUKA</span>
                                    <div class="text-[9px] text-gray-400 leading-tight">
                                        Buka: {{ \Carbon\Carbon::parse($b->tanggal_buka)->format('d/m/Y H:i') }}<br>
                                        Tutup: {{ \Carbon\Carbon::parse($b->tanggal_buka)->addDays(7)->format('d/m/Y H:i') }}<br>
                                        Ref Luas: {{ number_format($b->total_luas_snapshot, 0, ',', '.') }} m²
                                    </div>
                                </div>
                            @else
                                @if($b->tanggal_buka)
                                    <div class="space-y-1">
                                        <span class="bg-red-500 text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-tighter">DISTRIBUSI SELESAI</span>
                                        <div class="text-[9px] text-gray-400 leading-tight">
                                            Buka: {{ \Carbon\Carbon::parse($b->tanggal_buka)->format('d/m/Y H:i') }}<br>
                                            Tutup: {{ \Carbon\Carbon::parse($b->tanggal_buka)->addDays(7)->format('d/m/Y H:i') }}<br>
                                            <span class="italic opacity-75 font-bold text-red-400">Otomatis ditutup</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="bg-gray-100 text-gray-400 text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-tighter border border-gray-200">BELUM DIBUKA</span>
                                @endif
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex justify-center gap-2">
                                @if(!$b->is_buka)
                                    <form action="{{ route('admin.buka_bibit', $b->id) }}" method="POST">
                                        @csrf
                                        <button type="button" onclick="confirmAction(this, 'Buka distribusi bibit ini sekarang? Kuota akan dihitung proporsional berdasarkan data lahan saat ini.', 'question')" 
                                            class="w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded shadow-sm flex items-center justify-center transition" title="Buka Distribusi">
                                            <i class="fas fa-bullhorn text-[10px]"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.tutup_bibit', $b->id) }}" method="POST">
                                        @csrf
                                        <button type="button" onclick="confirmAction(this, 'Tutup distribusi bibit ini?', 'warning')" 
                                            class="w-8 h-8 bg-orange-500 hover:bg-orange-600 text-white rounded shadow-sm flex items-center justify-center transition" title="Tutup Distribusi">
                                            <i class="fas fa-ban text-[10px]"></i>
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('admin.detail_bibit', $b->id) }}" title="Lihat Detail Distribusi" 
                                   class="w-8 h-8 bg-indigo-500 hover:bg-indigo-600 text-white rounded shadow-sm flex items-center justify-center transition">
                                    <i class="fas fa-eye text-[10px]"></i>
                                </a>

                                {{-- Tombol Edit dengan Data Attributes --}}
                                @if(!$b->is_buka)
                                <button title="Edit" 
                                    onclick="openModal('edit', {{ json_encode($b) }})"
                                    class="w-8 h-8 bg-[#FFC107] hover:bg-yellow-500 text-white rounded shadow-sm flex items-center justify-center transition">
                                    <i class="fas fa-edit text-[10px]"></i>
                                </button>
                                @else
                                <button title="Tidak bisa diedit saat distribusi aktif" 
                                    disabled
                                    class="w-8 h-8 bg-gray-300 text-gray-500 cursor-not-allowed rounded shadow-sm flex items-center justify-center transition">
                                    <i class="fas fa-edit text-[10px]"></i>
                                </button>
                                @endif
                                
                                <form action="{{ route('admin.data_bibit.destroy', $b->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmAction(this, 'Hapus data bibit ini?', 'warning')" title="Hapus" class="w-8 h-8 bg-[#DC3545] hover:bg-red-600 text-white rounded shadow-sm flex items-center justify-center transition">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500 italic">Data bibit belum tersedia.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL FORM (BISA UNTUK TAMBAH & EDIT) --}}
<div id="modalBibit" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Catat Kedatangan Bibit</h3>
                <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest leading-none">Manajemen Batch Distribusi</p>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <form id="bibitForm" action="{{ route('admin.store_bibit') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div id="methodField"></div> {{-- Tempat untuk @method('PUT') saat edit --}}
            
            <div>
                <label class="block text-xs font-bold mb-1 uppercase text-gray-500">Komoditas Bibit / Varietas</label>
                <input type="text" name="nama_bibit" id="f_nama" class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 outline-none" placeholder="Misal: Padi Unggul Inpari 32" required>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 uppercase text-gray-500">Jenis/Varietas</label>
                <input type="text" name="jenis" id="f_jenis" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none" required>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 uppercase text-gray-500">Sumber Pasokan</label>
                <input type="text" name="sumber_pasokan" id="f_sumber_pasokan" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none" placeholder="Contoh: PT. Bisi Internasional" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold mb-1 uppercase text-gray-500">Volume Subsidi (Kg)</label>
                    <input type="text" name="stok" id="f_stok" oninput="sanitizeNumber(this)" class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 outline-none" placeholder="0" required>
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 uppercase text-gray-500">Nilai Tebus / Kg</label>
                    <input type="text" id="f_harga" oninput="formatNominal(this)" class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 outline-none" placeholder="Rp 0" required>
                    <input type="hidden" name="harga_subsidi" id="f_harga_real">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 uppercase text-gray-500">Deskripsi (Opsional)</label>
                <textarea name="deskripsi" id="f_deskripsi" rows="2" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 uppercase text-gray-500">Bukti Foto Fisik Bibit (Kosongkan jika tidak ganti)</label>
                <input type="file" name="gambar" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 transition">
            </div>


            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-bold text-gray-400 hover:text-gray-600 transition">Batal</button>
                <button type="submit" class="px-8 py-2 bg-[#2D6A4F] text-white rounded-lg text-sm font-bold shadow-md hover:bg-[#1B4332] transition">
                    Rekam Data Masuk
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    // FUNGSI MODAL DINAMIS (TAMBAH & EDIT)
    function openModal(mode, data = null) {
        const modal = document.getElementById('modalBibit');
        const form = document.getElementById('bibitForm');
        const title = document.getElementById('modalTitle');
        const methodField = document.getElementById('methodField');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (mode === 'edit') {
            title.innerText = 'Koreksi Data Batch Kedatangan';
            form.action = `/admin/data-bibit/update/${data.id}`; // Sesuaikan dengan route update kamu
            methodField.innerHTML = '@method("PUT")';
            
            // Isi Form dengan data lama
            document.getElementById('f_nama').value = data.nama_bibit;
            document.getElementById('f_jenis').value = data.jenis;
            document.getElementById('f_sumber_pasokan').value = data.sumber_pasokan || '';
            document.getElementById('f_stok').value = data.stok;
            
            // Atur nominal dengan format
            let hargaInput = document.getElementById('f_harga');
            hargaInput.value = data.harga_subsidi;
            formatNominal(hargaInput);

            document.getElementById('f_deskripsi').value = data.deskripsi || '';
        } else {
            title.innerText = 'Catat Kedatangan Bibit';
            form.action = "{{ route('admin.store_bibit') }}";
            methodField.innerHTML = '';
            form.reset();
        }
    }

    function closeModal() {
        const modal = document.getElementById('modalBibit');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // FUNGSI CARI BIBIT (CLIENT SIDE)
    function searchTable() {
        let input = document.getElementById("searchInput").value.toLowerCase();
        let rows = document.querySelectorAll("#bibitTable tbody tr");

        rows.forEach(row => {
            let name = row.querySelector(".bibit-name").innerText.toLowerCase();
            row.style.display = name.includes(input) ? "" : "none";
        });
    }

    // FUNGSI FILTER PETANI DI MODAL
    function filterPetani() {
        let input = document.getElementById("searchPetani").value.toLowerCase();
        let items = document.querySelectorAll(".petani-item");

        items.forEach(item => {
            let name = item.querySelector(".petani-name").innerText.toLowerCase();
            item.style.display = name.includes(input) ? "flex" : "none";
        });
    }

    // FUNGSI MUNCULKAN INPUT KUOTA SAAT CHECKBOX DICENTANG
    function toggleKuota(id) {
        let checkbox = document.querySelector(`input[value="${id}"].petani-checkbox`);
        let inputKuota = document.getElementById(`kuota_${id}`);
        // Jika dipanggil tanpa event tapi elemen checkbox ada
        if(checkbox) {
            if(checkbox.checked) {
                inputKuota.classList.remove('hidden');
                inputKuota.required = true;
            } else {
                inputKuota.classList.add('hidden');
                inputKuota.required = false;
                inputKuota.value = '';
            }
        }
    }

    // FUNGSI FORMAT NOMINAL & VALIDASI ANGKA
    function sanitizeNumber(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
    }

    function formatNominal(input) {
        let value = input.value.replace(/[^0-9]/g, '');
        if (value) {
            input.value = parseInt(value, 10).toLocaleString('id-ID'); // Format titik uang Indonesia
        } else {
            input.value = '';
        }
    }

    // FUNGSI CEK PADA SAAT SUBMIT
    document.getElementById('bibitForm').addEventListener('submit', function(e) {
        const hargaReal = document.getElementById('f_harga').value.replace(/[^0-9]/g, '');
        const stokReal = document.getElementById('f_stok').value.replace(/[^0-9]/g, '');

        if (!hargaReal || parseInt(hargaReal) <= 0) {
            alert('Perhatian: Harga Bibit per Kg tidak boleh 0 atau kosong!');
            e.preventDefault();
            return;
        }

        if (!stokReal || parseInt(stokReal) < 0) {
            alert('Perhatian: Stok tidak boleh kosong atau negatif!');
            e.preventDefault();
            return;
        }

        // Set the hidden input with unformatted value to submit into database
        document.getElementById('f_harga_real').value = hargaReal;
    });
</script>
@endsection