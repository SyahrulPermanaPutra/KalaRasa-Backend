<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShoppingListController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminRecipeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\RecipeRatingController;
use App\Http\Controllers\Api\BookmarkController;
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
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);


// Protected Routes (User & Admin)
Route::middleware('auth:api')->group(function () {
    
    // Auth Routes
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/devices', [AuthController::class, 'devices']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-device', [AuthController::class, 'logoutDevice']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    // Chatbot routes
    Route::prefix('chatbot')->group(function () {
        Route::post('/message', [ChatbotController::class, 'processMessage']);
        Route::get('/history', [ChatbotController::class, 'getHistory']);
        Route::get('/health', [ChatbotController::class, 'checkNLPHealth']);
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

    // bookmark routes
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks', [BookmarkController::class, 'store']);
    Route::delete('/bookmarks/{recipe_id}', [BookmarkController::class, 'destroy']);

    // Rating routes
    Route::prefix('recipes/{recipe_id}')->group(function () {
        // Add/update rating
        Route::post('/ratings', [RecipeRatingController::class, 'rate']);
        
        // Get my rating
        Route::get('/ratings/me', [RecipeRatingController::class, 'show']);
        
        // Delete my rating
        Route::delete('/ratings/me', [RecipeRatingController::class, 'destroy']);
        
        // List all ratings (dengan filter) - kayaknya ini buat admin aja ya?
        Route::get('/ratings', [RecipeRatingController::class, 'index']);
        
        // Rating statistics
        // Route::get('/ratings/statistics', [RecipeRatingController::class, 'statistics']);
    });

    // Admin Routes
    Route::prefix('admin')->middleware('admin')->group(function () {        
        // User Management
        Route::prefix('user')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);               
            Route::get('/{id}', [AdminUserController::class, 'show']);            
        });

        // Dashboard sesuai desain
        Route::get('/dashboard/summary', [AdminDashboardController::class,'summary']);
        Route::get('/recipe-submissions', [AdminDashboardController::class,'recipeSubmissions']);

        // Recipe Management
        Route::prefix('recipe')->group(function () {
            
            Route::get('/statistics', [AdminRecipeController::class, 'statistics']);    
        
            Route::get('/', [AdminRecipeController::class, 'index']);
            Route::get('/{id}', [AdminRecipeController::class, 'show']);

            Route::post('/', [AdminRecipeController::class, 'store']);
            Route::post('/{id}', [AdminRecipeController::class, 'update']);
            Route::delete('/{id}', [AdminRecipeController::class, 'destroy']);

            Route::patch('/{id}/approve', [AdminRecipeController::class, 'approve']);
            Route::patch('/{id}/reject', [AdminRecipeController::class, 'reject']);
        });
    });
});
