<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin - Margo Rahayu II</title>
    
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
        
        /* Hilangkan horizontal scrollbar pada body jika tidak perlu */
        body {
            overflow-x: hidden;
        }
    </style>
</head>
<body class="bg-[#F0F7F2] font-sans antialiased text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        @include('layouts.partials.sidebar_superadmin')

        <div class="flex-1 flex flex-col overflow-hidden">
            
            <header class="bg-white shadow-sm py-4 px-4 md:px-8 flex justify-between items-center border-b border-green-100 z-30">
                <div class="flex items-center gap-3">
                    <!-- Hamburger Khusus Mobile -->
                    <button onclick="toggleSuperadminSidebar()" class="md:hidden text-[#1B4332] p-2 focus:outline-none hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-lg md:text-xl font-bold text-[#1B4332] uppercase tracking-wider truncate max-w-[170px] md:max-w-none">@yield('title')</h1>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-500 uppercase italic leading-none">Pusat Superadmin</p>
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

        function toggleSuperadminSidebar() {
            const sidebar = document.getElementById('superadminSidebarMenu');
            const overlay = document.getElementById('mobileSuperadminOverlay');
            
            sidebar.classList.toggle('-translate-x-full');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                overlay.classList.add('hidden');
            } else {
                overlay.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>