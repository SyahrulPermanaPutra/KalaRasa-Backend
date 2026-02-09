<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Recipe 1: Ayam Goreng Kremes
        $recipeId = DB::table('recipes')->insertGetId([
            'nama' => 'Ayam Goreng Kremes',
            'tingkat_kesulitan' => 'mudah',
            'waktu_masak' => 45,
            'kalori_per_porsi' => 350,
            'region' => 'jawa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ingredients for Ayam Goreng Kremes
        $this->addRecipeIngredients($recipeId, [
            ['nama' => 'ayam', 'is_main' => true, 'jumlah' => '500 gram'],
            ['nama' => 'tepung terigu', 'is_main' => false, 'jumlah' => '100 gram'],
            ['nama' => 'bawang putih', 'is_main' => false, 'jumlah' => '3 siung'],
            ['nama' => 'garam', 'is_main' => false, 'jumlah' => 'secukupnya'],
        ]);

        // Cooking methods
        $this->addRecipeCookingMethods($recipeId, ['goreng']);

        // Taste profiles
        $this->addRecipeTasteProfiles($recipeId, ['gurih', 'asin']);

        // Suitability
        $this->addRecipeSuitability($recipeId, [
            'kolesterol' => false,
            'diet_rendah_kalori' => false,
        ]);

        // Recipe 2: Tumis Kangkung
        $recipeId = DB::table('recipes')->insertGetId([
            'nama' => 'Tumis Kangkung',
            'tingkat_kesulitan' => 'mudah',
            'waktu_masak' => 15,
            'kalori_per_porsi' => 80,
            'region' => 'umum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ingredients for Tumis Kangkung
        $this->addRecipeIngredients($recipeId, [
            ['nama' => 'kangkung', 'is_main' => true, 'jumlah' => '2 ikat'],
            ['nama' => 'bawang putih', 'is_main' => false, 'jumlah' => '3 siung'],
            ['nama' => 'cabai', 'is_main' => false, 'jumlah' => '2 buah'],
            ['nama' => 'terasi', 'is_main' => false, 'jumlah' => 'secukupnya'],
        ]);

        // Cooking methods
        $this->addRecipeCookingMethods($recipeId, ['tumis']);

        // Taste profiles
        $this->addRecipeTasteProfiles($recipeId, ['gurih', 'pedas']);

        // Suitability
        $this->addRecipeSuitability($recipeId, [
            'vegetarian' => true,
            'diet_rendah_kalori' => true,
            'diabetes' => true,
            'asam_urat' => false,
            'maag' => false,
        ]);

        // Recipe 3: Pepes Ikan
        $recipeId = DB::table('recipes')->insertGetId([
            'nama' => 'Pepes Ikan',
            'tingkat_kesulitan' => 'sedang',
            'waktu_masak' => 40,
            'kalori_per_porsi' => 180,
            'region' => 'sunda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ingredients for Pepes Ikan
        $this->addRecipeIngredients($recipeId, [
            ['nama' => 'ikan', 'is_main' => true, 'jumlah' => '500 gram'],
            ['nama' => 'bawang merah', 'is_main' => false, 'jumlah' => '4 siung'],
            ['nama' => 'bawang putih', 'is_main' => false, 'jumlah' => '2 siung'],
            ['nama' => 'cabai', 'is_main' => false, 'jumlah' => '5 buah'],
            ['nama' => 'tomat', 'is_main' => false, 'jumlah' => '1 buah'],
            ['nama' => 'kemangi', 'is_main' => false, 'jumlah' => 'secukupnya'],
        ]);

        // Cooking methods
        $this->addRecipeCookingMethods($recipeId, ['kukus', 'pepes']);

        // Taste profiles
        $this->addRecipeTasteProfiles($recipeId, ['gurih', 'segar']);

        // Suitability
        $this->addRecipeSuitability($recipeId, [
            'diet_rendah_kalori' => true,
            'diabetes' => true,
            'alergi_seafood' => false,
            'asam_urat' => false,
        ]);
    }

    /**
     * Helper method to add recipe ingredients
     */
    private function addRecipeIngredients(int $recipeId, array $ingredients): void
    {
        foreach ($ingredients as $ingredient) {
            $ingredientId = DB::table('ingredients')
                ->where('nama', $ingredient['nama'])
                ->value('id');

            if ($ingredientId) {
                DB::table('recipe_ingredients')->insert([
                    'recipe_id' => $recipeId,
                    'ingredient_id' => $ingredientId,
                    'is_main' => $ingredient['is_main'],
                    'jumlah' => $ingredient['jumlah'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Helper method to add recipe cooking methods
     */
    private function addRecipeCookingMethods(int $recipeId, array $methods): void
    {
        foreach ($methods as $methodName) {
            $methodId = DB::table('cooking_methods')
                ->where('nama', $methodName)
                ->value('id');

            if ($methodId) {
                DB::table('recipe_cooking_methods')->insert([
                    'recipe_id' => $recipeId,
                    'cooking_method_id' => $methodId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Helper method to add recipe taste profiles
     */
    private function addRecipeTasteProfiles(int $recipeId, array $tastes): void
    {
        foreach ($tastes as $tasteName) {
            $tasteId = DB::table('taste_profiles')
                ->where('nama', $tasteName)
                ->value('id');

            if ($tasteId) {
                DB::table('recipe_taste_profiles')->insert([
                    'recipe_id' => $recipeId,
                    'taste_profile_id' => $tasteId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Helper method to add recipe suitability
     */
    private function addRecipeSuitability(int $recipeId, array $suitabilities): void
    {
        foreach ($suitabilities as $conditionName => $isSuitable) {
            $conditionId = DB::table('health_conditions')
                ->where('nama', $conditionName)
                ->value('id');

            if ($conditionId) {
                DB::table('recipe_suitability')->insert([
                    'recipe_id' => $recipeId,
                    'health_condition_id' => $conditionId,
                    'is_suitable' => $isSuitable,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}