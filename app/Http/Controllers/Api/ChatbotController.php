<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\UserQuery; // Model untuk tabel user_queries
use Illuminate\Support\Facades\DB;

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
            // Step 1: Send to NLP Python/Flask API
            $nlpResult = $this->sendToNLP($message);

            if (!$nlpResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'NLP processing failed',
                    'details' => $nlpResult['error'] ?? 'Unknown error'
                ], 500);
            }

            // Step 2: Save chat history to user_queries table
            $this->saveUserQuery($user->id, $message, $nlpResult);

            // Step 3: Check if clarification needed
            if ($nlpResult['needs_clarification'] ?? false) {
                return response()->json([
                    'success' => true,
                    'type' => 'clarification',
                    'message' => $nlpResult['clarification_question'] ?? 'Mohon lengkapi informasi Anda.',
                    'nlp_data' => $nlpResult,
                    'recipes' => []
                ]);
            }

            // Step 4: Match recipes based on NLP entities
            $recipes = $this->matchRecipes($nlpResult);

            // Step 5: Generate response message
            $responseMessage = $this->generateResponseMessage(
                $nlpResult['nlp_result'] ?? [],
                $recipes
            );

            return response()->json([
                'success' => true,
                'type' => 'recipe_match',
                'message' => $responseMessage,
                'nlp_data' => $nlpResult,
                'recipes' => $recipes,
                'context' => $nlpResult['context_summary'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot Error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
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

        $history = UserQuery::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(function($query) {
                return [
                    'id' => $query->id,
                    'message' => $query->query_text,
                    'intent' => $query->intent,
                    'confidence' => $query->confidence,
                    'status' => $query->status,
                    'entities' => json_decode($query->entities ?? '[]', true),
                    'created_at' => $query->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Check NLP service health
     * GET /api/chatbot/health
     */
    public function checkNLPHealth()
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Key' => env('NLP_SERVICE_KEY'),
            ])->timeout(10)->get("{$this->nlpApiUrl}/health");

            return response()->json([
                'success' => true,
                'nlp_status' => $response->json()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'nlp_status' => ['error' => 'Service unreachable']
            ], 503);
        }
    }

    /**
     * Send message to NLP Python/Flask API
     */
    private function sendToNLP(string $message): array
        {
            try {
                $response = Http::withHeaders([
                    'X-Internal-Key' => env('NLP_SERVICE_KEY'),
                    'Content-Type' => 'application/json',
                ])
                ->timeout($this->nlpTimeout)
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
     * Match recipes based on NLP entities - Sesuai schema database Anda
     */
    private function matchRecipes(array $nlpResult)
    {
        $nlpData = $nlpResult['nlp_result'] ?? [];
        $entities = $nlpData['entities'] ?? [];
        $intent = $nlpData['intent'] ?? 'search_recipe';

        // Mulai query dengan status approved (bukan published)
        $query = Recipe::query()
            ->where('status', 'approved')
            ->with(['recipeIngredients.ingredient']);

        // Match by ingredients (melalui pivot table recipe_ingredients)
        if (!empty($entities['ingredients']) && is_array($entities['ingredients'])) {
            $ingredients = $entities['ingredients'];
            
            $query->whereHas('recipeIngredients', function($q) use ($ingredients) {
                $q->whereHas('ingredient', function($q2) use ($ingredients) {
                    foreach ($ingredients as $ingredient) {
                        $q2->orWhere('ingredients.nama', 'LIKE', "%{$ingredient}%");
                    }
                });
            });
        }

        // Match by region/cuisine (kolom: region)
        if (!empty($entities['cuisine']) && is_array($entities['cuisine'])) {
            $query->whereIn('region', $entities['cuisine']);
        }

        // Match by dietary/health conditions (melalui recipe_suitability)
        if (!empty($entities['dietary']) && is_array($entities['dietary'])) {
            $query->whereHas('recipeSuitability', function($q) use ($entities) {
                $q->whereHas('healthCondition', function($q2) use ($entities) {
                    foreach ($entities['dietary'] as $diet) {
                        $q2->orWhere('health_conditions.nama', 'LIKE', "%{$diet}%");
                    }
                })->where('recipe_suitability.is_suitable', true);
            });
        }

        // Match by cooking time (kolom: waktu_masak)
        if (!empty($entities['time']) && is_array($entities['time'])) {
            $time = $entities['time'][0];
            if (is_numeric($time)) {
                $query->where('waktu_masak', '<=', (int)$time);
            }
        }

        // Match by category (kolom: kategori)
        if (!empty($entities['meal_type']) && is_array($entities['meal_type'])) {
            $query->whereIn('kategori', $entities['meal_type']);
        }

        // Execute query dengan limit
        $recipes = $query->limit(10)->get()->map(function($recipe) {
            // Ambil list nama bahan dari pivot table
            $ingredientsList = $recipe->recipeIngredients->map(function($ri) {
                return $ri->ingredient->nama ?? null;
            })->filter()->values()->toArray();

            return [
                'id' => $recipe->id,
                'title' => $recipe->nama,              
                'description' => $recipe->deskripsi,    
                'image_url' => $recipe->gambar,         
                'cuisine_type' => $recipe->region,      
                'cooking_time' => $recipe->waktu_masak, 
                'category' => $recipe->kategori,        
                'rating' => floatval($recipe->avg_rating), 
                'ingredients' => $ingredientsList,      
                'author' => 'Kala Rasa',               
            ];
        });

        return $recipes;
    }

    /**
     * Generate response message dalam Bahasa Indonesia
     */
    private function generateResponseMessage(array $nlpResult, $recipes): string
    {
        $recipeCount = count($recipes);

        if ($recipeCount === 0) {
            return "Maaf, saya tidak menemukan resep yang sesuai dengan kriteria Anda. Coba ubah kriteria pencarian atau tambahkan detail lain.";
        }

        $entities = $nlpResult['entities'] ?? [];
        $criteria = [];

        if (!empty($entities['ingredients']) && is_array($entities['ingredients'])) {
            $criteria[] = "bahan: " . implode(', ', $entities['ingredients']);
        }
        if (!empty($entities['cuisine']) && is_array($entities['cuisine'])) {
            $criteria[] = "region: " . implode(', ', $entities['cuisine']);
        }
        if (!empty($entities['time']) && is_array($entities['time'])) {
            $criteria[] = "waktu masak ≤ {$entities['time'][0]} menit";
        }

        $criteriaText = !empty($criteria) ? " dengan " . implode(", ", $criteria) : "";

        if ($recipeCount === 1) {
            return "Saya menemukan 1 resep{$criteriaText}. Berikut rekomendasinya:";
        }

        return "Saya menemukan {$recipeCount} resep{$criteriaText}. Berikut rekomendasinya:";
    }

    /**
     * Save chat history to user_queries table (bukan chat_histories)
     */
    private function saveUserQuery(int $userId, string $message, array $nlpResult): void
    {
        $nlpData = $nlpResult['nlp_result'] ?? [];
        
        // Map status dari Flask API ke enum database
        $action = $nlpData['action'] ?? '';
        $status = match($action) {
            'ask_clarification' => 'clarification',
            'fallback' => 'fallback',
            default => 'ok'
        };

        UserQuery::create([
            'user_id' => $userId,
            'query_text' => $message,
            'intent' => $nlpData['intent'] ?? null,
            'confidence' => $nlpData['confidence'] ?? null,
            'status' => $status, // enum: ok, fallback, clarification
            'entities' => json_encode($nlpData['entities'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
    }
}