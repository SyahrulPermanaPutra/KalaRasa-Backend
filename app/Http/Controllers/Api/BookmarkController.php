<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookmarkController extends Controller
{
    /**
     * Tampilkan daftar bookmark user
     */
    public function index()
    {
        $user = Auth::user();

        // Debug: Cek class user dan apakah method bookmarks ada
        Log::info('User class: ' . get_class($user));
        Log::info('Method bookmarks exists: ' . (method_exists($user, 'bookmarks') ? 'YES' : 'NO'));

        if (!$user || !method_exists($user, 'bookmarks')) {
            return response()->json([
                'success' => false,
                'message' => 'User model tidak memiliki method bookmarks. Cek config/auth.php',
                'debug' => [
                    'user_class' => $user ? get_class($user) : null,
                    'has_bookmarks_method' => $user ? method_exists($user, 'bookmarks') : false
                ]
            ], 500);
        }

        // Ambil semua resep yang di-bookmark
        $bookmarks = $user->bookmarks()
            ->withPivot('created_at') // Ambil waktu saat di-bookmark
            ->orderBy('pivot_created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar bookmark berhasil diambil',
            'data' => $bookmarks
        ]);
    }

    /**
     * Tambah resep ke bookmark
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        $user = Auth::user();
        $recipeId = $request->recipe_id;

        // Cek apakah sudah di-bookmark
        $isBookmarked = $user->bookmarks()->where('recipe_id', $recipeId)->exists();

        if ($isBookmarked) {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah ada di bookmark Anda',
            ], 400);
        }

        // Simpan ke tabel bookmarks
        $user->bookmarks()->attach($recipeId);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil ditambahkan ke bookmark',
        ]);
    }

    /**
     * Hapus resep dari bookmark
     */
    public function destroy($recipeId)
    {
        $user = Auth::user();

        // Cek apakah resep ada di bookmark
        $isBookmarked = $user->bookmarks()->where('recipe_id', $recipeId)->exists();

        if (!$isBookmarked) {
            return response()->json([
                'success' => false,
                'message' => 'Resep tidak ditemukan di bookmark Anda',
            ], 404);
        }

        // Hapus dari tabel bookmarks
        $user->bookmarks()->detach($recipeId);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil dihapus dari bookmark',
        ]);
    }
}