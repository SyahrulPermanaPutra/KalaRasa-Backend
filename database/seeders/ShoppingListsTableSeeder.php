<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ShoppingListsTableSeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 20; $i++) {
            \App\Models\ShoppingList::create([
                'user_id' => ($i % 20) + 2,
                'recipe_id' => ($i % 20) + 1,
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
    }
}