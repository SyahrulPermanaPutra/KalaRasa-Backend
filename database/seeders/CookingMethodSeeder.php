<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CookingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cookingMethods = [
            // Panas Kering
            ['nama' => 'goreng', 'kategori' => 'panas_kering'],
            ['nama' => 'deep fry', 'kategori' => 'panas_kering'],
            ['nama' => 'panggang', 'kategori' => 'panas_kering'],
            ['nama' => 'bakar', 'kategori' => 'panas_kering'],
            ['nama' => 'grill', 'kategori' => 'panas_kering'],
            ['nama' => 'oven', 'kategori' => 'panas_kering'],
            ['nama' => 'tumis', 'kategori' => 'panas_kering'],
            ['nama' => 'oseng', 'kategori' => 'panas_kering'],
            ['nama' => 'saute', 'kategori' => 'panas_kering'],
            ['nama' => 'cah', 'kategori' => 'panas_kering'],
            
            // Panas Basah
            ['nama' => 'rebus', 'kategori' => 'panas_basah'],
            ['nama' => 'kukus', 'kategori' => 'panas_basah'],
            ['nama' => 'steam', 'kategori' => 'panas_basah'],
            ['nama' => 'ungkep', 'kategori' => 'panas_basah'],
            ['nama' => 'tim', 'kategori' => 'panas_basah'],
            ['nama' => 'sup', 'kategori' => 'panas_basah'],
            ['nama' => 'sop', 'kategori' => 'panas_basah'],
            ['nama' => 'kuah', 'kategori' => 'panas_basah'],
            ['nama' => 'gulai', 'kategori' => 'panas_basah'],
            ['nama' => 'kari', 'kategori' => 'panas_basah'],
            
            // Kombinasi
            ['nama' => 'rendang', 'kategori' => 'kombinasi'],
            ['nama' => 'semur', 'kategori' => 'kombinasi'],
            ['nama' => 'balado', 'kategori' => 'kombinasi'],
            ['nama' => 'rica-rica', 'kategori' => 'kombinasi'],
            ['nama' => 'pepes', 'kategori' => 'kombinasi'],
            ['nama' => 'botok', 'kategori' => 'kombinasi'],
            
            // Tanpa Panas
            ['nama' => 'salad', 'kategori' => 'tanpa_panas'],
            ['nama' => 'lalapan', 'kategori' => 'tanpa_panas'],
        ];

        foreach ($cookingMethods as $method) {
            DB::table('cooking_methods')->insert([
                'nama' => $method['nama'],
                'kategori' => $method['kategori'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}