<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShoppingListController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ResepController;
use App\Http\Controllers\Api\Admin\AdminResepController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::prefix('recipe')->group(function () {
    Route::get('/', [ResepController::class, 'index']);
    Route::get('/{id}', [ResepController::class, 'show']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



// Protected Routes (User & Admin)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);

    // Shopping List Routes
    Route::prefix('shopping-lists')->group(function () {
        Route::get('/', [ShoppingListController::class, 'index']);
        Route::post('/', [ShoppingListController::class, 'store']);
        Route::get('/{id}', [ShoppingListController::class, 'show']);
        Route::put('/{id}', [ShoppingListController::class, 'update']);
        Route::delete('/{id}', [ShoppingListController::class, 'destroy']);
        Route::patch('/{id}/update-harga', [ShoppingListController::class, 'updateHarga']);
        Route::patch('/{id}/mark-as-bought', [ShoppingListController::class, 'markAsBought']);
        Route::get('/calculate/total', [ShoppingListController::class, 'calculateTotal']);
    });

    // Expense Routes
    Route::prefix('expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index']);
        Route::post('/', [ExpenseController::class, 'store']);
        Route::get('/{id}', [ExpenseController::class, 'show']);
        Route::delete('/{id}', [ExpenseController::class, 'destroy']);
        
        // Rekap Routes
        Route::get('/rekap/harian', [ExpenseController::class, 'rekapHarian']);
        Route::get('/rekap/mingguan', [ExpenseController::class, 'rekapMingguan']);
        Route::get('/rekap/bulanan', [ExpenseController::class, 'rekapBulanan']);
    });

    // Resep Routes (User)
    Route::prefix('recipe')->group(function () {
        Route::post('/{id}/add-to-shopping-list', [ResepController::class, 'addToShoppingList']);
        Route::post('/{id}/toggle-favorite', [ResepController::class, 'toggleFavorite']);
        Route::get('/my/favorites', [ResepController::class, 'myFavorites']);
    });

    // Admin Routes
    Route::prefix('admin')->middleware('admin')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard/users', [AdminDashboardController::class, 'users']);
        Route::get('/dashboard/users/{id}', [AdminDashboardController::class, 'userDetail']);
        Route::get('/dashboard/resep-statistics', [AdminDashboardController::class, 'recipetatistics']);
        Route::get('/dashboard/expense-statistics', [AdminDashboardController::class, 'expenseStatistics']);

        // Resep Management
        Route::prefix('recipe')->group(function () {
            Route::get('/', [AdminResepController::class, 'index']);
            Route::post('/', [AdminResepController::class, 'store']);
            Route::get('/statistics', [AdminResepController::class, 'statistics']);
            Route::get('/{id}', [AdminResepController::class, 'show']);
            Route::put('/{id}', [AdminResepController::class, 'update']);
            Route::delete('/{id}', [AdminResepController::class, 'destroy']);
            Route::patch('/{id}/approve', [AdminResepController::class, 'approve']);
            Route::patch('/{id}/reject', [AdminResepController::class, 'reject']);
        });
    });
});
