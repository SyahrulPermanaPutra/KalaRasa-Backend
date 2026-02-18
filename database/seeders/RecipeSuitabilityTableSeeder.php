<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RecipeSuitabilityTableSeeder extends Seeder
{
    public function run()
    {
        $suitability = [
            ['recipe_id' => 1, 'health_condition_id' => 1, 'is_suitable' => 0, 'notes' => 'Mengandung nasi putih'],
            ['recipe_id' => 1, 'health_condition_id' => 2, 'is_suitable' => 0, 'notes' => 'Kandungan garam tinggi'],
            ['recipe_id' => 2, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Protein baik tanpa gula berlebih'],
            ['recipe_id' => 2, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Mengandung madu'],
            ['recipe_id' => 3, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Sayuran baik untuk diabetes'],
            ['recipe_id' => 3, 'health_condition_id' => 11, 'is_suitable' => 1, 'notes' => 'Rendah kalori'],
            ['recipe_id' => 4, 'health_condition_id' => 5, 'is_suitable' => 0, 'notes' => 'Kuah dapat memicu maag'],
            ['recipe_id' => 4, 'health_condition_id' => 12, 'is_suitable' => 1, 'notes' => 'Kaya protein'],
            ['recipe_id' => 5, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Mengandung santan'],
            ['recipe_id' => 5, 'health_condition_id' => 6, 'is_suitable' => 0, 'notes' => 'Lemak jenuh tinggi'],
            ['recipe_id' => 6, 'health_condition_id' => 4, 'is_suitable' => 0, 'notes' => 'Ikan air tawar'],
            ['recipe_id' => 6, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Rendah karbohidrat'],
            ['recipe_id' => 7, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Mengandung santan'],
            ['recipe_id' => 7, 'health_condition_id' => 2, 'is_suitable' => 0, 'notes' => 'Kandungan garam'],
            ['recipe_id' => 8, 'health_condition_id' => 1, 'is_suitable' => 0, 'notes' => 'Mie tinggi karbohidrat'],
            ['recipe_id' => 8, 'health_condition_id' => 11, 'is_suitable' => 0, 'notes' => 'Kalori tinggi'],
            ['recipe_id' => 9, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Sayuran segar'],
            ['recipe_id' => 9, 'health_condition_id' => 11, 'is_suitable' => 1, 'notes' => 'Rendah kalori'],
            ['recipe_id' => 10, 'health_condition_id' => 9, 'is_suitable' => 0, 'notes' => 'Mengandung ikan'],
            ['recipe_id' => 10, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Protein tanpa karbo'],
            ['recipe_id' => 11, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Sayuran hijau'],
            ['recipe_id' => 11, 'health_condition_id' => 11, 'is_suitable' => 1, 'notes' => 'Rendah kalori'],
            ['recipe_id' => 12, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Digoreng'],
            ['recipe_id' => 12, 'health_condition_id' => 11, 'is_suitable' => 0, 'notes' => 'Kalori tinggi'],
            ['recipe_id' => 13, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Mengandung santan'],
            ['recipe_id' => 13, 'health_condition_id' => 2, 'is_suitable' => 0, 'notes' => 'Kandungan garam'],
            ['recipe_id' => 14, 'health_condition_id' => 11, 'is_suitable' => 0, 'notes' => 'Digoreng'],
            ['recipe_id' => 14, 'health_condition_id' => 1, 'is_suitable' => 0, 'notes' => 'Karbohidrat tinggi'],
            ['recipe_id' => 15, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Mengandung kecap'],
            ['recipe_id' => 15, 'health_condition_id' => 2, 'is_suitable' => 0, 'notes' => 'Kandungan natrium'],
            ['recipe_id' => 16, 'health_condition_id' => 11, 'is_suitable' => 0, 'notes' => 'Digoreng'],
            ['recipe_id' => 16, 'health_condition_id' => 1, 'is_suitable' => 0, 'notes' => 'Karbohidrat'],
            ['recipe_id' => 17, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Mengandung santan'],
            ['recipe_id' => 17, 'health_condition_id' => 11, 'is_suitable' => 0, 'notes' => 'Lemak tinggi'],
            ['recipe_id' => 18, 'health_condition_id' => 4, 'is_suitable' => 0, 'notes' => 'Ikan lele'],
            ['recipe_id' => 18, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Protein baik'],
            ['recipe_id' => 19, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Sayuran segar'],
            ['recipe_id' => 19, 'health_condition_id' => 11, 'is_suitable' => 1, 'notes' => 'Rendah kalori'],
            ['recipe_id' => 20, 'health_condition_id' => 3, 'is_suitable' => 0, 'notes' => 'Bumbu kacang'],
            ['recipe_id' => 20, 'health_condition_id' => 1, 'is_suitable' => 1, 'notes' => 'Protein tanpa karbo'],
        ];

        foreach ($suitability as $s) {
            \App\Models\RecipeSuitability::create([
                'recipe_id' => $s['recipe_id'],
                'health_condition_id' => $s['health_condition_id'],
                'is_suitable' => $s['is_suitable'],
                'notes' => $s['notes'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}