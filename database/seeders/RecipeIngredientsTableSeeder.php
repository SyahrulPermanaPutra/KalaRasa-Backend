<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RecipeIngredientsTableSeeder extends Seeder
{
    public function run()
    {
        $recipeIngredients = [
            ['recipe_id' => 1, 'ingredient_id' => 9, 'is_main' => 1, 'jumlah' => 200, 'satuan' => 'gram'],
            ['recipe_id' => 1, 'ingredient_id' => 5, 'is_main' => 1, 'jumlah' => 2, 'satuan' => 'butir'],
            ['recipe_id' => 1, 'ingredient_id' => 17, 'is_main' => 0, 'jumlah' => 5, 'satuan' => 'siung'],
            ['recipe_id' => 1, 'ingredient_id' => 18, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'siung'],
            ['recipe_id' => 2, 'ingredient_id' => 1, 'is_main' => 1, 'jumlah' => 500, 'satuan' => 'gram'],
            ['recipe_id' => 2, 'ingredient_id' => 26, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'sdm'],
            ['recipe_id' => 2, 'ingredient_id' => 27, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'sdm'],
            ['recipe_id' => 3, 'ingredient_id' => 13, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 3, 'ingredient_id' => 14, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 3, 'ingredient_id' => 15, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 4, 'ingredient_id' => 1, 'is_main' => 1, 'jumlah' => 400, 'satuan' => 'gram'],
            ['recipe_id' => 4, 'ingredient_id' => 21, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'cm'],
            ['recipe_id' => 4, 'ingredient_id' => 17, 'is_main' => 0, 'jumlah' => 4, 'satuan' => 'siung'],
            ['recipe_id' => 5, 'ingredient_id' => 2, 'is_main' => 1, 'jumlah' => 500, 'satuan' => 'gram'],
            ['recipe_id' => 5, 'ingredient_id' => 23, 'is_main' => 0, 'jumlah' => 200, 'satuan' => 'ml'],
            ['recipe_id' => 5, 'ingredient_id' => 21, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'cm'],
            ['recipe_id' => 6, 'ingredient_id' => 3, 'is_main' => 1, 'jumlah' => 400, 'satuan' => 'gram'],
            ['recipe_id' => 6, 'ingredient_id' => 29, 'is_main' => 0, 'jumlah' => 5, 'satuan' => 'lembar'],
            ['recipe_id' => 6, 'ingredient_id' => 18, 'is_main' => 0, 'jumlah' => 4, 'satuan' => 'siung'],
            ['recipe_id' => 7, 'ingredient_id' => 2, 'is_main' => 1, 'jumlah' => 600, 'satuan' => 'gram'],
            ['recipe_id' => 7, 'ingredient_id' => 23, 'is_main' => 0, 'jumlah' => 250, 'satuan' => 'ml'],
            ['recipe_id' => 7, 'ingredient_id' => 20, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'cm'],
            ['recipe_id' => 8, 'ingredient_id' => 10, 'is_main' => 1, 'jumlah' => 300, 'satuan' => 'gram'],
            ['recipe_id' => 8, 'ingredient_id' => 5, 'is_main' => 1, 'jumlah' => 2, 'satuan' => 'butir'],
            ['recipe_id' => 8, 'ingredient_id' => 27, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'sdm'],
            ['recipe_id' => 9, 'ingredient_id' => 13, 'is_main' => 1, 'jumlah' => 150, 'satuan' => 'gram'],
            ['recipe_id' => 9, 'ingredient_id' => 15, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 9, 'ingredient_id' => 16, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 10, 'ingredient_id' => 4, 'is_main' => 1, 'jumlah' => 400, 'satuan' => 'gram'],
            ['recipe_id' => 10, 'ingredient_id' => 19, 'is_main' => 0, 'jumlah' => 5, 'satuan' => 'buah'],
            ['recipe_id' => 10, 'ingredient_id' => 24, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'sdm'],
            ['recipe_id' => 11, 'ingredient_id' => 14, 'is_main' => 1, 'jumlah' => 200, 'satuan' => 'gram'],
            ['recipe_id' => 11, 'ingredient_id' => 17, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'siung'],
            ['recipe_id' => 11, 'ingredient_id' => 18, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'siung'],
            ['recipe_id' => 12, 'ingredient_id' => 1, 'is_main' => 1, 'jumlah' => 500, 'satuan' => 'gram'],
            ['recipe_id' => 12, 'ingredient_id' => 24, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'sdm'],
            ['recipe_id' => 12, 'ingredient_id' => 18, 'is_main' => 0, 'jumlah' => 5, 'satuan' => 'siung'],
            ['recipe_id' => 13, 'ingredient_id' => 10, 'is_main' => 1, 'jumlah' => 250, 'satuan' => 'gram'],
            ['recipe_id' => 13, 'ingredient_id' => 23, 'is_main' => 0, 'jumlah' => 150, 'satuan' => 'ml'],
            ['recipe_id' => 13, 'ingredient_id' => 19, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'buah'],
            ['recipe_id' => 14, 'ingredient_id' => 11, 'is_main' => 1, 'jumlah' => 300, 'satuan' => 'gram'],
            ['recipe_id' => 14, 'ingredient_id' => 5, 'is_main' => 1, 'jumlah' => 1, 'satuan' => 'butir'],
            ['recipe_id' => 14, 'ingredient_id' => 17, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'siung'],
            ['recipe_id' => 15, 'ingredient_id' => 2, 'is_main' => 1, 'jumlah' => 400, 'satuan' => 'gram'],
            ['recipe_id' => 15, 'ingredient_id' => 27, 'is_main' => 0, 'jumlah' => 4, 'satuan' => 'sdm'],
            ['recipe_id' => 15, 'ingredient_id' => 18, 'is_main' => 0, 'jumlah' => 4, 'satuan' => 'siung'],
            ['recipe_id' => 16, 'ingredient_id' => 12, 'is_main' => 1, 'jumlah' => 200, 'satuan' => 'gram'],
            ['recipe_id' => 16, 'ingredient_id' => 5, 'is_main' => 1, 'jumlah' => 1, 'satuan' => 'butir'],
            ['recipe_id' => 16, 'ingredient_id' => 17, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'siung'],
            ['recipe_id' => 17, 'ingredient_id' => 1, 'is_main' => 1, 'jumlah' => 500, 'satuan' => 'gram'],
            ['recipe_id' => 17, 'ingredient_id' => 23, 'is_main' => 0, 'jumlah' => 200, 'satuan' => 'ml'],
            ['recipe_id' => 17, 'ingredient_id' => 21, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'cm'],
            ['recipe_id' => 18, 'ingredient_id' => 3, 'is_main' => 1, 'jumlah' => 400, 'satuan' => 'gram'],
            ['recipe_id' => 18, 'ingredient_id' => 28, 'is_main' => 0, 'jumlah' => 2, 'satuan' => 'sdm'],
            ['recipe_id' => 18, 'ingredient_id' => 19, 'is_main' => 0, 'jumlah' => 5, 'satuan' => 'buah'],
            ['recipe_id' => 19, 'ingredient_id' => 13, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 19, 'ingredient_id' => 14, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 19, 'ingredient_id' => 15, 'is_main' => 1, 'jumlah' => 100, 'satuan' => 'gram'],
            ['recipe_id' => 20, 'ingredient_id' => 1, 'is_main' => 1, 'jumlah' => 500, 'satuan' => 'gram'],
            ['recipe_id' => 20, 'ingredient_id' => 27, 'is_main' => 0, 'jumlah' => 3, 'satuan' => 'sdm'],
            ['recipe_id' => 20, 'ingredient_id' => 18, 'is_main' => 0, 'jumlah' => 4, 'satuan' => 'siung'],
        ];

        foreach ($recipeIngredients as $ri) {
            \App\Models\RecipeIngredient::create([
                'recipe_id' => $ri['recipe_id'],
                'ingredient_id' => $ri['ingredient_id'],
                'is_main' => $ri['is_main'],
                'jumlah' => $ri['jumlah'],
                'satuan' => $ri['satuan'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}