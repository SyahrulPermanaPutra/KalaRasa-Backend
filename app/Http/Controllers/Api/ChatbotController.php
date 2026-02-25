<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\HealthCondition;
use App\Models\UserQuery;
use App\Models\MatchedRecipe;

class ChatbotController extends Controller
{
    private string $nlpApiUrl;
    private int    $nlpTimeout = 30;
    private int $sessionTtl = 60;

    public function __construct()
    {
        $this->nlpApiUrl = env('NLP_API_URL', 'http://127.0.0.1:5000');
    }

    /**
     * Proses pesan dari user dengan integrasi CBR.
     */
    public function processMessage(Request $request)
    {
        $request->validate([
            'message'    => 'required|string|max:500',
            'session_id' => 'nullable|string|max:100',
            'reset'      => 'boolean',
        ]);

        // Ambil user dari auth_user yang sudah di-set oleh middleware
        $user = $request->auth_user ?? $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $message    = trim($request->input('message'));
        $sessionId  = $request->input('session_id') ?: $this->generateSessionId($user->id);
        $reset      = (bool) $request->input('reset', false);

        try {
            // ── 1. Kirim ke NLP Python ────────────────────────────────────
            $nlp = $this->callNlpChat($sessionId, (string) $user->id, $message, $reset);

            if (! ($nlp['success'] ?? false)) {
                return $this->errorResponse('Layanan NLP tidak tersedia', $nlp['error'] ?? 'Unknown');
            }

            // ── 2. Simpan ke user_queries ─────────────────────────────────
            $userQuery = $this->saveUserQuery($user->id, $message, $nlp);

            $convState = $nlp['conversation_state'] ?? 'collecting';
            $action    = $nlp['action'] ?? 'search_recipes';

            // ── 3. CBR Matching jika sudah siap ───────────────────────
            $recipes = [];
            $cbrMeta = null;

            if ($convState === 'ready') {
                $entities  = $nlp['context_entities'] ?? $nlp['entities'] ?? [];
                $cbrResult = $this->matchRecipesWithCBR(
                    $sessionId,
                    $user->id,
                    $message,
                    $entities,
                    topK: 5
                );

                if (! empty($cbrResult['matched_recipes'])) {
                    $recipes = $this->hydrateRecipes($cbrResult['matched_recipes']);
                }
                
                $cbrMeta = $cbrResult ? [
                    'from_cache'       => $cbrResult['from_cache'] ?? false,
                    'total_candidates' => $cbrResult['total_candidates'] ?? 0,
                    'query_hash'       => $cbrResult['query_hash'] ?? null,
                ] : null;
            } else {
                // Fallback ke database matching biasa
                $entities = $nlp['context_entities'] ?? $nlp['entities'] ?? [];
                $recipes = $this->matchRecipesFromDB($entities);
            }

            // Simpan matched_recipes ke DB
            if ($userQuery && count($recipes) > 0) {
                $this->saveMatchedRecipes($userQuery->id, $recipes);
            }

            // ── 4. Handle action khusus ─────────────────
            return match ($action) {
                'show_restrictions'  => $this->handleShowRestrictions($nlp, $sessionId),
                'show_detail'        => $this->handleShowDetail($nlp, $sessionId, $recipes),
                'chitchat'           => $this->chatResponse(
                    sessionId:    $sessionId,
                    botMessage:   $nlp['bot_message'],
                    quickReplies: $nlp['quick_replies'] ?? [],
                    recipes:      [],
                    nlp:          $nlp,
                    type:         'chat',
                    cbrMeta:      null,
                ),
                default => $this->buildSearchResponse($sessionId, $nlp, $recipes, $cbrMeta),
            };

        } catch (\Exception $e) {
            Log::error('ChatbotController Error', [
                'user_id' => $user->id,
                'message' => $message,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Terjadi kesalahan sistem, silakan coba lagi.');
        }
    }

    /**
     * Direct CBR search tanpa melalui percakapan.
     */
    public function directSearch(Request $request)
    {
        $request->validate([
            'session_id'            => 'required|string',
            'ingredients'           => 'array',
            'avoid_ingredients'     => 'array',
            'health_conditions'     => 'array',
            'time_constraint'       => 'nullable|integer|min:5|max:480',
            'region'                => 'nullable|string|max:100',
            'top_k'                 => 'integer|min:1|max:10',
        ]);

        $user = $request->auth_user ?? $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $entities = [
            'ingredients' => [
                'main'  => $request->input('ingredients', []),
                'avoid' => $request->input('avoid_ingredients', []),
            ],
            'health_conditions' => $request->input('health_conditions', []),
            'time_constraint'   => $request->input('time_constraint'),
            'region'            => $request->input('region'),
        ];

        $queryText = $this->buildQueryText($entities);

        $cbrResult = $this->callNlpMatchRecipes(
            $request->session_id,
            $user->id,
            $queryText,
            $entities,
            $request->input('top_k', 5)
        );

        if (! ($cbrResult['success'] ?? false)) {
            return $this->errorResponse('Gagal mencari resep. Silakan coba lagi.');
        }

        $recipes = $this->hydrateRecipes($cbrResult['matched_recipes'] ?? []);

        return response()->json([
            'success'  => true,
            'type'     => 'recipe_results',
            'recipes'  => $recipes,
            'total'    => count($recipes),
            'cbr_meta' => [
                'from_cache'       => $cbrResult['from_cache'] ?? false,
                'total_candidates' => $cbrResult['total_candidates'] ?? 0,
                'query_hash'       => $cbrResult['query_hash'] ?? null,
                'algorithm'        => $cbrResult['cbr_metadata']['algorithm'] ?? 'CBR',
            ],
        ]);
    }

    /**
     * Get chat history
     */
    public function getHistory(Request $request)
    {
        $user = $request->auth_user ?? $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $limit = min((int) $request->input('limit', 50), 100);

        $history = UserQuery::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($q) => [
                'id'         => $q->id,
                'message'    => $q->query_text,
                'intent'     => $q->intent,
                'confidence' => $q->confidence,
                'status'     => $q->status,
                'entities'   => json_decode($q->entities ?? '{}', true),
                'created_at' => $q->created_at,
            ]);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Reset sesi percakapan
     */
    public function resetSession(Request $request)
    {
        $user = $request->auth_user ?? $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $sessionId = $request->input('session_id');

        if ($sessionId) {
            // Reset di Python NLP
            $this->callNlpDeleteSession($sessionId);

            // Hapus dari cache Laravel
            Cache::forget("chatbot_session_{$sessionId}");
            Cache::forget("chatbot_last_results:{$sessionId}");
        }

        return response()->json([
            'success'        => true,
            'message'        => 'Sesi percakapan direset.',
            'new_session_id' => $this->generateSessionId($user->id),
        ]);
    }

    /**
     * Check NLP service health
     */
    public function checkNlpHealth()
    {
        try {
            $response = Http::withHeaders(['X-Internal-Key' => env('NLP_SERVICE_KEY')])
                ->timeout(10)
                ->get("{$this->nlpApiUrl}/health");

            return response()->json([
                'success'    => true,
                'nlp_status' => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'    => false,
                'nlp_status' => ['error' => 'Service unreachable'],
            ], 503);
        }
    }

    // =========================================================================
    // Action Handlers
    // =========================================================================

    private function buildSearchResponse(string $sessionId, array $nlp, array $recipes, ?array $cbrMeta): \Illuminate\Http\JsonResponse
    {
        $botMessage   = $this->buildRecipeResponseMessage($nlp, $recipes);
        $quickReplies = $this->buildQuickReplies($nlp, $recipes);

        if (count($recipes) > 0) {
            $this->cacheSessionRecipes($sessionId, collect($recipes)->pluck('id')->toArray());
            Cache::put("chatbot_last_results:{$sessionId}", $recipes, now()->addMinutes($this->sessionTtl));
        }

        return $this->chatResponse(
            sessionId:    $sessionId,
            botMessage:   $botMessage,
            quickReplies: $quickReplies,
            recipes:      $recipes,
            nlp:          $nlp,
            type:         'recipe_results',
            cbrMeta:      $cbrMeta,
        );
    }

    private function handleShowDetail(array $nlp, string $sessionId, array $currentRecipes): \Illuminate\Http\JsonResponse
    {
        $recipeIndex  = ($nlp['recipe_index'] ?? 1) - 1;
        
        $recipes = !empty($currentRecipes) ? $currentRecipes : $this->getCachedSessionRecipesWithDetails($sessionId);
        
        if (empty($recipes)) {
            return $this->chatResponse(
                sessionId:    $sessionId,
                botMessage:   'Belum ada resep yang dicari. Coba cari resep dulu ya! 😊',
                quickReplies: ['Cari resep ayam', 'Resep untuk diabetes', 'Masakan Padang'],
                recipes:      [],
                nlp:          $nlp,
                type:         'chat',
            );
        }

        $recipe = $recipes[$recipeIndex] ?? $recipes[0];

        if (is_numeric($recipe)) {
            $recipeId = $recipe;
            $recipeData = Recipe::with([
                'recipeIngredients.ingredient',
                'recipeSuitability.healthCondition',
            ])->find($recipeId);
            
            if (!$recipeData) {
                return $this->chatResponse(
                    sessionId:    $sessionId,
                    botMessage:   'Resep tidak ditemukan. Coba cari lagi ya!',
                    quickReplies: ['Cari resep baru'],
                    recipes:      [],
                    nlp:          $nlp,
                    type:         'chat',
                );
            }
            
            $formattedRecipe = $this->formatRecipe($recipeData);
        } else {
            $formattedRecipe = $recipe;
        }

        return $this->chatResponse(
            sessionId:    $sessionId,
            botMessage:   "📖 Berikut detail resep **{$formattedRecipe['nama']}**:",
            quickReplies: ['Cari resep lain'],
            recipes:      [$formattedRecipe],
            nlp:          $nlp,
            type:         'recipe_detail',
        );
    }

    private function handleShowRestrictions(array $nlp, string $sessionId): \Illuminate\Http\JsonResponse
    {
        $healthConditions = $nlp['context_entities']['health_conditions']
            ?? $nlp['entities']['health_conditions']
            ?? [];

        if (empty($healthConditions)) {
            return $this->chatResponse(
                sessionId:    $sessionId,
                botMessage:   'Sebutkan kondisi kesehatanmu ya, misalnya: diabetes, kolesterol, hipertensi. 😊',
                quickReplies: ['Diabetes', 'Kolesterol', 'Hipertensi', 'Asam Urat'],
                recipes:      [],
                nlp:          $nlp,
                type:         'chat',
            );
        }

        $lines = [];
        foreach ($healthConditions as $condName) {
            $cond = HealthCondition::where('nama', $condName)
                ->with(['restrictions.ingredient'])
                ->first();

            if (!$cond) continue;

            $lines[] = "\n📋 **Pantangan untuk {$cond->nama}:**";
            $hindari = $cond->restrictions->where('severity', 'hindari');
            $batasi  = $cond->restrictions->where('severity', 'batasi');
            $anjuran = $cond->restrictions->where('severity', 'anjuran');

            if ($hindari->isNotEmpty()) {
                $lines[] = '🚫 Hindari: ' . $hindari->map(fn ($r) => $r->ingredient->nama)->implode(', ');
            }
            if ($batasi->isNotEmpty()) {
                $lines[] = '⚠️ Batasi: ' . $batasi->map(fn ($r) => $r->ingredient->nama)->implode(', ');
            }
            if ($anjuran->isNotEmpty()) {
                $lines[] = '✅ Dianjurkan: ' . $anjuran->map(fn ($r) => $r->ingredient->nama)->implode(', ');
            }
        }

        $botMsg = implode("\n", $lines) ?: 'Informasi kondisi kesehatan tidak ditemukan.';

        return $this->chatResponse(
            sessionId:    $sessionId,
            botMessage:   $botMsg,
            quickReplies: ['Carikan resep yang cocok', 'Kondisi lain'],
            recipes:      [],
            nlp:          $nlp,
            type:         'chat',
        );
    }

    // =========================================================================
    // CBR Integration Helpers
    // =========================================================================

    private function callNlpChat(string $sessionId, string $userId, string $message, bool $reset = false): array
    {
        try {
            $payload = [
                'session_id' => $sessionId,
                'user_id'    => $userId,
                'message'    => $message,
            ];
            
            if ($reset) {
                $payload['reset'] = true;
            }

            $response = Http::withHeaders([
                'X-Internal-Key' => env('NLP_SERVICE_KEY'),
                'Content-Type'   => 'application/json',
            ])
            ->timeout($this->nlpTimeout)
            ->post("{$this->nlpApiUrl}/api/chat", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('NLP API Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['success' => false, 'error' => 'NLP API returned ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('NLP Connection Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Cannot connect to NLP service'];
        }
    }

    private function callNlpMatchRecipes(string $sessionId, int $userId, string $queryText, array $entities, int $topK = 5): array
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Key' => env('NLP_SERVICE_KEY'),
                'Content-Type'   => 'application/json',
            ])
            ->timeout($this->nlpTimeout)
            ->post("{$this->nlpApiUrl}/api/cbr/match", [
                'session_id' => $sessionId,
                'user_id'    => (string) $userId,
                'query_text' => $queryText,
                'entities'   => $entities,
                'top_k'      => $topK,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('NLP CBR API Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['success' => false, 'matched_recipes' => []];

        } catch (\Exception $e) {
            Log::error('NLP CBR Connection Error: ' . $e->getMessage());
            return ['success' => false, 'matched_recipes' => []];
        }
    }

    private function callNlpDeleteSession(string $sessionId): void
    {
        try {
            Http::withHeaders(['X-Internal-Key' => env('NLP_SERVICE_KEY')])
                ->timeout(10)
                ->delete("{$this->nlpApiUrl}/api/session/{$sessionId}");
        } catch (\Exception $e) {
            Log::warning('Failed to delete NLP session: ' . $e->getMessage());
        }
    }

    private function matchRecipesWithCBR(string $sessionId, int $userId, string $message, array $entities, int $topK = 5): array
    {
        $queryText = $this->buildQueryText($entities);
        return $this->callNlpMatchRecipes($sessionId, $userId, $queryText, $entities, $topK);
    }

    private function hydrateRecipes(array $matchedRecipes): array
    {
        if (empty($matchedRecipes)) {
            return [];
        }

        $recipeIds = array_column($matchedRecipes, 'recipe_id');
        $matchMap  = array_column($matchedRecipes, null, 'recipe_id');

        $dbRecipes = Recipe::with([
                'recipeIngredients.ingredient',
                'recipeSuitability.healthCondition',
            ])
            ->whereIn('id', $recipeIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($matchedRecipes as $match) {
            $rid    = $match['recipe_id'];
            $recipe = $dbRecipes->get($rid);

            if (!$recipe) {
                continue;
            }

            $formatted = $this->formatRecipe($recipe);
            
            $formatted['rank_position']    = $match['rank_position'];
            $formatted['match_score']      = $match['match_score'];
            $formatted['ingredients_main'] = $match['ingredients_main'] ?? [];
            $formatted['suitable_for']     = $match['suitable_for'] ?? [];
            $formatted['score_breakdown']  = $match['score_breakdown'] ?? [];

            $result[] = $formatted;
        }

        usort($result, fn($a, $b) => $a['rank_position'] <=> $b['rank_position']);

        return $result;
    }

    private function buildQueryText(array $entities): string
    {
        $parts = [];
        $ings  = $entities['ingredients']['main'] ?? [];
        if ($ings) {
            $parts[] = 'mau masak ' . implode(' ', $ings);
        }
        if ($conds = $entities['health_conditions'] ?? []) {
            $parts[] = 'untuk ' . implode(' ', $conds);
        }
        if ($region = $entities['region'] ?? null) {
            $parts[] = "masakan {$region}";
        }
        if ($time = $entities['time_constraint'] ?? null) {
            $parts[] = "kurang dari {$time} menit";
        }
        return implode(' ', $parts) ?: 'resep masakan';
    }

    // =========================================================================
    // Database Recipe Matching (Fallback)
    // =========================================================================

    private function matchRecipesFromDB(array $entities): array
    {
        $query = Recipe::query()
            ->where('status', 'approved')
            ->with([
                'recipeIngredients.ingredient',
                'recipeSuitability.healthCondition',
            ]);

        $mainIngredients = $entities['ingredients']['main'] ?? [];
        if (!empty($mainIngredients)) {
            $query->whereHas('recipeIngredients.ingredient', function ($q) use ($mainIngredients) {
                $q->where(function ($inner) use ($mainIngredients) {
                    foreach ($mainIngredients as $ing) {
                        $inner->orWhere('nama', 'LIKE', "%{$ing}%");
                    }
                });
            });
        }

        $avoidIngredients = $entities['ingredients']['avoid'] ?? [];
        if (!empty($avoidIngredients)) {
            $query->whereDoesntHave('recipeIngredients.ingredient', function ($q) use ($avoidIngredients) {
                $q->where(function ($inner) use ($avoidIngredients) {
                    foreach ($avoidIngredients as $ing) {
                        $inner->orWhere('nama', 'LIKE', "%{$ing}%");
                    }
                });
            });
        }

        $healthConditions = $entities['health_conditions'] ?? [];
        if (!empty($healthConditions)) {
            foreach ($healthConditions as $condName) {
                $query->whereHas('recipeSuitability', function ($q) use ($condName) {
                    $q->whereHas('healthCondition', fn ($q2) =>
                        $q2->where('nama', 'LIKE', "%{$condName}%")
                    )->where('is_suitable', true);
                });
            }
        }

        $timeConstraint = $entities['time_constraint'] ?? null;
        if ($timeConstraint !== null) {
            $query->where('waktu_masak', '<=', (int) $timeConstraint);
        }

        $region = $entities['region'] ?? null;
        if ($region) {
            $query->where('region', 'LIKE', "%{$region}%");
        }

        $query->orderByDesc('avg_rating')
              ->orderByDesc('view_count');

        $recipes = $query->limit(8)->get();

        return $recipes->map(fn ($r) => $this->formatRecipe($r))->toArray();
    }

    // =========================================================================
    // Formatters & Helpers
    // =========================================================================

    private function formatRecipe($recipe): array
    {
        $ingredients = $recipe->recipeIngredients
            ? $recipe->recipeIngredients->map(fn ($ri) => [
                'nama'    => $ri->ingredient->nama ?? null,
                'jumlah'  => $ri->jumlah,
                'satuan'  => $ri->satuan,
                'is_main' => (bool) $ri->is_main,
            ])->filter(fn ($i) => $i['nama'])->values()->toArray()
            : [];

        $suitability = $recipe->recipeSuitability
            ? $recipe->recipeSuitability->map(fn ($s) => [
                'condition'  => $s->healthCondition->nama ?? null,
                'is_suitable'=> (bool) $s->is_suitable,
                'notes'      => $s->notes,
            ])->filter(fn ($s) => $s['condition'])->values()->toArray()
            : [];

        return [
            'id'           => $recipe->id,
            'nama'         => $recipe->nama,
            'deskripsi'    => $recipe->deskripsi,
            'gambar'       => $recipe->gambar,
            'region'       => $recipe->region,
            'waktu_masak'  => $recipe->waktu_masak,
            'kategori'     => $recipe->kategori,
            'avg_rating'   => (float) $recipe->avg_rating,
            'total_ratings'=> (int) $recipe->total_ratings,
            'view_count'   => (int) $recipe->view_count,
            'ingredients'  => $ingredients,
            'suitability'  => $suitability,
        ];
    }

    private function buildRecipeResponseMessage(array $nlp, array $recipes): string
    {
        $count = count($recipes);
        if ($count === 0) {
            $suggestions = [
                '😔 Hmm, belum ada resep yang cocok dengan kriteriamu.',
                '',
                'Coba ubah atau kurangi filter pencarian, misalnya:',
                '• Ganti bahan dengan yang lebih umum',
                '• Hapus batasan waktu masak',
                '• Coba tanpa filter region',
            ];
            return implode("\n", $suggestions);
        }

        $entities = $nlp['context_entities'] ?? $nlp['entities'] ?? [];
        $parts    = [];

        $main = $entities['ingredients']['main'] ?? [];
        if (!empty($main)) {
            $parts[] = 'bahan **' . implode(', ', $main) . '**';
        }

        $health = $entities['health_conditions'] ?? [];
        if (!empty($health)) {
            $parts[] = 'cocok untuk **' . implode(', ', $health) . '**';
        }

        $region = $entities['region'] ?? null;
        if ($region) {
            $parts[] = "masakan **{$region}**";
        }

        $time = $entities['time_constraint'] ?? null;
        if ($time) {
            $parts[] = "waktu masak ≤ **{$time} menit**";
        }

        $criteriaText = !empty($parts) ? ' dengan ' . implode(', ', $parts) : '';

        return $count === 1
            ? "✅ Aku menemukan **1 resep**{$criteriaText}:"
            : "✅ Aku menemukan **{$count} resep**{$criteriaText}:";
    }

    private function buildQuickReplies(array $nlp, array $recipes): array
    {
        $replies = [];

        if (count($recipes) > 0) {
            $replies[] = 'Lihat detail resep 1';
            if (count($recipes) > 1) {
                $replies[] = 'Lihat detail resep 2';
                $replies[] = 'Lihat detail resep 3';
            }
        }

        $health = $nlp['context_entities']['health_conditions'] ?? [];
        if (empty($health)) {
            $replies[] = 'Resep untuk diabetes';
            $replies[] = 'Resep untuk kolesterol';
        }

        $replies[] = 'Cari resep lain';
        return array_slice(array_unique($replies), 0, 5);
    }

    // =========================================================================
    // Database Helpers
    // =========================================================================

    private function saveUserQuery(int $userId, string $message, array $nlp): ?UserQuery
    {
        try {
            $status = match ($nlp['status'] ?? 'ok') {
                'clarification' => 'clarification',
                'fallback'      => 'fallback',
                default         => 'ok',
            };

            return UserQuery::create([
                'user_id'    => $userId,
                'query_text' => $message,
                'intent'     => $nlp['intent']     ?? null,
                'confidence' => $nlp['confidence'] ?? null,
                'status'     => $status,
                'entities'   => json_encode(
                    $nlp['entities'] ?? [],
                    JSON_UNESCAPED_UNICODE
                ),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to save user_query: ' . $e->getMessage());
            return null;
        }
    }

    private function saveMatchedRecipes(int $queryId, array $recipes): void
    {
        try {
            foreach ($recipes as $rank => $recipe) {
                MatchedRecipe::create([
                    'user_query_id' => $queryId,
                    'recipe_id'     => $recipe['id'],
                    'match_score'   => $recipe['match_score'] ?? $recipe['avg_rating'] ?? 0,
                    'rank_position' => $rank + 1,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to save matched_recipes: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // Session Cache
    // =========================================================================

    private function generateSessionId(int $userId): string
    {
        return "user_{$userId}_" . Str::random(12);
    }

    private function cacheSessionRecipes(string $sessionId, array $recipeIds): void
    {
        Cache::put(
            "chatbot_session_{$sessionId}_recipes",
            $recipeIds,
            now()->addMinutes($this->sessionTtl)
        );
    }

    private function getCachedSessionRecipes(string $sessionId): array
    {
        return Cache::get("chatbot_session_{$sessionId}_recipes", []);
    }

    private function getCachedSessionRecipesWithDetails(string $sessionId): array
    {
        return Cache::get("chatbot_last_results:{$sessionId}", []);
    }

    // =========================================================================
    // Response Builder
    // =========================================================================

    private function chatResponse(
        string $sessionId,
        string $botMessage,
        array  $quickReplies,
        array  $recipes,
        array  $nlp,
        string $type = 'chat',
        ?array $cbrMeta = null,
    ): \Illuminate\Http\JsonResponse {
        $response = [
            'success'              => true,
            'type'                 => $type,
            'session_id'           => $sessionId,
            'bot_message'          => $botMessage,
            'quick_replies'        => $quickReplies,
            'recipes'              => $recipes,
            'total_found'          => count($recipes),
            'conversation_state'   => $nlp['conversation_state'] ?? 'collecting',
            'clarification_needed' => $nlp['clarification_needed'] ?? false,
            'clarification_question' => $nlp['clarification_question'] ?? null,
            'turn_count'           => $nlp['turn_count'] ?? 0,
            'nlp_data' => [
                'intent'     => $nlp['intent']     ?? null,
                'confidence' => $nlp['confidence'] ?? null,
                'status'     => $nlp['status']     ?? null,
                'entities'   => $nlp['entities']   ?? [],
            ],
        ];

        if ($cbrMeta !== null) {
            $response['cbr_meta'] = $cbrMeta;
        }

        return response()->json($response);
    }

    private function errorResponse(string $message, ?string $detail = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success'     => false,
            'type'        => 'error',
            'bot_message' => $message,
            'recipes'     => [],
            'error'       => $detail,
        ], 500);
    }
}