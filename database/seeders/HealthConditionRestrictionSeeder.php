<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthConditionRestrictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Diabetes restrictions
        $this->createRestrictions('diabetes', [
            'gula', 'gula merah', 'kecap manis', 'nasi', 'beras putih', 'tepung terigu'
        ], 'hindari');

        // Kolesterol restrictions
        $this->createRestrictions('kolesterol', [
            'santan', 'mentega', 'margarin'
        ], 'hindari');

        // Asam urat restrictions
        $this->createRestrictions('asam_urat', [
            'bayam', 'kangkung'
        ], 'hindari');

        // Hipertensi restrictions
        $this->createRestrictions('hipertensi', [
            'garam', 'kecap asin', 'saus tiram', 'terasi'
        ], 'hindari');

        // Vegetarian restrictions
        $vegetarianHealthConditionId = DB::table('health_conditions')
            ->where('nama', 'vegetarian')
            ->value('id');

        $meatIngredients = DB::table('ingredients')
            ->where('kategori', 'protein')
            ->whereIn('sub_kategori', ['daging', 'seafood'])
            ->pluck('id');

        foreach ($meatIngredients as $ingredientId) {
            DB::table('health_condition_restrictions')->insert([
                'health_condition_id' => $vegetarianHealthConditionId,
                'ingredient_id' => $ingredientId,
                'severity' => 'hindari',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Helper method to create restrictions
     */
    private function createRestrictions(string $healthCondition, array $ingredients, string $severity): void
    {
        $healthConditionId = DB::table('health_conditions')
            ->where('nama', $healthCondition)
            ->value('id');

        foreach ($ingredients as $ingredientName) {
            $ingredientId = DB::table('ingredients')
                ->where('nama', $ingredientName)
                ->value('id');

            if ($ingredientId) {
                DB::table('health_condition_restrictions')->insert([
                    'health_condition_id' => $healthConditionId,
                    'ingredient_id' => $ingredientId,
                    'severity' => $severity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}