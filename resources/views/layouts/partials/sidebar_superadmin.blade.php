<!-- Overlay Mobile Superadmin -->
<div id="mobileSuperadminOverlay" onclick="toggleSuperadminSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

<div id="superadminSidebarMenu" class="w-64 bg-[#1B4332] text-white flex-shrink-0 flex flex-col shadow-2xl h-screen border-r border-[#2D6A4F] fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 z-50">
    <div class="p-6 border-b border-[#2D6A4F] bg-[#0F2921] flex justify-between items-center">
        <div class="flex items-center gap-3">
            <i class="fas fa-crown text-yellow-400 text-2xl"></i>
            <span class="text-sm font-bold tracking-widest uppercase text-[#D8F3DC]">Superadmin SI</span>
        </div>
        <!-- Tombol Tutup Sidebar Khusus Mobile -->
        <button onclick="toggleSuperadminSidebar()" class="md:hidden text-green-100 hover:text-white transition focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>
    
    <nav class="mt-4 flex-1 px-4 space-y-2 overflow-y-auto custom-scrollbar">
        {{-- Menu Dashboard --}}
        <a href="{{ route('superadmin.dashboard') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('superadmin.dashboard') ? 'bg-[#2D6A4F] shadow-inner font-bold border-l-4 border-yellow-400' : 'text-green-100 hover:bg-[#2D6A4F]' }}">
            <i class="fas fa-globe mr-3 group-hover:scale-110 transition"></i> Master Dashboard
        </a>

        {{-- Menu Data Admin --}}
        <a href="{{ route('superadmin.data_admin') }}" 
            class="flex items-center py-3 px-4 rounded-xl transition group {{ request()->routeIs('superadmin.data_admin') ? 'bg-[#2D6A4F] font-bold border-l-4 border-yellow-400' : 'text-green-100 hover:bg-[#2D6A4F]' }}">
            <i class="fas fa-user-tie mr-3 group-hover:scale-110 transition"></i> Kelola Admin
        </a>
    </nav>

    {{-- Tombol Logout --}}
    <div class="p-4 border-t border-[#2D6A4F] bg-[#0F2921]/50">
        <form id="logoutFormSuperadmin" action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="button" onclick="confirmLogoutSuperadmin()" class="w-full flex items-center justify-center py-3 px-4 rounded-xl text-white bg-red-600 hover:bg-red-700 transition duration-300 shadow-lg group">
                <i class="fas fa-power-off mr-3 text-sm group-hover:rotate-90 transition"></i> 
                <span class="font-bold uppercase tracking-wider text-xs">Logout Superadmin</span>
            </button>
        </form>
    </div>
</div>

<script>
    function confirmLogoutSuperadmin() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Apakah anda ingin logout?',
                text: "Anda akan keluar dari kendali penuh superadmin.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#2D6A4F',
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutFormSuperadmin').submit();
                }
            });
        } else {
            if (confirm('Apakah Anda yakin ingin keluar?')) {
                document.getElementById('logoutFormSuperadmin').submit();
            }
        }
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #2D6A4F; border-radius: 10px; }
</style>
