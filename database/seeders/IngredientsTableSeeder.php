<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class IngredientsTableSeeder extends Seeder
{
    public function run()
    {
        $ingredients = [
            ['nama' => 'Ayam', 'kategori' => 'protein', 'sub_kategori' => 'unggas'],
            ['nama' => 'Daging Sapi', 'kategori' => 'protein', 'sub_kategori' => 'daging merah'],
            ['nama' => 'Ikan Lele', 'kategori' => 'protein', 'sub_kategori' => 'ikan air tawar'],
            ['nama' => 'Ikan Salmon', 'kategori' => 'protein', 'sub_kategori' => 'ikan laut'],
            ['nama' => 'Telur', 'kategori' => 'protein', 'sub_kategori' => 'unggas'],
            ['nama' => 'Tahu', 'kategori' => 'protein', 'sub_kategori' => 'olahan kedelai'],
            ['nama' => 'Tempe', 'kategori' => 'protein', 'sub_kategori' => 'olahan kedelai'],
            ['nama' => 'Udang', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'Nasi', 'kategori' => 'karbohidrat', 'sub_kategori' => 'beras'],
            ['nama' => 'Mie', 'kategori' => 'karbohidrat', 'sub_kategori' => 'tepung'],
            ['nama' => 'Kentang', 'kategori' => 'karbohidrat', 'sub_kategori' => 'umbi'],
            ['nama' => 'Singkong', 'kategori' => 'karbohidrat', 'sub_kategori' => 'umbi'],
            ['nama' => 'Bayam', 'kategori' => 'sayuran', 'sub_kategori' => 'daun'],
            ['nama' => 'Kangkung', 'kategori' => 'sayuran', 'sub_kategori' => 'daun'],
            ['nama' => 'Wortel', 'kategori' => 'sayuran', 'sub_kategori' => 'umbi'],
            ['nama' => 'Tomat', 'kategori' => 'sayuran', 'sub_kategori' => 'buah'],
            ['nama' => 'Bawang Merah', 'kategori' => 'bumbu', 'sub_kategori' => 'umbi'],
            ['nama' => 'Bawang Putih', 'kategori' => 'bumbu', 'sub_kategori' => 'umbi'],
            ['nama' => 'Cabai', 'kategori' => 'bumbu', 'sub_kategori' => 'buah'],
            ['nama' => 'Jahe', 'kategori' => 'bumbu', 'sub_kategori' => 'rimpang'],
            ['nama' => 'Kunyit', 'kategori' => 'bumbu', 'sub_kategori' => 'rimpang'],
            ['nama' => 'Lengkuas', 'kategori' => 'bumbu', 'sub_kategori' => 'rimpang'],
            ['nama' => 'Santan', 'kategori' => 'lemak', 'sub_kategori' => 'nabati'],
            ['nama' => 'Minyak Goreng', 'kategori' => 'lemak', 'sub_kategori' => 'nabati'],
            ['nama' => 'Garam', 'kategori' => 'penyedap', 'sub_kategori' => 'mineral'],
            ['nama' => 'Gula', 'kategori' => 'penyedap', 'sub_kategori' => 'pemanis'],
            ['nama' => 'Kecap Manis', 'kategori' => 'penyedap', 'sub_kategori' => 'saus'],
            ['nama' => 'Terasi', 'kategori' => 'penyedap', 'sub_kategori' => 'fermentasi'],
            ['nama' => 'Daun Jeruk', 'kategori' => 'bumbu', 'sub_kategori' => 'daun'],
            ['nama' => 'Serai', 'kategori' => 'bumbu', 'sub_kategori' => 'batang'],
        ];

        foreach ($ingredients as $ingredient) {
            \App\Models\Ingredient::create([
                'nama' => $ingredient['nama'],
                'kategori' => $ingredient['kategori'],
                'sub_kategori' => $ingredient['sub_kategori'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}