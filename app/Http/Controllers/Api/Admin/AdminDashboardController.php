<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Recipe;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * SUMMARY DASHBOARD
     */
    public function summary()
    {
        $totalUsers = User::whereIn('role', ['user','admin'])->count();

        $totalRecipes = Recipe::count();

        $totalSubmissions = Recipe::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_recipes' => $totalRecipes,
                'total_submissions' => $totalSubmissions,
            ]
        ]);
    }

    /**
     * LIST PENGAJUAN RESEP (PENDING)
     */
    public function recipeSubmissions(Request $request)
    {
        $allowedPerPage = [10,25,50,100];
        $perPage = $request->input('per_page', 10);

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $recipes = Recipe::where('status','pending')
            ->with('creator:id,name,email') // pastikan relasi creator ada
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }
}
