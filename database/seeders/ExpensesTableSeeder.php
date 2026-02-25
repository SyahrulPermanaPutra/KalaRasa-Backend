<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Role;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;

class ExpensesTableSeeder extends Seeder
{
    public function run()
    {
        // Dapatkan role_id untuk 'user'
        $userRole = Role::where('name', 'user')->first();
        
        if (!$userRole) {
            $this->command->error('Role "user" tidak ditemukan! Jalankan RoleSeeder terlebih dahulu.');
            return;
        }
        
        // Ambil user_id berdasarkan role_id (hanya user biasa, bukan admin)
        $userIds = User::where('role_id', $userRole->id)->pluck('id')->toArray();
        
        // Jika tidak ada user dengan role 'user', fallback ke semua user
        if (empty($userIds)) {
            $this->command->warn('Tidak ada user dengan role "user" ditemukan. Mengambil semua user...');
            $userIds = User::pluck('id')->toArray();
        }
        
        // Ambil semua shopping_list_id yang ada
        $shoppingListIds = ShoppingList::pluck('id')->toArray();
        
        // Ambil semua shopping_list_item_id yang ada
        $shoppingListItemIds = ShoppingListItem::pluck('id')->toArray();
        
        // Validasi data
        if (empty($userIds)) {
            $this->command->error('Tidak ada user ditemukan! Jalankan UsersTableSeeder terlebih dahulu.');
            return;
        }
        
        if (empty($shoppingListIds)) {
            $this->command->error('Tidak ada shopping lists ditemukan! Jalankan ShoppingListsTableSeeder terlebih dahulu.');
            return;
        }
        
        if (empty($shoppingListItemIds)) {
            $this->command->error('Tidak ada shopping list items ditemukan! Jalankan ShoppingListItemsTableSeeder terlebih dahulu.');
            return;
        }
        
        $this->command->info('Memulai pembuatan data expenses...');
        $this->command->info('Total users: ' . count($userIds));
        $this->command->info('Total shopping lists: ' . count($shoppingListIds));
        $this->command->info('Total shopping list items: ' . count($shoppingListItemIds));
        
        $expenses = [];
        $usedCombinations = []; // Untuk menghindari duplikasi kombinasi
        
        for ($i = 1; $i <= 20; $i++) {
            // Pilih random data
            $randomUserId = $userIds[array_rand($userIds)];
            $randomShoppingListId = $shoppingListIds[array_rand($shoppingListIds)];
            $randomShoppingListItemId = $shoppingListItemIds[array_rand($shoppingListItemIds)];
            
            // Buat unique key untuk menghindari duplikasi
            $combinationKey = $randomUserId . '-' . $randomShoppingListId . '-' . $randomShoppingListItemId;
            
            // Skip jika kombinasi sudah pernah digunakan
            if (in_array($combinationKey, $usedCombinations)) {
                $i--;
                continue;
            }
            
            $usedCombinations[] = $combinationKey;
            
            // Ambil data shopping list dan item untuk referensi
            $shoppingList = ShoppingList::find($randomShoppingListId);
            $shoppingListItem = ShoppingListItem::find($randomShoppingListItemId);
            
            // Tentukan harga actual (bisa lebih tinggi atau lebih rendah dari estimated)
            $estimatedPrice = $shoppingListItem->estimated_price ?? 0;
            $actualPrice = $this->generateActualPrice($estimatedPrice);
            
            // Tentukan tanggal pembelian (random antara 1-30 hari yang lalu)
            $purchaseDate = Carbon::now()->subDays(rand(1, 30));
            
            // Tentukan store name berdasarkan item atau random
            $storeName = $this->getRandomStoreName($shoppingListItem->nama_item ?? '');
            
            $expenses[] = [
                'user_id' => $randomUserId,
                'shopping_list_id' => $randomShoppingListId,
                'shopping_list_item_id' => $randomShoppingListItemId,
                'actual_price' => $actualPrice,
                'purchase_date' => $purchaseDate,
                'store_name' => $storeName,
                'catatan' => $this->getRandomCatatan($shoppingListItem->nama_item ?? 'Item'),
                'created_at' => $purchaseDate->copy()->subHours(rand(1, 24)),
                'updated_at' => $purchaseDate,
            ];
            
            // Update actual_price di shopping_list_item jika ada
            if ($shoppingListItem) {
                $shoppingListItem->actual_price = $actualPrice;
                $shoppingListItem->is_purchased = true;
                $shoppingListItem->purchased_at = $purchaseDate;
                $shoppingListItem->save();
            }
            
            // Update total_actual_price di shopping_list
            if ($shoppingList) {
                $shoppingList->total_actual_price += $actualPrice;
                $shoppingList->save();
            }
        }
        
        // Insert expenses
        if (!empty($expenses)) {
            \App\Models\Expense::insert($expenses);
            $this->command->info(count($expenses) . ' data expenses berhasil dibuat!');
        } else {
            $this->command->error('Tidak ada data expenses yang berhasil dibuat!');
        }
        
        // Tampilkan statistik
        $this->command->table(
            ['Total Expenses', 'Total Users', 'Total Shopping Lists', 'Total Items', 'Total Amount'],
            [[
                count($expenses),
                count(array_unique(array_column($expenses, 'user_id'))),
                count(array_unique(array_column($expenses, 'shopping_list_id'))),
                count(array_unique(array_column($expenses, 'shopping_list_item_id'))),
                'Rp ' . number_format(array_sum(array_column($expenses, 'actual_price')), 0, ',', '.')
            ]]
        );
    }
    
    /**
     * Generate actual price berdasarkan estimated price
     */
    private function generateActualPrice($estimatedPrice)
    {
        if ($estimatedPrice > 0) {
            // Actual price bisa +/- 20% dari estimated
            $variation = rand(-20, 20) / 100;
            return max(1000, round($estimatedPrice * (1 + $variation)));
        }
        
        // Jika estimated price 0, generate random
        return rand(5000, 150000);
    }
    
    /**
     * Get random store name
     */
    private function getRandomStoreName($itemName = '')
    {
        $stores = [
            'Supermarket Indomaret',
            'Supermarket Alfamart',
            'Pasar Tradisional Senen',
            'Pasar Modern Bintaro',
            'Hypermart Mall',
            'Transmart Carrefour',
            'Lotte Mart Wholesale',
            'Farmers Market',
            'Toko Kelontong 24 Jam',
            'E-commerce Shopee',
            'E-commerce Tokopedia',
            'E-commerce Lazada',
            'Pasar Online GrabMart',
            'Pasar Online GoFood',
        ];
        
        // Kadang-kadang kaitkan dengan nama item
        if (str_contains(strtolower($itemName), 'sayur') || str_contains(strtolower($itemName), 'buah')) {
            $stores = array_merge($stores, ['Pasar Induk Kramat Jati', 'Pasar Minggu', 'Pasar Kebayoran']);
        } elseif (str_contains(strtolower($itemName), 'daging') || str_contains(strtolower($itemName), 'ayam')) {
            $stores = array_merge($stores, ['Pasar Daging Slipi', 'Butcher Shop', 'Meat Locker']);
        }
        
        return $stores[array_rand($stores)];
    }
    
    /**
     * Get random catatan
     */
    private function getRandomCatatan($itemName)
    {
        $catatans = [
            'Pembelian ' . $itemName . ' untuk stok mingguan',
            'Harga lebih murah dari estimasi',
            'Beli dalam jumlah besar karena diskon',
            'Kualitas barang bagus',
            'Membeli di toko langganan',
            'Harga sedikit lebih mahal dari biasanya',
            'Dapat voucher belanja',
            'Belanja bulanan',
            'Stok habis, beli urgent',
            'Belanja untuk acara keluarga',
            'Memanfaatkan promo akhir pekan',
            'Beli online karena lebih praktis',
        ];
        
        return $catatans[array_rand($catatans)];
    }
}