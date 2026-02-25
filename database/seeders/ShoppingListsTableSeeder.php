<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Recipe;
use App\Models\Role;

class ShoppingListsTableSeeder extends Seeder
{
    public function run()
    {
        // Dapatkan role_id untuk 'user'
        $userRole = Role::where('name', 'user')->first();
        
        if (!$userRole) {
            $this->command->error('Role "user" tidak ditemukan! Jalankan RoleSeeder terlebih dahulu.');
            return;
        }
        
        // Ambil semua user_id dengan role 'user' (bukan admin)
        $userIds = User::where('role_id', $userRole->id)->pluck('id')->toArray();
        
        // Jika ingin include admin juga, gunakan ini:
        // $userIds = User::pluck('id')->toArray();
        
        // Ambil semua recipe_id yang ada
        $recipeIds = Recipe::pluck('id')->toArray();
        
        if (empty($userIds)) {
            $this->command->error('Tidak ada user dengan role "user" ditemukan!');
            $this->command->info('Mencoba mengambil semua user...');
            
            // Fallback: ambil semua user
            $userIds = User::pluck('id')->toArray();
            
            if (empty($userIds)) {
                $this->command->error('Tidak ada user sama sekali ditemukan! Jalankan UsersTableSeeder terlebih dahulu.');
                return;
            }
            
            $this->command->info('Menggunakan ' . count($userIds) . ' user yang tersedia.');
        }
        
        if (empty($recipeIds)) {
            $this->command->error('Tidak ada recipe ditemukan! Jalankan RecipesTableSeeder terlebih dahulu.');
            return;
        }
        
        $shoppingLists = [];
        
        for ($i = 1; $i <= 20; $i++) {
            // Ambil random user_id dari array yang valid
            $randomUserId = $userIds[array_rand($userIds)];
            
            // Ambil random recipe_id dari array yang valid
            $randomRecipeId = $recipeIds[array_rand($recipeIds)];
            
            // Tentukan status random
            $status = ['pending', 'completed'][rand(0, 1)];
            
            // Hitung total harga random
            $totalEstimated = rand(50000, 200000);
            $totalActual = $status === 'completed' 
                ? $totalEstimated - rand(0, 15000)  // Jika completed, actual bisa lebih murah
                : 0;  // Jika pending, actual = 0 (belum dibayar)
            
            $shoppingLists[] = [
                'user_id' => $randomUserId,
                'recipe_id' => $randomRecipeId,
                'nama_list' => 'Belanja Resep ' . $i . ' - ' . Carbon::now()->format('d M'),
                'shopping_date' => Carbon::now()->addDays($i),
                'status' => $status,
                'total_estimated_price' => $totalEstimated,
                'total_actual_price' => $totalActual,
                'catatan' => $this->getRandomCatatan($i),
                'created_at' => Carbon::now()->subDays(20 - $i),
                'updated_at' => Carbon::now()->subDays(rand(0, 5)),
            ];
        }
        
        // Insert data
        \App\Models\ShoppingList::insert($shoppingLists);
        
        $this->command->info('20 data shopping lists berhasil dibuat!');
        
        // Tampilkan statistik
        $this->command->table(
            ['Total', 'Pending', 'Completed', 'User Terlibat', 'Recipe Terlibat'],
            [[
                20,
                \App\Models\ShoppingList::where('status', 'pending')->count(),
                \App\Models\ShoppingList::where('status', 'completed')->count(),
                count(array_unique(array_column($shoppingLists, 'user_id'))),
                count(array_unique(array_column($shoppingLists, 'recipe_id')))
            ]]
        );
    }
    
    /**
     * Generate random catatan yang bervariasi
     */
    private function getRandomCatatan($index)
    {
        $catatans = [
            'Jangan lupa beli yang segar',
            'Cek harga di pasar tradisional dulu',
            'Beli di supermarket dekat rumah',
            'Gunakan voucher diskon',
            'Prioritas bahan utama',
            'Boleh ganti merk sesuai selera',
            'Cek tanggal kadaluarsa',
            'Beli dalam jumlah besar untuk stok',
            'Hindari jam sibuk',
            'Bawa tas belanja sendiri',
            'Jangan lupa beli bumbu tambahan',
            'Cek ketersediaan di toko langganan',
        ];
        
        return 'Catatan #' . $index . ': ' . $catatans[array_rand($catatans)];
    }
}