<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RecipeRatingController extends Controller
{
    /**
     * Add or update rating untuk recipe
     */
    public function rate(Request $request, $recipeId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Rating wajib diisi',
            'rating.min' => 'Rating minimal 1 bintang',
            'rating.max' => 'Rating maksimal 5 bintang',
            'review.max' => 'Review maksimal 1000 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $recipe = Recipe::find($recipeId);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Resep tidak ditemukan'
            ], 404);
        }

        // Hanya bisa rate recipe yang sudah approved
        if (!$recipe->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa memberikan rating untuk resep yang belum disetujui'
            ], 403);
        }

        $user = $request->user();

        try {
            DB::beginTransaction();

            // Create or update rating
            $rating = RecipeRating::updateOrCreate(
                [
                    'recipe_id' => $recipeId,
                    'user_id'   => $user->id
                ],
                [
                    'rating' => $request->rating,
                    'review' => $request->review
                ]
            );

            $wasRecentlyCreated = $rating->wasRecentlyCreated;

            // Update avg_rating dan total_ratings di Recipe
            $recipe->updateRating();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $wasRecentlyCreated ? 'Rating berhasil ditambahkan' : 'Rating berhasil diupdate',
                'data'    => [
                    'rating' => $rating->load('user:id,name'),
                    'recipe_stats' => [
                        'avg_rating' => $recipe->fresh()->avg_rating,
                        'total_ratings' => $recipe->fresh()->total_ratings
                    ]
                ]
            ], $wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan rating',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all ratings untuk recipe tertentu (with pagination)
     */
    public function index(Request $request, $recipeId)
    {
        $recipe = Recipe::find($recipeId);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Resep tidak ditemukan'
            ], 404);
        }

        $perPage = $request->get('per_page', 10);
        $sortBy = $request->get('sort', 'latest'); // latest, highest, lowest

        $query = RecipeRating::where('recipe_id', $recipeId)
            ->with('user:id,name');

        // Sorting
        switch ($sortBy) {
            case 'highest':
                $query->orderByDesc('rating')->orderByDesc('created_at');
                break;
            case 'lowest':
                $query->orderBy('rating')->orderByDesc('created_at');
                break;
            default: // latest
                $query->orderByDesc('created_at');
        }

        $ratings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'recipe' => [
                    'id' => $recipe->id,
                    'nama' => $recipe->nama,
                    'avg_rating' => $recipe->avg_rating,
                    'total_ratings' => $recipe->total_ratings,
                ],
                'ratings' => $ratings
            ]
        ]);
    }

    /**
     * Get user's rating untuk recipe tertentu
     */
    public function show(Request $request, $recipeId)
    {
        $user = $request->user();

        $rating = RecipeRating::where('recipe_id', $recipeId)
            ->where('user_id', $user->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memberikan rating untuk resep ini'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $rating
        ]);
    }

    /**
     * Delete user's rating
     */
    public function destroy(Request $request, $recipeId)
    {
        $user = $request->user();

        $rating = RecipeRating::where('recipe_id', $recipeId)
            ->where('user_id', $user->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Rating tidak ditemukan'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $rating->delete();

            // Update avg_rating di Recipe
            $recipe = Recipe::find($recipeId);
            $recipe->updateRating();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rating berhasil dihapus',
                'data' => [
                    'recipe_stats' => [
                        'avg_rating' => $recipe->fresh()->avg_rating,
                        'total_ratings' => $recipe->fresh()->total_ratings
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus rating',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rating statistics dengan breakdown
     */
    public function statistics($recipeId)
    {
        $recipe = Recipe::find($recipeId);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Resep tidak ditemukan'
            ], 404);
        }

        $stats = RecipeRating::where('recipe_id', $recipeId)
            ->selectRaw('
                COUNT(*) as total,
                AVG(rating) as average,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            ')
            ->first();

        // Calculate percentages
        $total = $stats->total ?? 0;
        $breakdown = [];
        
        if ($total > 0) {
            $breakdown = [
                '5_stars' => [
                    'count' => $stats->five_stars,
                    'percentage' => round(($stats->five_stars / $total) * 100, 1)
                ],
                '4_stars' => [
                    'count' => $stats->four_stars,
                    'percentage' => round(($stats->four_stars / $total) * 100, 1)
                ],
                '3_stars' => [
                    'count' => $stats->three_stars,
                    'percentage' => round(($stats->three_stars / $total) * 100, 1)
                ],
                '2_stars' => [
                    'count' => $stats->two_stars,
                    'percentage' => round(($stats->two_stars / $total) * 100, 1)
                ],
                '1_star' => [
                    'count' => $stats->one_star,
                    'percentage' => round(($stats->one_star / $total) * 100, 1)
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'recipe' => [
                    'id' => $recipe->id,
                    'nama' => $recipe->nama,
                ],
                'total_ratings' => $total,
                'average_rating' => $total > 0 ? round($stats->average, 2) : 0,
                'breakdown' => $breakdown
            ]
        ]);
    }
}