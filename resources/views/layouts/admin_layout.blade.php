<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Margo Rahayu II</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- SweetAlert2 untuk Notifikasi Estetik -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        /* Animasi halus untuk transisi halaman */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* CUSTOM SCROLLBAR GLOBAL - Agar tidak "jelek" dan merusak tampilan */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; /* slate-300 */
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; /* slate-400 */
        }
        
        /* Hilangkan horizontal scrollbar pada body jika tidak perlu */
        body {
            overflow-x: hidden;
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
    </style>
</head>
<body class="bg-[#F0F7F2] font-sans antialiased text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        @include('layouts.partials.sidebar_admin')

        <div class="flex-1 flex flex-col overflow-hidden">
            
            <header class="bg-white shadow-sm py-4 px-4 md:px-8 flex justify-between items-center border-b border-green-100 z-30">
                <div class="flex items-center gap-3">
                    <!-- Hamburger Khusus Mobile -->
                    <button onclick="toggleAdminSidebar()" class="md:hidden text-[#1B4332] p-2 focus:outline-none hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-lg md:text-xl font-bold text-[#1B4332] uppercase tracking-wider truncate max-w-[170px] md:max-w-none">@yield('title')</h1>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-gray-500 uppercase italic leading-none">Ketua Kelompok</p>
                    </div>

                    {{-- NOTIFICATION CENTER ADMIN --}}
                    @php
                        $adminNotifications = Auth::check() ? Auth::user()->notifications()->latest()->take(10)->get() : collect();
                        $adminUnreadCount = Auth::check() ? Auth::user()->unreadNotifications->count() : 0;
                    @endphp
                    <div class="relative items-center flex" id="adminNotifDropdownWrapper">
                        <button onclick="toggleAdminNotif()" class="relative p-2 text-gray-500 hover:text-[#2D6A4F] focus:outline-none transition group">
                            <i class="fas fa-bell text-xl group-hover:scale-110 transition-transform"></i>
                            @if($adminUnreadCount > 0)
                            <span class="absolute top-1 right-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white"></span>
                            </span>
                            @endif
                        </button>

                        <div id="adminNotifMenu" class="absolute right-0 top-full mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 hidden flex-col z-50 transform origin-top-right transition-all duration-200 scale-95 opacity-0">
                            <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl flex justify-between items-center">
                                <h3 class="font-bold text-[#1B4332] text-sm"><i class="fas fa-inbox mr-2"></i> Laporan Admin</h3>
                                <span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold">Terbaru</span>
                            </div>
                            
                            <div class="max-h-80 overflow-y-auto custom-scrollbar p-2">
                                @forelse($adminNotifications as $notif)
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
                                    <div class="p-3 mb-2 rounded-xl border transition cursor-pointer {{ $bgClass }} group/item"
                                         onclick="window.location.href='{{ route('notifikasi.baca', $notif->id) }}'">
                                        <div class="flex gap-3">
                                            <div class="mt-0.5"><i class="fas {{ $iconClass }}"></i></div>
                                            <div>
                                                <h4 class="text-xs font-bold text-gray-800 mb-1 {{ empty($notif->read_at) ? 'text-black' : '' }} tracking-tight">{{ $notif->data['judul'] ?? 'Pemberitahuan' }}</h4>
                                                <p class="text-[11px] text-gray-600 leading-relaxed">{{ $notif->data['pesan'] ?? '' }}</p>
                                                <div class="flex justify-between items-center mt-2">
                                                    <p class="text-[9px] text-gray-400"><i class="far fa-clock mr-1"></i>{{ $notif->created_at->diffForHumans() }}</p>
                                                    @if($notif->data['url'] ?? false)
                                                        <span class="text-[9px] font-bold text-[#2D6A4F] opacity-0 group-hover/item:opacity-100 transition-opacity">Cek Detail &rarr;</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-gray-500 text-xs">
                                        <i class="fas fa-box-open text-2xl text-gray-300 block mb-2"></i> Belum ada laporan baru.
                                    </div>
                                @endforelse
                            </div>
                            
                            <div class="p-3 border-t border-gray-100 bg-gray-50 flex justify-between items-center px-4 rounded-b-2xl">
                                <button onclick="markAdminAllAsRead(event)" id="btnAdminMarkAll" class="text-[10px] font-bold text-gray-500 hover:text-black transition uppercase tracking-widest btn-notif-action focus:outline-none">
                                    <i class="fas fa-check-double mr-1"></i> Tandai Dibaca
                                </button>
                                <button onclick="toggleAdminNotif()" class="text-[10px] font-bold text-[#2D6A4F] hover:text-[#1B4332] transition uppercase tracking-widest btn-notif-action focus:outline-none">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="w-10 h-10 rounded-full bg-[#2D6A4F] flex items-center justify-center text-white shadow-md">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 bg-[#F8FBF9]">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Script Notifikasi Flash SweetAlert2 -->
    <script>
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                showConfirmButton: false,
                timer: 2500,
                background: '#fff',
                color: '#1B4332',
                iconColor: '#2D6A4F',
                customClass: {
                    popup: 'rounded-2xl border-2 border-green-100 shadow-2xl'
                }
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Perhatian!',
                text: "{{ session('error') }}",
                confirmButtonColor: '#2D6A4F',
                background: '#fff',
                color: '#852121',
                customClass: {
                    popup: 'rounded-2xl border-2 border-red-100 shadow-2xl'
                }
            });
        @endif
    </script>

    <!-- Global Image Modal Overlay -->
    <div id="globalImageModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black bg-opacity-80 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeGlobalImageModal()">
        <span class="absolute top-4 right-6 text-white text-4xl font-bold cursor-pointer hover:text-gray-300" onclick="closeGlobalImageModal()">&times;</span>
        <img id="globalImageModalImg" class="max-w-[90%] max-h-[90%] object-contain rounded-lg shadow-2xl scale-95 transition-transform duration-300">
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const zoomableImages = document.querySelectorAll(".zoomable-image");
            zoomableImages.forEach(img => {
                img.addEventListener("click", function(e) {
                    e.stopPropagation();
                    const modal = document.getElementById('globalImageModal');
                    const modalImg = document.getElementById('globalImageModalImg');
                    modalImg.src = this.src;
                    
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    
                    // Trigger reflow to apply transition
                    setTimeout(() => {
                        modal.classList.remove('opacity-0');
                        modalImg.classList.remove('scale-95');
                        modalImg.classList.add('scale-100');
                    }, 50);
                });
            });
        });

        function closeGlobalImageModal() {
            const modal = document.getElementById('globalImageModal');
            const modalImg = document.getElementById('globalImageModalImg');
            
            modal.classList.add('opacity-0');
            modalImg.classList.remove('scale-100');
            modalImg.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                modalImg.src = '';
            }, 300); // Wait for transition to end
        }

        function toggleAdminSidebar() {
            const sidebar = document.getElementById('adminSidebarMenu');
            const overlay = document.getElementById('mobileAdminOverlay');
            
            sidebar.classList.toggle('-translate-x-full');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                overlay.classList.add('hidden');
            } else {
                overlay.classList.remove('hidden');
            }
        }

        let isAdminNotifOpen = false;
        const adminNotifMenu = document.getElementById('adminNotifMenu');
        
        function toggleAdminNotif() {
            if (!adminNotifMenu) return;
            
            isAdminNotifOpen = !isAdminNotifOpen;
            if(isAdminNotifOpen) {
                adminNotifMenu.classList.remove('hidden');
                adminNotifMenu.classList.add('flex');
                setTimeout(() => {
                    adminNotifMenu.classList.remove('scale-95', 'opacity-0');
                    adminNotifMenu.classList.add('scale-100', 'opacity-100');
                }, 10);
            } else {
                adminNotifMenu.classList.remove('scale-100', 'opacity-100');
                adminNotifMenu.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    adminNotifMenu.classList.add('hidden');
                    adminNotifMenu.classList.remove('flex');
                }, 200);
            }
        }

        function markAdminAllAsRead(e) {
            if(e) e.preventDefault();
            const btn = document.getElementById('btnAdminMarkAll');
            const badge = document.querySelector('#adminNotifDropdownWrapper .bg-red-500');
            const notifItems = document.querySelectorAll('#adminNotifMenu .p-3.mb-2');
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Pelan...';
            
            fetch("{{ url('admin/notifikasi/baca-semua') }}", {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if(badge) badge.parentElement.remove();
                notifItems.forEach(item => {
                    const title = item.querySelector('h4');
                    if(title) title.classList.remove('text-black');
                });
                btn.innerHTML = '<i class="fas fa-check-double mr-1"></i> Selesai';
                btn.classList.add('text-green-600');
                btn.disabled = true;
                setTimeout(() => { toggleAdminNotif(); }, 1000);
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = 'Gagal';
            });
        }

        // Klik di luar dropdown untuk menutup
        document.addEventListener('click', function(event) {
            const wrapper = document.getElementById('adminNotifDropdownWrapper');
            if (isAdminNotifOpen && wrapper && !wrapper.contains(event.target)) {
                toggleAdminNotif();
            }
        });

        // Global SweetAlert Confirm Handler
        function confirmAction(button, message, type = 'question') {
            const form = button.closest('form');
            Swal.fire({
                title: 'Konfirmasi',
                text: message,
                icon: type,
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal',
                background: '#fff',
                customClass: {
                    popup: 'rounded-3xl shadow-2xl border border-gray-100',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold uppercase tracking-wider text-xs',
                    cancelButton: 'rounded-xl px-6 py-2.5 font-bold uppercase tracking-wider text-xs'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>