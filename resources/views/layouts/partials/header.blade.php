<header class="bg-white shadow-sm py-4 px-4 md:px-8 flex justify-between items-center border-b border-green-100 sticky top-0 z-30">
    <div class="flex items-center gap-3">
        <!-- Hamburger Menu Button Khusus Mobile -->
        <button onclick="toggleSidebar()" class="md:hidden text-[#1B4332] p-2 focus:outline-none hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-lg md:text-xl font-bold text-[#1B4332] uppercase tracking-wide truncate max-w-[150px] md:max-w-none">
            @yield('title')
        </h1>
    </div>
    
    <div class="flex items-center gap-4">
        <div class="text-right hidden sm:block">
            {{-- Menampilkan Nama Lengkap dari Profil jika ada, jika tidak pakai username --}}
            <p class="text-sm font-bold text-[#2D6A4F]">
                {{ $petani->nama_lengkap ?? Auth::user()->username }}
            </p>
            
            {{-- LOGIKA STATUS VERIFIKASI - Disinkronkan ke variabel $petani --}}
            @if(isset($petani) && $petani->status == 'disetujui')
                <p class="text-[10px] text-green-600 font-bold italic flex items-center justify-end">
                    <span class="relative flex h-2 w-2 mr-1">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    Status: Terverifikasi
                </p>
            @else
                <p class="text-[10px] text-orange-600 font-semibold italic flex items-center justify-end">
                    <i class="fas fa-clock mr-1"></i>Status: Menunggu Verifikasi
                </p>
            @endif
        </div>
        <div class="w-10 h-10 rounded-full bg-[#2D6A4F] flex items-center justify-center text-white shadow-lg border-2 border-[#D8F3DC]">
            <i class="fas fa-user"></i>
        </div>
    </div>
</header>