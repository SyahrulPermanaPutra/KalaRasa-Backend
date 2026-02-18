<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ExpensesTableSeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 20; $i++) {
            \App\Models\Expense::create([
                'user_id' => ($i % 20) + 2,
                'shopping_list_id' => $i,
                'shopping_list_item_id' => (($i - 1) * 5) + 1,
                'actual_price' => rand(10000, 100000),
                'purchase_date' => Carbon::now()->subDays($i),
                'store_name' => 'Toko Swalayan ' . $i,
                'catatan' => 'Pembelian expense ' . $i,
                'created_at' => Carbon::now()->subDays($i),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}