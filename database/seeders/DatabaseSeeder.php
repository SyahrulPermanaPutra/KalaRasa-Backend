<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesTableSeeder::class,
            UsersTableSeeder::class,
            IngredientsTableSeeder::class,
            RecipesTableSeeder::class,
            RecipeIngredientsTableSeeder::class,
            HealthConditionsTableSeeder::class,
            HealthConditionRestrictionsTableSeeder::class,
            RecipeSuitabilityTableSeeder::class,
            BookmarkSeeder::class,
            ShoppingListsTableSeeder::class,
            ShoppingListItemsTableSeeder::class,
            ExpensesTableSeeder::class,
            userqueriesTableSeeder::class,
            MatchedRecipesTableSeeder::class,
        ]);
    }
}