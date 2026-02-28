<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Recipe;
use App\Models\Role;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * SUMMARY DASHBOARD
     */
    public function summary()
    {
        // Ambil ID role 'user' dan 'admin' dari tabel roles
        $roleIds = Role::whereIn('name', ['user', 'admin'])->pluck('id');

        $totalUsers = User::whereIn('role_id', $roleIds)->count();
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
        $allowedPerPage = [10, 25, 50, 100];
        $perPage = in_array($request->input('per_page', 10), $allowedPerPage) 
            ? $request->input('per_page', 10) 
            : 10;

        $recipes = Recipe::where('status', 'pending')
            ->with('creator:id,name,email') // ← Pastikan relasi 'creator' ada di Model Recipe
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }
}