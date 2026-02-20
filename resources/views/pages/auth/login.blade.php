
<x-layouts::auth>
    <div class="flex flex-col items-center justify-center min-h-100 py-6 px-2 sm:px-0">
        <!-- Logo -->
        <div class="mb-6 flex flex-col items-center">
            <img src="images/logo-jtvhub.png" alt="JTV Hub" class="h-20 w-20 mb-3" />
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-md p-4 sm:p-6 w-full max-w-xs sm:max-w-sm border border-gray-100">
            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-3">
                @csrf
                <label class="font-semibold text-sm mb-1" for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="Masukkan Email" value="{{ old('email') }}" required autofocus autocomplete="email" class="border border-gray-300 rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />

                <label class="font-semibold text-sm mb-1 mt-1" for="password">Kata Sandi</label>
                <div class="relative flex items-center">
                    <input id="password" name="password" type="password" placeholder="Masukkan Kata Sandi" required autocomplete="current-password" class="border border-gray-300 rounded-lg px-3 py-2 w-full text-base focus:outline-none focus:ring-2 focus:ring-blue-200 pr-10" />
                    <button type="button" id="togglePassword" tabindex="-1" class="absolute right-3 cursor-pointer text-gray-400 bg-transparent border-0 p-0 focus:outline-none" aria-label="Lihat Sandi">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                </div>

                @if (Route::has('password.request'))
                    <div class="flex justify-end mt-1">
                        <a href="{{ route('password.request') }}" class="text-xs text-orange-500 hover:underline">Lupa Kata Sandi?</a>
                    </div>
                @endif

                <button type="submit" class="mt-4 bg-blue-900 text-white rounded-lg py-2 font-semibold hover:bg-blue-800 transition active:scale-95">Masuk</button>
            </form>

            <div class="text-center mt-4 text-sm">
                <span class="text-gray-500">Belum Memiliki Akun?</span>
                <a href="{{ route('register') }}" class="text-orange-500 font-semibold hover:underline ml-1">Daftar</a>
            </div>
        </div>
    </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const eyeIcon = document.getElementById('eyeIcon');
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = type;
                    // Ganti icon jika perlu (opsional)
                    if(type === 'text') {
                        eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 002.25 12s3.75 6.75 9.75 6.75c1.772 0 3.432-.37 4.98-1.027M21.75 12c-.443-.86-1.05-1.747-1.82-2.577m-2.12-2.12A10.45 10.45 0 0012 5.25c-2.376 0-4.548.78-6.23 2.097m12.36 0A10.477 10.477 0 0121.75 12m-1.82 2.577c-.87.93-2.01 1.923-3.36 2.697m-2.12 1.027A10.45 10.45 0 0112 18.75c-2.376 0-4.548-.78-6.23-2.097m12.36 0A10.477 10.477 0 0021.75 12m-1.82-2.577c-.87-.93-2.01-1.923-3.36-2.697m-2.12-1.027A10.45 10.45 0 0012 5.25c-2.376 0-4.548.78-6.23 2.097" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />`;
                    } else {
                        eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />`;
                    }
                });
            }
        });
        </script>
</x-layouts::auth>
