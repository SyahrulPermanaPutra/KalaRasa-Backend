<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeSuitabilitySeeder extends Seeder
{
    public function run(): void
    {
        // [id, recipe_id, health_condition_id, is_suitable, notes]
        $data = [
            [1,  1,  1,  0, 'Mengandung nasi putih'],
            [2,  1,  2,  0, 'Kandungan garam tinggi'],
            [3,  2,  1,  1, 'Protein baik tanpa gula berlebih'],
            [4,  2,  3,  0, 'Mengandung madu'],
            [5,  3,  1,  1, 'Sayuran baik untuk diabetes'],
            [6,  3,  11, 1, 'Rendah kalori'],
            [7,  4,  5,  0, 'Kuah dapat memicu maag'],
            [8,  4,  12, 1, 'Kaya protein'],
            [9,  5,  3,  0, 'Mengandung santan'],
            [10, 5,  6,  0, 'Lemak jenuh tinggi'],
            [11, 6,  4,  0, 'Ikan air tawar'],
            [12, 6,  1,  1, 'Rendah karbohidrat'],
            [13, 7,  3,  0, 'Mengandung santan'],
            [14, 7,  2,  0, 'Kandungan garam'],
            [15, 8,  1,  0, 'Mie tinggi karbohidrat'],
            [16, 8,  11, 0, 'Kalori tinggi'],
            [17, 9,  1,  1, 'Sayuran segar'],
            [18, 9,  11, 1, 'Rendah kalori'],
            [19, 10, 9,  0, 'Mengandung ikan'],
            [20, 10, 1,  1, 'Protein tanpa karbo'],
            [21, 11, 1,  1, 'Sayuran hijau'],
            [22, 11, 11, 1, 'Rendah kalori'],
            [23, 12, 3,  0, 'Digoreng'],
            [24, 12, 11, 0, 'Kalori tinggi'],
            [25, 13, 3,  0, 'Mengandung santan'],
            [26, 13, 2,  0, 'Kandungan garam'],
            [27, 14, 11, 0, 'Digoreng'],
            [28, 14, 1,  0, 'Karbohidrat tinggi'],
            [29, 15, 3,  0, 'Mengandung kecap'],
            [30, 15, 2,  0, 'Kandungan natrium'],
            [31, 16, 11, 0, 'Digoreng'],
            [32, 16, 1,  0, 'Karbohidrat'],
            [33, 17, 3,  0, 'Mengandung santan'],
            [34, 17, 11, 0, 'Lemak tinggi'],
            [35, 18, 4,  0, 'Ikan lele'],
            [36, 18, 1,  1, 'Protein baik'],
            [37, 19, 1,  1, 'Sayuran segar'],
            [38, 19, 11, 1, 'Rendah kalori'],
            [39, 20, 3,  0, 'Bumbu kacang'],
            [40, 20, 1,  1, 'Protein tanpa karbo'],
        ];

        foreach ($data as [$id, $rid, $hcid, $suitable, $notes]) {
            DB::table('recipe_suitability')->insert([
                'id'                   => $id,
                'recipe_id'            => $rid,
                'health_condition_id'  => $hcid,
                'is_suitable'          => $suitable,
                'notes'                => $notes,
                'created_at'           => '2026-02-28 19:41:03',
                'updated_at'           => '2026-02-28 19:41:03',
            ]);
        }
    }
}
