<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resep;
use App\Models\ShoppingList;
use Illuminate\Http\Request;

class ResepController extends Controller
{
    public function index(Request $request)
    {
        $query = Resep::approved()->with(['creator']);

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
                $q->where('nama_resep', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $reseps = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reseps
        ]);
    }

    public function show($id)
    {
        $resep = Resep::approved()
            ->with(['creator', 'favoritedBy'])
            ->findOrFail($id);

        // Check if current user favorited this
        $isFavorited = false;
        if (auth()->check()) {
            $isFavorited = $resep->favoritedBy->contains(auth()->id());
        }

        return response()->json([
            'success' => true,
            'data' => [
                'resep' => $resep,
                'is_favorited' => $isFavorited,
                'total_favorites' => $resep->favoritedBy->count()
            ]
        ]);
    }

    public function addToShoppingList(Request $request, $id)
    {
        $resep = Resep::approved()->findOrFail($id);
        
        $bahanMakanan = $resep->bahan_makanan;
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
                'catatan' => 'Dari resep: ' . $resep->nama_resep,
            ]);

            $addedItems[] = $shoppingItem;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bahan makanan berhasil ditambahkan ke daftar belanja',
            'data' => $addedItems
        ]);
    }

    public function toggleFavorite($id)
    {
        $resep = Resep::approved()->findOrFail($id);
        $user = auth()->user();

        if ($user->favoriteReseps()->where('resep_id', $id)->exists()) {
            $user->favoriteReseps()->detach($id);
            $message = 'Resep dihapus dari favorit';
            $isFavorited = false;
        } else {
            $user->favoriteReseps()->attach($id);
            $message = 'Resep ditambahkan ke favorit';
            $isFavorited = true;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'is_favorited' => $isFavorited,
                'total_favorites' => $resep->favoritedBy()->count()
            ]
        ]);
    }

    public function myFavorites(Request $request)
    {
        $favorites = $request->user()
            ->favoriteReseps()
            ->with(['creator'])
            ->orderBy('favorite_reseps.created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }
}
