<x-layouts::auth>
    <div class="flex flex-col items-center justify-center min-h-100 py-6 px-2 sm:px-0">
        <!-- Logo -->
        <div class="mb-4 flex flex-col items-center">
            <img src="images/logo-jtvhub.png" alt="JTV Hub" class="h-16 w-16 mb-2" />
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-md px-4 py-4 sm:px-6 sm:py-5 w-full max-w-xs sm:max-w-md border border-gray-100 flex flex-col items-center">
            <div class="mb-2 text-center">
                <div class="text-2xl font-bold text-blue-900 mb-1">Reset Kata Sandi</div>
                <div class="text-gray-500 text-sm">Masukkan email Anda untuk menerima tautan reset kata sandi.</div>
            </div>
            <!-- Session Status -->
            <x-auth-session-status class="text-center" :status="session('status')" />
            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-2 w-full">
                @csrf
                <label class="font-semibold text-sm mb-1" for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="Masukkan Email" required autofocus autocomplete="email" class="border border-gray-300 rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-200" />
                <button type="submit" class="mt-3 bg-blue-900 text-white rounded-lg py-2 font-semibold hover:bg-blue-800 transition active:scale-95">Kirim Tautan Reset</button>
            </form>
        </div>
    </div>
</x-layouts::auth>
