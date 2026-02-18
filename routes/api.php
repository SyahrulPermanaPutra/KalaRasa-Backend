<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShoppingListController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminRecipeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\NlpAuthController;

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


// NLP Python API verification endpoint
Route::middleware(['api.key'])->group(function () {
    Route::post('/verify-token', [NlpAuthController::class, 'verifyToken']);
});

// Protected Routes (User & Admin)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    // Chatbot routes
    Route::prefix('chatbot')->group(function () {
        Route::post('/message', [ChatbotController::class, 'processMessage']);
        Route::get('/history', [ChatbotController::class, 'getHistory']);
        Route::post('/clear-context', [ChatbotController::class, 'clearContext']);
    });

    // Recipe Routes (User)
    Route::prefix('recipe')->group(function () {
        Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
        Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
        Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
        Route::post('/{id}/add-to-shopping-list', [RecipeController::class, 'addToShoppingList']);
        Route::post('/{id}/toggle-favorite', [RecipeController::class, 'toggleFavorite']);
        Route::get('/my/favorites', [RecipeController::class, 'myFavorites']);
    });

    // Admin Routes
    Route::prefix('admin')->middleware('admin')->group(function () {        
        // User Management
        Route::prefix('user')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);               
            Route::get('/{id}', [AdminUserController::class, 'show']);            
        });

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard/recipe-statistics', [AdminDashboardController::class, 'recipeStatistics']);
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
