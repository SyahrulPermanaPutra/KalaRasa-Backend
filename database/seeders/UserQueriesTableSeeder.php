<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserQueriesTableSeeder extends Seeder
{
    public function run()
    {
        $queries = [
            'Resep nasi goreng enak',
            'Masakan untuk diabetes',
            'Resep ayam bakar',
            'Makanan rendah kalori',
            'Resep soto ayam',
            'Masakan sehat untuk jantung',
            'Resep rendang daging',
            'Makanan untuk ibu hamil',
            'Resep ikan bakar',
            'Masakan tanpa santan',
            'Resep sayur asem',
            'Makanan untuk maag',
            'Resep gado-gado',
            'Masakan rendah garam',
            'Resep pepes ikan',
            'Makanan untuk kolesterol',
            'Resep mie goreng',
            'Masakan untuk asam urat',
            'Resep opor ayam',
            'Makanan untuk hipertensi',
        ];

        for ($i = 0; $i < 20; $i++) {
            \App\Models\UserQuery::create([
                'user_id' => ($i % 20) + 2,
                'query_text' => $queries[$i],
                'intent' => 'search_recipe',
                'confidence' => rand(70, 99) / 100,
                'status' => 'ok',
                'entities' => json_encode(['type' => 'recipe', 'category' => 'main']),
                'created_at' => Carbon::now()->subDays($i),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}