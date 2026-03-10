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

        {{-- NOTIFICATION CENTER (Untuk Semua Petani) --}}
        @if(isset($petani))
        @php
            $userNotifications = Auth::check() ? Auth::user()->notifications()->latest()->take(10)->get() : collect();
            $unreadCount = Auth::check() ? Auth::user()->unreadNotifications->count() : 0;
        @endphp
        <div class="relative items-center flex" id="notificationCenterDropdown">
            <button onclick="toggleNotificationDropdown()" class="relative p-2 text-gray-500 hover:text-[#2D6A4F] focus:outline-none transition group">
                <i class="fas fa-bell text-xl group-hover:scale-110 transition-transform"></i>
                @if($unreadCount > 0)
                <span class="absolute top-1 right-1 flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white"></span>
                </span>
                @endif
            </button>

            <!-- Dropdown Menu Pusat Notifikasi -->
            <div id="notifDropdownMenu" class="absolute right-0 top-full mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 hidden flex-col z-50 transform origin-top-right transition-all duration-200 scale-95 opacity-0">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl flex justify-between items-center">
                    <h3 class="font-bold text-[#1B4332] text-sm"><i class="fas fa-inbox mr-2"></i> Pusat Informasi</h3>
                    <span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold">Terbaru</span>
                </div>
                
                <div class="max-h-80 overflow-y-auto custom-scrollbar p-2">
                    @forelse($userNotifications as $notif)
                        @php 
                            $bgClass = $notif->data['tipe'] == 'success' ? 'bg-green-50/50 hover:bg-green-50 border-green-100' : 
                                      ($notif->data['tipe'] == 'bibit' ? 'bg-indigo-50/50 hover:bg-indigo-50 border-indigo-100' : 
                                      ($notif->data['tipe'] == 'warning' ? 'bg-orange-50/50 hover:bg-orange-50 border-orange-100' : 
                                      'bg-blue-50/50 hover:bg-blue-50 border-blue-100'));
                            $iconClass = $notif->data['tipe'] == 'success' ? 'fa-check-circle text-green-500' : 
                                        ($notif->data['tipe'] == 'bibit' ? 'fa-seedling text-indigo-500' : 
                                        ($notif->data['tipe'] == 'warning' ? 'fa-exclamation-triangle text-orange-500' : 
                                        'fa-info-circle text-blue-500'));
                        @endphp
                        <div class="p-3 mb-2 rounded-xl border transition cursor-pointer {{ $bgClass }} relative group/item" 
                             onclick="window.location.href='{{ route('notifikasi.baca', $notif->id) }}'">
                            <div class="flex gap-3">
                                <div class="mt-0.5"><i class="fas {{ $iconClass }}"></i></div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-800 mb-1 {{ empty($notif->read_at) ? 'text-black' : '' }} tracking-tight">{{ $notif->data['judul'] ?? 'Pemberitahuan' }}</h4>
                                    <p class="text-[11px] text-gray-600 leading-relaxed">{{ $notif->data['pesan'] ?? '' }}</p>
                                    <div class="flex justify-between items-center mt-2">
                                        <p class="text-[9px] text-gray-400"><i class="far fa-clock mr-1"></i>{{ $notif->created_at->diffForHumans() }}</p>
                                        @if($notif->data['url'] ?? false)
                                            <span class="text-[9px] font-bold text-[#2D6A4F] opacity-0 group-hover/item:opacity-100 transition-opacity">Buka &rarr;</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 text-xs">
                            <i class="fas fa-box-open text-2xl text-gray-300 block mb-2"></i> Belum ada notifikasi baru.
                        </div>
                    @endforelse
                </div>
                
                <div class="p-3 border-t border-gray-100 text-center bg-gray-50/50 rounded-b-2xl flex justify-between px-4">
                    <button onclick="markAllAsRead(event)" id="btnMarkAll" class="text-[10px] font-bold text-gray-500 hover:text-black transition uppercase tracking-widest outline-none focus:outline-none"><i class="fas fa-check-double mr-1"></i>Tandai Dibaca</button>
                    <button onclick="toggleNotificationDropdown()" class="text-[10px] font-bold text-[#2D6A4F] hover:text-[#1B4332] transition uppercase tracking-widest outline-none focus:outline-none">Tutup Notifikasi</button>
                </div>
            </div>
        </div>
        @endif

        <div class="w-10 h-10 rounded-full bg-[#2D6A4F] flex items-center justify-center text-white shadow-lg border-2 border-[#D8F3DC]">
            <i class="fas fa-user"></i>
        </div>
    </div>
</header>

<script>
    let isNotifOpen = false;
    const notifDropdown = document.getElementById('notifDropdownMenu');
    
    function toggleNotificationDropdown() {
        if (!notifDropdown) return;
        
        isNotifOpen = !isNotifOpen;
        if(isNotifOpen) {
            notifDropdown.classList.remove('hidden');
            notifDropdown.classList.add('flex');
            setTimeout(() => {
                notifDropdown.classList.remove('scale-95', 'opacity-0');
                notifDropdown.classList.add('scale-100', 'opacity-100');
            }, 10);
        } else {
            notifDropdown.classList.remove('scale-100', 'opacity-100');
            notifDropdown.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                notifDropdown.classList.add('hidden');
                notifDropdown.classList.remove('flex');
            }, 200);
        }
    }

    function markAllAsRead(e) {
        if(e) e.preventDefault();
        const btn = document.getElementById('btnMarkAll');
        const badge = document.querySelector('#notificationCenterDropdown .bg-red-500');
        const notifItems = document.querySelectorAll('#notifDropdownMenu .p-3.mb-2');
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
        
        fetch("{{ url('petani/notifikasi/baca-semua') }}", {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            // Hilangkan badge merah
            if(badge) badge.parentElement.remove();
            
            // Ubah tampilan item notif (hapus bold)
            notifItems.forEach(item => {
                const title = item.querySelector('h4');
                if(title) title.classList.remove('text-black');
            });
            
            btn.innerHTML = '<i class="fas fa-check-double mr-1"></i> Sudah Dibaca';
            btn.classList.add('text-green-600');
            btn.disabled = true;

            // Tutup dropdown setelah jeda singkat
            setTimeout(() => { toggleNotificationDropdown(); }, 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Gagal';
        });
    }

    // Klik di luar dropdown untuk menutup
    document.addEventListener('click', function(event) {
        const center = document.getElementById('notificationCenterDropdown');
        if (isNotifOpen && center && !center.contains(event.target)) {
            toggleNotificationDropdown();
        }
    });
</script>