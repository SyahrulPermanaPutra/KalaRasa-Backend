<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $healthConditions = [
            ['nama' => 'diabetes', 'description' => 'Kondisi kadar gula darah tinggi'],
            ['nama' => 'kolesterol', 'description' => 'Kondisi kolesterol tinggi dalam darah'],
            ['nama' => 'asam_urat', 'description' => 'Kondisi asam urat tinggi'],
            ['nama' => 'hipertensi', 'description' => 'Tekanan darah tinggi'],
            ['nama' => 'maag', 'description' => 'Gangguan lambung'],
            ['nama' => 'alergi_dairy', 'description' => 'Alergi terhadap produk susu'],
            ['nama' => 'alergi_gluten', 'description' => 'Alergi terhadap gluten'],
            ['nama' => 'alergi_seafood', 'description' => 'Alergi terhadap seafood'],
            ['nama' => 'vegetarian', 'description' => 'Tidak mengonsumsi daging'],
            ['nama' => 'diet_rendah_kalori', 'description' => 'Program diet rendah kalori'],
        ];

        foreach ($healthConditions as $condition) {
            DB::table('health_conditions')->insert([
                'nama' => $condition['nama'],
                'description' => $condition['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}