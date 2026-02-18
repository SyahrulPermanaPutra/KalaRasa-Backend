<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HealthConditionsTableSeeder extends Seeder
{
    public function run()
    {
        $healthConditions = [
            ['nama' => 'Diabetes', 'description' => 'Kadar gula darah tinggi'],
            ['nama' => 'Hipertensi', 'description' => 'Tekanan darah tinggi'],
            ['nama' => 'Kolesterol Tinggi', 'description' => 'Kadar kolesterol dalam darah tinggi'],
            ['nama' => 'Asam Urat', 'description' => 'Kadar asam urat tinggi'],
            ['nama' => 'Maag', 'description' => 'Gangguan lambung'],
            ['nama' => 'Jantung', 'description' => 'Penyakit jantung'],
            ['nama' => 'Ginjal', 'description' => 'Gangguan fungsi ginjal'],
            ['nama' => 'Hati', 'description' => 'Gangguan fungsi hati'],
            ['nama' => 'Alergi Seafood', 'description' => 'Alergi terhadap makanan laut'],
            ['nama' => 'Alergi Kacang', 'description' => 'Alergi terhadap kacang-kacangan'],
            ['nama' => 'Obesitas', 'description' => 'Kelebihan berat badan'],
            ['nama' => 'Anemia', 'description' => 'Kekurangan sel darah merah'],
            ['nama' => 'Osteoporosis', 'description' => 'Tulang keropos'],
            ['nama' => 'Asma', 'description' => 'Gangguan pernapasan'],
            ['nama' => 'Gerd', 'description' => 'Refluks asam lambung'],
            ['nama' => 'Kanker', 'description' => 'Penyakit kanker'],
            ['nama' => 'Tuberkulosis', 'description' => 'Penyakit paru-paru'],
            ['nama' => 'Thyroid', 'description' => 'Gangguan kelenjar tiroid'],
            ['nama' => 'Autoimun', 'description' => 'Gangguan sistem kekebalan'],
            ['nama' => 'Ibu Hamil', 'description' => 'Kondisi kehamilan'],
        ];

        foreach ($healthConditions as $hc) {
            \App\Models\HealthCondition::create([
                'nama' => $hc['nama'],
                'description' => $hc['description'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}