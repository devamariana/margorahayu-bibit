<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem - Margo Rahayu II</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F0F7F2] flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(45,106,79,0.15)] overflow-hidden border border-gray-100 relative">
        <div class="h-3 bg-[#2D6A4F]"></div>

        <div class="p-10 pb-6">
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-[#D8F3DC] rounded-3xl mb-6 rotate-3 hover:rotate-0 transition-transform duration-300">
                    <i class="fas fa-user-circle text-[#2D6A4F] text-4xl"></i>
                </div>
                
                <h2 class="text-3xl font-extrabold text-[#1B4332] tracking-tight uppercase">Selamat Datang</h2>
                <p class="text-gray-400 mt-2 font-medium">Silakan masuk ke akun Anda</p>
            </div>

            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-xl text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl text-sm text-red-600">
                    Username atau password salah.
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                
                <div class="space-y-1.5">
                    <label for="username" class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Username</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors">
                            <i class="fas fa-user"></i> </span>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Masukkan username" required 
                            class="block w-full pl-11 pr-4 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-bold text-gray-800 uppercase tracking-widest ml-1">Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-[#2D6A4F] transition-colors">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input id="password" name="password" type="password" placeholder="********" required 
                            class="block w-full pl-11 pr-12 py-3 bg-white border border-gray-300 rounded-xl focus:border-[#2D6A4F] focus:ring-2 focus:ring-[#2D6A4F] focus:ring-opacity-50 outline-none transition-all duration-200 text-sm">
                        <button type="button" onclick="togglePasswordVisibility('password', 'eyeIcon')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[#2D6A4F] focus:outline-none focus:text-[#2D6A4F] transition-colors">
                            <i id="eyeIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center px-1 pt-1">
                    <label class="flex items-center cursor-pointer group">
                        <input id="remember_me" name="remember" type="checkbox" 
                            class="w-5 h-5 border-gray-300 rounded-lg text-[#2D6A4F] focus:ring-[#D8F3DC] transition duration-200">
                        <span class="ml-3 text-sm text-gray-500 font-medium group-hover:text-gray-700 transition-colors">Ingat saya</span>
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit" 
                        class="w-full py-4 bg-[#2D6A4F] hover:bg-[#1B4332] text-white font-bold rounded-2xl shadow-[0_10px_20px_rgba(45,106,79,0.3)] hover:shadow-none transform hover:translate-y-1 transition duration-300 uppercase tracking-widest text-sm">
                        Masuk
                    </button>
                </div>
            </form>

            <div class="mt-10 text-center border-t border-gray-100 pt-8 space-y-4">
                <a href="{{ url('/') }}" class="group flex items-center justify-center gap-3 w-full py-3.5 bg-gray-50 text-[#2D6A4F] border border-[#D8F3DC] rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-[#2D6A4F] hover:text-white hover:shadow-xl transition-all duration-500 transform hover:-translate-y-1">
                    <i class="fas fa-chevron-left group-hover:-translate-x-1 transition-transform"></i>
                    Kembali ke Beranda Utama
                </a>

                <p class="text-sm text-gray-500 font-medium">
                    Belum punya akun? 
                    <a href="{{ route('register') }}" class="ml-1 font-bold text-[#2D6A4F] hover:text-[#40916C] underline-offset-4 hover:underline transition-all">
                        Daftar Sekarang
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
    </script>
</body>
</html>