<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthConditionRestrictionSeeder extends Seeder
{
    public function run(): void
    {
        // [id, health_condition_id, ingredient_id, severity, notes]
        $data = [
            [1,  1,  26, 'hindari', 'Gula dapat meningkatkan kadar gula darah'],
            [2,  1,  9,  'batasi',  'Nasi putih memiliki indeks glikemik tinggi'],
            [3,  2,  25, 'batasi',  'Garam dapat meningkatkan tekanan darah'],
            [4,  2,  28, 'batasi',  'Terasi mengandung natrium tinggi'],
            [5,  3,  23, 'batasi',  'Santan mengandung lemak jenuh'],
            [6,  3,  24, 'batasi',  'Minyak goreng dapat meningkatkan kolesterol'],
            [7,  4,  3,  'hindari', 'Ikan lele dapat meningkatkan asam urat'],
            [8,  4,  8,  'batasi',  'Udang mengandung purin'],
            [9,  5,  19, 'batasi',  'Cabai dapat mengiritasi lambung'],
            [10, 5,  20, 'batasi',  'Jahe dapat memicu asam lambung'],
            [11, 6,  23, 'batasi',  'Lemak jenuh tidak baik untuk jantung'],
            [12, 6,  25, 'batasi',  'Garam berlebihan tidak baik untuk jantung'],
            [13, 7,  25, 'hindari', 'Garam membebani kerja ginjal'],
            [14, 7,  2,  'batasi',  'Protein berlebihan membebani ginjal'],
            [15, 8,  24, 'hindari', 'Minyak goreng membebani hati'],
            [16, 8,  23, 'batasi',  'Santan dapat membebani hati'],
            [17, 9,  3,  'hindari', 'Ikan dapat memicu alergi'],
            [18, 9,  4,  'hindari', 'Salmon dapat memicu alergi'],
            [19, 9,  8,  'hindari', 'Udang dapat memicu alergi'],
            [20, 10, 6,  'hindari', 'Tahu dari kedelai'],
            [21, 10, 7,  'hindari', 'Tempe dari kedelai'],
            [22, 11, 9,  'batasi',  'Karbohidrat tinggi dapat menambah berat'],
            [23, 11, 24, 'batasi',  'Minyak tinggi kalori'],
            [24, 12, 2,  'anjuran', 'Daging merah mengandung zat besi'],
            [25, 12, 13, 'anjuran', 'Bayam kaya zat besi'],
            [26, 13, 6,  'anjuran', 'Tahu mengandung kalsium'],
            [27, 13, 5,  'anjuran', 'Telur mengandung kalsium'],
            [28, 14, 19, 'hindari', 'Cabai dapat memicu asma'],
            [29, 14, 28, 'batasi',  'Terasi dapat memicu alergi pernapasan'],
            [30, 15, 19, 'hindari', 'Pedas memicu refluks'],
            [31, 15, 23, 'batasi',  'Santan dapat memicu refluks'],
            [32, 20, 19, 'hindari', 'Pedas tidak baik untuk ibu hamil'],
            [33, 20, 3,  'batasi',  'Ikan tertentu harus dibatasi'],
        ];

        foreach ($data as [$id, $hcid, $ingid, $severity, $notes]) {
            DB::table('health_condition_restrictions')->insert([
                'id'                   => $id,
                'health_condition_id'  => $hcid,
                'ingredient_id'        => $ingid,
                'severity'             => $severity,
                'notes'                => $notes,
                'created_at'           => '2026-02-28 19:41:02',
                'updated_at'           => '2026-02-28 19:41:02',
            ]);
        }
    }
}
