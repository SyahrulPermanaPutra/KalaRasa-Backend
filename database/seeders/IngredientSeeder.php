<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        // [id, nama, kategori, sub_kategori]
        $data = [
            [1,  'Ayam',          'protein',     'unggas'],
            [2,  'Daging Sapi',   'protein',     'daging merah'],
            [3,  'Ikan Lele',     'protein',     'ikan air tawar'],
            [4,  'Ikan Salmon',   'protein',     'ikan laut'],
            [5,  'Telur',         'protein',     'unggas'],
            [6,  'Tahu',          'protein',     'olahan kedelai'],
            [7,  'Tempe',         'protein',     'olahan kedelai'],
            [8,  'Udang',         'protein',     'seafood'],
            [9,  'Nasi',          'karbohidrat', 'beras'],
            [10, 'Mie',           'karbohidrat', 'tepung'],
            [11, 'Kentang',       'karbohidrat', 'umbi'],
            [12, 'Singkong',      'karbohidrat', 'umbi'],
            [13, 'Bayam',         'sayuran',     'daun'],
            [14, 'Kangkung',      'sayuran',     'daun'],
            [15, 'Wortel',        'sayuran',     'umbi'],
            [16, 'Tomat',         'sayuran',     'buah'],
            [17, 'Bawang Merah',  'bumbu',       'umbi'],
            [18, 'Bawang Putih',  'bumbu',       'umbi'],
            [19, 'Cabai',         'bumbu',       'buah'],
            [20, 'Jahe',          'bumbu',       'rimpang'],
            [21, 'Kunyit',        'bumbu',       'rimpang'],
            [22, 'Lengkuas',      'bumbu',       'rimpang'],
            [23, 'Santan',        'lemak',       'nabati'],
            [24, 'Minyak Goreng', 'lemak',       'nabati'],
            [25, 'Garam',         'penyedap',    'mineral'],
            [26, 'Gula',          'penyedap',    'pemanis'],
            [27, 'Kecap Manis',   'penyedap',    'saus'],
            [28, 'Terasi',        'penyedap',    'fermentasi'],
            [29, 'Daun Jeruk',    'bumbu',       'daun'],
            [30, 'Serai',         'bumbu',       'batang'],
        ];

        foreach ($data as [$id, $nama, $kategori, $sub_kategori]) {
            DB::table('ingredients')->insert([
                'id'           => $id,
                'nama'         => $nama,
                'kategori'     => $kategori,
                'sub_kategori' => $sub_kategori,
                'created_at'   => '2026-02-28 19:41:02',
                'updated_at'   => '2026-02-28 19:41:02',
            ]);
        }
    }
}
