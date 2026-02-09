<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingredients = [
            // Protein - Daging
            ['nama' => 'ayam', 'kategori' => 'protein', 'sub_kategori' => 'daging'],
            ['nama' => 'sapi', 'kategori' => 'protein', 'sub_kategori' => 'daging'],
            ['nama' => 'kambing', 'kategori' => 'protein', 'sub_kategori' => 'daging'],
            ['nama' => 'bebek', 'kategori' => 'protein', 'sub_kategori' => 'daging'],
            ['nama' => 'babi', 'kategori' => 'protein', 'sub_kategori' => 'daging'],

            // Protein - Seafood
            ['nama' => 'ikan', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'udang', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'cumi', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'kerang', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'kepiting', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'salmon', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'tuna', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],
            ['nama' => 'kakap', 'kategori' => 'protein', 'sub_kategori' => 'seafood'],

            // Protein - Nabati
            ['nama' => 'tahu', 'kategori' => 'protein', 'sub_kategori' => 'nabati'],
            ['nama' => 'tempe', 'kategori' => 'protein', 'sub_kategori' => 'nabati'],
            ['nama' => 'oncom', 'kategori' => 'protein', 'sub_kategori' => 'nabati'],
            ['nama' => 'kacang merah', 'kategori' => 'protein', 'sub_kategori' => 'nabati'],
            ['nama' => 'kacang hijau', 'kategori' => 'protein', 'sub_kategori' => 'nabati'],

            // Protein - Telur
            ['nama' => 'telur ayam', 'kategori' => 'protein', 'sub_kategori' => 'telur'],
            ['nama' => 'telur bebek', 'kategori' => 'protein', 'sub_kategori' => 'telur'],

            // Sayuran - Hijau
            ['nama' => 'bayam', 'kategori' => 'sayuran', 'sub_kategori' => 'hijau'],
            ['nama' => 'kangkung', 'kategori' => 'sayuran', 'sub_kategori' => 'hijau'],
            ['nama' => 'sawi', 'kategori' => 'sayuran', 'sub_kategori' => 'hijau'],
            ['nama' => 'brokoli', 'kategori' => 'sayuran', 'sub_kategori' => 'hijau'],
            ['nama' => 'selada', 'kategori' => 'sayuran', 'sub_kategori' => 'hijau'],

            // Sayuran - Umbi
            ['nama' => 'kentang', 'kategori' => 'sayuran', 'sub_kategori' => 'umbi'],
            ['nama' => 'ubi', 'kategori' => 'sayuran', 'sub_kategori' => 'umbi'],
            ['nama' => 'singkong', 'kategori' => 'sayuran', 'sub_kategori' => 'umbi'],
            ['nama' => 'wortel', 'kategori' => 'sayuran', 'sub_kategori' => 'umbi'],

            // Sayuran - Buah Sayur
            ['nama' => 'tomat', 'kategori' => 'sayuran', 'sub_kategori' => 'buah_sayur'],
            ['nama' => 'terong', 'kategori' => 'sayuran', 'sub_kategori' => 'buah_sayur'],
            ['nama' => 'timun', 'kategori' => 'sayuran', 'sub_kategori' => 'buah_sayur'],
            ['nama' => 'labu', 'kategori' => 'sayuran', 'sub_kategori' => 'buah_sayur'],
            ['nama' => 'cabai', 'kategori' => 'sayuran', 'sub_kategori' => 'buah_sayur'],

            // Sayuran - Lainnya
            ['nama' => 'kacang panjang', 'kategori' => 'sayuran', 'sub_kategori' => 'kacang_polong'],
            ['nama' => 'buncis', 'kategori' => 'sayuran', 'sub_kategori' => 'kacang_polong'],
            ['nama' => 'jagung', 'kategori' => 'sayuran', 'sub_kategori' => 'lainnya'],

            // Karbohidrat
            ['nama' => 'beras putih', 'kategori' => 'karbohidrat', 'sub_kategori' => 'nasi'],
            ['nama' => 'beras merah', 'kategori' => 'karbohidrat', 'sub_kategori' => 'nasi'],
            ['nama' => 'nasi', 'kategori' => 'karbohidrat', 'sub_kategori' => 'nasi'],
            ['nama' => 'mie', 'kategori' => 'karbohidrat', 'sub_kategori' => 'mie'],
            ['nama' => 'pasta', 'kategori' => 'karbohidrat', 'sub_kategori' => 'mie'],
            ['nama' => 'roti', 'kategori' => 'karbohidrat', 'sub_kategori' => 'roti'],
            ['nama' => 'tepung terigu', 'kategori' => 'karbohidrat', 'sub_kategori' => 'tepung'],
            ['nama' => 'tepung beras', 'kategori' => 'karbohidrat', 'sub_kategori' => 'tepung'],

            // Bumbu
            ['nama' => 'bawang merah', 'kategori' => 'bumbu', 'sub_kategori' => 'bawang'],
            ['nama' => 'bawang putih', 'kategori' => 'bumbu', 'sub_kategori' => 'bawang'],
            ['nama' => 'bawang bombay', 'kategori' => 'bumbu', 'sub_kategori' => 'bawang'],
            ['nama' => 'jahe', 'kategori' => 'bumbu', 'sub_kategori' => 'rempah'],
            ['nama' => 'kunyit', 'kategori' => 'bumbu', 'sub_kategori' => 'rempah'],
            ['nama' => 'lengkuas', 'kategori' => 'bumbu', 'sub_kategori' => 'rempah'],
            ['nama' => 'serai', 'kategori' => 'bumbu', 'sub_kategori' => 'aromatik'],
            ['nama' => 'daun salam', 'kategori' => 'bumbu', 'sub_kategori' => 'aromatik'],
            ['nama' => 'daun jeruk', 'kategori' => 'bumbu', 'sub_kategori' => 'aromatik'],
            ['nama' => 'kemangi', 'kategori' => 'bumbu', 'sub_kategori' => 'aromatik'],

            // Lemak
            ['nama' => 'minyak goreng', 'kategori' => 'lemak', 'sub_kategori' => 'minyak'],
            ['nama' => 'minyak zaitun', 'kategori' => 'lemak', 'sub_kategori' => 'minyak'],
            ['nama' => 'santan', 'kategori' => 'lemak', 'sub_kategori' => 'santan'],
            ['nama' => 'mentega', 'kategori' => 'lemak', 'sub_kategori' => 'mentega'],
            ['nama' => 'margarin', 'kategori' => 'lemak', 'sub_kategori' => 'mentega'],

            // Penyedap
            ['nama' => 'garam', 'kategori' => 'penyedap', 'sub_kategori' => 'asin'],
            ['nama' => 'gula', 'kategori' => 'penyedap', 'sub_kategori' => 'manis'],
            ['nama' => 'gula merah', 'kategori' => 'penyedap', 'sub_kategori' => 'manis'],
            ['nama' => 'kecap manis', 'kategori' => 'penyedap', 'sub_kategori' => 'manis'],
            ['nama' => 'kecap asin', 'kategori' => 'penyedap', 'sub_kategori' => 'asin'],
            ['nama' => 'saus tiram', 'kategori' => 'penyedap', 'sub_kategori' => 'asin'],
            ['nama' => 'terasi', 'kategori' => 'penyedap', 'sub_kategori' => 'asin'],
        ];

        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->insert([
                'nama' => $ingredient['nama'],
                'kategori' => $ingredient['kategori'],
                'sub_kategori' => $ingredient['sub_kategori'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}