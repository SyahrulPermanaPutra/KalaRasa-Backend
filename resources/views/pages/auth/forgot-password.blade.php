<x-layouts::auth>
    <div class="flex flex-col items-center justify-center min-h-100 py-6 px-2 sm:px-0">
        <!-- Logo -->
        <div class="mb-6 flex flex-col items-center">
            <img src="images/logo-jtvhub.png" alt="JTV Hub" class="h-20 w-20 mb-3" />
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-md p-4 sm:p-6 w-full max-w-xs sm:max-w-sm border border-gray-100 dark:border-neutral-800">
            <div class="mb-2 text-center">
                <div class="text-2xl font-bold text-blue-900 dark:text-blue-300 mb-1">Reset Kata Sandi</div>
                <div class="text-gray-500 dark:text-gray-300 text-sm">Masukkan email Anda untuk menerima tautan reset kata sandi.</div>
            </div>
            <!-- Session Status -->
            <x-auth-session-status class="text-center" :status="session('status')" />
            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-2 w-full">
                @csrf
                <label class="font-semibold text-sm mb-1 dark:text-gray-200" for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="Masukkan Email" required autofocus autocomplete="email" class="border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                <button type="submit" class="mt-3 bg-blue-900 text-white rounded-lg py-2 font-semibold hover:bg-blue-800 transition active:scale-95 dark:bg-blue-800 dark:hover:bg-blue-700">Kirim Tautan Reset</button>
            </form>
        </div>
    </div>
</x-layouts::auth>
