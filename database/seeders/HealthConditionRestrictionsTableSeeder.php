<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HealthConditionRestrictionsTableSeeder extends Seeder
{
    public function run()
    {
        $restrictions = [
            ['health_condition_id' => 1, 'ingredient_id' => 26, 'severity' => 'hindari', 'notes' => 'Gula dapat meningkatkan kadar gula darah'],
            ['health_condition_id' => 1, 'ingredient_id' => 9, 'severity' => 'batasi', 'notes' => 'Nasi putih memiliki indeks glikemik tinggi'],
            ['health_condition_id' => 2, 'ingredient_id' => 25, 'severity' => 'batasi', 'notes' => 'Garam dapat meningkatkan tekanan darah'],
            ['health_condition_id' => 2, 'ingredient_id' => 28, 'severity' => 'batasi', 'notes' => 'Terasi mengandung natrium tinggi'],
            ['health_condition_id' => 3, 'ingredient_id' => 23, 'severity' => 'batasi', 'notes' => 'Santan mengandung lemak jenuh'],
            ['health_condition_id' => 3, 'ingredient_id' => 24, 'severity' => 'batasi', 'notes' => 'Minyak goreng dapat meningkatkan kolesterol'],
            ['health_condition_id' => 4, 'ingredient_id' => 3, 'severity' => 'hindari', 'notes' => 'Ikan lele dapat meningkatkan asam urat'],
            ['health_condition_id' => 4, 'ingredient_id' => 8, 'severity' => 'batasi', 'notes' => 'Udang mengandung purin'],
            ['health_condition_id' => 5, 'ingredient_id' => 19, 'severity' => 'batasi', 'notes' => 'Cabai dapat mengiritasi lambung'],
            ['health_condition_id' => 5, 'ingredient_id' => 20, 'severity' => 'batasi', 'notes' => 'Jahe dapat memicu asam lambung'],
            ['health_condition_id' => 6, 'ingredient_id' => 23, 'severity' => 'batasi', 'notes' => 'Lemak jenuh tidak baik untuk jantung'],
            ['health_condition_id' => 6, 'ingredient_id' => 25, 'severity' => 'batasi', 'notes' => 'Garam berlebihan tidak baik untuk jantung'],
            ['health_condition_id' => 7, 'ingredient_id' => 25, 'severity' => 'hindari', 'notes' => 'Garam membebani kerja ginjal'],
            ['health_condition_id' => 7, 'ingredient_id' => 2, 'severity' => 'batasi', 'notes' => 'Protein berlebihan membebani ginjal'],
            ['health_condition_id' => 8, 'ingredient_id' => 24, 'severity' => 'hindari', 'notes' => 'Minyak goreng membebani hati'],
            ['health_condition_id' => 8, 'ingredient_id' => 23, 'severity' => 'batasi', 'notes' => 'Santan dapat membebani hati'],
            ['health_condition_id' => 9, 'ingredient_id' => 3, 'severity' => 'hindari', 'notes' => 'Ikan dapat memicu alergi'],
            ['health_condition_id' => 9, 'ingredient_id' => 4, 'severity' => 'hindari', 'notes' => 'Salmon dapat memicu alergi'],
            ['health_condition_id' => 9, 'ingredient_id' => 8, 'severity' => 'hindari', 'notes' => 'Udang dapat memicu alergi'],
            ['health_condition_id' => 10, 'ingredient_id' => 6, 'severity' => 'hindari', 'notes' => 'Tahu dari kedelai'],
            ['health_condition_id' => 10, 'ingredient_id' => 7, 'severity' => 'hindari', 'notes' => 'Tempe dari kedelai'],
            ['health_condition_id' => 11, 'ingredient_id' => 9, 'severity' => 'batasi', 'notes' => 'Karbohidrat tinggi dapat menambah berat'],
            ['health_condition_id' => 11, 'ingredient_id' => 24, 'severity' => 'batasi', 'notes' => 'Minyak tinggi kalori'],
            ['health_condition_id' => 12, 'ingredient_id' => 2, 'severity' => 'anjuran', 'notes' => 'Daging merah mengandung zat besi'],
            ['health_condition_id' => 12, 'ingredient_id' => 13, 'severity' => 'anjuran', 'notes' => 'Bayam kaya zat besi'],
            ['health_condition_id' => 13, 'ingredient_id' => 6, 'severity' => 'anjuran', 'notes' => 'Tahu mengandung kalsium'],
            ['health_condition_id' => 13, 'ingredient_id' => 5, 'severity' => 'anjuran', 'notes' => 'Telur mengandung kalsium'],
            ['health_condition_id' => 14, 'ingredient_id' => 19, 'severity' => 'hindari', 'notes' => 'Cabai dapat memicu asma'],
            ['health_condition_id' => 14, 'ingredient_id' => 28, 'severity' => 'batasi', 'notes' => 'Terasi dapat memicu alergi pernapasan'],
            ['health_condition_id' => 15, 'ingredient_id' => 19, 'severity' => 'hindari', 'notes' => 'Pedas memicu refluks'],
            ['health_condition_id' => 15, 'ingredient_id' => 23, 'severity' => 'batasi', 'notes' => 'Santan dapat memicu refluks'],
            ['health_condition_id' => 20, 'ingredient_id' => 19, 'severity' => 'hindari', 'notes' => 'Pedas tidak baik untuk ibu hamil'],
            ['health_condition_id' => 20, 'ingredient_id' => 3, 'severity' => 'batasi', 'notes' => 'Ikan tertentu harus dibatasi'],
        ];

        foreach ($restrictions as $r) {
            \App\Models\HealthConditionRestriction::create([
                'health_condition_id' => $r['health_condition_id'],
                'ingredient_id' => $r['ingredient_id'],
                'severity' => $r['severity'],
                'notes' => $r['notes'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}