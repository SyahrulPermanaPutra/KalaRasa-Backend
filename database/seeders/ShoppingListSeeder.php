<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShoppingListSeeder extends Seeder
{
    public function run(): void
    {
        // [id, recipe_id, nama_list, shopping_date, status, total_estimated, total_actual, catatan, created_at, updated_at]
        $data = [
            [1,  18, 'Belanja Resep 1 - 01 Mar',  '2026-03-02', 'pending',   116481.00, 0.00,       'Catatan #1: Jangan lupa beli yang segar',            '2026-02-09 19:41:03', '2026-02-28 19:41:03'],
            [2,  5,  'Belanja Resep 2 - 01 Mar',  '2026-03-03', 'completed', 111947.00, 3762936.00, 'Catatan #2: Jangan lupa beli bumbu tambahan',         '2026-02-10 19:41:03', '2026-02-28 19:41:04'],
            [3,  19, 'Belanja Resep 3 - 01 Mar',  '2026-03-04', 'pending',   94049.00,  48792.00,   'Catatan #3: Beli dalam jumlah besar untuk stok',      '2026-02-11 19:41:03', '2026-02-28 19:41:04'],
            [4,  3,  'Belanja Resep 4 - 01 Mar',  '2026-03-05', 'pending',   64455.00,  92340.00,   'Catatan #4: Bawa tas belanja sendiri',                '2026-02-12 19:41:03', '2026-02-28 19:41:04'],
            [5,  7,  'Belanja Resep 5 - 01 Mar',  '2026-03-06', 'pending',   188122.00, 0.00,       'Catatan #5: Jangan lupa beli bumbu tambahan',         '2026-02-13 19:41:03', '2026-02-24 19:41:03'],
            [6,  12, 'Belanja Resep 6 - 01 Mar',  '2026-03-07', 'pending',   140411.00, 5160820.00, 'Catatan #6: Cek ketersediaan di toko langganan',      '2026-02-14 19:41:03', '2026-02-28 19:41:04'],
            [7,  8,  'Belanja Resep 7 - 01 Mar',  '2026-03-08', 'completed', 64247.00,  58785.00,   'Catatan #7: Beli dalam jumlah besar untuk stok',      '2026-02-15 19:41:03', '2026-02-24 19:41:03'],
            [8,  14, 'Belanja Resep 8 - 01 Mar',  '2026-03-09', 'pending',   168760.00, 224020.00,  'Catatan #8: Jangan lupa beli yang segar',             '2026-02-16 19:41:03', '2026-02-28 19:41:04'],
            [9,  6,  'Belanja Resep 9 - 01 Mar',  '2026-03-10', 'pending',   136538.00, 36120.00,   'Catatan #9: Cek tanggal kadaluarsa',                  '2026-02-17 19:41:03', '2026-02-28 19:41:04'],
            [10, 4,  'Belanja Resep 10 - 01 Mar', '2026-03-11', 'completed', 108403.00, 101477.00,  'Catatan #10: Hindari jam sibuk',                      '2026-02-18 19:41:03', '2026-02-28 19:41:03'],
            [11, 5,  'Belanja Resep 11 - 01 Mar', '2026-03-12', 'pending',   160082.00, 65520.00,   'Catatan #11: Cek harga di pasar tradisional dulu',    '2026-02-19 19:41:03', '2026-02-28 19:41:04'],
            [12, 13, 'Belanja Resep 12 - 01 Mar', '2026-03-13', 'pending',   116619.00, 0.00,       'Catatan #12: Jangan lupa beli bumbu tambahan',        '2026-02-20 19:41:03', '2026-02-24 19:41:03'],
            [13, 4,  'Belanja Resep 13 - 01 Mar', '2026-03-14', 'completed', 191772.00, 6653567.00, 'Catatan #13: Bawa tas belanja sendiri',               '2026-02-21 19:41:03', '2026-02-28 19:41:04'],
            [14, 5,  'Belanja Resep 14 - 01 Mar', '2026-03-15', 'completed', 82507.00,  165882.00,  'Catatan #14: Cek ketersediaan di toko langganan',     '2026-02-22 19:41:03', '2026-02-28 19:41:04'],
            [15, 16, 'Belanja Resep 15 - 01 Mar', '2026-03-16', 'completed', 62557.00,  283698.00,  'Catatan #15: Boleh ganti merk sesuai selera',         '2026-02-23 19:41:03', '2026-02-28 19:41:04'],
            [16, 4,  'Belanja Resep 16 - 01 Mar', '2026-03-17', 'pending',   79834.00,  43350.00,   'Catatan #16: Cek ketersediaan di toko langganan',     '2026-02-24 19:41:03', '2026-02-28 19:41:04'],
            [17, 19, 'Belanja Resep 17 - 01 Mar', '2026-03-18', 'pending',   70228.00,  0.00,       'Catatan #17: Jangan lupa beli yang segar',            '2026-02-25 19:41:03', '2026-02-24 19:41:03'],
            [18, 14, 'Belanja Resep 18 - 01 Mar', '2026-03-19', 'pending',   133739.00, 84705.00,   'Catatan #18: Prioritas bahan utama',                  '2026-02-26 19:41:03', '2026-02-28 19:41:04'],
            [19, 9,  'Belanja Resep 19 - 01 Mar', '2026-03-20', 'pending',   156332.00, 101776.00,  'Catatan #19: Beli dalam jumlah besar untuk stok',     '2026-02-27 19:41:03', '2026-02-28 19:41:04'],
            [20, 17, 'Belanja Resep 20 - 01 Mar', '2026-03-21', 'pending',   119290.00, 6091080.00, 'Catatan #20: Jangan lupa beli bumbu tambahan',        '2026-02-28 19:41:03', '2026-02-28 19:41:04'],
        ];

        foreach ($data as [$id, $rid, $nama, $date, $status, $est, $actual, $catatan, $ca, $ua]) {
            DB::table('shopping_lists')->insert([
                'id'                    => $id,
                'user_id'               => 1,
                'recipe_id'             => $rid,
                'nama_list'             => $nama,
                'shopping_date'         => $date,
                'status'                => $status,
                'total_estimated_price' => $est,
                'total_actual_price'    => $actual,
                'catatan'               => $catatan,
                'created_at'            => $ca,
                'updated_at'            => $ua,
            ]);
        }
    }
}
