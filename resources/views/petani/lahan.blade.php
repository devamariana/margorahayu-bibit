@extends('layouts.petani_layout')

@section('title', 'Data Lahan Pertanian')

@section('content')
<div class="p-8 bg-[#F0F7F2] min-h-screen">
    {{-- Header --}}
    {{-- HEADER STICKY --}}
    <div class="sticky top-0 z-20 bg-[#F0F7F2]/95 backdrop-blur-sm pt-2 pb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-center md:text-left">
            <div>
                <h1 class="text-3xl font-extrabold text-[#1B4332] tracking-tight text-uppercase">DATA LAHAN PERTANIAN</h1>
                <p class="text-gray-500 text-sm">Kelola semua aset lahan yang Anda miliki di sini.</p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Filter Tahun --}}
                <form action="{{ route('petani.lahan') }}" method="GET" id="filterTahunForm" class="flex items-center">
                    <select name="tahun" onchange="this.form.submit()" class="bg-white border border-gray-300 text-gray-700 text-sm rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#2D6A4F] outline-none font-bold shadow-sm">
                        @foreach($tahunTersedia as $thn)
                            <option value="{{ $thn }}" {{ $selectedTahun == $thn ? 'selected' : '' }}>Tahun {{ $thn }}</option>
                        @endforeach
                        @if(!$tahunTersedia->contains(date('Y')))
                            <option value="{{ date('Y') }}" {{ $selectedTahun == date('Y') ? 'selected' : '' }}>Tahun {{ date('Y') }}</option>
                        @endif
                    </select>
                </form>

                @if(($petani->status ?? '') !== 'disetujui')
                    <button onclick="showUnverifiedWarning()" class="bg-gray-400 text-white px-6 py-3 rounded-2xl font-bold shadow-lg transition cursor-not-allowed">
                        <i class="fas fa-plus mr-2"></i> Tambah Lahan Baru
                    </button>
                @else
                    <button onclick="toggleModal()" class="bg-[#2D6A4F] hover:bg-[#1B4332] text-white px-6 py-3 rounded-2xl font-bold shadow-lg transition transform hover:scale-105">
                        <i class="fas fa-plus mr-2"></i> Tambah Lahan Baru
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Alert Success --}}
    {{-- Notifikasi via Layout (Global SweetAlert2) --}}

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
            <h3 class="text-2xl font-black text-[#2D6A4F]">{{ number_format($estimasiJatah, 1) }} kg</h3>
        </div>
    </div>

    {{-- Tabel Daftar Lahan --}}
    <div class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-gray-50">
        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-320px)] relative">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 z-10 bg-[#2D6A4F]">
                    <tr class="bg-[#2D6A4F] text-white">
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">No</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase">Nama/Blok Lahan</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Luas (m²)</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Pengajuan Musim Ini</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Bibit yang Dibeli</th>
                    <th class="px-6 py-4 text-xs font-bold uppercase text-center">Status Lahan</th>
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
                        @php
                            $pengajuanAktif = $lahan->pengajuans->where('periode_id', $periodeAktif->id ?? 0)->last();
                        @endphp
                        
                        @if($pengajuanAktif)
                            <div class="flex flex-col items-center gap-1">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-[10px] font-black uppercase border border-blue-200">
                                    {{ $pengajuanAktif->bibit->nama_bibit }}
                                </span>
                                @if($pengajuanAktif->status === 'menunggu')
                                    <span class="text-[9px] text-amber-600 font-bold"><i class="fas fa-clock mr-1"></i>Menunggu</span>
                                @elseif($pengajuanAktif->status === 'disetujui')
                                    <span class="text-[9px] text-green-600 font-bold"><i class="fas fa-check-circle mr-1"></i>Disetujui</span>
                                @else
                                    <span class="text-[9px] text-red-600 font-bold"><i class="fas fa-times-circle mr-1"></i>Ditolak</span>
                                @endif
                            </div>
                        @else
                            @if($lahan->status === 'disetujui')
                                <button onclick="openModalPengajuan({{ $lahan->id }}, '{{ $lahan->nama_blok }}')" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[9px] font-bold transition flex items-center gap-1 mx-auto">
                                    <i class="fas fa-paper-plane text-[8px]"></i> AJUKAN BIBIT
                                </button>
                            @else
                                <span class="text-[10px] text-gray-400 italic">Menunggu Lahan diverifikasi</span>
                            @endif
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $bibitTerbaru = $lahan->transaksi
                                ->whereIn('status_pembayaran', ['sukses', 'lunas'])
                                ->filter(function($t) use ($selectedTahun) {
                                    return $t->bibit && $t->created_at->year == $selectedTahun;
                                })
                                ->sortByDesc('created_at')
                                ->first();
                        @endphp
                        
                        @if($bibitTerbaru)
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase inline-block m-0.5">
                                {{ $bibitTerbaru->bibit->nama_bibit }}
                            </span>
                        @else
                            <span class="text-[10px] text-gray-400 italic">Belum ada pembelian</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($lahan->status == 'disetujui')
                            <span class="px-3 py-1 bg-green-100 text-green-700 font-bold rounded-lg text-xs">DISETUJUI</span>
                        @elseif($lahan->status == 'ditolak')
                            <div class="flex flex-col items-center gap-1">
                                <span class="px-3 py-1 bg-red-100 text-red-700 font-bold rounded-lg text-xs">DITOLAK</span>
                                @if($lahan->catatan_admin)
                                    <span class="text-[9px] text-red-500 italic max-w-[120px] leading-tight">"{{ $lahan->catatan_admin }}"</span>
                                @endif
                            </div>
                        @else
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 font-bold rounded-lg text-xs">PENDING</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center gap-3">
                            <button class="text-blue-500 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('petani.hapus_lahan', $lahan->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="button" onclick="confirmAction(this, 'Hapus data lahan ini?', 'warning')" class="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition">
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
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Rencana Bibit yang Ditanam</label>
                    <select name="rencana_bibit" required class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                        <option value="">-- Pilih Varietas --</option>
                        @foreach($bibitPilihan as $bp)
                            <option value="{{ $bp->nama_bibit }}">{{ $bp->nama_bibit }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full mt-8 bg-[#2D6A4F] text-white p-4 rounded-2xl font-black shadow-lg hover:bg-[#1B4332] transition tracking-widest uppercase">
                SIMPAN DATA LAHAN
            </button>
        </form>
    </div>
</div>

{{-- MODAL AJUKAN BIBIT (INTEGRATED) --}}
<div id="modalPengajuan" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl">
        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
            <h2 class="text-2xl font-black text-[#1B4332] uppercase">AJUKAN BIBIT</h2>
            <button onclick="closeModalPengajuan()" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form action="{{ route('petani.store_pengajuan') }}" method="POST">
            @csrf
            <input type="hidden" name="lahan_id" id="pengajuan_lahan_id">
            
            <div class="space-y-5">
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">Lahan Terpilih</label>
                    <input type="text" id="pengajuan_nama_lahan" class="w-full p-4 bg-gray-50 border-2 border-gray-100 rounded-2xl font-bold text-gray-500" readonly>
                </div>

                @if(isset($periodeAktif) && $periodeAktif)
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">Pilih Varietas Bibit (Musim {{ $periodeAktif->musim }})</label>
                    <select name="bibit_id" class="w-full p-4 bg-white border-2 border-gray-100 rounded-2xl focus:ring-2 focus:ring-[#2D6A4F] focus:border-[#2D6A4F] outline-none font-bold text-[#1B4332] transition" required>
                        <option value="">-- Pilih Bibit --</option>
                        @foreach($bibitsTersedia as $b)
                            <option value="{{ $b->id }}">{{ $b->nama_bibit }} ({{ $b->jenis }})</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200">
                    <p class="text-xs text-amber-700 font-bold text-center">Maaf, saat ini belum ada periode distribusi bibit yang aktif.</p>
                </div>
                @endif

                <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100">
                    <p class="text-[10px] text-blue-600 leading-relaxed italic">
                        <i class="fas fa-info-circle mr-1"></i> Setelah diajukan, mohon tunggu Admin memverifikasi agar jatah Anda muncul di menu "Pesan Bibit".
                    </p>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModalPengajuan()" class="flex-1 px-6 py-4 bg-gray-100 text-gray-400 font-bold rounded-2xl hover:bg-gray-200 transition uppercase tracking-widest text-xs">Batal</button>
                    <button type="submit" class="flex-1 px-6 py-4 bg-[#2D6A4F] text-white font-bold rounded-2xl hover:bg-[#1B4332] transition shadow-lg uppercase tracking-widest text-xs">Kirim Pengajuan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('lahanForm').addEventListener('submit', function(e) {
        const luas = document.getElementById('luas_lahan').value;
        if (!luas || parseInt(luas) <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Luas lahan tidak boleh 0 atau kosong!',
                confirmButtonColor: '#2D6A4F'
            });
            e.preventDefault();
            return;
        }
        document.getElementById('luas_lahan_real').value = luas;
    });

    function toggleModal() {
        const modal = document.getElementById('modalLahan');
        modal.classList.toggle('hidden');
    }

    function openModalPengajuan(lahanId, namaLahan) {
        document.getElementById('pengajuan_lahan_id').value = lahanId;
        document.getElementById('pengajuan_nama_lahan').value = namaLahan;
        document.getElementById('modalPengajuan').classList.remove('hidden');
    }

    function closeModalPengajuan() {
        document.getElementById('modalPengajuan').classList.add('hidden');
    }

    function showUnverifiedWarning() {
        Swal.fire({
            title: '<div class="text-xl font-black text-red-600 uppercase tracking-tighter">Akun Belum Terverifikasi</div>',
            html: `
                <div class="py-2 text-sm text-gray-600 leading-relaxed text-center">
                    Mohon maaf, Anda belum dapat menambahkan lahan baru.<br>
                    Silakan lengkapi biodata Anda di halaman <b>Profil Petani</b>, unggah berkas KTP & KK, dan tunggu persetujuan oleh Ketua Kelompok Tani (Admin).
                </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#2D6A4F',
            confirmButtonText: 'OKE, MENGERTI',
            customClass: {
                popup: 'rounded-[2rem] border-none shadow-2xl',
                confirmButton: 'rounded-xl px-8 py-3 text-xs font-black tracking-widest uppercase shadow-lg shadow-green-100'
            }
        });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modalLahan = document.getElementById('modalLahan');
        const modalPengajuan = document.getElementById('modalPengajuan');
        if (event.target == modalLahan) toggleModal();
        if (event.target == modalPengajuan) closeModalPengajuan();
    }
</script>
@endsection