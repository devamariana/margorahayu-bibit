@extends('layouts.admin_layout')
@section('title', 'Fitur Pengalihan Jatah')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Input Pengalihan Jatah</h3>
            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-widest italic">
                <i class="fas fa-calendar-alt mr-1"></i> Musim {{ $currentMusimAktif ?? '-' }}
            </span>
        </div>
        <form id="pindahForm" action="{{ route('admin.proses_transfer') }}" method="POST">
            @csrf
            <div class="space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Pilih Bibit</label>
                    <div class="relative">
                        <select name="bibit_id" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm pr-10" required>
                            <option value="">-- Pilih Jenis Bibit --</option>
                            @foreach($bibitsAktif as $ba)
                                <option value="{{ $ba->id }}">{{ $ba->nama_bibit }} ({{ $ba->jenis }})</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Petani Pengirim</label>
                        <select name="pengirim_id" id="pengirim_id" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm" required>
                            <option value="">-- Pilih Pengirim --</option>
                            @foreach($petanis as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Dari Blok Lahan</label>
                        <select name="lahan_id" id="lahan_id" class="appearance-none block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] outline-none text-sm" required disabled>
                            <option value="">-- Pilih Petani Dulu --</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Pilih Petani Penerima (Tambah Jatah)</label>
                    <div class="relative">
                        <select name="penerima_id" class="appearance-none block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm pr-10" required>
                            <option value="">-- Pilih Penerima --</option>
                            <option value="admin" class="font-bold text-[#2D6A4F]">Kembalikan ke Admin / Gudang</option>
                            @foreach($petanis as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Jumlah Jatah (Kg)</label>
                    <input type="number" step="0.1" name="jumlah_kg" id="jumlah_kg" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm" placeholder="Misal: 5" required>
                    <p id="infoSisa" class="text-[10px] text-gray-400 font-bold ml-1 hidden italic">Sisa Jatah di Blok Terpilih: <span id="labelSisa" class="text-[#2D6A4F]">0</span> Kg</p>
                </div>
                <div class="pt-2 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-[#2D6A4F] hover:bg-[#1B4332] text-white text-xs font-bold rounded-lg shadow-sm transition uppercase tracking-widest w-full">Alihkan Sekarang</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const pengirimSelect = document.getElementById('pengirim_id');
        const lahanSelect = document.getElementById('lahan_id');

        // Reset dan muat lahan saat petani pengirim dipilih
        pengirimSelect.addEventListener('change', function() {
            const petaniId = this.value;
            lahanSelect.innerHTML = '<option value="">-- Loading Lahan... --</option>';
            lahanSelect.disabled = true;
            lahanSelect.classList.add('bg-gray-50');

            if (petaniId) {
                fetch(`/admin/get-petani-lahans/${petaniId}`)
                    .then(res => res.json())
                    .then(data => {
                        lahanSelect.innerHTML = '<option value="">-- Pilih Blok Lahan --</option>';
                        data.forEach(l => {
                            lahanSelect.innerHTML += `<option value="${l.id}">${l.nama_blok} (${l.luas_lahan} m²)</option>`;
                        });
                        lahanSelect.disabled = false;
                        lahanSelect.classList.remove('bg-gray-50');
                    });
            } else {
                lahanSelect.innerHTML = '<option value="">-- Pilih Petani Dulu --</option>';
            }
            updateSisa();
        });

        function updateSisa() {
            const bibitId = document.querySelector('select[name="bibit_id"]').value;
            const pengirimId = pengirimSelect.value;
            const lahanId = lahanSelect.value;
            const infoSisa = document.getElementById('infoSisa');
            const labelSisa = document.getElementById('labelSisa');

            if (bibitId && pengirimId && lahanId) {
                fetch(`{{ route('admin.cek_sisa_jatah') }}?petani_id=${pengirimId}&bibit_id=${bibitId}&lahan_id=${lahanId}`)
                    .then(response => response.json())
                    .then(data => {
                        labelSisa.innerText = data.sisa;
                        infoSisa.classList.remove('hidden');
                    });
            } else {
                infoSisa.classList.add('hidden');
            }
        }

        document.querySelector('select[name="bibit_id"]').addEventListener('change', updateSisa);
        lahanSelect.addEventListener('change', updateSisa);

        document.getElementById('pindahForm').addEventListener('submit', function(e) {
            const jumlahKg = document.getElementById('jumlah_kg').value;
            const sisaJatah = parseFloat(document.getElementById('labelSisa').innerText);

            if (!jumlahKg || parseFloat(jumlahKg) <= 0) {
                Swal.fire('Error', 'Jumlah jatah tidak boleh kosong atau 0!', 'error');
                e.preventDefault();
                return;
            }

            if (parseFloat(jumlahKg) > sisaJatah) {
                Swal.fire('Gagal', 'Jumlah transfer (Kg) melebihi sisa jatah pada blok lahan terpilih!', 'error');
                e.preventDefault();
                return;
            }
        });
    </script>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold mb-4">Riwayat Pengalihan</h3>
        <div class="overflow-x-auto relative">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-gray-50">
                        <th class="p-2">Bibit</th>
                        <th class="p-2">Pengirim</th>
                        <th class="p-2">Penerima</th>
                        <th class="p-2">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riwayatPindah as $r)
                    <tr class="border-b">
                        <td class="p-2 text-xs font-bold uppercase tracking-tighter">{{ $r->bibit->nama_bibit ?? '-' }}</td>
                        <td class="p-2">
                            <span class="font-bold">{{ $r->pengirim->nama_lengkap }}</span>
                        </td>
                        <td class="p-2">{{ $r->penerima->nama_lengkap ?? 'Admin (Gudang)' }}</td>
                        <td class="p-2 text-blue-600 font-bold text-right">{{ $r->jumlah_kg }} Kg</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection