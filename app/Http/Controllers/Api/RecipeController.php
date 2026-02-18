<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecipeRequest;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Services\RecipeClassificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    protected RecipeClassificationService $classificationService;

    public function __construct(RecipeClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
        
        // Initialize dengan fallback aman (tidak akan crash jika config null)
        try {
            $this->classificationService
                ->setIngredientMapping(config('recipe_mappings.ingredients', null))
                ->setIngredientAliases(config('recipe_mappings.aliases', null))
                ->setHealthRestrictions(config('recipe_mappings.health_restrictions', null));
            
            // Log status initialization
            if (!$this->classificationService->isMappingLoaded()) {
                Log::warning('RecipeClassificationService initialized with fallback mode.', [
                    'context' => 'RecipeController::__construct',
                    'timestamp' => now()->toIso8601String()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to initialize RecipeClassificationService.', [
                'error' => $e->getMessage(),
                'context' => 'RecipeController::__construct'
            ]);
            // Service tetap bisa digunakan dengan fallback default
        }
    }

    public function index(Request $request)
    {
        $query = Recipe::approved()->with(['creator']);
        
        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        
        $recipes = $query->paginate($request->input('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }

    public function store(StoreRecipeRequest $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                // 1. Handle Upload Gambar (Max 1MB)
                $gambarPath = null;
                if ($request->hasFile('gambar')) {
                    $file = $request->file('gambar');
                    
                    // Validasi ukuran file (double check)
                    if ($file->getSize() > 1024 * 1024) { // 1MB in bytes
                        throw new \Exception('Ukuran gambar tidak boleh lebih dari 1MB');
                    }
                    
                    // Generate unique filename
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $gambarPath = $file->storeAs('recipes', $filename, 'public');
                }

                // 2. Simpan Resep Dasar
                $recipe = Recipe::create([
                    'nama' => $request->nama,
                    'waktu_masak' => $request->waktu_masak,
                    'region' => $request->region,
                    'deskripsi' => $request->deskripsi,
                    'gambar' => $gambarPath,
                    'kategori' => $request->kategori, // Akan di-update oleh classifier jika perlu
                    'status' => 'pending',
                    'created_by' => $request->user()->id,
                    'avg_rating' => 0.00,
                    'total_ratings' => 0,
                    'view_count' => 0,
                ]);

                // 3. Proses Bahan-bahan dari Input User
                $ingredientIds = [];
                $fallbackIngredients = [];

                foreach ($request->bahan_bahan as $bahan) {
                    // Normalisasi nama bahan
                    $normalizedNama = $this->classificationService->normalize($bahan['nama']);
                    
                    // Cari atau buat ingredient dengan klasifikasi otomatis
                    $ingredient = $this->classificationService->findOrCreateIngredient($normalizedNama);
                    
                    // Track ingredients yang menggunakan fallback classification
                    if ($ingredient->sub_kategori === 'umum' || $ingredient->sub_kategori === null) {
                        $fallbackIngredients[] = $ingredient->nama;
                    }

                    // Tentukan apakah bahan utama
                    $isMain = $this->classificationService->isMainIngredient($normalizedNama);

                    // Attach ke recipe_ingredients pivot table
                    $recipe->ingredients()->attach($ingredient->id, [
                        'is_main' => $isMain,
                        'jumlah' => $bahan['jumlah'],
                        'satuan' => $bahan['satuan'],
                    ]);

                    $ingredientIds[] = $ingredient->id;
                }

                // 4. Log warning untuk ingredients yang menggunakan fallback
                if (!empty($fallbackIngredients)) {
                    Log::warning('Recipe created with fallback ingredient classifications', [
                        'recipe_id' => $recipe->id,
                        'recipe_name' => $recipe->nama,
                        'fallback_ingredients' => $fallbackIngredients,
                        'count' => count($fallbackIngredients),
                    ]);
                }

                // 5. Generate Recipe Suitability (Kesehatan)
                if (!empty($ingredientIds)) {
                    $this->classificationService->generateRecipeSuitability($recipe->id, $ingredientIds);
                }

                // 6. Simpan Langkah-langkah (jika ada)
                if ($request->filled('langkah_langkah') && !empty($request->langkah_langkah)) {
                    $stepsText = implode("\n", array_map(
                        fn($index, $step) => ($index + 1) . ". " . trim($step),
                        array_keys($request->langkah_langkah),
                        $request->langkah_langkah
                    ));
                    
                    $recipe->deskripsi .= "\n\nLangkah-langkah:\n" . $stepsText;
                    $recipe->save();
                }

                return [
                    'recipe' => $recipe->fresh(['ingredients']),
                    'image_url' => $gambarPath ? Storage::url($gambarPath) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil disimpan dan diklasifikasi! Menunggu approval.',
                'data' => [
                    'recipe' => $result['recipe'],
                    'image_url' => $result['image_url'],
                    'fallback_count' => count($fallbackIngredients ?? []),
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to store recipe', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan resep: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function show(Request $request, $id)
    {
        $recipe = Recipe::approved()->with([
            'creator:id,name,email',
            'ingredients:id,nama,kategori,sub_kategori',
            'suitabilities.healthCondition'
        ])->findOrFail($id);
        
        $isFavorited = false;
        if ($request->user()) {
            $isFavorited = $recipe->favoritedBy()->where('user_id', $request->user()->id)->exists();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'recipe' => $recipe,
                'is_favorited' => $isFavorited
            ]
        ]);
    }

    public function addToShoppingList(Request $request, $id)
    {
        $recipe = Recipe::approved()->findOrFail($id);
        $user = $request->user();
        $addedItems = [];
        
        $shoppingList = ShoppingList::firstOrCreate(
            [
                'user_id' => $user->id,
                'recipe_id' => $recipe->id,
                'nama_list' => 'Belanja: ' . $recipe->nama
            ],
            [
                'status' => 'pending',
                'shopping_date' => now()->toDateString()
            ]
        );
        
        if ($recipe->ingredients()->exists()) {
            foreach ($recipe->ingredients as $ingredient) {
                $pivot = $ingredient->pivot;
                $shoppingItem = ShoppingListItem::create([
                    'shopping_list_id' => $shoppingList->id,
                    'ingredient_id' => $ingredient->id,
                    'nama_item' => $ingredient->nama,
                    'jumlah' => $pivot->jumlah ?? 1,
                    'satuan' => $pivot->satuan ?? 'pcs',
                    'catatan' => 'Dari resep: ' . $recipe->nama,
                ]);
                $addedItems[] = $shoppingItem;
            }
        } else {
            $bahanMakanan = $recipe->bahan_makanan ?? [];
            foreach ($bahanMakanan as $bahan) {
                if (is_array($bahan)) {
                    $namaItem = $bahan['nama'] ?? $bahan[0] ?? 'Unknown';
                    $jumlah = $bahan['jumlah'] ?? $bahan[1] ?? 1;
                    $satuan = $bahan['satuan'] ?? $bahan[2] ?? 'pcs';
                } else {
                    $namaItem = $bahan;
                    $jumlah = 1;
                    $satuan = 'pcs';
                }
                $shoppingItem = ShoppingListItem::create([
                    'shopping_list_id' => $shoppingList->id,
                    'ingredient_id' => null,
                    'nama_item' => $namaItem,
                    'jumlah' => $jumlah,
                    'satuan' => $satuan,
                    'catatan' => 'Dari resep: ' . $recipe->nama,
                ]);
                $addedItems[] = $shoppingItem;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Bahan makanan berhasil ditambahkan ke daftar belanja',
            'data' => $addedItems
        ]);
    }

    public function toggleFavorite(Request $request, $id)
    {
        $recipe = Recipe::approved()->findOrFail($id);
        $user = $request->user();
        
        if ($user->favoriteRecipes()->where('recipe_id', $id)->exists()) {
            $user->favoriteRecipes()->detach($id);
            $message = 'Resep dihapus dari favorit';
            $isFavorited = false;
        } else {
            $user->favoriteRecipes()->attach($id);
            $message = 'Resep ditambahkan ke favorit';
            $isFavorited = true;
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'is_favorited' => $isFavorited,
                'total_favorites' => $recipe->favoritedBy()->count()
            ]
        ]);
    }

    public function myFavorites(Request $request)
    {
        $favorites = $request->user()
            ->favoriteRecipes()
            ->with(['creator'])
            ->orderBy('favorite_recipes.created_at', 'desc')
            ->paginate($request->input('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }
}