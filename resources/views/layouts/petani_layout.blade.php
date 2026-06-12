<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Margo Rahayu II</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Khusus untuk area konten utama agar scrollbar tetap rapi */
        .main-content-scroll::-webkit-scrollbar {
            width: 5px;
        }
        
        /* Hilangkan horizontal scrollbar pada body jika tidak perlu */
        body {
            overflow-x: hidden;
        }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">
    <style>
        /* Custom Intro.js Styling for better integration */
        .introjs-tooltip {
            border-radius: 16px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            font-family: inherit !important;
            padding: 10px !important; /* Add some padding to tooltip body */
        }
        .introjs-tooltip-header {
            padding-right: 30px !important; /* give room for the close button */
        }
        .introjs-tooltip-title {
            font-size: 16px !important;
            font-weight: 700 !important;
            color: #1B4332 !important;
        }
        .introjs-tooltiptext {
            font-size: 13px !important;
            color: #4B5563 !important;
            line-height: 1.6 !important;
            padding-top: 10px !important;
        }
        .introjs-button {
            border-radius: 8px !important;
            font-weight: 600 !important;
            text-shadow: none !important;
            box-shadow: none !important;
        }
        .introjs-nextbutton, .introjs-prevbutton, .introjs-donebutton {
            padding: 8px 16px !important;
        }
        .introjs-nextbutton, .introjs-donebutton {
            background-color: #2D6A4F !important;
            color: white !important;
            border: none !important;
        }
        .introjs-prevbutton {
            color: #4B5563 !important;
            background-color: #F3F4F6 !important;
            border: 1px solid #D1D5DB !important;
        }
        .introjs-skipbutton {
            position: absolute !important;
            top: 10px !important;
            right: 10px !important;
            color: #9CA3AF !important;
            font-size: 20px !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
        }
        .introjs-skipbutton:hover {
            color: #EF4444 !important;
        }
    </style>
</head>
<body class="bg-[#F0F7F2] font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        
        {{-- Sidebar di samping kiri --}}
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            
            {{-- Header (Di sinilah letak status verifikasi di pojok kanan) --}}
            @include('layouts.partials.header')

            {{-- Toast Overlay for Latest Notification --}}
            @php
                $latestUnread = Auth::check() ? Auth::user()->unreadNotifications()->latest()->first() : null;
                // Hanya tampilkan toast ini sekali per ID notifikasi menggunakan LocalStorage
            @endphp
            @if($latestUnread)
            <div id="toastNotificationOverlay" class="absolute top-[80px] right-8 z-[100] transform transition-all duration-500 translate-x-full opacity-0 max-w-sm w-full bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.3)] border-l-4 {{ $latestUnread->data['tipe'] == 'success' ? 'border-green-500' : ($latestUnread->data['tipe'] == 'warning' ? 'border-orange-500' : 'border-blue-500') }} overflow-hidden">
                <button onclick="closeToastNotif()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
                <div class="p-4 flex items-start gap-4">
                    <div class="mt-1">
                        @if($latestUnread->data['tipe'] == 'success')
                            <i class="fas fa-check-circle text-green-500 text-2xl animate-bounce"></i>
                        @elseif($latestUnread->data['tipe'] == 'warning')
                            <i class="fas fa-exclamation-triangle text-orange-500 text-2xl animate-pulse"></i>
                        @elseif($latestUnread->data['tipe'] == 'bibit')
                            <i class="fas fa-seedling text-indigo-500 text-2xl animate-pulse"></i>
                        @else
                            <i class="fas fa-bell text-blue-500 text-2xl animate-pulse"></i>
                        @endif
                    </div>
                    <div class="flex-1 pr-4">
                        <h3 class="text-sm font-bold text-gray-800">{{ $latestUnread->data['judul'] ?? 'Pemberitahuan Baru!' }}</h3>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ $latestUnread->data['pesan'] ?? 'Anda memiliki notifikasi baru yang belum dibaca.' }}</p>
                        <a href="{{ route('notifikasi.baca', $latestUnread->id) }}" onclick="closeToastNotif()" class="text-[10px] uppercase tracking-widest font-bold text-[#2D6A4F] mt-2 inline-block hover:underline">Lihat Lanjut &rarr;</a>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const notifId = "{{ $latestUnread->id }}";
                    const shown = localStorage.getItem('toast_shown_' + notifId);
                    
                    if (!shown) {
                        const toast = document.getElementById('toastNotificationOverlay');
                        setTimeout(() => {
                            toast.classList.remove('translate-x-full', 'opacity-0');
                            // Suara notifikasi kecil opsional
                            try {
                                let audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                                audio.volume = 0.5;
                                audio.play();
                            } catch(e) {}
                        }, 500);

                        // Auto close
                        setTimeout(() => {
                            closeToastNotif();
                        }, 8000);

                        localStorage.setItem('toast_shown_' + notifId, 'true');
                    }
                });

                function closeToastNotif() {
                    const toast = document.getElementById('toastNotificationOverlay');
                    if(toast) {
                        toast.classList.add('translate-x-full', 'opacity-0');
                        setTimeout(() => { toast.remove(); }, 500);
                    }
                }
            </script>
            @endif

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
                            popup: 'rounded-3xl border-2 border-green-100 shadow-2xl'
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
                            popup: 'rounded-3xl border-2 border-red-100 shadow-2xl'
                        }
                    });
                @endif
            </script>

            <main class="flex-1 overflow-y-auto p-6 md:p-8 fade-in">
                {{-- Konten dari dashboard atau profil akan muncul di sini --}}
                @yield('content')
            </main>

        </div>
    </div>

    {{-- Script tambahan jika diperlukan --}}
    @stack('scripts')

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
    </script>

    <!-- Script JavaScript untuk memanipulasi aksi UI Interaktif -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            const overlay = document.getElementById('mobileSidebarOverlay');
            
            // Toggle translate class
            sidebar.classList.toggle('-translate-x-full');
            
            // Toggle overlay
            if (sidebar.classList.contains('-translate-x-full')) {
                overlay.classList.add('hidden');
            } else {
                overlay.classList.remove('hidden');
            }
        }

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
                borderRadius: '1.25rem',
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

    <!-- Intro.js Logic -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>
    @if(isset($petani))
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Check if user has already completed the onboarding
            if (!localStorage.getItem('onboarding_completed_{{ $petani->id }}')) {
                const intro = introJs();
                intro.setOptions({
                    steps: [
                        {
                            title: '🌟 Selamat Datang!',
                            intro: 'Pendaftaran Anda berhasil! Mari ikuti panduan singkat ini untuk melihat apa yang harus Anda lakukan di dalam aplikasi.',
                        },
                        {
                            element: document.querySelector('#notificationCenterDropdown'),
                            title: '🔔 Pusat Notifikasi',
                            intro: 'Ini adalah pusat informasi Anda. Kami akan mengirimkan pengumuman penting (seperti status verifikasi) melalui fitur ini.',
                            position: 'left'
                        },
                        {
                            element: document.querySelector('#sidebar-profil'),
                            title: '👤 Lengkapi Profil Anda',
                            intro: 'Sembari menunggu Admin memverifikasi, langkah PERTAMA yang wajib Anda lakukan adalah melengkapi data diri Anda secara mendetail di sini.',
                            position: 'right'
                        },
                        {
                            element: document.querySelector('#sidebar-lahan'),
                            title: '🌍 Tambahkan Data Lahan',
                            intro: 'Langkah KEDUA, daftarkan lahan pertanian yang Anda miliki. Ini adalah syarat wajib sebelum Anda bisa memesan bibit.',
                            position: 'right'
                        },
                        {
                            title: '🚀 Anda Siap!',
                            intro: 'Pantau terus status verifikasi Anda di Pojok Kanan Atas. Jika sudah Terverifikasi, Anda bebas berbelanja bibit unggul kami!'
                        }
                    ],
                    nextLabel: 'Lanjut',
                    prevLabel: 'Kembali',
                    skipLabel: '×',
                    doneLabel: 'Selesai',
                    showProgress: true,
                    showBullets: false,
                    exitOnOverlayClick: false, // Force them to read or hit skip
                });

                // When tour is complete or exited
                intro.oncomplete(function() {
                    localStorage.setItem('onboarding_completed_{{ $petani->id }}', 'true');
                });
                intro.onexit(function() {
                    localStorage.setItem('onboarding_completed_{{ $petani->id }}', 'true');
                });

                // Start the tour
                setTimeout(() => {
                    intro.start();
                }, 500); // slight delay to let the page render properly
            }
        });
    </script>
    @endif
</body>
</html>