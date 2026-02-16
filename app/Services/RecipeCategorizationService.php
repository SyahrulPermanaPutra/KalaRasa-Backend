<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\HealthCondition;
use App\Models\HealthConditionRestriction;
use App\Services\RecipeCategorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeCategorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $categorizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categorizationService = new RecipeCategorizationService();
        $this->seedTestData();
    }

    protected function seedTestData()
    {
        // Create test ingredients
        $this->gulaPasir = Ingredient::create([
            'nama' => 'Gula Pasir',
            'kategori' => 'penyedap',
            'sub_kategori' => 'manis',
        ]);

        $this->cabai = Ingredient::create([
            'nama' => 'Cabai Merah',
            'kategori' => 'bumbu',
            'sub_kategori' => 'pedas',
        ]);

        $this->garam = Ingredient::create([
            'nama' => 'Garam',
            'kategori' => 'penyedap',
            'sub_kategori' => 'asin',
        ]);

        // Create health conditions
        $this->diabetes = HealthCondition::create([
            'nama' => 'Diabetes',
            'description' => 'Kondisi gula darah tinggi',
        ]);

        // Create restrictions
        HealthConditionRestriction::create([
            'health_condition_id' => $this->diabetes->id,
            'ingredient_id' => $this->gulaPasir->id,
            'severity' => 'hindari',
            'notes' => 'Tinggi gula',
        ]);

        // Create taste profiles
        TasteProfile::create(['nama' => 'pedas']);
        TasteProfile::create(['nama' => 'manis']);
        TasteProfile::create(['nama' => 'asin']);

        // Create cooking methods
        CookingMethod::create(['nama' => 'goreng', 'kategori' => 'panas_kering']);
        CookingMethod::create(['nama' => 'tumis', 'kategori' => 'panas_kering']);
    }

    /** @test */
    public function it_detects_unsuitable_recipe_for_diabetes()
    {
        // Create recipe
        $recipe = Recipe::create([
            'nama' => 'Kue Manis',
            'deskripsi' => 'Kue dengan gula',
            'waktu_masak' => 30,
            'tingkat_kesulitan' => 'mudah',
            'kalori_per_porsi' => 200,
        ]);

        // Add ingredients with sugar
        $ingredientIds = [$this->gulaPasir->id];
        $recipe->ingredients()->attach($ingredientIds);

        // Run categorization
        $this->categorizationService->categorizeRecipe($recipe, $ingredientIds);

        // Assert recipe is not suitable for diabetes
        $suitability = $recipe->suitabilities()
            ->where('health_condition_id', $this->diabetes->id)
            ->first();

        $this->assertNotNull($suitability);
        $this->assertFalse($suitability->is_suitable);
        $this->assertStringContainsString('Gula Pasir', $suitability->reason);
    }

    /** @test */
    public function it_detects_suitable_recipe_for_diabetes()
    {
        // Create recipe without sugar
        $recipe = Recipe::create([
            'nama' => 'Tumis Sayur',
            'deskripsi' => 'Sayur tanpa gula',
            'waktu_masak' => 15,
            'tingkat_kesulitan' => 'mudah',
            'kalori_per_porsi' => 100,
        ]);

        // Add ingredients without sugar
        $ingredientIds = [$this->garam->id];
        $recipe->ingredients()->attach($ingredientIds);

        // Run categorization
        $this->categorizationService->categorizeRecipe($recipe, $ingredientIds);

        // Assert recipe is suitable for diabetes
        $suitability = $recipe->suitabilities()
            ->where('health_condition_id', $this->diabetes->id)
            ->first();

        $this->assertNotNull($suitability);
        $this->assertTrue($suitability->is_suitable);
    }

    /** @test */
    public function it_detects_pedas_taste_profile()
    {
        // Create recipe with spicy ingredient
        $recipe = Recipe::create([
            'nama' => 'Sambal Goreng',
            'deskripsi' => 'Sambal pedas',
            'waktu_masak' => 20,
            'tingkat_kesulitan' => 'mudah',
            'kalori_per_porsi' => 150,
        ]);

        $ingredientIds = [$this->cabai->id];
        $recipe->ingredients()->attach($ingredientIds);

        // Run categorization
        $this->categorizationService->categorizeRecipe($recipe, $ingredientIds);

        // Assert pedas taste profile is attached
        $hasPedas = $recipe->tasteProfiles()
            ->where('nama', 'pedas')
            ->exists();

        $this->assertTrue($hasPedas);
    }

    /** @test */
    public function it_detects_manis_taste_profile()
    {
        // Create recipe with sweet ingredient
        $recipe = Recipe::create([
            'nama' => 'Kue',
            'deskripsi' => 'Kue manis',
            'waktu_masak' => 30,
            'tingkat_kesulitan' => 'sedang',
            'kalori_per_porsi' => 250,
        ]);

        $ingredientIds = [$this->gulaPasir->id];
        $recipe->ingredients()->attach($ingredientIds);

        // Run categorization
        $this->categorizationService->categorizeRecipe($recipe, $ingredientIds);

        // Assert manis taste profile is attached
        $hasManis = $recipe->tasteProfiles()
            ->where('nama', 'manis')
            ->exists();

        $this->assertTrue($hasManis);
    }

    /** @test */
    public function it_detects_multiple_taste_profiles()
    {
        // Create recipe with multiple taste ingredients
        $recipe = Recipe::create([
            'nama' => 'Rendang',
            'deskripsi' => 'Rendang pedas manis',
            'waktu_masak' => 120,
            'tingkat_kesulitan' => 'sulit',
            'kalori_per_porsi' => 400,
        ]);

        $ingredientIds = [$this->cabai->id, $this->gulaPasir->id, $this->garam->id];
        $recipe->ingredients()->attach($ingredientIds);

        // Run categorization
        $this->categorizationService->categorizeRecipe($recipe, $ingredientIds);

        // Assert multiple taste profiles
        $tasteNames = $recipe->tasteProfiles()->pluck('nama')->toArray();

        $this->assertContains('pedas', $tasteNames);
        $this->assertContains('manis', $tasteNames);
        $this->assertContains('asin', $tasteNames);
    }

    /** @test */
    public function it_detects_cooking_method_based_on_cooking_time()
    {
        // Recipe with short cooking time
        $recipe = Recipe::create([
            'nama' => 'Tumis Cepat',
            'deskripsi' => 'Tumis cepat saji',
            'waktu_masak' => 10, // Quick cooking
            'tingkat_kesulitan' => 'mudah',
            'kalori_per_porsi' => 150,
        ]);

        $ingredientIds = [$this->garam->id];
        $recipe->ingredients()->attach($ingredientIds);

        // Run categorization
        $this->categorizationService->categorizeRecipe($recipe, $ingredientIds);

        // Assert quick cooking methods (tumis/goreng) are detected
        $methodNames = $recipe->cookingMethods()->pluck('nama')->toArray();

        $this->assertTrue(
            in_array('tumis', $methodNames) || in_array('goreng', $methodNames),
            'Should detect quick cooking methods for recipes under 15 minutes'
        );
    }

    /** @test */
    public function it_can_recategorize_existing_recipe()
    {
        // Create recipe with initial ingredients
        $recipe = Recipe::create([
            'nama' => 'Test Recipe',
            'deskripsi' => 'Initial recipe',
            'waktu_masak' => 20,
            'tingkat_kesulitan' => 'mudah',
            'kalori_per_porsi' => 200,
        ]);

        $recipe->ingredients()->attach([$this->garam->id]);

        // Initial categorization
        $this->categorizationService->categorizeRecipe($recipe, [$this->garam->id]);

        // Update ingredients to include sugar
        $recipe->ingredients()->sync([$this->garam->id, $this->gulaPasir->id]);

        // Recategorize
        $this->categorizationService->recategorizeRecipe($recipe);

        // Assert new categorization includes diabetes restriction
        $suitability = $recipe->suitabilities()
            ->where('health_condition_id', $this->diabetes->id)
            ->first();

        $this->assertNotNull($suitability);
        $this->assertFalse($suitability->is_suitable);
    }

    /** @test */
    public function it_handles_recipe_with_no_ingredients()
    {
        // Create recipe without ingredients
        $recipe = Recipe::create([
            'nama' => 'Empty Recipe',
            'deskripsi' => 'Recipe without ingredients',
            'waktu_masak' => 10,
            'tingkat_kesulitan' => 'mudah',
            'kalori_per_porsi' => 0,
        ]);

        // Try categorization with empty array
        $this->categorizationService->categorizeRecipe($recipe, []);

        // Should not throw error
        // Taste profiles should be empty
        $this->assertEquals(0, $recipe->tasteProfiles()->count());
    }

    /** @test */
    public function it_handles_multiple_health_conditions()
    {
        // Create additional health condition
        $hipertensi = HealthCondition::create([
            'nama' => 'Hipertensi',
            'description' => 'Tekanan darah tinggi',
        ]);

        HealthConditionRestriction::create([
            'health_condition_id' => $hipertensi->id,
            'ingredient_id' => $this->garam->id,
            'severity' => 'batasi',
            'notes' => 'Tinggi sodium',
        ]);

        // Create recipe with both restricted ingredients
        $recipe = Recipe::create([
            'nama' => 'Kue Asin',
            'deskripsi' => 'Kue dengan gula dan garam',
            'waktu_masak' => 30,
            'tingkat_kesulitan' => 'sedang',
            'kalori_per_porsi' => 300,
        ]);

        $ingredientIds = [$this->gulaPasir->id, $this->garam->id];
        $recipe->ingredients()->attach($ingredientIds);

        // Run categorization
        $this->categorizationService->categorizeRecipe($recipe, $ingredientIds);

        // Check both health conditions
        $diabetesSuitability = $recipe->suitabilities()
            ->where('health_condition_id', $this->diabetes->id)
            ->first();

        $hipertensiSuitability = $recipe->suitabilities()
            ->where('health_condition_id', $hipertensi->id)
            ->first();

        $this->assertFalse($diabetesSuitability->is_suitable);
        $this->assertTrue($hipertensiSuitability->is_suitable); // batasi, not hindari
    }
}