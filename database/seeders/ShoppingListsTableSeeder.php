<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Recipe;

class ShoppingListsTableSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua user_id yang valid (kecuali admin jika perlu)
        $userIds = User::where('role', 'user')->pluck('id')->toArray();
        
        // Jika ingin termasuk admin, gunakan ini:
        // $userIds = User::pluck('id')->toArray();
        
        // Ambil semua recipe_id yang ada
        $recipeIds = Recipe::pluck('id')->toArray();
        
        if (empty($userIds)) {
            $this->command->error('Tidak ada user ditemukan!');
            return;
        }
        
        if (empty($recipeIds)) {
            $this->command->error('Tidak ada recipe ditemukan!');
            return;
        }
        
        for ($i = 1; $i <= 20; $i++) {
            // Ambil random user_id dari array yang valid
            $randomUserId = $userIds[array_rand($userIds)];
            
            // Ambil random recipe_id dari array yang valid
            $randomRecipeId = $recipeIds[array_rand($recipeIds)];
            
            \App\Models\ShoppingList::create([
                'user_id' => $randomUserId,
                'recipe_id' => $randomRecipeId,
                'nama_list' => 'Belanja Resep ' . $i,
                'shopping_date' => Carbon::now()->addDays($i),
                'status' => $i % 2 == 0 ? 'completed' : 'pending',
                'total_estimated_price' => rand(50000, 200000),
                'total_actual_price' => rand(45000, 195000),
                'catatan' => 'Catatan belanja ' . $i,
                'created_at' => Carbon::now()->subDays($i),
                'updated_at' => Carbon::now(),
            ]);
        }
        
        $this->command->info('20 data shopping lists berhasil dibuat!');
    }
}