<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatbotController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Chatbot Routes
|--------------------------------------------------------------------------
*/

Route::get('/test-python', function (\App\Services\RecipeChatbotService $chatbot) {
    return $chatbot->sendMessage('mau masak yang daging');
    });


Route::middleware(['auth'])->group(function () {

    // Chatbot UI
    Route::get('/chatbot', [ChatbotController::class, 'index'])
        ->name('chatbot.index');

    // Chatbot actions
    Route::post('/chatbot/message', [ChatbotController::class, 'sendMessage'])
        ->name('chatbot.message');

    Route::get('/chatbot/history', [ChatbotController::class, 'getHistory'])
        ->name('chatbot.history');

    Route::post('/chatbot/clear', [ChatbotController::class, 'clearConversation'])
        ->name('chatbot.clear');

    // Recipes
    Route::post('/recipes/search', [ChatbotController::class, 'searchRecipes'])
        ->name('recipes.search');

    Route::get('/recipes/{id}', [ChatbotController::class, 'getRecipe'])
        ->name('recipes.show');

});
