<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecipeRequest;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem; // Pastikan model ini ada
use App\Services\RecipeClassificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    protected RecipeClassificationService $classificationService;

    public function __construct(RecipeClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
        
        // Load mapping dari config
        $this->classificationService
            ->setIngredientMapping(config('recipe_mappings.ingredients'))
            ->setIngredientAliases(config('recipe_mappings.aliases'))
            ->setHealthRestrictions(config('recipe_mappings.health_restrictions'));
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
                // SESUAI DB: kolom 'nama', bukan 'nama_recipe'
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
                // Handle Upload Gambar
                $gambarPath = null;
                $gambarUrl = null;
                
                if ($request->hasFile('gambar')) {
                    $file = $request->file('gambar');
                    $gambarPath = $file->store('recipes', 'public');
                    $gambarUrl = Storage::url($gambarPath);
                }

                // Simpan Resep Dasar
                // SESUAI DB: kolom 'nama', bukan 'nama_recipe'
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

                // Proses Bahan
                $ingredientIds = [];
                $bahanBahan = $request->input('bahan_bahan');
                
                if (is_string($bahanBahan)) {
                    $bahanBahan = json_decode($bahanBahan, true);
                }

                if (!empty($bahanBahan) && is_array($bahanBahan)) {
                    foreach ($bahanBahan as $bahanInput) {
                        if (!is_string($bahanInput)) continue;

                        $parsed = $this->classificationService->parseIngredient($bahanInput);
                        $ingredient = $this->classificationService->findOrCreateIngredient($parsed['nama']);
                        
                        // SESUAI DB: pivot table menggunakan 'jumlah' & 'satuan'
                        $recipe->ingredients()->attach($ingredient->id, [
                            'is_main' => $this->classificationService->isMainIngredient($parsed['nama']),
                            'jumlah' => $parsed['jumlah'],
                            'satuan' => $parsed['satuan'],
                        ]);
                        
                        $ingredientIds[] = $ingredient->id;
                    }
                }

                // Generate Kesehatan
                if (!empty($ingredientIds)) {
                    $this->classificationService->generateRecipeSuitability($recipe->id, $ingredientIds);
                }

                // Simpan Langkah
                if ($request->filled('langkah_langkah')) {
                    $steps = is_array($request->langkah_langkah) 
                        ? $request->langkah_langkah 
                        : json_decode($request->langkah_langkah, true);
                    
                    if (is_array($steps)) {
                        $steps = array_filter($steps);
                        if (!empty($steps)) {
                            $stepsText = implode("\n", array_map(fn($s) => "- $s", $steps));
                            $recipe->deskripsi .= "\n\nLangkah:\n" . $stepsText;
                            $recipe->save();
                        }
                    }
                }

                return [
                    'recipe' => $recipe,
                    'image_url' => $gambarUrl
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil disimpan dan diklasifikasi! Menunggu approval.',
                'data' => [
                    'recipe' => $result['recipe'],
                    'image_url' => $result['image_url'],
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan resep.',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        // SESUAI DB: hapus 'avatar' karena tidak ada di tabel users
        $recipe = Recipe::approved()->with([
            'creator:id,name,email', 
            'ingredients:id,nama,kategori',
            'suitabilities.healthCondition'
        ])
        ->findOrFail($id);

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

        // SESUAI DB: ShoppingListItems terhubung ke ShoppingLists (yang punya user_id)
        // Kita perlu membuat atau mencari ShoppingList dulu
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

        // Cek apakah resep menggunakan relasi ingredients (struktur baru)
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
            // Fallback jika ada kolom bahan_makanan (struktur lama)
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

        // CATATAN: Tabel 'favorite_recipes' harus dibuat via migration (lihat instruksi di bawah)
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