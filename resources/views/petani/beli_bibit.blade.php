@extends('layouts.petani_layout')

@section('title', 'Form Pembelian Bibit')

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

    {{-- STEP 1: PILIH LAHAN --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-green-100 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-[#2D6A4F]">
                <i class="fas fa-map-marked-alt text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">1. Pilih Lahan yang Akan Ditanami</h3>
                <p class="text-xs text-gray-500">Jatah bibit akan dihitung otomatis sesuai luas lahan yang dipilih.</p>
            </div>
        </div>
        <div class="w-full md:w-1/3 relative">
            <select id="pilih-lahan" onchange="resetPilihanBibit()" class="appearance-none w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2D6A4F] outline-none font-bold text-[#1B4332] pr-10">
                <option value="" data-luas="0">-- Pilih Lokasi Lahan --</option>
                @foreach($lahans as $l)
                    <option value="{{ $l->id }}" data-luas="{{ $l->luas_lahan }}">
                        {{ $l->nama_blok }} ({{ $l->luas_lahan }} m²)
                    </option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                <i class="fas fa-chevron-down text-[#2D6A4F] text-xs"></i>
            </div>
        </div>
    </div>

    {{-- STEP 2: PILIH BIBIT (VERSI LEBIH PADAT) --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-orange-100">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600">
                <i class="fas fa-seedling text-xl"></i>
            </div>
            <div class="flex flex-col md:flex-row md:items-center justify-between w-full gap-4">
                <div>
                    <h3 class="font-bold text-gray-800">2. Pilih Varietas Bibit</h3>
                    <p class="text-xs text-gray-500">Pilih salah satu bibit yang ingin Anda beli untuk lahan di atas.</p>
                </div>
                {{-- Dropdown Filter Musim --}}
                <div class="relative w-full md:w-max">
                    <select id="filter-musim" onchange="filterBibitByMusim(this.value)" class="appearance-none w-full md:w-64 p-3 bg-orange-50 border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none font-bold text-orange-700 pr-10">
                        <option value="all" selected>-- Pilih Musim --</option>
                        <option value="kemarau">Musim Kemarau</option>
                        <option value="penghujan">Musim Penghujan</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-orange-600">
                        <i class="fas fa-filter text-xs"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="bibit-grid">
            @forelse($semuaBibit as $b)
                @php
                    $isSalahMusim = ($b->kategori_musim !== $currentMusimAktif);
                    $isTutup = !$b->is_buka;
                    $isStokHabis = $b->stok <= 0;
                    
                    $cardClasses = 'bibit-card p-4 rounded-xl border-2 transition-all cursor-pointer relative ';
                    if ($isStokHabis) {
                        $cardClasses .= 'bg-gray-50 border-gray-100 opacity-60 grayscale cursor-not-allowed pointer-events-none';
                    } elseif ($isSalahMusim || $isTutup) {
                        $cardClasses .= 'bg-red-50 border-red-100 opacity-80';
                    } else {
                        $cardClasses .= 'bg-white border-gray-100 hover:border-green-500 hover:shadow-md';
                    }
                @endphp
                
                <div onclick="pilihBibit('{{ $b->id }}', '{{ $b->nama_bibit }}', {{ $b->harga_subsidi }}, {{ $b->stok }}, {{ $b->sisa_jatah_global ?? 0 }}, '{{ $b->kategori_musim }}', '{{ $currentMusimAktif }}', {{ $isTutup ? 'true' : 'false' }})" 
                     class="{{ $cardClasses }}" 
                     data-id="{{ $b->id }}"
                     data-musim="{{ $b->kategori_musim }}">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                            @if($b->gambar)
                                <img src="{{ asset('uploads/bibit/' . $b->gambar) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <i class="fas fa-seedling"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-gray-900 truncate uppercase text-sm">{{ $b->nama_bibit }}</h4>
                            <p class="text-[#2D6A4F] font-black text-xs">Rp {{ number_format($b->harga_subsidi, 0, ',', '.') }}/kg</p>
                            <div class="flex items-center gap-1 mt-1">
                                <span class="text-[9px] px-1.5 font-bold rounded {{ $b->kategori_musim === 'kemarau' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $b->kategori_musim === 'kemarau' ? '☀ Kemarau' : '🌧 Penghujan' }}
                                </span>
                                <span class="text-[9px] font-bold text-gray-500">Stok: {{ $b->stok }} kg</span>
                            </div>
                        </div>
                    </div>

                    @if($isSalahMusim)
                        <div class="mt-2 text-[8px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded text-center">⚠ Saluran dikunci (Salah Musim)</div>
                    @elseif($isTutup)
                        <div class="mt-2 text-[8px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded text-center">🔒 Distribusi Masa Tunggu</div>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-gray-500 italic">Tidak ada bibit yang tersedia.</div>
            @endforelse
        </div>
    </div>

    {{-- RINGKASAN PESANAN DENGAN FORM --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6 uppercase tracking-wider">3. Detail & Konfirmasi Pesanan</h3>
        
        <form id="lahanForm" action="{{ route('petani.proses_beli') }}" method="POST">
            @csrf
            {{-- Input Hidden untuk mengirim data ke TransaksiController --}}
            <input type="hidden" name="lahan_id" id="input-lahan-id">
            <input type="hidden" name="bibit_id" id="input-bibit-id">
            <input type="hidden" name="total_harga" id="input-total-harga">

            <div class="space-y-4">
                <div id="placeholder-text" class="flex justify-between text-gray-600 border-b pb-4 italic">
                    <span>Silahkan pilih lahan & bibit di atas untuk mulai memesan</span>
                    <span class="font-bold text-gray-800">Rp 0</span>
                </div>

                <div id="detail-pesanan" class="hidden space-y-6 border-b pb-6">
                    <div class="flex justify-between items-center text-gray-700">
                        <span id="label-bibit" class="font-black text-2xl text-[#2D6A4F]">Nama Bibit</span>
                        <span id="harga-item" class="text-sm font-bold bg-gray-100 px-3 py-1 rounded-full">Rp 0 /kg</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest mb-1">Analisis Perhitungan</p>
                            <p class="text-[10px] text-gray-600 font-mono italic">(Jatah otomatis sesuai luas lahan)</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-orange-400 uppercase font-black tracking-widest mb-1">Hak Jatah Maksimal</p>
                            <p id="berat-estimasi" class="font-black text-orange-600 text-lg">0 Kg</p>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 py-4 border-t border-dashed border-gray-200">
                        <div class="max-w-xs">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Jumlah Dijemput/Diambil (Kg)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="jumlah_beli" id="input-jumlah-beli" value="0" min="0.1" class="w-full p-4 bg-white border-2 border-green-600 rounded-xl font-black text-[#2D6A4F] text-xl focus:ring-4 focus:ring-green-100 outline-none transition" oninput="hitungTotalManual()">
                                <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
                                    <span class="font-bold text-gray-400 uppercase text-xs italic">Kg</span>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 italic">* Maksimal pengambilan sesuai hak jatah Anda.</p>
                        </div>

                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-500 block mb-1">Subtotal Pembayaran</p>
                            <p id="total-harga" class="text-4xl font-black text-[#2D6A4F]">Rp 0</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 py-6 border-t border-dashed border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Pilih Metode Pembayaran</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Opsi Midtrans --}}
                        <label class="relative flex flex-col p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-green-500 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                            <input type="radio" name="metode_pembayaran" value="midtrans" class="sr-only" checked>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 group-has-[:checked]:bg-green-600 group-has-[:checked]:text-white">
                                    <i class="fas fa-credit-card text-xs"></i>
                                </div>
                                <span class="font-bold text-sm text-gray-800">Otomatis (Midtrans)</span>
                            </div>
                            <p class="text-[10px] text-gray-400">VA, QRIS, E-Wallet</p>
                        </label>

                        {{-- Opsi Transfer Manual --}}
                        <label class="relative flex flex-col p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-green-500 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                            <input type="radio" name="metode_pembayaran" value="transfer_manual" class="sr-only">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 group-has-[:checked]:bg-green-600 group-has-[:checked]:text-white">
                                    <i class="fas fa-university text-xs"></i>
                                </div>
                                <span class="font-bold text-sm text-gray-800">Transfer Manual</span>
                            </div>
                            <p class="text-[10px] text-gray-400">Upload bukti transfer</p>
                        </label>

                        {{-- Opsi Tunai (Kasir) --}}
                        <label class="relative flex flex-col p-4 bg-white border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-green-500 transition-all group has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                            <input type="radio" name="metode_pembayaran" value="tunai" class="sr-only">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 group-has-[:checked]:bg-green-600 group-has-[:checked]:text-white">
                                    <i class="fas fa-money-bill-wave text-xs"></i>
                                </div>
                                <span class="font-bold text-sm text-gray-800">Bayar Tunai</span>
                            </div>
                            <p class="text-[10px] text-gray-400">Bayar di lokasi</p>
                        </label>
                    </div>
                </div>

                <p class="text-center text-[10px] text-gray-400 mt-6 italic" id="instruksi-bayar">Klik tombol di bawah untuk memproses pesanan dan melakukan pembayaran</p>

                <div class="flex justify-end mt-6">
                    <button type="submit" id="btn-bayar" class="bg-gray-400 text-white font-bold py-4 px-10 rounded-2xl shadow-lg transition duration-300 cursor-not-allowed uppercase tracking-widest text-sm" disabled>
                        Konfirmasi & Bayar Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let currentHarga = 0;
    let currentQuota = 0;
    const purchasesMap = @json($purchases ?? []);
    let isSelectedWrongSeason = false;
    let isDistributionClosed = false;

    function filterBibitByMusim(musim) {
        document.querySelectorAll('.bibit-card').forEach(card => {
            if (musim === 'all' || card.getAttribute('data-musim') === musim) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
        
        // Reset pilihan bibit jika yang sedang dipilih sekarang disembunyikan
        const selectedId = document.getElementById('input-bibit-id').value;
        if (selectedId) {
            const selectedCard = document.querySelector(`.bibit-card[data-id="${selectedId}"]`);
            if (selectedCard && selectedCard.classList.contains('hidden')) {
                resetPilihanBibit();
            }
        }
    }

    function resetPilihanBibit() {
        document.querySelectorAll('.bibit-card').forEach(card => {
            card.classList.remove('border-green-600', 'bg-green-50', 'ring-2', 'ring-green-100');
            card.classList.add('border-gray-100', 'bg-white');
        });
        document.getElementById('placeholder-text').classList.remove('hidden');
        document.getElementById('detail-pesanan').classList.add('hidden');
        document.getElementById('btn-bayar').classList.add('bg-gray-400', 'cursor-not-allowed');
        document.getElementById('btn-bayar').disabled = true;
        document.getElementById('total-harga').innerText = 'Rp 0';
        
        document.getElementById('input-bibit-id').value = '';
        document.getElementById('input-jumlah-beli').value = 0;
        isSelectedWrongSeason = false;
        isDistributionClosed = false;
    }

    async function pilihBibit(id, nama, harga, currentStok = 0, sisaJatahGlobal = 0, musimBibit, musimAktif, isTutup = false) {
        
        const selectLahan = document.getElementById('pilih-lahan');
        const selectedOption = selectLahan.options[selectLahan.selectedIndex];
        
        if (!selectedOption.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Lahan Belum Dipilih',
                text: 'Silakan pilih lokasi lahan Anda terlebih dahulu pada langkah pertama.',
                confirmButtonColor: '#2D6A4F'
            });
            return;
        }

        isDistributionClosed = isTutup;
        isSelectedWrongSeason = (musimBibit !== musimAktif);

        if (isDistributionClosed || isSelectedWrongSeason) {
            Swal.fire({
                icon: 'info',
                title: 'Status Bibit',
                text: isDistributionClosed 
                    ? 'Maaf, distribusi bibit ini sedang ditutup oleh Admin.' 
                    : 'Bibit ini hanya dapat dibeli pada ' + musimBibit.toUpperCase() + '.',
                confirmButtonColor: '#3085d6'
            });
        }

        // Loading
        Swal.fire({
            title: 'Verifikasi Jatah...',
            text: 'Kami sedang menghitung hak Anda berdasarkan data pengajuan.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
        });

        try {
            const response = await fetch('{{ route("petani.cek_jatah") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    bibit_id: id,
                    lahan_id: selectedOption.value
                })
            });
            
            const result = await response.json();
            Swal.close();

            if (result.status === 'info' || result.status === 'error') {
                Swal.fire({
                    icon: result.status,
                    title: 'Informasi Jatah',
                    text: result.message,
                    confirmButtonColor: '#2D6A4F'
                });
                resetPilihanBibit();
                return;
            }

            const jatah = result.sisa || 0;
            currentHarga = harga;
            currentQuota = jatah;

            document.getElementById('input-lahan-id').value = selectedOption.value;
            document.getElementById('input-bibit-id').value = id;
            document.getElementById('input-jumlah-beli').value = jatah;
            
            document.getElementById('placeholder-text').classList.add('hidden');
            document.getElementById('detail-pesanan').classList.remove('hidden');
            document.getElementById('label-bibit').innerText = 'Bibit ' + nama;
            document.getElementById('berat-estimasi').innerText = jatah + ' Kg';
            document.getElementById('harga-item').innerText = 'Rp ' + harga.toLocaleString('id-ID') + ' /kg';
            
            if (jatah <= 0) {
                 Swal.fire({
                    icon: 'info',
                    title: 'Jatah Tidak Tersedia',
                    text: 'Anda tidak memiliki sisa jatah untuk varietas ini pada lahan yang dipilih.',
                    confirmButtonColor: '#2D6A4F'
                });
                resetPilihanBibit();
                return;
            }

            document.querySelectorAll('.bibit-card').forEach(card => {
                card.classList.remove('border-green-600', 'bg-green-50', 'ring-2', 'ring-green-100');
                card.classList.add('border-gray-100', 'bg-white');
            });
            const activeCard = document.querySelector(`.bibit-card[data-id="${id}"]`);
            if (activeCard) {
                activeCard.classList.remove('border-gray-100', 'bg-white');
                activeCard.classList.add('border-green-600', 'bg-green-50', 'ring-2', 'ring-green-100');
            }

            hitungTotalManual();

        } catch (error) {
            Swal.close();
            console.error('Check Quota Error:', error);
            Swal.fire('Error', 'Gagal memverifikasi jatah ke server. Periksa koneksi Anda.', 'error');
        }
    }

    function hitungTotalManual() {
        let inputVal = document.getElementById('input-jumlah-beli').value;
        let qty = parseFloat(inputVal) || 0;
        const btnBayar = document.getElementById('btn-bayar');

        qty = Math.round(qty * 10) / 10;

        if (qty > currentQuota) {
            qty = currentQuota;
            document.getElementById('input-jumlah-beli').value = qty;
            Swal.fire({
                icon: 'error',
                title: 'Melebihi Jatah',
                text: 'Jatah maksimal Anda adalah ' + currentQuota + ' Kg.',
                confirmButtonColor: '#2D6A4F'
            });
        }

        const total = qty * currentHarga;
        document.getElementById('input-total-harga').value = Math.round(total);
        document.getElementById('total-harga').innerText = 'Rp ' + Math.round(total).toLocaleString('id-ID');

        const instruksi = document.getElementById('instruksi-bayar');
        if (qty > 0 && document.getElementById('input-bibit-id').value && qty <= currentQuota && !isSelectedWrongSeason && !isDistributionClosed) {
            btnBayar.disabled = false;
            btnBayar.classList.remove('bg-gray-400', 'cursor-not-allowed', 'opacity-50');
            btnBayar.classList.add('bg-[#D97706]', 'hover:bg-[#B45309]');
            instruksi.innerText = "Klik tombol di bawah untuk memproses pesanan dan melakukan pembayaran";
            instruksi.classList.remove('text-red-500', 'font-black');
            instruksi.classList.add('text-gray-400', 'italic');
        } else {
            btnBayar.disabled = true;
            btnBayar.classList.add('bg-gray-400', 'cursor-not-allowed', 'opacity-50');
            btnBayar.classList.remove('bg-[#D97706]', 'hover:bg-[#B45309]');
            
            if (isDistributionClosed) {
                instruksi.innerText = "🔒 Transaksi dikunci (Distribusi Tutup)";
                instruksi.classList.add('text-red-500', 'font-black');
            } else if (isSelectedWrongSeason) {
                instruksi.innerText = "⚠ Transaksi dikunci (Salah Musim)";
                instruksi.classList.add('text-red-500', 'font-black');
            }
        }
    }

    // Auto-select bibit if passed via URL
    document.addEventListener('DOMContentLoaded', () => {
        // Inisialisasi Filter Musim sesuai default select
        const initialMusim = document.getElementById('filter-musim').value;
        filterBibitByMusim(initialMusim);

        const urlParams = new URLSearchParams(window.location.search);
        const bibitId = urlParams.get('bibit_id');
        if (bibitId) {
            const targetCard = document.querySelector(`.bibit-card[data-id="${bibitId}"]`);
            if (targetCard) {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const selectLahan = document.getElementById('pilih-lahan');
                if (selectLahan.options.length === 2) { 
                    selectLahan.selectedIndex = 1;
                    targetCard.click();
                } else {
                    targetCard.classList.add('ring-4', 'ring-orange-400');
                    setTimeout(() => targetCard.classList.remove('ring-4', 'ring-orange-400'), 3000);
                }
            }
        }
    });
</script>
@endsection