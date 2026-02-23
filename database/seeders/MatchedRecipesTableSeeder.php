<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\UserQuery;
use App\Models\Recipe;

class MatchedRecipesTableSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua user_query_id yang ada
        $userQueryIds = UserQuery::pluck('id')->toArray();
        $recipeIds = Recipe::pluck('id')->toArray();
        
        if (empty($userQueryIds)) {
            $this->command->error('Tidak ada user queries ditemukan! Jalankan UserQueriesTableSeeder dulu.');
            return;
        }
        
        if (empty($recipeIds)) {
            $this->command->error('Tidak ada recipes ditemukan!');
            return;
        }
        
        $totalMatched = 0;
        
        // Buat 3-5 matched recipes untuk setiap user query
        foreach ($userQueryIds as $queryId) {
            $matchCount = rand(3, 5);
            
            // Pilih recipe acak untuk query ini
            $selectedRecipeIds = array_rand(array_flip($recipeIds), min($matchCount, count($recipeIds)));
            
            if (!is_array($selectedRecipeIds)) {
                $selectedRecipeIds = [$selectedRecipeIds];
            }
            
            $rank = 1;
            foreach ($selectedRecipeIds as $recipeId) {
                // Hitung match score (makin tinggi rank, makin tinggi score)
                $matchScore = rand(85, 99); // Score tinggi untuk rank 1
                if ($rank > 1) {
                    $matchScore = rand(70, 84); // Score lebih rendah untuk rank berikutnya
                }
                
                \App\Models\MatchedRecipe::create([
                    'user_query_id' => $queryId,
                    'recipe_id' => $recipeId,
                    'match_score' => $matchScore,
                    'rank_position' => $rank,
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now(),
                ]);
                
                $rank++;
                $totalMatched++;
            }
        }
        
        $this->command->info("Berhasil membuat {$totalMatched} data matched recipes dari " . count($userQueryIds) . " user queries!");
    }
}