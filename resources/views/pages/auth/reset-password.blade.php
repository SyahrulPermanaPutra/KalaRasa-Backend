<x-layouts::auth>
    <div class="flex flex-col gap-6 items-center">
        <div class="w-full max-w-xs sm:max-w-md">
            <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-md px-4 py-4 sm:px-6 sm:py-5 border border-gray-100 dark:border-neutral-800 flex flex-col items-center">
                <div class="mb-2 text-center">
                    <div class="text-2xl font-bold text-blue-900 mb-1 dark:text-blue-200">Reset Kata Sandi</div>
                    <div class="text-gray-500 text-sm dark:text-gray-300">Silakan masukkan kata sandi baru Anda di bawah ini.</div>
                </div>
                <!-- Session Status -->
                <x-auth-session-status class="text-center" :status="session('status')" />
                <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-2 w-full">
                    @csrf
                    <!-- Token -->
                    <input type="hidden" name="token" value="{{ request()->route('token') }}">
                    <label class="font-semibold text-sm mb-1 dark:text-gray-100" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ request('email') }}" placeholder="Masukkan Email" required autocomplete="email" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                    <label class="font-semibold text-sm mb-1 mt-2 dark:text-gray-100" for="password">Kata Sandi Baru</label>
                    <input id="password" name="password" type="password" placeholder="Kata Sandi Baru" required autocomplete="new-password" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                    <label class="font-semibold text-sm mb-1 mt-2 dark:text-gray-100" for="password_confirmation">Konfirmasi Kata Sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Konfirmasi Kata Sandi" required autocomplete="new-password" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                    <button type="submit" class="mt-3 bg-blue-900 text-white rounded-lg py-2 font-semibold hover:bg-blue-800 transition active:scale-95">Reset Kata Sandi</button>
                </form>
                <div class="text-center mt-3 text-sm">
                    <a href="{{ route('login') }}" class="text-blue-900 dark:text-blue-200 font-semibold hover:underline">Kembali ke Login</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts::auth>
