<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Recipe;
use App\Models\ChatHistory;

class ChatbotController extends Controller
{
    private $nlpApiUrl;
    private $nlpTimeout = 30; // seconds

    public function __construct()
    {
        $this->nlpApiUrl = env('NLP_API_URL', 'http://127.0.0.1:5000');
    }

    /**
     * Process chatbot message
     * POST /api/chatbot/message
     */
    public function processMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $message = $request->input('message');

        try {
            // Step 1: Send to NLP Python API
            $nlpResult = $this->sendToNLP($message, $user->id);

            if (!$nlpResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'NLP processing failed',
                    'details' => $nlpResult['error'] ?? 'Unknown error'
                ], 500);
            }

            // Step 2: Save chat history
            $this->saveChatHistory($user->id, $message, $nlpResult);

            // Step 3: Check if clarification needed
            if ($nlpResult['needs_clarification']) {
                return response()->json([
                    'success' => true,
                    'type' => 'clarification',
                    'message' => $nlpResult['clarification_question'],
                    'nlp_data' => $nlpResult['nlp_result'],
                    'recipes' => []
                ]);
            }

            // Step 4: Match recipes based on NLP entities
            $recipes = $this->matchRecipes($nlpResult['nlp_result']);

            // Step 5: Generate response message
            $responseMessage = $this->generateResponseMessage(
                $nlpResult['nlp_result'],
                $recipes
            );

            return response()->json([
                'success' => true,
                'type' => 'recipe_match',
                'message' => $responseMessage,
                'nlp_data' => $nlpResult['nlp_result'],
                'recipes' => $recipes,
                'context' => $nlpResult['context_summary'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'message' => $message,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred processing your message',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get chat history
     * GET /api/chatbot/history
     */
    public function getHistory(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 50);

        $history = ChatHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Clear chat context
     * POST /api/chatbot/clear-context
     */
    public function clearContext(Request $request)
    {
        $user = $request->user();

        try {
            // Clear NLP context
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $user->currentAccessToken()->token,
            ])->timeout($this->nlpTimeout)
              ->post("{$this->nlpApiUrl}/api/nlp/context/{$user->id}/clear");

            if ($response->successful()) {
                // Clear local chat history (optional)
                // ChatHistory::where('user_id', $user->id)->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Chat context cleared successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear context'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Clear Context Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred clearing context'
            ], 500);
        }
    }

    // ================================================
    // PRIVATE HELPER METHODS
    // ================================================

    /**
     * Send message to NLP Python API
     */
    private function sendToNLP(string $message, int $userId)
    {
        try {
            $user = \App\Models\User::find($userId);
            $token = $user->currentAccessToken()->token;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->timeout($this->nlpTimeout)
              ->post("{$this->nlpApiUrl}/api/nlp/process", [
                  'message' => $message
              ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('NLP API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'NLP service unavailable'
            ];

        } catch (\Exception $e) {
            Log::error('NLP Connection Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Cannot connect to NLP service'
            ];
        }
    }

    /**
     * Match recipes based on NLP entities
     */
    private function matchRecipes(array $nlpResult)
    {
        $entities = $nlpResult['entities'] ?? [];
        $intent = $nlpResult['intent'] ?? 'search_recipe';

        $query = Recipe::query()->where('status', 'published');

        // Match by ingredients
        if (!empty($entities['ingredients'])) {
            $ingredients = $entities['ingredients'];
            
            $query->where(function($q) use ($ingredients) {
                foreach ($ingredients as $ingredient) {
                    $q->orWhereJsonContains('ingredients', ['name' => $ingredient])
                      ->orWhere('ingredients', 'like', "%{$ingredient}%");
                }
            });
        }

        // Match by cuisine
        if (!empty($entities['cuisine'])) {
            $query->whereIn('cuisine_type', $entities['cuisine']);
        }

        // Match by dietary restrictions
        if (!empty($entities['dietary'])) {
            foreach ($entities['dietary'] as $diet) {
                $query->whereJsonContains('dietary_tags', $diet);
            }
        }

        // Match by cooking time
        if (!empty($entities['time'])) {
            $time = $entities['time'][0];
            if (is_numeric($time)) {
                $query->where('cooking_time', '<=', (int)$time);
            }
        }

        // Match by difficulty
        if (!empty($entities['difficulty'])) {
            $query->whereIn('difficulty_level', $entities['difficulty']);
        }

        // Match by meal type
        if (!empty($entities['meal_type'])) {
            $query->whereIn('meal_type', $entities['meal_type']);
        }

        // Execute query with limit
        $recipes = $query->with(['user:id,name', 'category:id,name'])
            ->limit(10)
            ->get()
            ->map(function($recipe) {
                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'description' => $recipe->description,
                    'image_url' => $recipe->image_url,
                    'cuisine_type' => $recipe->cuisine_type,
                    'difficulty_level' => $recipe->difficulty_level,
                    'cooking_time' => $recipe->cooking_time,
                    'servings' => $recipe->servings,
                    'rating' => $recipe->rating,
                    'author' => $recipe->user->name ?? 'Unknown',
                    'category' => $recipe->category->name ?? 'Uncategorized',
                ];
            });

        return $recipes;
    }

    /**
     * Generate response message
     */
    private function generateResponseMessage(array $nlpResult, $recipes)
    {
        $intent = $nlpResult['intent'] ?? 'search_recipe';
        $recipeCount = count($recipes);

        if ($recipeCount === 0) {
            return "Maaf, saya tidak menemukan resep yang sesuai dengan kriteria Anda. Coba ubah kriteria pencarian atau tambahkan detail lain.";
        }

        $entities = $nlpResult['entities'] ?? [];
        $criteria = [];

        if (!empty($entities['ingredients'])) {
            $criteria[] = "bahan: " . implode(', ', $entities['ingredients']);
        }
        if (!empty($entities['cuisine'])) {
            $criteria[] = "masakan: " . implode(', ', $entities['cuisine']);
        }
       

        $criteriaText = !empty($criteria) ? " dengan " . implode(", ", $criteria) : "";

        if ($recipeCount === 1) {
            return "Saya menemukan 1 resep{$criteriaText}. Berikut rekomendasinya:";
        }

        return "Saya menemukan {$recipeCount} resep{$criteriaText}. Berikut rekomendasinya:";
    }

    /**
     * Save chat history
     */
    private function saveChatHistory(int $userId, string $message, array $nlpResult)
    {
        ChatHistory::create([
            'user_id' => $userId,
            'message' => $message,
            'intent' => $nlpResult['nlp_result']['intent'] ?? null,
            'entities' => json_encode($nlpResult['nlp_result']['entities'] ?? []),
            'response_type' => $nlpResult['needs_clarification'] ? 'clarification' : 'recipe_match',
        ]);
    }
}