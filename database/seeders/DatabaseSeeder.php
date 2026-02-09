<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CookingMethodSeeder::class,
            IngredientSeeder::class,
            TasteProfileSeeder::class,
            HealthConditionSeeder::class,
            HealthConditionRestrictionSeeder::class,
            RecipeSeeder::class,
        ]);
    }
}