<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi - Margo Rahayu II</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        /* Hilangkan panah up/down di input number */
        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none; margin: 0;
        }
        input[type="number"] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-[#F0F7F2] flex items-center justify-center min-h-screen p-6">

    <div class="w-full max-w-[450px] bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(45,106,79,0.1)] overflow-hidden border border-gray-50">
        <div class="h-2 bg-[#2D6A4F]"></div>
        <div class="p-10">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#D8F3DC] rounded-2xl mb-4 shadow-sm">
                    <i class="fas fa-shield-alt text-[#2D6A4F] text-2xl"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-[#1B4332] tracking-tight uppercase">Verifikasi OTP</h2>
                <p class="text-gray-500 text-sm mt-3 font-medium">Masukkan 6 digit kode unik yang telah dikirim ke WhatsApp Anda.</p>
            </div>

            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-xl text-xs font-semibold text-green-700">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl">
                    <ul class="list-disc list-inside text-[11px] font-bold text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.verify.post') }}" method="POST" class="space-y-6" id="otpForm">
                @csrf
                <div class="space-y-2">
                    <label class="block text-center text-xs font-bold text-gray-700 tracking-wider">KODE OTP</label>
                    
                    <!-- Hidden input to hold the actual value sent to server -->
                    <input type="hidden" name="otp" id="realOtp" required>
                    
                    <div class="flex justify-center gap-2 sm:gap-4 w-full" id="otp-container">
                        <input type="text" maxlength="1" autofocus autocomplete="off"
                            class="otp-box w-10 sm:w-12 h-12 sm:h-14 text-center text-xl sm:text-2xl font-extrabold text-[#1B4332] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#2D6A4F] focus:border-transparent outline-none transition-all duration-200 shadow-sm">
                        <input type="text" maxlength="1" autocomplete="off"
                            class="otp-box w-10 sm:w-12 h-12 sm:h-14 text-center text-xl sm:text-2xl font-extrabold text-[#1B4332] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#2D6A4F] focus:border-transparent outline-none transition-all duration-200 shadow-sm">
                        <input type="text" maxlength="1" autocomplete="off"
                            class="otp-box w-10 sm:w-12 h-12 sm:h-14 text-center text-xl sm:text-2xl font-extrabold text-[#1B4332] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#2D6A4F] focus:border-transparent outline-none transition-all duration-200 shadow-sm">
                        <input type="text" maxlength="1" autocomplete="off"
                            class="otp-box w-10 sm:w-12 h-12 sm:h-14 text-center text-xl sm:text-2xl font-extrabold text-[#1B4332] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#2D6A4F] focus:border-transparent outline-none transition-all duration-200 shadow-sm">
                        <input type="text" maxlength="1" autocomplete="off"
                            class="otp-box w-10 sm:w-12 h-12 sm:h-14 text-center text-xl sm:text-2xl font-extrabold text-[#1B4332] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#2D6A4F] focus:border-transparent outline-none transition-all duration-200 shadow-sm">
                        <input type="text" maxlength="1" autocomplete="off"
                            class="otp-box w-10 sm:w-12 h-12 sm:h-14 text-center text-xl sm:text-2xl font-extrabold text-[#1B4332] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#2D6A4F] focus:border-transparent outline-none transition-all duration-200 shadow-sm">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="button" id="submitBtn" onclick="submitForm()"
                        class="w-full py-4 bg-[#2D6A4F] hover:bg-[#1B4332] text-white font-bold rounded-xl shadow-[0_10px_20px_rgba(45,106,79,0.3)] hover:shadow-none transform hover:translate-y-1 transition duration-300 uppercase tracking-widest text-xs">
                        Verifikasi
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center border-t border-gray-50 pt-6">
                <form action="{{ route('register.resend_otp') }}" method="POST" id="resendForm">
                    @csrf
                    <p class="text-xs text-gray-500 font-medium pb-2">Belum menerima kode?</p>
                    <button type="submit" id="resendBtn" class="text-xs font-bold text-[#2D6A4F] hover:text-[#1B4332] underline-offset-4 hover:underline transition hidden">
                        Kirim Ulang Kode OTP
                    </button>
                    <p id="timerText" class="text-xs font-bold text-gray-400">
                        Kirim ulang dalam <span id="countdown">60</span> detik
                    </p>
                </form>

                <p class="text-xs text-gray-500 font-medium mt-4">
                    Salah memasukkan Nomor WA? <br>
                    <a href="{{ route('register') }}" class="mt-1 inline-block font-bold text-red-500 hover:text-red-700 underline-offset-4 hover:underline transition">
                        Batal & Daftar Ulang
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Script Timer Resend OTP & Logic OTP Boxes -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // TIMER LOGIC
            let timeLeft = 60; // Set 60 Detik
            const timerEl = document.getElementById('countdown');
            const timerText = document.getElementById('timerText');
            const resendBtn = document.getElementById('resendBtn');
            
            const countdownInterval = setInterval(() => {
                timeLeft--;
                if(timerEl) timerEl.innerText = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    if(timerText) timerText.classList.add('hidden');
                    if(resendBtn) resendBtn.classList.remove('hidden');
                }
            }, 1000);

            // OTP BOXES UI LOGIC
            const otpBoxes = document.querySelectorAll('.otp-box');
            
            otpBoxes.forEach((box, index) => {
                // Focus Event
                box.addEventListener('focus', function() {
                    this.select(); // auto highlight bila sudah ada angkanya
                });

                // Tipe angka doang
                box.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');

                    if (this.value !== '') {
                        // Move to next box jika tidak empty
                        if(index < otpBoxes.length - 1) {
                            otpBoxes[index + 1].focus();
                        } else {
                            // kalau ini box terakhir dan terisi
                            this.blur(); 
                        }
                    }
                });

                // Backspace untuk ganti field ke kiri
                box.addEventListener('keydown', function(e) {
                    if(e.key === 'Backspace' && this.value === '') {
                        if(index > 0) {
                            otpBoxes[index - 1].focus();
                        }
                    }
                });

                // Support Paste 6 Angka sekaligus
                box.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const clipText = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                    if(clipText.length > 0) {
                        for(let i = 0; i < otpBoxes.length; i++) {
                            if(i < clipText.length) {
                                otpBoxes[i].value = clipText[i];
                            }
                        }
                        if(clipText.length <= otpBoxes.length) {
                            otpBoxes[clipText.length - 1].focus();
                        } else {
                            otpBoxes[otpBoxes.length - 1].focus();
                        }
                    }
                });
            });
        });

        // Submit form wrapper
        function submitForm() {
            const boxes = document.querySelectorAll('.otp-box');
            let realValue = "";
            boxes.forEach(box => {
                realValue += box.value;
            });
            
            // set value & submit
            document.getElementById('realOtp').value = realValue;
            document.getElementById('otpForm').submit();
        }
    </script>
</body>
</html>
