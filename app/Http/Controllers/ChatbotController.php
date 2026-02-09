<?php

namespace App\Http\Controllers;

use App\Services\RecipeChatbotService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected $chatbot;

    public function __construct(RecipeChatbotService $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    /**
     * Show chatbot page
     */
    public function index()
    {
        return view('chatbot.index');
    }

    /**
     * Send message to chatbot (AJAX)
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);

        $response = $this->chatbot->sendMessage($request->message);

        return response()->json($response);
    }

    /**
     * Get conversation history
     */
    public function getHistory()
    {
        $history = $this->chatbot->getHistory();
        return response()->json($history);
    }

    /**
     * Clear conversation
     */
    public function clearConversation()
    {
        $success = $this->chatbot->clearConversation();
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Conversation cleared' : 'Failed to clear conversation'
        ]);
    }

    /**
     * Search recipes
     */
    public function searchRecipes(Request $request)
    {
        $filters = $request->only([
            'ingredients',
            'cooking_methods',
            'health_conditions',
            'max_time',
            'difficulty',
            'limit'
        ]);

        $result = $this->chatbot->searchRecipes($filters);

        return response()->json($result);
    }

    /**
     * Get recipe details
     */
    public function getRecipe($id)
    {
        $recipe = $this->chatbot->getRecipe($id);

        if ($recipe) {
            return response()->json([
                'success' => true,
                'recipe' => $recipe
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Recipe not found'
        ], 404);
    }
}