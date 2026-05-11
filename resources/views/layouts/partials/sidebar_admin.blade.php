<!-- Overlay Mobile Admin -->
<div id="mobileAdminOverlay" onclick="toggleAdminSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

<div id="adminSidebarMenu" class="w-64 bg-[#2D6A4F] text-white flex-shrink-0 flex flex-col shadow-2xl h-screen fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 z-50">
    <div class="p-6 border-b border-[#40916C] bg-[#1B4332] flex justify-between items-center">
        <div class="flex items-center gap-3">
            <i class="fas fa-user-shield text-[#D8F3DC] text-2xl"></i>
            <span class="text-sm font-bold tracking-widest uppercase text-[#D8F3DC]">Admin Margo Rahayu</span>
        </div>
        <!-- Tombol Tutup Sidebar Khusus Mobile -->
        <button onclick="toggleAdminSidebar()" class="md:hidden text-green-100 hover:text-white transition focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>
    
    <nav class="mt-4 flex-1 px-4 space-y-2 overflow-y-auto custom-scrollbar">
        {{-- Menu Dashboard --}}
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('admin.dashboard') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-chart-line mr-3 group-hover:scale-110 transition"></i> Dashboard
        </a>

        {{-- Menu Data Petani --}}
        <a href="{{ route('admin.data_petani') }}" 
            class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('admin.data_petani') ? 'bg-[#40916C] font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-users mr-3 group-hover:scale-110 transition"></i> Data Petani
        </a>

        {{-- Menu Data Bibit --}}
        <a href="{{ route('admin.data_bibit') }}" 
            class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('admin.data_bibit') ? 'bg-[#40916C] font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-seedling mr-3 group-hover:scale-110 transition"></i> Data Bibit
        </a>
        {{-- REVISI: Menu Pengalihan Jatah --}}
        <a href="{{ route('admin.transfer_jatah') }}" 
            class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('admin.transfer_jatah') ? 'bg-[#40916C] shadow-inner font-bold border-l-4 border-[#D8F3DC]' : 'text-orange-100 hover:bg-[#40916C]' }} border border-dashed border-orange-300/30">
            <i class="fas fa-exchange-alt mr-3 group-hover:scale-110 transition duration-500"></i> Pengalihan Jatah
        </a>
        <div class="py-2 border-t border-[#40916C]/30 my-2"></div>

        {{-- Menu Data Periode --}}
        <a href="{{ route('admin.data_periode') }}" 
            class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('admin.data_periode') ? 'bg-[#40916C] font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-calendar-alt mr-3 group-hover:scale-110 transition"></i> Data Periode
        </a>

        {{-- Menu Data Lahan --}}
        <a href="{{ route('admin.data_lahan') }}" 
            class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('admin.data_lahan') ? 'bg-[#40916C] font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-map-marked-alt mr-3 group-hover:scale-110 transition text-sm"></i> Data Lahan
        </a>

        {{-- Menu Riwayat Transaksi --}}
        <a href="{{ route('admin.riwayat_transaksi') }}" 
            class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('admin.riwayat_transaksi') ? 'bg-[#40916C] font-bold border-l-4 border-[#D8F3DC]' : 'text-green-100 hover:bg-[#40916C]' }}">
            <i class="fas fa-list-ul mr-3"></i> Riwayat Transaksi
        </a>
    </nav>

    {{-- Tombol Logout --}}
    <div class="p-4 border-t border-[#40916C] bg-[#1B4332]/50">
        <form id="logoutFormAdmin" action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="button" onclick="confirmLogoutAdmin()" class="w-full flex items-center justify-center py-3 px-4 rounded-xl text-white bg-red-600 hover:bg-red-700 transition duration-300 shadow-lg group">
                <i class="fas fa-power-off mr-3 text-sm group-hover:rotate-90 transition"></i> 
                <span class="font-bold uppercase tracking-wider text-xs">Logout Admin</span>
            </button>
        </form>
    </div>
</div>

<script>
    function confirmLogoutAdmin() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Apakah anda ingin logout?',
                text: "Anda akan keluar dari sesi administrator.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#2D6A4F',
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutFormAdmin').submit();
                }
            });
        } else {
            if (confirm('Apakah Anda yakin ingin keluar?')) {
                document.getElementById('logoutFormAdmin').submit();
            }
        }
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #40916C;
        border-radius: 10px;
    }
</style>