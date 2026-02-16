<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\ShoppingList;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        // $query = Recipe::all();

        $query = Recipe::approved()->with(['creator']);

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter berdasarkan tingkat kesulitan
        if ($request->has('tingkat_kesulitan')) {
            $query->where('tingkat_kesulitan', $request->tingkat_kesulitan);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_recipe', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $recipes = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }

    public function show(Request $request, $id)
    {
        $recipe = Recipe::approved()->with([
            'creator:id,name,avatar',
            'ingredients:id,nama,kategori'
        ])
        ->findOrFail($id);

        // $recipe = Recipe::approved()
        //     ->with(['creator', 'favoritedBy'])
        //     ->findOrFail($id);

        // Check if current user favorited this
        // $isFavorited = false;
        // if ($request->user()) {
        //     $isFavorited = $recipe->favoritedBy->contains($request->user()->id); 
        // }

        return response()->json([
            'success' => true,
            'data' => [
                'recipe' => $recipe,
            ]
        ]);
    }

    public function addToShoppingList(Request $request, $id)
    {
        $recipe = Recipe::approved()->findOrFail($id);
        
        $bahanMakanan = $recipe->bahan_makanan;
        $user = $request->user();

        $addedItems = [];

        foreach ($bahanMakanan as $bahan) {
            // Asumsikan format bahan: ["nama bahan", "jumlah", "satuan"]
            // atau bisa juga format object: {"nama": "...", "jumlah": "...", "satuan": "..."}
            
            if (is_array($bahan)) {
                $namaItem = $bahan['nama'] ?? $bahan[0] ?? 'Unknown';
                $jumlah = $bahan['jumlah'] ?? $bahan[1] ?? 1;
                $satuan = $bahan['satuan'] ?? $bahan[2] ?? 'pcs';
            } else {
                $namaItem = $bahan;
                $jumlah = 1;
                $satuan = 'pcs';
            }

            $shoppingItem = ShoppingList::create([
                'user_id' => $user->id,
                'nama_item' => $namaItem,
                'jumlah' => $jumlah,
                'satuan' => $satuan,
                'kategori' => 'bahan_makanan',
                'catatan' => 'Dari resep: ' . $recipe->nama_recipe,
            ]);

            $addedItems[] = $shoppingItem;
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
