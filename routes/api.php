<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShoppingListController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminRecipeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\API\NLPController;
use App\Http\Controllers\API\RecipeController;
use App\Http\Middleware\VerifyApiKey;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::prefix('recipe')->group(function () {
    Route::get('/', [RecipeController::class, 'index']);
    Route::get('/{id}', [RecipeController::class, 'show']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



// Protected Routes (User & Admin)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);
    
    // Recipe Routes (User)
    Route::prefix('recipe')->group(function () {
        Route::post('/{id}/add-to-shopping-list', [RecipeController::class, 'addToShoppingList']);
        Route::post('/{id}/toggle-favorite', [RecipeController::class, 'toggleFavorite']);
        Route::get('/my/favorites', [RecipeController::class, 'myFavorites']);
    });

    // Admin Routes
    Route::prefix('admin')->middleware('admin')->group(function () {        
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard/users', [AdminDashboardController::class, 'users']);
        Route::get('/dashboard/users/{id}', [AdminDashboardController::class, 'userDetail']);
        Route::get('/dashboard/resep-statistics', [AdminDashboardController::class, 'recipetatistics']);
        Route::get('/dashboard/expense-statistics', [AdminDashboardController::class, 'expenseStatistics']);

        // Recipe Management
        Route::prefix('recipe')->group(function () {
            Route::get('/', [AdminRecipeController::class, 'index']);
            Route::post('/', [AdminRecipeController::class, 'store']);
            Route::get('/statistics', [AdminRecipeController::class, 'statistics']);
            Route::get('/{id}', [AdminRecipeController::class, 'show']);
            Route::put('/{id}', [AdminRecipeController::class, 'update']);
            Route::delete('/{id}', [AdminRecipeController::class, 'destroy']);
            Route::patch('/{id}/approve', [AdminRecipeController::class, 'approve']);
            Route::patch('/{id}/reject', [AdminRecipeController::class, 'reject']);
        });
    });
});
