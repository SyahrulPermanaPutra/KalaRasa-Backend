<?php
/**
 * ChatbotController — Refactored
 *
 * Controller ini sengaja dibuat TIPIS (thin controller).
 * Semua bisnis logic didelegasikan ke service classes:
 *
 *   NlpService      → komunikasi ke Flask NLP
 *   CbrService      → CBR matching + recipe hydration
 *   SessionService  → cache management session chatbot
 *
 * Alur per request:
 *   [1] Validasi & ambil user
 *   [2] NlpService::chat() → intent + entities dari Flask
 *   [3] Log query ke DB
 *   [4] CbrService atau DB matching sesuai conv_state
 *   [5] Route ke action handler
 *   [6] Bangun response via ResponseBuilder
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessMessageRequest;
use App\Http\Requests\DirectSearchRequest;
use App\Services\NlpService;
use App\Services\CbrService;
use App\Services\SessionService;
use App\Models\UserQuery;
use App\Models\MatchedRecipe;
use App\Models\HealthCondition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    private int $sessionTtl = 60; // menit

    public function __construct(
        private readonly NlpService      $nlp,
        private readonly CbrService      $cbr,
        private readonly SessionService  $session,
    ) {}

    // =========================================================================
    // ENDPOINT 1: Proses pesan percakapan
    // =========================================================================

    public function processMessage(ProcessMessageRequest $request): JsonResponse
    {
        $user      = $request->auth_user ?? $request->user();
        $message   = trim($request->input('message'));
        $sessionId = $request->input('session_id') ?: $this->generateSessionId($user->id);
        $reset     = (bool) $request->input('reset', false);

        try {
            // [2] NLP Processing — satu-satunya call ke Flask untuk intent/entity
            $nlp = $this->nlp->chat($sessionId, (string) $user->id, $message, $reset);

            if (! ($nlp['success'] ?? false)) {
                Log::warning('NLP unavailable', ['session' => $sessionId, 'error' => $nlp['error'] ?? '?']);
                return $this->errorResponse('Layanan NLP tidak tersedia saat ini.');
            }

            // [3] Log ke DB
            $userQuery = $this->saveUserQuery($user->id, $message, $nlp);
            $this->session->cacheLastQuery($sessionId, $message, $this->sessionTtl);

            $convState = $nlp['conversation_state'] ?? 'collecting';
            $action    = $nlp['action'] ?? 'search_recipes';

            // [4] Recipe matching — gunakan SELALU context_entities dari Flask
            // Lihat CATATAN PENTING di CbrService::matchOrFallback()
            [$recipes, $cbrMeta] = $this->resolveRecipes($sessionId, $user->id, $message, $nlp, $convState);

            // [5] Simpan matched recipes
            if ($userQuery && count($recipes) > 0) {
                $this->saveMatchedRecipes($userQuery->id, $recipes);
            }

            // [6] Route ke action handler
            return $this->routeAction($action, $sessionId, $nlp, $recipes, $cbrMeta);

        } catch (\Exception $e) {
            Log::error('ChatbotController::processMessage', [
                'user_id' => $user->id,
                'message' => $message,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Terjadi kesalahan sistem, silakan coba lagi.');
        }
    }

    // =========================================================================
    // ENDPOINT 2: Direct CBR search (tanpa percakapan)
    // =========================================================================

    public function directSearch(DirectSearchRequest $request): JsonResponse
    {
        $user = $request->auth_user ?? $request->user();

        // Pencarian nama resep
        if ($searchName = $request->input('search_name')) {
            $recipes = $this->cbr->searchByName(
                $searchName,
                $request->input('top_k', 10),
                $request->input('health_conditions', []),
                $request->input('time_constraint'),
            );
            return response()->json([
                'success'      => true,
                'type'         => 'recipe_results',
                'search_type'  => 'name_search',
                'search_query' => $searchName,
                'recipes'      => $recipes,
                'total'        => count($recipes),
            ]);
        }

        // CBR matching dengan entities langsung
        $entities = [
            'ingredients'       => [
                'main'  => $request->input('ingredients', []),
                'avoid' => $request->input('avoid_ingredients', []),
            ],
            'health_conditions' => $request->input('health_conditions', []),
            'time_constraint'   => $request->input('time_constraint'),
            'region'            => $request->input('region'),
        ];

        $result = $this->cbr->match(
            $request->input('session_id'),
            $user->id,
            $entities,
            $request->input('top_k', 5),
        );

        return response()->json([
            'success'     => true,
            'type'        => 'recipe_results',
            'search_type' => 'cbr_match',
            'recipes'     => $result['recipes'],
            'total'       => count($result['recipes']),
            'cbr_meta'    => $result['meta'] ?? null,
        ]);
    }

    // =========================================================================
    // ENDPOINT 3: History percakapan
    // =========================================================================

    public function getHistory(Request $request): JsonResponse
    {
        $user  = $request->auth_user ?? $request->user();
        $limit = min((int) $request->input('limit', 50), 100);

        $history = UserQuery::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($q) => [
                'id'         => $q->id,
                'message'    => $q->query_text,
                'intent'     => $q->intent,
                'confidence' => $q->confidence,
                'status'     => $q->status,
                'entities'   => json_decode($q->entities ?? '{}', true),
                'created_at' => $q->created_at,
            ]);

        return response()->json(['success' => true, 'history' => $history]);
    }

    // =========================================================================
    // ENDPOINT 4: Reset sesi
    // =========================================================================

    public function resetSession(Request $request): JsonResponse
    {
        $user      = $request->auth_user ?? $request->user();
        $sessionId = $request->input('session_id');

        if ($sessionId) {
            $this->nlp->deleteSession($sessionId);
            $this->session->clearAll($sessionId);
        }

        return response()->json([
            'success'        => true,
            'message'        => 'Sesi percakapan direset.',
            'new_session_id' => $this->generateSessionId($user->id),
        ]);
    }

    // =========================================================================
    // ENDPOINT 5: Health check NLP
    // =========================================================================

    public function checkNlpHealth(): JsonResponse
    {
        $status = $this->nlp->health();
        return response()->json([
            'success'    => $status['success'] ?? false,
            'nlp_status' => $status,
        ], ($status['success'] ?? false) ? 200 : 503);
    }

    // =========================================================================
    // Private: Recipe resolution
    // =========================================================================

    /**
     * Resolusi resep berdasarkan conversation state.
     *
     * CATATAN PENTING — Entity yang digunakan untuk matching:
     *
     *   state=ready   → gunakan $nlp['context_entities'] (akumulasi bersih dari Flask)
     *   state=lainnya → gunakan $nlp['entities'] (dari pesan saat ini saja)
     *
     * JANGAN pernah merge $nlp['entities'] + $nlp['context_entities'] secara mandiri
     * di sini. Flask (Python) sudah mengelola topic-switch detection dengan benar.
     * Merge mandiri akan memunculkan kembali entity lama yang sudah di-replace.
     *
     * @return array{0: array, 1: array|null}  [recipes, cbrMeta]
     */
    private function resolveRecipes(
        string $sessionId,
        int    $userId,
        string $message,
        array  $nlp,
        string $convState,
    ): array {
        if ($convState === 'ready') {
            $entities = $nlp['context_entities'] ?? [];

            // Guard: context_entities kosong padahal state=ready → anomali
            if ($this->isEmptyEntities($entities)) {
                Log::warning('context_entities kosong saat state=ready', [
                    'session_id' => $sessionId,
                    'intent'     => $nlp['intent'] ?? null,
                ]);
                // Fallback ke entity turn ini (lebih baik dari tidak ada sama sekali)
                $entities = $nlp['entities'] ?? [];
                $recipes  = $this->cbr->matchFromDb($entities);
                return [$recipes, null];
            }

            $result  = $this->cbr->match($sessionId, $userId, $entities);
            $recipes = $result['recipes'];
            $meta    = $result['meta'] ?? null;

            return [$recipes, $meta];
        }

        // state != ready: gunakan entity dari pesan saat ini saja
        $entities = $nlp['entities'] ?? [];

        if ($this->isEmptyEntities($entities)) {
            return [[], null]; // tidak ada entity bermakna → jangan search
        }

        return [$this->cbr->matchFromDb($entities), null];
    }

    private function isEmptyEntities(array $entities): bool
    {
        return empty($entities['ingredients']['main'] ?? [])
            && empty($entities['health_conditions'] ?? [])
            && empty($entities['region'] ?? null)
            && is_null($entities['time_constraint'] ?? null);
    }

    // =========================================================================
    // Private: Action routing
    // =========================================================================

    private function routeAction(
        string  $action,
        string  $sessionId,
        array   $nlp,
        array   $recipes,
        ?array  $cbrMeta,
    ): JsonResponse {
        return match ($action) {
            'show_restrictions' => $this->handleShowRestrictions($nlp, $sessionId),
            'show_detail'       => $this->handleShowDetail($nlp, $sessionId, $recipes),
            'chitchat'          => $this->buildChatResponse(
                sessionId:    $sessionId,
                botMessage:   $nlp['bot_message'],
                quickReplies: $nlp['quick_replies'] ?? [],
                recipes:      [],
                nlp:          $nlp,
                type:         'chat',
            ),
            default => $this->buildSearchResponse($sessionId, $nlp, $recipes, $cbrMeta),
        };
    }

    // =========================================================================
    // Private: Action handlers
    // =========================================================================

    private function buildSearchResponse(
        string $sessionId,
        array  $nlp,
        array  $recipes,
        ?array $cbrMeta,
    ): JsonResponse {
        // Gunakan context_entities (bukan entities mentah) untuk pesan response
        $contextEntities = $nlp['context_entities'] ?? [];

        if (count($recipes) > 0) {
            $this->session->cacheRecipes($sessionId, $recipes, $this->sessionTtl);
        }

        return $this->buildChatResponse(
            sessionId:    $sessionId,
            botMessage:   $this->buildRecipeMessage($contextEntities, count($recipes)),
            quickReplies: $this->buildQuickReplies($contextEntities, $recipes),
            recipes:      $recipes,
            nlp:          $nlp,
            type:         'recipe_results',
            cbrMeta:      $cbrMeta,
        );
    }

    private function handleShowDetail(array $nlp, string $sessionId, array $currentRecipes): JsonResponse
    {
        $recipeIndex = ($nlp['recipe_index'] ?? 1) - 1;
        $recipes     = !empty($currentRecipes)
            ? $currentRecipes
            : $this->session->getCachedRecipes($sessionId);

        if (empty($recipes)) {
            return $this->buildChatResponse(
                sessionId:    $sessionId,
                botMessage:   'Belum ada resep yang dicari. Coba cari resep dulu ya! 😊',
                quickReplies: ['Cari resep ayam', 'Resep untuk diabetes'],
                recipes:      [],
                nlp:          $nlp,
                type:         'chat',
            );
        }

        $recipe = $recipes[$recipeIndex] ?? $recipes[0];

        return $this->buildChatResponse(
            sessionId:    $sessionId,
            botMessage:   "📖 Berikut detail resep **{$recipe['nama']}**:",
            quickReplies: ['Simpan ke favorit', 'Cari resep lain'],
            recipes:      [$recipe],
            nlp:          $nlp,
            type:         'recipe_detail',
        );
    }

    private function handleShowRestrictions(array $nlp, string $sessionId): JsonResponse
    {
        $healthConditions = $nlp['context_entities']['health_conditions'] ?? [];

        if (empty($healthConditions)) {
            return $this->buildChatResponse(
                sessionId:    $sessionId,
                botMessage:   'Sebutkan kondisi kesehatanmu ya, misalnya: diabetes, kolesterol. 😊',
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

            if ($hindari->isNotEmpty())
                $lines[] = '🚫 Hindari: ' . $hindari->map(fn($r) => $r->ingredient->nama)->implode(', ');
            if ($batasi->isNotEmpty())
                $lines[] = '⚠️ Batasi: ' . $batasi->map(fn($r) => $r->ingredient->nama)->implode(', ');
            if ($anjuran->isNotEmpty())
                $lines[] = '✅ Dianjurkan: ' . $anjuran->map(fn($r) => $r->ingredient->nama)->implode(', ');
        }

        return $this->buildChatResponse(
            sessionId:    $sessionId,
            botMessage:   implode("\n", $lines) ?: 'Informasi kondisi kesehatan tidak ditemukan.',
            quickReplies: ['Carikan resep yang cocok', 'Kondisi lain'],
            recipes:      [],
            nlp:          $nlp,
            type:         'chat',
        );
    }

    // =========================================================================
    // Private: Response builder
    // =========================================================================

    /**
     * Bangun pesan deskripsi hasil pencarian resep.
     * Menggunakan context_entities (bukan entities mentah) agar konsisten
     * dengan entity yang dipakai untuk CBR matching.
     */
    private function buildRecipeMessage(array $contextEntities, int $count): string
    {
        if ($count === 0) {
            return implode("\n", [
                '😔 Hmm, belum ada resep yang cocok dengan kriteriamu.',
                '',
                'Coba ubah atau kurangi filter pencarian:',
                '• Ganti bahan dengan yang lebih umum',
                '• Hapus batasan waktu masak',
                '• Coba tanpa filter region',
            ]);
        }

        $parts = [];
        if ($main = $contextEntities['ingredients']['main'] ?? [])
            $parts[] = 'bahan **' . implode(', ', $main) . '**';
        if ($health = $contextEntities['health_conditions'] ?? [])
            $parts[] = 'cocok untuk **' . implode(', ', $health) . '**';
        if ($region = $contextEntities['region'] ?? null)
            $parts[] = "masakan **{$region}**";
        if ($time = $contextEntities['time_constraint'] ?? null)
            $parts[] = "waktu masak ≤ **{$time} menit**";

        $criteria = !empty($parts) ? ' dengan ' . implode(', ', $parts) : '';
        return $count === 1
            ? "✅ Aku menemukan **1 resep**{$criteria}:"
            : "✅ Aku menemukan **{$count} resep**{$criteria}:";
    }

    private function buildQuickReplies(array $contextEntities, array $recipes): array
    {
        $replies = [];

        if (count($recipes) > 0) {
            $replies[] = 'Lihat detail resep 1';
            if (count($recipes) > 1) $replies[] = 'Lihat detail resep 2';
            if (count($recipes) > 2) $replies[] = 'Lihat detail resep 3';
        }

        if (empty($contextEntities['health_conditions'] ?? [])) {
            $replies[] = 'Resep untuk diabetes';
            $replies[] = 'Resep untuk kolesterol';
        }

        $replies[] = 'Cari resep lain';
        return array_slice(array_unique($replies), 0, 5);
    }

    private function buildChatResponse(
        string  $sessionId,
        string  $botMessage,
        array   $quickReplies,
        array   $recipes,
        array   $nlp,
        string  $type = 'chat',
        ?array  $cbrMeta = null,
    ): JsonResponse {
        $response = [
            'success'                => true,
            'type'                   => $type,
            'session_id'             => $sessionId,
            'bot_message'            => $botMessage,
            'quick_replies'          => $quickReplies,
            'recipes'                => $recipes,
            'total_found'            => count($recipes),
            'conversation_state'     => $nlp['conversation_state'] ?? 'collecting',
            'clarification_needed'   => $nlp['clarification_needed'] ?? false,
            'clarification_question' => $nlp['clarification_question'] ?? null,
            'turn_count'             => $nlp['turn_count'] ?? 0,
            'nlp_data'               => [
                'intent'     => $nlp['intent'] ?? null,
                'confidence' => $nlp['confidence'] ?? null,
                'status'     => $nlp['status'] ?? null,
                'entities'   => $nlp['entities'] ?? [],
            ],
        ];

        if ($cbrMeta !== null) {
            $response['cbr_meta'] = $cbrMeta;
        }

        return response()->json($response);
    }

    private function errorResponse(string $message, ?string $detail = null): JsonResponse
    {
        return response()->json([
            'success'     => false,
            'type'        => 'error',
            'bot_message' => $message,
            'recipes'     => [],
            'error'       => $detail,
        ], 500);
    }

    // =========================================================================
    // Private: DB helpers
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
                'entities'   => json_encode($nlp['entities'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Exception $e) {
            Log::warning('saveUserQuery failed: ' . $e->getMessage());
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
            Log::warning('saveMatchedRecipes failed: ' . $e->getMessage());
        }
    }

    private function generateSessionId(int $userId): string
    {
        return "user_{$userId}_" . Str::random(12);
    }
}
