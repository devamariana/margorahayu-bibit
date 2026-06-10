<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Margo Rahayu II - Solusi Kebutuhan Bibit Petani</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #1a5d1a;
            --secondary: #fbffdc;
            --accent: #f8de22;
            --dark: #12372a;
            --light: #fbfada;
            --text-dark: #1c1c1c;
            --text-muted: #4b5563; /* Dipergelap untuk aksesibilitas kontras */
            --glass: rgba(255, 255, 255, 0.8);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
            background-color: #fafdfb;
        }

        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
        }

        /* --- Header --- */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8%;
            background: var(--glass);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        header.scrolled {
            height: 70px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 28px;
        }

        nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        nav a:hover {
            color: var(--primary);
        }

        .btn-login {
            padding: 10px 25px;
            border-radius: 50px;
            background: var(--primary);
            color: white !important;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(26, 93, 26, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 93, 26, 0.3);
        }

        /* --- Hero Section --- */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 8%;
            background: linear-gradient(rgba(255,255,255,0.7), rgba(255,255,255,0.2)), 
                        url('{{ asset('bibit_hero_background_1780829827545.png') }}');
            background-size: cover;
            background-position: center;
            padding-top: 80px;
        }

        .hero-content {
            max-width: 600px;
            animation: fadeInUp 1s ease;
        }

        .hero-content h1 {
            font-size: 56px;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .hero-content p {
            font-size: 18px;
            line-height: 1.6;
            color: #1a1d1a; /* Hitam pekat agar jelas */
            font-weight: 600; /* Dibuat lebih tebal */
            margin-bottom: 35px;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8); /* Tambah shadow putih tipis di belakang teks */
        }

        .hero-btns {
            display: flex;
            gap: 20px;
        }

        .btn-primary {
            padding: 15px 35px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-outline {
            padding: 15px 35px;
            border: 2px solid var(--primary);
            color: var(--primary);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(26, 93, 26, 0.2); }
        .btn-outline:hover { background: var(--primary); color: white; transform: translateY(-3px); }

        /* --- Features --- */
        .section {
            padding: 100px 8%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .section-title p {
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--secondary);
            color: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 25px;
        }

        .feature-card h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }

        .feature-card p {
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* --- Footer --- */
        footer {
            background: var(--dark);
            color: white;
            padding: 80px 8% 40px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 50px;
            margin-bottom: 60px;
        }

        .footer-col h4 {
            font-size: 18px;
            margin-bottom: 25px;
        }

        .footer-col p {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 15px;
        }

        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: #888;
            font-size: 14px;
        }

        /* --- Animations --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 991px) {
            .hero-content h1 { font-size: 42px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            header { padding: 0 5%; }
            nav { display: none; }
            .hero { padding: 0 5%; text-align: center; justify-content: center; }
            .hero-btns { justify-content: center; flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header id="main-header">
        <a href="#" class="logo">
            <i class="fa-solid fa-leaf"></i>
            <span>Margo Rahayu II</span>
        </a>
        <nav>
            <a href="#home">Home</a>
            <a href="#about">Tentang</a>
            <a href="#features">Fitur</a>
            <a href="#contact">Kontak</a>
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-login">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-login">Mulai Sekarang</a>
                @endauth
            @endif
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero" id="home">
            <div class="hero-content">
                <h1>Tanam Masa Depan Lebih Hijau & Makmur</h1>
                <p>Platform distribusi bibit terbaik untuk para petani Margo Rahayu II. Dapatkan bibit berkualitas tinggi dengan proses yang mudah, transparan, dan terintegrasi.</p>
                <div class="hero-btns">
                    <a href="{{ route('register') }}" class="btn-primary">Daftar Sekarang <i class="fa-solid fa-arrow-right"></i></a>
                    <a href="#features" class="btn-outline">Lihat Layanan</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="section" id="features">
            <div class="section-title">
                <h2>Layanan Unggulan Kami</h2>
                <p>Kami hadir untuk memudahkan produktivitas petani dengan teknologi digital yang tepat guna.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-seedling"></i></div>
                    <h3>Bibit Berkualitas</h3>
                    <p>Penyediaan berbagai jenis bibit unggul yang telah terverifikasi kualitasnya untuk hasil panen optimal.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <h3>Proses Mudah</h3>
                    <p>Pemesanan bibit dilakukan secara digital, tidak perlu repot dengan administrasi manual yang rumit.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-wallet"></i></div>
                    <h3>Pembayaran Aman</h3>
                    <p>Terintegrasi dengan sistem pembayaran digital (Midtrans) untuk transaksi yang cepat, aman, dan otomatis.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h3>Transparansi Data</h3>
                    <p>Riwayat pesanan dan pembagian jatah bibit dapat dipantau secara langsung kapan pun dan di mana pun.</p>
                </div>
            </div>
        </section>

        <!-- About/Stats Section -->
        <section class="section" id="about" style="background: white;">
            <div class="section-title">
                <h2>Mengapa Memilih Kami?</h2>
                <p>Margo Rahayu II berkomitmen membangun ekosistem pertanian yang modern dan berkelanjutan.</p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 50px; align-items: center;">
                <div style="flex: 1; min-width: 300px;">
                    <img src="https://images.unsplash.com/photo-1592982537447-7440770bbfc9?auto=format&fit=crop&q=80&w=800" alt="Petani" style="width: 100%; border-radius: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                </div>
                <div style="flex: 1.2; min-width: 300px;">
                    <h3 style="font-size: 28px; margin-bottom: 20px; color: var(--primary);">Misi Kami</h3>
                    <p style="margin-bottom: 20px; line-height: 1.8; color: var(--text-muted);">Kami memahami tantangan yang dihadapi petani dalam mendapatkan akses bibit. Melalui platform ini, kami menjembatani kesenjangan tersebut dengan sistem manajemen yang efisien.</p>
                    <ul class="footer-links" style="color: var(--text-dark);">
                        <li style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: var(--primary);"></i> Pemantauan jatah lahan yang akurat.</li>
                        <li style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: var(--primary);"></i> Sistem transfer jatah antar petani.</li>
                        <li style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px;"><i class="fa-solid fa-circle-check" style="color: var(--primary);"></i> Notifikasi kedatangan bibit secara instan.</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="logo" style="color: white; margin-bottom: 25px;">
                    <i class="fa-solid fa-leaf"></i>
                    <span>Margo Rahayu II</span>
                </a>
                <p>Membantu petani Indonesia mencapai kemandirian dan kemakmuran melalui inovasi teknologi di bidang distribusi bibit.</p>
                <div style="display: flex; gap: 15px; font-size: 20px;">
                    <a href="#" style="color: white;"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" style="color: white;"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" style="color: white;"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Menu</h4>
                <ul class="footer-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="#features">Fitur</a></li>
                    <li><a href="{{ route('login') }}">Login Petani</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Bantuan</h4>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Cara Pemesanan</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                </ul>
            </div>
            <div class="footer-col" id="contact">
                <h4>Hubungi Kami</h4>
                <p><i class="fa-brands fa-whatsapp" style="margin-right: 10px; color: #25D366;"></i> +62 822 2815 4201</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Margo Rahayu II. All rights reserved.</p>
        </div>
    </footer>

    <script>
        window.addEventListener('scroll', function() {
            const header = document.getElementById('main-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
