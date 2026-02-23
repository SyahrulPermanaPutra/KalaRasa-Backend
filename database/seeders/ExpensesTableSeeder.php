<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;

class ExpensesTableSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua shopping_list_item_id yang ada
        $shoppingListItemIds = ShoppingListItem::pluck('id')->toArray();
        $shoppingListIds = ShoppingList::pluck('id')->toArray();
        $userIds = User::where('role', 'user')->pluck('id')->toArray();
        
        if (empty($shoppingListItemIds)) {
            $this->command->error('Tidak ada shopping list items ditemukan! Jalankan ShoppingListItemsTableSeeder dulu.');
            return;
        }
        
        if (empty($shoppingListIds)) {
            $this->command->error('Tidak ada shopping lists ditemukan!');
            return;
        }
        
        if (empty($userIds)) {
            $this->command->error('Tidak ada user ditemukan!');
            return;
        }
        
        for ($i = 1; $i <= 20; $i++) {
            \App\Models\Expense::create([
                'user_id' => $userIds[array_rand($userIds)],
                'shopping_list_id' => $shoppingListIds[array_rand($shoppingListIds)],
                'shopping_list_item_id' => $shoppingListItemIds[array_rand($shoppingListItemIds)],
                'actual_price' => rand(10000, 100000),
                'purchase_date' => Carbon::now()->subDays($i),
                'store_name' => 'Toko Swalayan ' . $i,
                'catatan' => 'Pembelian expense ' . $i,
                'created_at' => Carbon::now()->subDays($i),
                'updated_at' => Carbon::now(),
            ]);
        }
        
        $this->command->info('20 data expenses berhasil dibuat!');
    }
}