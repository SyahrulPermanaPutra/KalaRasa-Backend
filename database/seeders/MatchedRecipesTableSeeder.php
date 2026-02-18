<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MatchedRecipesTableSeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 20; $i++) {
            \App\Models\MatchedRecipe::create([
                'user_query_id' => $i,
                'recipe_id' => ($i % 20) + 1,
                'match_score' => rand(70, 99) / 100 * 100,
                'rank_position' => $i,
                'created_at' => Carbon::now()->subDays($i),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}