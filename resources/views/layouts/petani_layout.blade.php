<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - SI Petani</title>
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
    </style>
</head>
<body class="bg-[#F0F7F2] font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        
        {{-- Sidebar di samping kiri --}}
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            
            {{-- Header (Di sinilah letak status verifikasi di pojok kanan) --}}
            @include('layouts.partials.header')

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-8 fade-in">
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
    </script>
</body>
</html>