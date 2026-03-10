@extends('layouts.superadmin_layout')

@section('title', 'Data Administrator')

@section('content')
<div class="space-y-6">
    
    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            <span class="font-medium">Berhasil!</span> {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <span class="font-medium">Gagal!</span> {{ session('error') }}
        </div>
    @endif
    
    @if($errors->any())
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Kelola Akun Admin</h3>
                <p class="text-xs text-gray-500 mt-1">Hanya Superadmin yang bisa menambah dan menghapus admin.</p>
            </div>
            
            <button onclick="document.getElementById('addAdminModal').classList.remove('hidden')" class="bg-[#2D6A4F] hover:bg-[#1B4332] text-white px-4 py-2 rounded-lg text-xs font-bold uppercase transition flex items-center shadow-md">
                <i class="fas fa-plus mr-2"></i> Tambah Admin Baru
            </button>
        </div>

        <div class="overflow-x-auto text-xs p-4">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase font-bold">
                    <tr>
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">Username</th>
                        <th class="p-4 border-b">Tanggal Dibuat</th>
                        <th class="p-4 border-b text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($admins as $admin)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-600">#{{ $admin->id }}</td>
                        <td class="p-4 font-bold text-gray-800">{{ $admin->username }}</td>
                        <td class="p-4 text-gray-600">{{ $admin->created_at->format('d M Y H:i') }}</td>
                        <td class="p-4 text-right">
                            <form action="{{ route('superadmin.hapus_admin', $admin->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus Admin ini permanently?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1 rounded shadow-sm text-[10px] font-bold uppercase transition border border-red-200">
                                    <i class="fas fa-trash-alt mr-1"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">Data Admin masih kosong.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Admin -->
<div id="addAdminModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden transform scale-100 border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="fas fa-user-plus mr-2 text-[#2D6A4F]"></i>Tambah Admin</h3>
            <button onclick="document.getElementById('addAdminModal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6">
            <form action="{{ route('superadmin.store_admin') }}" method="POST" class="space-y-5">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest">Username</label>
                    <input type="text" name="username" required autocomplete="off"
                        class="block w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest">Password Baru</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input id="password" type="password" name="password" required placeholder="********"
                            class="block w-full pl-11 py-3 pr-12 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                        <button type="button" onclick="togglePasswordVisibility('password', 'eyeIconPass')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[#2D6A4F] focus:outline-none focus:text-[#2D6A4F] transition-colors">
                            <i id="eyeIconPass" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                {{-- PASSWORD VALIDATION LIVE CHECK --}}
                <div class="space-y-1 px-1">
                    <p id="lenCheck" class="text-[10px] text-gray-500 font-medium transition-colors"><i class="fas fa-times-circle mr-1"></i>Minimal 8 karakter</p>
                    <p id="numCheck" class="text-[10px] text-gray-500 font-medium transition-colors"><i class="fas fa-times-circle mr-1"></i>Mengandung Angka (0-9)</p>
                    <p id="charCheck" class="text-[10px] text-gray-500 font-medium transition-colors"><i class="fas fa-times-circle mr-1"></i>Mengandung Huruf (a-z)</p>
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-widest">Konfirmasi Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors">
                            <i class="fas fa-check-double text-sm"></i>
                        </span>
                        <input id="password_confirmation" type="password" name="password_confirmation" required placeholder="********"
                            class="block w-full pl-11 py-3 pr-12 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'eyeIconConf')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[#2D6A4F] focus:outline-none focus:text-[#2D6A4F] transition-colors">
                            <i id="eyeIconConf" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    <p id="matchCheck" class="text-[10px] text-red-500 font-medium hidden ml-1"><i class="fas fa-exclamation-triangle mr-1"></i>Password konfirmasi tidak cocok</p>
                </div>

                <div class="pt-6 flex justify-end gap-4 items-center">
                    <button type="button" onclick="document.getElementById('addAdminModal').classList.add('hidden')" class="text-sm font-bold text-gray-500 hover:text-gray-700 transition">Batal</button>
                    <button type="submit" class="px-6 py-2.5 bg-[#2D6A4F] hover:bg-[#1B4332] text-white text-xs font-bold rounded-lg shadow-sm transition uppercase tracking-widest">
                        Simpan Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
    // Logic Eye Toggle
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const passInput = document.getElementById('password');
        const confInput = document.getElementById('password_confirmation');
        
        const lenCheck = document.getElementById('lenCheck');
        const charCheck = document.getElementById('charCheck');
        const numCheck = document.getElementById('numCheck');
        const matchCheck = document.getElementById('matchCheck');

        function validatePassword(val) {
            // Check Length (>= 8)
            if (val.length >= 8) {
                lenCheck.classList.replace('text-gray-500', 'text-green-600');
                lenCheck.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Minimal 8 karakter';
            } else {
                lenCheck.classList.replace('text-green-600', 'text-gray-500');
                lenCheck.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Minimal 8 karakter';
            }

            // Check Character (a-z or A-Z)
            if (/[a-zA-Z]/.test(val)) {
                charCheck.classList.replace('text-gray-500', 'text-green-600');
                charCheck.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Mengandung Huruf (a-z)';
            } else {
                charCheck.classList.replace('text-green-600', 'text-gray-500');
                charCheck.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Mengandung Huruf (a-z)';
            }

            // Check Number (0-9)
            if (/[0-9]/.test(val)) {
                numCheck.classList.replace('text-gray-500', 'text-green-600');
                numCheck.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Mengandung Angka (0-9)';
            } else {
                numCheck.classList.replace('text-green-600', 'text-gray-500');
                numCheck.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Mengandung Angka (0-9)';
            }
        }

        function validateConfirm(val, confVal) {
            if (confVal.length > 0 && val !== confVal) {
                matchCheck.classList.remove('hidden');
            } else {
                matchCheck.classList.add('hidden');
            }
        }

        if(passInput && confInput) {
            passInput.addEventListener('input', function () {
                validatePassword(this.value);
                validateConfirm(this.value, confInput.value);
            });

            confInput.addEventListener('input', function () {
                validateConfirm(passInput.value, this.value);
            });
        }
    });
</script>
@endsection
