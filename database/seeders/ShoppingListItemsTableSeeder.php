<?php
// database/seeders/ShoppingListItemsTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShoppingList;
use App\Models\Ingredient;
use App\Models\ShoppingListItem;
use Carbon\Carbon;

class ShoppingListItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua shopping lists yang ada
        $shoppingLists = ShoppingList::all();
        
        if ($shoppingLists->isEmpty()) {
            $this->command->error('Tidak ada shopping lists ditemukan! Jalankan ShoppingListsTableSeeder dulu.');
            return;
        }
        
        // Ambil semua ingredients yang ada untuk referensi
        $ingredients = Ingredient::all();
        $hasIngredients = !$ingredients->isEmpty();
        
        $totalItems = 0;
        
        // Buat 3-7 item untuk setiap shopping list
        foreach ($shoppingLists as $shoppingList) {
            $itemCount = rand(3, 7);
            
            // Daftar item umum untuk belanja
            $commonItems = [
                ['nama' => 'Beras', 'satuan' => 'kg', 'harga' => 12000],
                ['nama' => 'Gula Pasir', 'satuan' => 'kg', 'harga' => 14000],
                ['nama' => 'Minyak Goreng', 'satuan' => 'liter', 'harga' => 15000],
                ['nama' => 'Telur', 'satuan' => 'kg', 'harga' => 28000],
                ['nama' => 'Tepung Terigu', 'satuan' => 'kg', 'harga' => 10000],
                ['nama' => 'Bawang Merah', 'satuan' => 'kg', 'harga' => 35000],
                ['nama' => 'Bawang Putih', 'satuan' => 'kg', 'harga' => 30000],
                ['nama' => 'Cabai Merah', 'satuan' => 'kg', 'harga' => 45000],
                ['nama' => 'Ayam', 'satuan' => 'kg', 'harga' => 35000],
                ['nama' => 'Ikan', 'satuan' => 'kg', 'harga' => 40000],
                ['nama' => 'Tahu', 'satuan' => 'buah', 'harga' => 2000],
                ['nama' => 'Tempe', 'satuan' => 'papan', 'harga' => 5000],
                ['nama' => 'Kecap Manis', 'satuan' => 'ml', 'harga' => 8000],
                ['nama' => 'Garam', 'satuan' => 'gram', 'harga' => 5000],
                ['nama' => 'Penyedap Rasa', 'satuan' => 'sachet', 'harga' => 500],
            ];
            
            for ($i = 1; $i <= $itemCount; $i++) {
                // Pilih item secara acak
                $itemIndex = array_rand($commonItems);
                $item = $commonItems[$itemIndex];
                
                // Tentukan jumlah acak
                $jumlah = $this->getRandomJumlah($item['satuan']);
                
                // Hitung estimated price
                $estimatedPrice = $item['harga'] * $jumlah;
                
                // Tentukan apakah sudah dibeli (untuk status pending atau completed)
                $isPurchased = ($shoppingList->status === 'completed') ? true : (rand(0, 100) > 70);
                
                // Cari ingredient_id jika ada yang cocok
                $ingredientId = null;
                if ($hasIngredients) {
                    $matchingIngredient = $ingredients->where('nama', 'like', '%' . $item['nama'] . '%')->first();
                    if ($matchingIngredient) {
                        $ingredientId = $matchingIngredient->id;
                    }
                }
                
                // Data item
                $data = [
                    'shopping_list_id' => $shoppingList->id,
                    'ingredient_id' => $ingredientId,
                    'nama_item' => $item['nama'] . ' ' . ($i + rand(1, 100)), // Tambah variasi
                    'jumlah' => $jumlah,
                    'satuan' => $item['satuan'],
                    'estimated_price' => $estimatedPrice,
                    'actual_price' => $isPurchased ? $estimatedPrice * rand(90, 110) / 100 : 0,
                    'is_purchased' => $isPurchased,
                    'purchased_at' => $isPurchased ? Carbon::now()->subDays(rand(1, 5)) : null,
                    'catatan' => rand(0, 1) ? 'Catatan untuk ' . $item['nama'] : null,
                    'created_at' => $shoppingList->created_at,
                    'updated_at' => $shoppingList->updated_at,
                ];
                
                ShoppingListItem::create($data);
                $totalItems++;
            }
        }
        
        $this->command->info("Berhasil membuat {$totalItems} data shopping list items dari " . $shoppingLists->count() . " shopping lists!");
    }
    
    /**
     * Get random jumlah based on satuan
     */
    private function getRandomJumlah($satuan)
    {
        switch ($satuan) {
            case 'kg':
            case 'liter':
                return rand(1, 50) / 10; // 0.1 - 5.0
            case 'gram':
                return rand(100, 1000); // 100 - 1000 gram
            case 'buah':
            case 'papan':
            case 'sachet':
                return rand(1, 10);
            case 'ml':
                return rand(100, 1000); // 100 - 1000 ml
            default:
                return rand(1, 5);
        }
    }
}