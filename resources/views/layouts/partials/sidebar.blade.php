<!-- Tambahkan ID dan manipulasi class untuk mobile -->
<div id="mobileSidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

<div id="sidebarMenu" class="w-64 bg-[#2D6A4F] text-white flex-shrink-0 flex flex-col shadow-2xl h-screen fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 z-50">
    <div class="p-6 border-b border-[#40916C] bg-[#1B4332] flex justify-between items-center">
        <div class="flex items-center gap-3">
            <i class="fas fa-leaf text-[#D8F3DC] text-2xl"></i>
            <span class="text-lg font-bold tracking-widest uppercase">Margo Rahayu II</span>
        </div>
        <!-- Tombol Tutup Sidebar Khusus Mobile -->
        <button onclick="toggleSidebar()" class="md:hidden text-green-100 hover:text-white transition focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>
    
    <nav class="mt-6 flex-1 px-4 space-y-2">
        <a href="{{ route('petani.dashboard') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('petani.dashboard') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-th-large mr-3 group-hover:scale-110 transition"></i> Dashboard
        </a>

        {{-- MENU PROFIL (DIPECAH) --}}
        <a href="{{ route('petani.profil') }}" id="sidebar-profil"
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('petani.profil') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-user-circle mr-3 group-hover:scale-110 transition"></i> Profil Saya
        </a>

        {{-- MENU LAHAN (BARU) --}}
        <a href="{{ route('petani.lahan') }}" id="sidebar-lahan"
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('petani.lahan') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-map-marked-alt mr-3 group-hover:scale-110 transition"></i> Data Lahan
        </a>

        <a href="{{ route('petani.informasi_bibit') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('petani.informasi_bibit') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-info-circle mr-3 group-hover:scale-110 transition"></i> Informasi Bibit
        </a>



        <a href="{{ route('petani.beli_bibit') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('petani.beli_bibit') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-shopping-cart mr-3 group-hover:scale-110 transition"></i> Pesan Bibit
        </a>

        <a href="{{ route('petani.riwayat') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('petani.riwayat') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-history mr-3 group-hover:scale-110 transition"></i> Riwayat Pembelian
        </a>

        <a href="{{ route('petani.transfer_jatah') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('petani.transfer_jatah') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-exchange-alt mr-3 group-hover:scale-110 transition"></i> Transfer Jatah
        </a>


    </nav>

    <div class="p-4 border-t border-[#40916C]">
        <form id="logoutForm" action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="button" onclick="confirmLogout()" class="w-full flex items-center py-3 px-4 rounded-xl text-white bg-red-600 hover:bg-red-700 transition duration-300 shadow-md">
                <i class="fas fa-power-off mr-3 text-sm"></i> 
                <span class="font-bold uppercase tracking-wider text-xs">Logout</span>
            </button>
        </form>
    </div>
</div>

<script>
    function confirmLogout() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Apakah anda ingin logout?',
                text: "Sesi Anda akan berakhir jika Anda logout.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#2D6A4F',
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutForm').submit();
                }
            });
        } else {
            if (confirm('Apakah anda ingin logout?')) {
                document.getElementById('logoutForm').submit();
            }
        }
    }
</script>