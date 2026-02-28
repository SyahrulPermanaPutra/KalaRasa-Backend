<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthConditionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [1,  'Diabetes',        'Kadar gula darah tinggi'],
            [2,  'Hipertensi',      'Tekanan darah tinggi'],
            [3,  'Kolesterol Tinggi','Kadar kolesterol dalam darah tinggi'],
            [4,  'Asam Urat',       'Kadar asam urat tinggi'],
            [5,  'Maag',            'Gangguan lambung'],
            [6,  'Jantung',         'Penyakit jantung'],
            [7,  'Ginjal',          'Gangguan fungsi ginjal'],
            [8,  'Hati',            'Gangguan fungsi hati'],
            [9,  'Alergi Seafood',  'Alergi terhadap makanan laut'],
            [10, 'Alergi Kacang',   'Alergi terhadap kacang-kacangan'],
            [11, 'Obesitas',        'Kelebihan berat badan'],
            [12, 'Anemia',          'Kekurangan sel darah merah'],
            [13, 'Osteoporosis',    'Tulang keropos'],
            [14, 'Asma',            'Gangguan pernapasan'],
            [15, 'Gerd',            'Refluks asam lambung'],
            [16, 'Kanker',          'Penyakit kanker'],
            [17, 'Tuberkulosis',    'Penyakit paru-paru'],
            [18, 'Thyroid',         'Gangguan kelenjar tiroid'],
            [19, 'Autoimun',        'Gangguan sistem kekebalan'],
            [20, 'Ibu Hamil',       'Kondisi kehamilan'],
        ];

        foreach ($data as [$id, $nama, $description]) {
            DB::table('health_conditions')->insert([
                'id'          => $id,
                'nama'        => $nama,
                'description' => $description,
                'created_at'  => '2026-02-28 19:41:02',
                'updated_at'  => '2026-02-28 19:41:02',
            ]);
        }
    }
}
