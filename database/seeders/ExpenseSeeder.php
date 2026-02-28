<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        // [id, shopping_list_id, shopping_list_item_id, actual_price, purchase_date, store_name, catatan, created_at, updated_at]
        $data = [
            [1,  15, 18, 226395.00,  '2026-02-07', 'E-commerce Lazada',       'Pembelian Cabai Merah 8 untuk stok mingguan',  '2026-02-06 14:41:03', '2026-02-06 19:41:03'],
            [2,  19, 29, 59976.00,   '2026-02-06', 'Supermarket Alfamart',    'Stok habis, beli urgent',                      '2026-02-05 18:41:04', '2026-02-05 19:41:04'],
            [3,  4,  5,  92340.00,   '2026-02-19', 'E-commerce Lazada',       'Dapat voucher belanja',                        '2026-02-18 14:41:04', '2026-02-18 19:41:04'],
            [4,  18, 66, 15450.00,   '2026-02-27', 'Toko Kelontong 24 Jam',   'Pembelian Tepung Terigu 80 untuk stok mingguan','2026-02-26 03:41:04', '2026-02-26 19:41:04'],
            [5,  19, 32, 1200.00,    '2026-02-03', 'E-commerce Shopee',       'Memanfaatkan promo akhir pekan',               '2026-02-02 07:41:04', '2026-02-02 19:41:04'],
            [6,  11, 84, 65520.00,   '2026-02-18', 'Pasar Online GrabMart',   'Harga sedikit lebih mahal dari biasanya',      '2026-02-17 15:41:04', '2026-02-17 19:41:04'],
            [7,  20, 36, 6072000.00, '2026-02-24', 'Supermarket Indomaret',   'Kualitas barang bagus',                        '2026-02-23 02:41:04', '2026-02-23 19:41:04'],
            [8,  8,  30, 54520.00,   '2026-02-16', 'Transmart Carrefour',     'Beli dalam jumlah besar karena diskon',        '2026-02-15 12:41:04', '2026-02-15 19:41:04'],
            [9,  18, 5,  69255.00,   '2026-02-21', 'Supermarket Indomaret',   'Stok habis, beli urgent',                      '2026-02-20 14:41:04', '2026-02-20 19:41:04'],
            [10, 8,  4,  169500.00,  '2026-02-10', 'Pasar Modern Bintaro',    'Harga lebih murah dari estimasi',              '2026-02-09 12:41:04', '2026-02-09 19:41:04'],
            [11, 2,  24, 3655120.00, '2026-02-22', 'E-commerce Lazada',       'Kualitas barang bagus',                        '2026-02-21 17:41:04', '2026-02-21 19:41:04'],
            [12, 20, 28, 19080.00,   '2026-02-01', 'Lotte Mart Wholesale',    'Belanja untuk acara keluarga',                 '2026-01-31 14:41:04', '2026-01-31 19:41:04'],
            [13, 19, 85, 40600.00,   '2026-02-26', 'Farmers Market',          'Belanja bulanan',                              '2026-02-25 04:41:04', '2026-02-25 19:41:04'],
            [14, 6,  23, 8820.00,    '2026-02-19', 'Toko Kelontong 24 Jam',   'Belanja untuk acara keluarga',                 '2026-02-17 19:41:04', '2026-02-18 19:41:04'],
            [15, 16, 33, 43350.00,   '2026-02-09', 'Pasar Online GoFood',     'Kualitas barang bagus',                        '2026-02-08 01:41:04', '2026-02-08 19:41:04'],
            [16, 13, 46, 6473520.00, '2026-02-02', 'Supermarket Indomaret',   'Memanfaatkan promo akhir pekan',               '2026-02-01 16:41:04', '2026-02-01 19:41:04'],
            [17, 3,  19, 48792.00,   '2026-02-14', 'Pasar Online GrabMart',   'Belanja bulanan',                              '2026-02-13 10:41:04', '2026-02-13 19:41:04'],
            [18, 14, 25, 85500.00,   '2026-02-07', 'Farmers Market',          'Beli dalam jumlah besar karena diskon',        '2026-02-06 18:41:04', '2026-02-06 19:41:04'],
            [19, 6,  47, 5152000.00, '2026-02-10', 'Pasar Modern Bintaro',    'Stok habis, beli urgent',                      '2026-02-08 20:41:04', '2026-02-09 19:41:04'],
            [20, 9,  76, 36120.00,   '2026-02-24', 'Lotte Mart Wholesale',    'Stok habis, beli urgent',                      '2026-02-23 18:41:04', '2026-02-23 19:41:04'],
        ];

        foreach ($data as [$id, $slid, $sliid, $price, $date, $store, $catatan, $ca, $ua]) {
            DB::table('expenses')->insert([
                'id'                    => $id,
                'user_id'               => 1,
                'shopping_list_id'      => $slid,
                'shopping_list_item_id' => $sliid,
                'actual_price'          => $price,
                'purchase_date'         => $date,
                'store_name'            => $store,
                'catatan'               => $catatan,
                'created_at'            => $ca,
                'updated_at'            => $ua,
            ]);
        }
    }
}
