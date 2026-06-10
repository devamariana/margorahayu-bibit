<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Petani - Margo Rahayu II</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F0F7F2] flex items-center justify-center min-h-screen p-6">

    <div class="w-full max-w-[450px] bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(45,106,79,0.1)] overflow-hidden border border-gray-50 relative">
        <div class="h-2 bg-[#2D6A4F]"></div>

        <div class="p-10 pb-6">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#D8F3DC] rounded-2xl mb-4 shadow-sm">
                    <i class="fas fa-user-plus text-[#2D6A4F] text-2xl"></i>
                </div>
                
                <h2 class="text-2xl font-extrabold text-[#1B4332] tracking-tight uppercase">Daftar Akun</h2>
                <p class="text-gray-400 text-sm mt-1 font-medium italic">Sistem Informasi Petani Margo Rahayu II</p>
            </div>

            {{-- PESAN ERROR UMUM --}}
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl">
                    <p class="text-xs font-bold text-red-600 mb-1">Terjadi Kesalahan:</p>
                    <ul class="list-disc list-inside text-[10px] text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" class="space-y-4">
                @csrf
                


                {{-- USERNAME --}}
                <div class="space-y-1.5">
                    <label for="username" class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Username</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors">
                            <i class="fas fa-user text-sm"></i>
                        </span>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Pilih username unik" required 
                            class="block w-full pl-11 pr-4 py-3 bg-white border @error('username') border-red-500 @else border-gray-300 @enderror rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                    </div>
                    @error('username') <p class="text-[10px] text-red-500 ml-1">{{ $message }}</p> @enderror
                </div>

                {{-- NOMOR WA --}}
                <div class="space-y-1.5">
                    <label for="no_hp" class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Nomor WA <span class="text-[9px] text-gray-400 lowercase normal-case">(Aktif Fonnte)</span></label>
                    <div class="relative group flex">
                        <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 @error('no_hp') border-red-500 @else border-gray-300 @enderror bg-gray-50 text-gray-500 text-sm font-bold">
                            +62
                        </span>
                        <input id="no_hp" name="no_hp" type="tel" value="{{ old('no_hp') }}" placeholder="8123456789" required oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                            class="block w-full px-4 py-3 bg-white border @error('no_hp') border-red-500 @else border-gray-300 @enderror rounded-r-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                    </div>
                    @error('no_hp') <p class="text-[10px] text-red-500 ml-1">{{ $message }}</p> @enderror
                </div>

                {{-- PASSWORD --}}
                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input id="password" name="password" type="password" placeholder="********" required 
                            class="block w-full pl-11 pr-12 py-3 bg-white border @error('password') border-red-500 @else border-gray-300 @enderror rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
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

                {{-- KONFIRMASI --}}
                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Konfirmasi Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors">
                            <i class="fas fa-check-double text-sm"></i>
                        </span>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="********" required 
                            class="block w-full pl-11 pr-12 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'eyeIconConf')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[#2D6A4F] focus:outline-none focus:text-[#2D6A4F] transition-colors">
                            <i id="eyeIconConf" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    <p id="matchCheck" class="text-[10px] text-red-500 font-medium hidden ml-1"><i class="fas fa-exclamation-triangle mr-1"></i>Password konfirmasi tidak cocok</p>
                </div>

                <div class="pt-4">
                    <button type="submit" 
                        class="w-full py-4 bg-[#2D6A4F] hover:bg-[#1B4332] text-white font-bold rounded-xl shadow-lg hover:shadow-none transform hover:translate-y-0.5 transition duration-300 uppercase tracking-widest text-xs">
                        Daftar Akun
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center border-t border-gray-100 pt-6 space-y-4">
                <a href="{{ url('/') }}" class="group flex items-center justify-center gap-3 w-full py-3.5 bg-gray-50 text-[#2D6A4F] border border-[#D8F3DC] rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-[#2D6A4F] hover:text-white hover:shadow-xl transition-all duration-500 transform hover:-translate-y-1">
                    <i class="fas fa-chevron-left group-hover:-translate-x-1 transition-transform"></i>
                    Kembali ke Beranda Utama
                </a>

                <p class="text-xs text-gray-500 font-medium">
                    Sudah memiliki akun? 
                    <a href="{{ route('login') }}" class="ml-1 font-bold text-[#2D6A4F] hover:text-[#40916C] underline-offset-4 hover:underline transition-all">
                        Masuk Sekarang
                    </a>
                </p>
            </div>

        </div>
    </div>

    <script>
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

        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        
        const lenCheck = document.getElementById('lenCheck');
        const numCheck = document.getElementById('numCheck');
        const charCheck = document.getElementById('charCheck');
        const matchCheck = document.getElementById('matchCheck');

        function updateCheck(el, isValid) {
            if(isValid) {
                el.classList.remove('text-gray-500', 'text-red-500');
                el.classList.add('text-green-500');
                el.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' + el.innerText;
            } else {
                el.classList.remove('text-green-500');
                el.classList.add('text-gray-500');
                el.innerHTML = '<i class="fas fa-times-circle mr-1"></i>' + el.innerText;
            }
        }

        passwordInput.addEventListener('input', function() {
            const val = this.value;
            // 8 chars
            updateCheck(lenCheck, val.length >= 8);
            // Numerics
            updateCheck(numCheck, /\d/.test(val));
            // Characters
            updateCheck(charCheck, /[a-zA-Z]/.test(val));
            
            checkMatch();
        });

        confirmInput.addEventListener('input', checkMatch);

        function checkMatch() {
            if(confirmInput.value !== '' && confirmInput.value !== passwordInput.value) {
                matchCheck.classList.remove('hidden');
            } else {
                matchCheck.classList.add('hidden');
            }
        }
    </script>
</body>
</html>