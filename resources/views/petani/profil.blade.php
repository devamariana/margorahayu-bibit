@extends('layouts.petani_layout')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Notifikasi Sukses --}}
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded shadow-sm">
        <p class="text-green-700 font-bold"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
    </div>
    @endif

    {{-- Alert Status Verifikasi Dinamis --}}
    @if($petani->status == 'disetujui')
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-double text-green-500 mr-3 text-xl"></i>
                <p class="text-sm text-green-700">
                    <strong>Selamat!</strong> Akun Anda telah <span class="font-bold uppercase">Terverifikasi</span>. Identitas dan berkas Anda sudah disetujui oleh Ketua.
                </p>
            </div>
        </div>
    @else
        <div class="bg-orange-50 border-l-4 border-orange-400 p-4 rounded-r-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-clock text-orange-400 mr-3 text-xl"></i>
                <p class="text-sm text-orange-700">
                    <strong>Perhatian:</strong> Akun Anda sedang dalam status <span class="font-bold uppercase">Menunggu Verifikasi</span>. Pastikan data identitas dan berkas KTP/KK sudah benar.
                </p>
            </div>
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-8">
        <div class="text-center md:text-left">
            <p class="text-gray-500 text-sm font-medium">Lengkapi dan perbarui informasi akun Anda secara berkala.</p>
        </div>
    </div>
    
    {{-- Form Simpan Data --}}
    <form action="{{ route('petani.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden">
            <div class="bg-[#2D6A4F] px-6 py-3">
                <h3 class="text-white font-bold flex items-center">
                    <i class="fas fa-user-id mr-2"></i> Informasi Identitas Diri
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest mb-1.5 ml-1">Username (Akun)</label>
                    <input type="text" value="{{ Auth::user()->username }}" class="block w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-500 outline-none cursor-not-allowed text-sm" readonly>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest mb-1.5 ml-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="{{ $petani->nama_lengkap }}" placeholder="Masukkan Nama Sesuai KTP" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest mb-1.5 ml-1">NIK (Sesuai KTP)</label>
                    <input type="text" name="nik" value="{{ $petani->nik }}" oninput="this.value = this.value.replace(/[^0-9]/g, '');" placeholder="Contoh: 3512XXXXXXXXXXXX" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest mb-1.5 ml-1">Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" value="{{ $petani->no_hp }}" oninput="this.value = this.value.replace(/[^0-9]/g, '');" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                
                {{-- INFO: Luas lahan sekarang dikelola di menu terpisah --}}
                <div class="md:col-span-2 bg-blue-50 p-4 rounded-xl border border-blue-100 flex items-center gap-4">
                    <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                    <p class="text-xs text-blue-700">
                        Data luas lahan sekarang dikelola secara terpisah untuk mendukung kepemilikan lebih dari satu lahan. Silakan buka menu <strong>"Data Lahan"</strong> untuk memperbarui aset pertanian Anda.
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest mb-1.5 ml-1">Alamat Lengkap</label>
                    <textarea name="alamat" rows="2" class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">{{ $petani->alamat }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden">
            <div class="bg-[#2D6A4F] px-6 py-3">
                <h3 class="text-white font-bold flex items-center">
                    <i class="fas fa-file-upload mr-2"></i> Berkas Pendukung (Foto KTP & KK)
                </h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                    <label class="block text-sm font-bold text-gray-700">Foto KTP Asli</label>
                    @if($petani->foto_ktp)
                        <div class="mb-2">
                            <img src="{{ asset('uploads/identitas/' . $petani->foto_ktp) }}" class="h-20 w-32 object-cover rounded border zoomable-image cursor-pointer hover:opacity-80 transition" alt="KTP">
                            <p class="text-[10px] text-green-600 font-bold mt-1"><i class="fas fa-check-circle"></i> File Terunggah</p>
                        </div>
                    @endif
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-[#2D6A4F] transition group cursor-pointer relative">
                        <input type="file" name="foto_ktp" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-green-50 file:text-[#2D6A4F] hover:file:bg-green-100">
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="block text-sm font-bold text-gray-700">Foto Kartu Keluarga (KK)</label>
                    @if($petani->foto_kk)
                        <div class="mb-2">
                            <img src="{{ asset('uploads/identitas/' . $petani->foto_kk) }}" class="h-20 w-32 object-cover rounded border zoomable-image cursor-pointer hover:opacity-80 transition" alt="KK">
                            <p class="text-[10px] text-green-600 font-bold mt-1"><i class="fas fa-check-circle"></i> File Terunggah</p>
                        </div>
                    @endif
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-[#2D6A4F] transition group cursor-pointer relative">
                        <input type="file" name="foto_kk" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-green-50 file:text-[#2D6A4F] hover:file:bg-green-100">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-[#2D6A4F] hover:bg-[#1B4332] text-white font-bold py-3 px-10 rounded-xl shadow-lg transform hover:-translate-y-1 transition duration-300 flex items-center">
                <i class="fas fa-save mr-2"></i> SIMPAN PERUBAHAN PROFIL
            </button>
        </div>
    </form>
</div>
@endsection