<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UsersTableSeeder::class,
            IngredientsTableSeeder::class,
            RecipesTableSeeder::class,
            RecipeIngredientsTableSeeder::class,
            HealthConditionsTableSeeder::class,
            HealthConditionRestrictionsTableSeeder::class,
            RecipeSuitabilityTableSeeder::class,
            // FavoriteRecipesTableSeeder::class,
            ShoppingListsTableSeeder::class,
            // ShoppingListItemsTableSeeder::class,
            ExpensesTableSeeder::class,
            UserQueriesTableSeeder::class,
            MatchedRecipesTableSeeder::class,
        ]);
    }
}