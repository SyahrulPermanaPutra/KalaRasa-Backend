
<x-layouts::auth>
    <div class="flex flex-col items-center justify-center min-h-100 py-6 px-2 sm:px-0">
        <!-- Logo -->
        <div class="mb-4 flex flex-col items-center">
            <img src="images/logo-jtvhub.png" alt="JTV Hub" class="h-16 w-16 mb-2" />
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-md px-4 py-4 sm:px-6 sm:py-5 w-full max-w-xs sm:max-w-md border border-gray-100 dark:border-neutral-800 flex flex-col items-center">
                        @if ($errors->any())
                            <div class="mb-2 w-full">
                                <ul class="bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 rounded-lg px-3 py-2 text-sm text-red-700 dark:text-red-200">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
            <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-2 w-full">
                @csrf
                <label class="font-semibold text-sm mb-1" for="name">Nama Lengkap</label>
                <input id="name" name="name" type="text" placeholder="Masukkan Nama Lengkap" value="{{ old('name') }}" required autofocus autocomplete="name" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />

                <label class="font-semibold text-sm mb-1 mt-1" for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="Masukkan Email" value="{{ old('email') }}" required autocomplete="email" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                <div id="emailError" class="text-red-500 text-xs mb-1 hidden">Format email tidak valid.</div>

                <label class="font-semibold text-sm mb-1 mt-1" for="password">Kata Sandi</label>
                <input id="password" name="password" type="password" placeholder="Buat Kata Sandi" required autocomplete="new-password" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                <div id="passwordInfo" class="text-xs text-gray-500 mb-1">
                    Minimal 8 karakter, 1 huruf kecil, 1 huruf kapital, dan 1 simbol.
                </div>
                <div id="passwordStrength" class="text-xs font-semibold mb-1"></div>

                <label class="font-semibold text-sm mb-1 mt-1" for="password_confirmation">Konfirmasi Kata Sandi</label>
                <div class="relative flex items-center">
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi Kata Sandi" required autocomplete="new-password" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 w-full text-base focus:outline-none focus:ring-2 focus:ring-blue-200 pr-10" />
                    <button type="button" id="togglePasswordConfirm" tabindex="-1" class="absolute right-3 cursor-pointer text-gray-400 bg-transparent border-0 p-0 focus:outline-none" aria-label="Lihat Sandi">
                        <svg id="eyeIconConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                </div>

                <label class="font-semibold text-sm mb-1 mt-1" for="phone">No Telp</label>
                <div class="flex items-center">
                    <span class="px-3 py-2 bg-gray-100 dark:bg-neutral-700 rounded-l-lg text-base font-semibold text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-neutral-700 border-r-0 select-none">+62</span>
                    <input id="phone" name="phone" type="text" inputmode="numeric" pattern="[0-9]*" placeholder="Masukkan No Telp (812...)" value="{{ old('phone') ? ltrim(old('phone'), '0') : '' }}" required class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-r-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200 w-full" style="border-left: none;" />
                </div>
                <div id="phoneError" class="text-red-500 text-xs mb-1 hidden">Nomor telepon hanya boleh angka.</div>

                <label class="font-semibold text-sm mb-1 mt-1">Jenis Kelamin</label>
                <div class="flex gap-4 mb-1 flex-wrap">
                    <label class="inline-flex items-center">
                        <input type="radio" class="form-radio accent-blue-900" name="gender" value="wanita" {{ old('gender') == 'wanita' ? 'checked' : '' }} required>
                        <span class="ml-2">Wanita</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" class="form-radio accent-blue-900" name="gender" value="pria" {{ old('gender') == 'pria' ? 'checked' : '' }} required>
                        <span class="ml-2">Pria</span>
                    </label>
                </div>

                <label class="font-semibold text-sm mb-1 mt-1" for="birthdate">Tanggal Lahir</label>
                <div class="relative flex items-center">
                    <input id="birthdate" name="birthdate" type="date" placeholder="dd/mm/yyyy" value="{{ old('birthdate') }}" required class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 w-full text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                </div>

                <button type="submit" class="mt-3 bg-blue-900 text-white rounded-lg py-2 font-semibold hover:bg-blue-800 transition active:scale-95">Daftar</button>
            </form>

            <div class="text-center mt-3 text-sm">
                <a href="{{ route('password.request') }}" class="text-orange-500 font-semibold hover:underline">Lupa Kata Sandi Akun JTVHub</a>
            </div>
        </div>
    </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show/hide password confirmation
            const passwordConfirmInput = document.getElementById('password_confirmation');
            const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
            const eyeIconConfirm = document.getElementById('eyeIconConfirm');
            if (togglePasswordConfirm && passwordConfirmInput) {
                togglePasswordConfirm.addEventListener('click', function() {
                    const type = passwordConfirmInput.type === 'password' ? 'text' : 'password';
                    passwordConfirmInput.type = type;
                    if(type === 'text') {
                        eyeIconConfirm.innerHTML = `<path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.98 8.223A10.477 10.477 0 002.25 12s3.75 6.75 9.75 6.75c1.772 0 3.432-.37 4.98-1.027M21.75 12c-.443-.86-1.05-1.747-1.82-2.577m-2.12-2.12A10.45 10.45 0 0012 5.25c-2.376 0-4.548.78-6.23 2.097m12.36 0A10.477 10.477 0 0121.75 12m-1.82 2.577c-.87.93-2.01 1.923-3.36 2.697m-2.12 1.027A10.45 10.45 0 0112 18.75c-2.376 0-4.548-.78-6.23-2.097m12.36 0A10.477 10.477 0 0021.75 12m-1.82-2.577c-.87-.93-2.01-1.923-3.36-2.697m-2.12-1.027A10.45 10.45 0 0012 5.25c-2.376 0-4.548.78-6.23 2.097\" /><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\" />`;
                    } else {
                        eyeIconConfirm.innerHTML = `<path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z\" /><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z\" />`;
                    }
                });
            }

            // Email validation
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            emailInput.addEventListener('input', function() {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailInput.value)) {
                    emailError.classList.remove('hidden');
                } else {
                    emailError.classList.add('hidden');
                }
            });

            // Phone validation (numbers only)
            const phoneInput = document.getElementById('phone');
            const phoneError = document.getElementById('phoneError');
            phoneInput.addEventListener('input', function(e) {
                // Remove all non-digit characters
                let cleaned = phoneInput.value.replace(/\D/g, '');
                // Prevent leading 0
                if (cleaned.startsWith('0')) {
                    cleaned = cleaned.replace(/^0+/, '');
                }
                phoneInput.value = cleaned;
                phoneError.classList.add('hidden');
            });
            // Prevent typing 0 as first character
            phoneInput.addEventListener('keypress', function(e) {
                if (e.key.length === 1 && !/\d/.test(e.key)) {
                    e.preventDefault();
                }
                // Prevent 0 as first character
                if (e.key === '0' && phoneInput.selectionStart === 0) {
                    e.preventDefault();
                }
            });
            // Tambahkan 0 di depan sebelum submit
            const form = phoneInput.closest('form');
            form.addEventListener('submit', function(e) {
                if (phoneInput.value && !phoneInput.value.startsWith('0')) {
                    phoneInput.value = '0' + phoneInput.value;
                }
            });

            // Password strength checker
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('passwordStrength');
            passwordInput.addEventListener('input', function() {
                const value = passwordInput.value;
                let score = 0;
                let info = [];
                if (value.length >= 8) score++;
                else info.push('Minimal 8 karakter');
                if (/[a-z]/.test(value)) score++;
                else info.push('1 Huruf kecil');
                if (/[A-Z]/.test(value)) score++;
                else info.push('1 Huruf kapital');
                if (/[^A-Za-z0-9]/.test(value)) score++;
                else info.push('1 Simbol');
                let status = '';
                if (score <= 1) status = '<span class="text-red-500">Lemah</span>';
                else if (score === 2 || score === 3) status = '<span class="text-yellow-500">Sedang</span>';
                else if (score === 4) status = '<span class="text-green-600">Kuat</span>';
                passwordStrength.innerHTML = 'Status: ' + status + (info.length ? ' <span class="text-gray-400">(' + info.join(', ') + ')</span>' : '');
            });
        });
        </script>
</x-layouts::auth>
