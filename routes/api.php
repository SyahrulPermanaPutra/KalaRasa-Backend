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
use App\Http\Controllers\Api\ShoppingListItemController;

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

Route::prefix('chatbot')->group(function () {
        Route::get('/health', [ChatbotController::class, 'checkNLPHealth']);
});

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/refresh', [AuthController::class, 'refresh']);

// Protected Routes (User & Admin)
Route::middleware(['auth.sso'])->group(function () {
    
    // Auth Routes
    Route::post('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    // Chatbot routes
    Route::prefix('chatbot')->group(function () {
        Route::post('message', [App\Http\Controllers\Api\ChatbotController::class, 'processMessage']);
        Route::post('search', [App\Http\Controllers\Api\ChatbotController::class, 'directSearch']);
        Route::get('history', [App\Http\Controllers\Api\ChatbotController::class, 'getHistory']);
        Route::post('reset', [App\Http\Controllers\Api\ChatbotController::class, 'resetSession']);
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

    // ══════════════════════════════════════════════════════════
    // GRAFIK PENGELUARAN
    // ══════════════════════════════════════════════════════════
    Route::prefix('shopping-lists/grafik')->group(function () {
        Route::get('/harian', [ShoppingListController::class, 'grafikHarian']);
        Route::get('/mingguan', [ShoppingListController::class, 'grafikMingguan']);
        Route::get('/bulanan', [ShoppingListController::class, 'grafikBulanan']);
    });

    // ══════════════════════════════════════════════════════════
    // SHOPPING LIST / MEMO CRUD
    // ══════════════════════════════════════════════════════════
    Route::prefix('shopping-lists')->group(function () {
        // List & Create
        Route::get('/', [ShoppingListController::class, 'index']);
        Route::post('/', [ShoppingListController::class, 'store']);
        
        // Create from recipe
        Route::post('/from-recipe/{recipeId}', [ShoppingListController::class, 'storeFromRecipe']);
        
        // Show, Update, Delete
        Route::get('/{id}', [ShoppingListController::class, 'show']);
        Route::put('/{id}', [ShoppingListController::class, 'update']);
        Route::patch('/{id}/status', [ShoppingListController::class, 'updateStatus']);
        Route::delete('/{id}', [ShoppingListController::class, 'destroy']);
        
        // ══════════════════════════════════════════════════════════
        // SHOPPING LIST ITEMS (Nested Routes)
        // ══════════════════════════════════════════════════════════
        Route::prefix('{listId}/items')->group(function () {
            // List & Create
            Route::get('/', [ShoppingListItemController::class, 'index']);
            Route::post('/', [ShoppingListItemController::class, 'store']);
            
            // Bulk operations
            Route::post('/bulk-toggle-purchased', [ShoppingListItemController::class, 'bulkTogglePurchased']);
            Route::delete('/bulk-delete', [ShoppingListItemController::class, 'bulkDelete']);
            
            // Show, Update, Delete
            Route::get('/{itemId}', [ShoppingListItemController::class, 'show']);
            Route::put('/{itemId}', [ShoppingListItemController::class, 'update']);
            Route::patch('/{itemId}/toggle-purchased', [ShoppingListItemController::class, 'togglePurchased']);
            Route::delete('/{itemId}', [ShoppingListItemController::class, 'destroy']);
        });
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
