<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\role;
use App\Models\User;
use App\Models\HealthCondition;
use App\Models\Ingredient;
use App\Models\HealthConditionRestriction;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeSuitability;
use App\Models\RecipeRating;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $adminRole = role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $userRole = role::create(['name' => 'user', 'display_name' => 'Regular User']);

        // 2. Users
        $admin = User::create([
            'name' => 'Admin KalaRasa',
            'email' => 'admin@kalarasa.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'points' => 1000,
        ]);

        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'points' => 50,
            'gender' => 'Pria',
            'birthdate' => '1990-05-15',
        ]);

        // 3. Health Conditions
        $diabetes = HealthCondition::create([
            'nama' => 'Diabetes',
            'description' => 'Kondisi kadar gula darah tinggi.'
        ]);
        $hipertensi = HealthCondition::create([
            'nama' => 'Hipertensi',
            'description' => 'Tekanan darah tinggi.'
        ]);
        $asamUrat = HealthCondition::create([
            'nama' => 'Asam Urat',
            'description' => 'Kadar asam urat tinggi dalam sendi.'
        ]);

        // 4. Ingredients
        $ingredients = [
            ['nama' => 'Ayam Fillet', 'kategori' => 'protein'],
            ['nama' => 'Bayam', 'kategori' => 'sayuran'],
            ['nama' => 'Beras Merah', 'kategori' => 'karbohidrat'],
            ['nama' => 'Garam', 'kategori' => 'penyedap'],
            ['nama' => 'Gula Pasir', 'kategori' => 'penyedap'],
            ['nama' => 'Minyak Goreng', 'kategori' => 'lemak'],
            ['nama' => 'Bawang Putih', 'kategori' => 'bumbu'],
            ['nama' => 'Tempe', 'kategori' => 'protein'],
            ['nama' => 'Daging Sapi', 'kategori' => 'protein'],
            ['nama' => 'Ikan Salmon', 'kategori' => 'protein'],
        ];

        foreach ($ingredients as $ing) {
            Ingredient::create($ing);
        }

        // 5. Health Condition Restrictions
        // Diabetes: Batasi Gula
        HealthConditionRestriction::create([
            'health_condition_id' => $diabetes->id,
            'ingredient_id' => Ingredient::where('nama', 'Gula Pasir')->first()->id,
            'severity' => 'hindari',
            'notes' => 'Gula dapat melonjakkan kadar glukosa darah.'
        ]);

        // Hipertensi: Batasi Garam
        HealthConditionRestriction::create([
            'health_condition_id' => $hipertensi->id,
            'ingredient_id' => Ingredient::where('nama', 'Garam')->first()->id,
            'severity' => 'batasi',
            'notes' => 'Konsumsi garam berlebih memicu tekanan darah tinggi.'
        ]);

        // 6. Recipes
        $recipe1 = Recipe::create([
            'nama' => 'Sup Ayam Bayam Sehat',
            'waktu_masak' => 30,
            'region' => 'Nasional',
            'deskripsi' => 'Sup ayam dengan sayur bayam yang rendah lemak dan tinggi nutrisi.',
            'langkah_langkah' => "1. Rebus air sampai mendidih.\n2. Masukkan ayam fillet.\n3. Masukkan bawang putih geprek.\n4. Tambahkan bayam sebentar saja.\n5. Sajikan hangat.",
            'kategori' => 'Healthy',
            'status' => 'approved',
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'avg_rating' => 4.5,
            'total_ratings' => 1,
            'view_count' => 10,
        ]);

        $recipe2 = Recipe::create([
            'nama' => 'Salmon Panggang Lemon',
            'waktu_masak' => 20,
            'region' => 'Western',
            'deskripsi' => 'Salmon panggang dengan bumbu lemon yang segar dan sehat.',
            'langkah_langkah' => "1. Marinasi salmon dengan lemon.\n2. Masukkan ke dalam oven.\n3. Panggang 15 menit.\n4. Beri sedikit lada.",
            'kategori' => 'Diet',
            'status' => 'approved',
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'avg_rating' => 0,
            'total_ratings' => 0,
            'view_count' => 5,
        ]);

        // 7. Recipe Ingredients
        RecipeIngredient::create([
            'recipe_id' => $recipe1->id,
            'ingredient_id' => Ingredient::where('nama', 'Ayam Fillet')->first()->id,
            'is_main' => 1,
            'jumlah' => 250,
            'satuan' => 'gram'
        ]);

        RecipeIngredient::create([
            'recipe_id' => $recipe1->id,
            'ingredient_id' => Ingredient::where('nama', 'Bayam')->first()->id,
            'is_main' => 0,
            'jumlah' => 100,
            'satuan' => 'gram'
        ]);

        // 8. Recipe Suitability
        RecipeSuitability::create([
            'recipe_id' => $recipe1->id,
            'health_condition_id' => $diabetes->id,
            'is_suitable' => 1,
            'notes' => 'Sangat cocok untuk penderita diabetes karena rendah gula.'
        ]);

        // 9. Recipe Ratings
        RecipeRating::create([
            'recipe_id' => $recipe1->id,
            'user_id' => $user->id,
            'rating' => 5,
            'review' => 'Enak sekali dan sangat membantu diet saya!'
        ]);
        
        // 10. CBR Weight Snapshots (Initial weights)
        DB::table('cbr_weight_snapshots')->insert([
            'snapshot_type' => 'manual',
            'weights' => json_encode([
                'ingredient_weight' => 0.4,
                'health_suitability_weight' => 0.4,
                'rating_weight' => 0.1,
                'view_count_weight' => 0.1,
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
