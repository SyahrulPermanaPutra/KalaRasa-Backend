<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Database: kala_rasa_jtv
     *
     * Urutan seeding mengikuti dependency foreign key:
     * 1. roles              → tidak ada dependency
     * 2. users              → roles
     * 3. health_conditions  → tidak ada dependency
     * 4. ingredients        → tidak ada dependency
     * 5. health_condition_restrictions → health_conditions, ingredients
     * 6. recipes            → users
     * 7. recipe_ingredients → recipes, ingredients
     * 8. recipe_suitability → recipes, health_conditions
     * 9. bookmarks          → users, recipes
     * 10. shopping_lists    → users, recipes
     * 11. shopping_list_items → shopping_lists
     * 12. expenses          → users, shopping_lists, shopping_list_items
     * 13. user_queries      → users
     * 14. matched_recipes   → user_queries, recipes
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            HealthConditionSeeder::class,
            IngredientSeeder::class,
            HealthConditionRestrictionSeeder::class,
            RecipeSeeder::class,
            RecipeIngredientSeeder::class,
            RecipeSuitabilitySeeder::class,
            BookmarkSeeder::class,
            ShoppingListSeeder::class,
            ShoppingListItemSeeder::class,
            ExpenseSeeder::class,
            UserQuerySeeder::class,
            MatchedRecipeSeeder::class,
        ]);
    }
}
