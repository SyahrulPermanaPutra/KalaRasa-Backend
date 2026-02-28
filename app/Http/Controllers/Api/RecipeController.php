<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecipeRequest;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Bookmark;
use App\Models\RecipeRating;
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
        
        try {
            $this->classificationService
                ->setIngredientMapping(config('recipe_mappings.ingredients', []))
                ->setIngredientAliases(config('recipe_mappings.aliases', []))
                ->setHealthRestrictions(config('recipe_mappings.health_restrictions', []));
            
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
        }
    }

    /**
     * Helper: Cek apakah user adalah admin
     */
    protected function isAdmin($user): bool
    {
        return $user && $user->role_id === 1; // role_id 1 = admin berdasarkan dump SQL
    }

    /**
     * Helper: Cek authorization untuk resep
     */
    protected function authorizeRecipeAction($user, $recipe): bool
    {
        return $this->isAdmin($user) || $recipe->created_by === $user->id;
    }

    public function index(Request $request)
    {
        $query = Recipe::approved()->with(['creator:id,name']);
        
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        
        // Filter by region jika ada
        if ($request->has('region')) {
            $query->where('region', $request->region);
        }
        
        // Filter by waktu_masak maksimal
        if ($request->has('max_waktu')) {
            $query->where('waktu_masak', '<=', $request->max_waktu);
        }
        
        $recipes = $query->paginate($request->input('per_page', 15));
        
        // Tambahkan data user-specific jika sudah login
        if ($request->user()) {
            $recipes->getCollection()->transform(function($recipe) use ($request) {
                $recipe->is_favorited = Bookmark::where('user_id', $request->user()->id)
                    ->where('recipe_id', $recipe->id)
                    ->exists();
                return $recipe;
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }

    public function store(StoreRecipeRequest $request)
    {
        $user = $request->user();
        try {
            $fallbackIngredients = [];
            
            $result = DB::transaction(function () use ($request, &$fallbackIngredients) {
                // 1. Handle Upload Gambar (Max 1MB)
                $gambarPath = null;
                if ($request->hasFile('gambar')) {
                    $file = $request->file('gambar');
                    
                    if ($file->getSize() > 1024 * 1024) {
                        throw new \Exception('Ukuran gambar tidak boleh lebih dari 1MB');
                    }
                    
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
                    'kategori' => $request->kategori,
                    'status' => 'pending',
                    'created_by' => $request->user()->id,
                    'avg_rating' => 0.00,
                    'total_ratings' => 0,
                    'view_count' => 0,
                ]);

                // 3. Proses Bahan-bahan
                $ingredientIds = [];

                foreach ($request->bahan_bahan as $bahan) {
                    $normalizedNama = $this->classificationService->normalize($bahan['nama']);
                    $ingredient = $this->classificationService->findOrCreateIngredient($normalizedNama);
                    
                    if ($ingredient->sub_kategori === 'umum' || $ingredient->sub_kategori === null) {
                        $fallbackIngredients[] = $ingredient->nama;
                    }

                    $isMain = $this->classificationService->isMainIngredient($normalizedNama);

                    $recipe->ingredients()->attach($ingredient->id, [
                        'is_main' => $isMain,
                        'jumlah' => $bahan['jumlah'],
                        'satuan' => $bahan['satuan'],
                    ]);

                    $ingredientIds[] = $ingredient->id;
                }

                if (!empty($fallbackIngredients)) {
                    Log::warning('Recipe created with fallback ingredient classifications', [
                        'recipe_id' => $recipe->id,
                        'recipe_name' => $recipe->nama,
                        'fallback_ingredients' => $fallbackIngredients,
                    ]);
                }

                // 4. Generate Recipe Suitability
                if (!empty($ingredientIds)) {
                    $this->classificationService->generateRecipeSuitability($recipe->id, $ingredientIds);
                }

                // 5. Simpan Langkah-langkah
                if ($request->filled('langkah_langkah') && is_array($request->langkah_langkah)) {
                    $stepsText = implode("\n", array_map(
                        fn($index, $step) => ($index + 1) . ". " . trim($step),
                        array_keys($request->langkah_langkah),
                        $request->langkah_langkah
                    ));
                    
                    $recipe->langkah_langkah = $stepsText;
                    $recipe->save();
                }

                return [
                    'recipe' => $recipe->fresh(['ingredients']),
                    'image_url' => $gambarPath ? Storage::url($gambarPath) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil disimpan! Menunggu approval.',
                'data' => [
                    'recipe' => $result['recipe'],
                    'image_url' => $result['image_url'],
                    'fallback_count' => count($fallbackIngredients),
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
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan resep: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(StoreRecipeRequest $request, $id)
    {
        try {
            $recipe = Recipe::findOrFail($id);
            
            // Authorization check
            if (!$this->authorizeRecipeAction($request->user(), $recipe)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengubah resep ini'
                ], 403);
            }

            // Tidak bisa update resep approved (kecuali admin)
            if ($recipe->status === 'approved' && !$this->isAdmin($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resep yang sudah disetujui tidak dapat diubah'
                ], 403);
            }

            $fallbackIngredients = [];
            
            $result = DB::transaction(function () use ($request, $recipe, &$fallbackIngredients) {
                
                // 1. Handle Upload Gambar Baru
                if ($request->hasFile('gambar')) {
                    $file = $request->file('gambar');
                    
                    if ($file->getSize() > 1024 * 1024) {
                        throw new \Exception('Ukuran gambar tidak boleh lebih dari 1MB');
                    }
                    
                    // Hapus gambar lama
                    if ($recipe->gambar && Storage::disk('public')->exists($recipe->gambar)) {
                        Storage::disk('public')->delete($recipe->gambar);
                    }
                    
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $recipe->gambar = $file->storeAs('recipes', $filename, 'public');
                }

                // 2. Update Data Dasar
                $recipe->update([
                    'nama' => $request->nama,
                    'waktu_masak' => $request->waktu_masak,
                    'region' => $request->region,
                    'deskripsi' => $request->deskripsi,
                    'kategori' => $request->kategori,
                    'status' => 'pending', // Reset ke pending
                ]);

                // 3. Update Bahan-bahan
                if ($request->filled('bahan_bahan') && is_array($request->bahan_bahan)) {
                    $newIngredientIds = [];
                    
                    foreach ($request->bahan_bahan as $bahan) {
                        $normalizedNama = $this->classificationService->normalize($bahan['nama']);
                        $ingredient = $this->classificationService->findOrCreateIngredient($normalizedNama);
                        
                        if ($ingredient->sub_kategori === 'umum' || $ingredient->sub_kategori === null) {
                            $fallbackIngredients[] = $ingredient->nama;
                        }

                        $isMain = $this->classificationService->isMainIngredient($normalizedNama);

                        $newIngredientIds[$ingredient->id] = [
                            'is_main' => $isMain,
                            'jumlah' => $bahan['jumlah'],
                            'satuan' => $bahan['satuan'],
                        ];
                    }
                    
                    $recipe->ingredients()->sync($newIngredientIds);
                    
                    // Re-generate suitability
                    $this->classificationService->generateRecipeSuitability(
                        $recipe->id, 
                        array_keys($newIngredientIds)
                    );
                }

                // 4. Update Langkah-langkah
                if ($request->filled('langkah_langkah') && is_array($request->langkah_langkah)) {
                    $recipe->langkah_langkah = implode("\n", array_map(
                        fn($index, $step) => ($index + 1) . ". " . trim($step),
                        array_keys($request->langkah_langkah),
                        $request->langkah_langkah
                    ));
                    $recipe->save();
                }

                if (!empty($fallbackIngredients)) {
                    Log::warning('Recipe updated with fallback classifications', [
                        'recipe_id' => $recipe->id,
                        'fallback_ingredients' => $fallbackIngredients,
                    ]);
                }

                return [
                    'recipe' => $recipe->fresh(['ingredients']),
                    'image_url' => $recipe->gambar ? Storage::url($recipe->gambar) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil diperbarui! Menunggu approval.',
                'data' => [
                    'recipe' => $result['recipe'],
                    'image_url' => $result['image_url'],
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update recipe', [
                'error' => $e->getMessage(),
                'recipe_id' => $id,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui resep: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $recipe = Recipe::findOrFail($id);
            
            // Authorization
            if (!$this->authorizeRecipeAction($request->user(), $recipe)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus resep ini'
                ], 403);
            }

            // Simpan path gambar sebelum delete
            $gambarPath = $recipe->gambar;
            
            // Delete recipe (soft delete jika menggunakan trait)
            $recipe->delete();

            // Hapus file gambar
            if ($gambarPath && Storage::disk('public')->exists($gambarPath)) {
                Storage::disk('public')->delete($gambarPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete recipe', [
                'error' => $e->getMessage(),
                'recipe_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus resep: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function show(Request $request, $id)
    {
        // Increment view count
        Recipe::where('id', $id)->approved()->increment('view_count');
        
        $recipe = Recipe::approved()
            ->with([
                'creator:id,name',
                'ingredients:id,nama,kategori,sub_kategori',
                'suitabilities.healthCondition:id,nama',
            ])
            ->findOrFail($id);
        
        $userData = [
            'is_favorited' => false,
            'my_rating' => null,
            'has_rated' => false
        ];
        
        $isFavorited = false;
        $userData = null;
        if ($request->user()) {
            $userData = [
                'is_favorited' => Bookmark::where('user_id', $request->user()->id)
                    ->where('recipe_id', $recipe->id)
                    ->exists(),
                'my_rating' => RecipeRating::where('user_id', $request->user()->id)
                    ->where('recipe_id', $recipe->id)
                    ->value('rating'),
                'has_rated' => RecipeRating::where('user_id', $request->user()->id)
                    ->where('recipe_id', $recipe->id)
                    ->exists()
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'recipe' => $recipe,
                'user_data' => $userData
            ]
        ]);
    }

    public function addToShoppingList(Request $request, $id)
    {
        $recipe = Recipe::approved()->findOrFail($id);
        $user = $request->user();
        
        $shoppingList = ShoppingList::firstOrCreate(
            [
                'user_id' => $user->id,
                'recipe_id' => $recipe->id,
                'nama_list' => 'Belanja: ' . $recipe->nama,
            ],
            [
                'status' => 'pending',
                'shopping_date' => now()->toDateString(),
                'total_estimated_price' => 0,
            ]
        );
        
        $addedItems = [];
        
        // Ambil ingredients dari pivot table recipe_ingredients
        $recipeIngredients = $recipe->ingredients()
            ->withPivot('jumlah', 'satuan')
            ->get();
        
        foreach ($recipeIngredients as $ingredient) {
            $shoppingItem = ShoppingListItem::create([
                'shopping_list_id' => $shoppingList->id,
                'ingredient_id' => $ingredient->id,
                'nama_item' => $ingredient->nama,
                'jumlah' => $ingredient->pivot->jumlah ?? 1,
                'satuan' => $ingredient->pivot->satuan ?? 'pcs',
                'estimated_price' => 0,
                'is_purchased' => false,
                'catatan' => 'Dari resep: ' . $recipe->nama,
            ]);
            $addedItems[] = $shoppingItem;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Bahan berhasil ditambahkan ke daftar belanja',
            'data' => [
                'shopping_list_id' => $shoppingList->id,
                'items' => $addedItems,
                'total_items' => count($addedItems)
            ]
        ]);
    }

    public function toggleFavorite(Request $request, $id)
    {
        $recipe = Recipe::approved()->findOrFail($id);
        $user = $request->user();
        
        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('recipe_id', $id)
            ->first();
        
        if ($bookmark) {
            $bookmark->delete();
            $message = 'Resep dihapus dari favorit';
            $isFavorited = false;
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'recipe_id' => $id,
            ]);
            $message = 'Resep ditambahkan ke favorit';
            $isFavorited = true;
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'is_favorited' => $isFavorited,
                'total_favorites' => Bookmark::where('recipe_id', $id)->count()
            ]
        ]);
    }

    public function myFavorites(Request $request)
    {
        $user = $request->user();
        
        $favorites = Bookmark::where('user_id', $user->id)
            ->with(['recipe:id,nama,waktu_masak,region,kategori,gambar,avg_rating,total_ratings,view_count'])
            ->orderBy('bookmarks.created_at', 'desc')
            ->paginate($request->input('per_page', 15));
        
        // Transform response untuk format yang lebih rapi
        $favorites->getCollection()->transform(function($bookmark) {
            return [
                'id' => $bookmark->recipe->id,
                'nama' => $bookmark->recipe->nama,
                'waktu_masak' => $bookmark->recipe->waktu_masak,
                'region' => $bookmark->recipe->region,
                'kategori' => $bookmark->recipe->kategori,
                'gambar_url' => $bookmark->recipe->gambar ? Storage::url($bookmark->recipe->gambar) : null,
                'avg_rating' => $bookmark->recipe->avg_rating,
                'total_ratings' => $bookmark->recipe->total_ratings,
                'view_count' => $bookmark->recipe->view_count,
                'favorited_at' => $bookmark->created_at,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }
    
    /**
     * Endpoint tambahan: Rate resep
     */
    public function rate(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000'
        ]);
        
        $recipe = Recipe::approved()->findOrFail($id);
        $user = $request->user();
        
        // Update atau buat rating
        $rating = RecipeRating::updateOrCreate(
            [
                'recipe_id' => $id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
            ]
        );
        
        // Recalculate average rating
        $avgRating = RecipeRating::where('recipe_id', $id)->avg('rating') ?? 0;
        $totalRatings = RecipeRating::where('recipe_id', $id)->count();
        
        $recipe->update([
            'avg_rating' => round($avgRating, 2),
            'total_ratings' => $totalRatings,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas rating Anda!',
            'data' => [
                'my_rating' => $rating->rating,
                'avg_rating' => $recipe->avg_rating,
                'total_ratings' => $recipe->total_ratings,
            ]
        ]);
    }
}