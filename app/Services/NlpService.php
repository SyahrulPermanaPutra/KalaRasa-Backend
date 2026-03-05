<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * NlpService
 *
 * Mengelola semua komunikasi dari Laravel ke Flask NLP Service.
 * Menggunakan Redis (atau file cache) untuk cache populer resep dan hasil query.
 *
 * Arsitektur:
 *   Laravel (bisnis logic, DB, auth) <──HTTP──> Flask (NLP, CBR reasoning)
 */
class NlpService
{
    protected string $baseUrl;
    protected string $internalKey;
    protected int    $timeout;

    public function __construct()
    {
        $this->baseUrl     = config('services.nlp.url',          env('NLP_SERVICE_URL', 'http://127.0.0.1:5000'));
        $this->internalKey = config('services.nlp.internal_key', env('NLP_SERVICE_KEY', ''));
        $this->timeout     = (int) config('services.nlp.timeout', 10);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. CHAT – NLP Processing
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kirim pesan user ke Flask untuk NLP processing.
     *
     * @return array{
     *   success: bool,
     *   intent: string,
     *   confidence: float,
     *   status: string,
     *   entities: array,
     *   context_entities: array,
     *   action: string,
     *   conversation_state: string,
     *   bot_message: string,
     *   quick_replies: array,
     *   clarification_needed: bool,
     *   turn_count: int
     * }
     */
    public function chat(string $sessionId, string $userId, string $message, bool $reset = false): array
    {
        try {
            $response = $this->post('/api/chat', [
                'session_id' => $sessionId,
                'user_id'    => $userId,
                'message'    => $message,
                'reset'      => $reset,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('NlpService::chat error', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);

            return $this->fallbackChatResponse();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. CBR INDEX – Kirim data resep ke Flask untuk di-index
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Bangun CBR index di Flask dari data resep yang ada di database.
     * Dipanggil: saat bootstrap, saat ada resep baru di-approve.
     */
    public function buildCbrIndex(): array
    {
        $recipes = $this->fetchRecipesForCbr();

        if (empty($recipes)) {
            return ['success' => false, 'error' => 'No approved recipes found'];
        }

        try {
            $response = $this->post('/api/cbr/index', ['recipes' => $recipes], timeout: 30);

            if ($response['success'] ?? false) {
                // Simpan hash index ke Laravel cache untuk monitoring
                Cache::put('cbr_index_hash',  $response['index_hash'] ?? '', now()->addDay());
                Cache::put('cbr_cases_count', $response['cases_indexed'] ?? 0, now()->addDay());

                Log::info('CBR index built', [
                    'cases'      => $response['cases_indexed'] ?? 0,
                    'index_hash' => $response['index_hash'] ?? '',
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('NlpService::buildCbrIndex error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. CBR MATCH – Cari resep menggunakan CBR similarity
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Jalankan CBR matching dan simpan hasilnya ke tabel matched_recipes.
     *
     * @param  array  $entities  Hasil accumulated entities dari /api/chat
     * @param  int    $userId    Untuk foreign key user_queries
     * @return array
     */
    public function matchRecipes(
        string $sessionId,
        int $userId,
        string $queryText,
        array $entities,
        int $topK = 5
    ): array {
        try {
            $response = $this->post('/api/cbr/match', [
                'session_id' => $sessionId,
                'user_id'    => $userId,
                'query_text' => $queryText,
                'entities'   => $entities,
                'top_k'      => $topK,
            ]);

            if (($response['success'] ?? false) && !empty($response['matched_recipes'])) {
                // ── Simpan ke user_queries + matched_recipes ───────────
                $this->persistQueryResults($userId, $queryText, $entities, $response);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('NlpService::matchRecipes error', ['error' => $e->getMessage()]);
            return ['success' => false, 'matched_recipes' => [], 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Popular Recipes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kirim daftar resep populer ke Flask untuk di-cache.
     * Dipanggil dari scheduler atau setelah ada update rating.
     */
    public function syncPopularRecipes(): array
    {
        $recipes = DB::table('recipes')
            ->where('status', 'approved')
            ->orderByDesc('avg_rating')
            ->orderByDesc('view_count')
            ->limit(20)
            ->select(['id', 'nama', 'waktu_masak', 'region', 'kategori', 'avg_rating', 'view_count'])
            ->get()
            ->toArray();

        $recipeArr = array_map(fn($r) => (array) $r, $recipes);

        try {
            return $this->post('/api/cbr/popular', ['recipes' => $recipeArr]);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Session management
    // ─────────────────────────────────────────────────────────────────────────

    public function getSessionContext(string $sessionId): array
    {
        try {
            return $this->get("/api/session/{$sessionId}/context");
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteSession(string $sessionId): array
    {
        try {
            return $this->delete("/api/session/{$sessionId}");
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. NLP Retrain dari Conversation History
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kirim conversation history ke Flask untuk retrain intent classifier.
     *
     * Dipanggil oleh artisan command: php artisan nlp:retrain
     *
     * @param  array  $history         [{"query_text": str, "intent": str, "confidence": float}, ...]
     * @param  float  $minConfidence   Threshold confidence untuk menerima sample
     * @return array  {"success": bool, "train_score": float, "test_score": float, "new_samples": int}
     */
    public function retrainIntentClassifier(array $history, float $minConfidence = 0.75): array
    {
        try {
            return $this->post('/api/nlp/retrain', [
                'history'        => $history,
                'min_confidence' => $minConfidence,
            ], timeout: 120); // Retrain butuh waktu lebih lama
        } catch (\Exception $e) {
            Log::error('NlpService::retrainIntentClassifier error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. CBR Force Rebuild (berbeda dari buildCbrIndex yang skip jika hash sama)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Force rebuild CBR index tanpa hash check.
     *
     * Gunakan ini ketika ada resep baru di-approve atau data resep berubah.
     * buildCbrIndex() skip rebuild jika hash data tidak berubah.
     * forceRebuildCbrIndex() selalu rebuild.
     *
     * @param  string $reason  Alasan rebuild (untuk logging)
     * @return array
     */
    public function forceRebuildCbrIndex(string $reason = 'manual'): array
    {
        $recipes = $this->fetchRecipesForCbr();

        if (empty($recipes)) {
            return ['success' => false, 'error' => 'No approved recipes found'];
        }

        try {
            $response = $this->post('/api/cbr/rebuild', [
                'recipes' => $recipes,
                'reason'  => $reason,
            ], timeout: 30);

            if ($response['success'] ?? false) {
                Cache::put('cbr_index_hash',  $response['index_hash']    ?? '', now()->addDay());
                Cache::put('cbr_cases_count', $response['cases_indexed'] ?? 0,  now()->addDay());
                Cache::put('cbr_last_rebuild', now()->toIso8601String(),         now()->addDay());

                Log::info('CBR index force-rebuilt', [
                    'cases'  => $response['cases_indexed'] ?? 0,
                    'reason' => $reason,
                    'hash'   => $response['index_hash'] ?? '',
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('NlpService::forceRebuildCbrIndex error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Bulk Feedback untuk seeding historical weights
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kirim batch feedback ke Flask CBR engine.
     *
     * Dipakai oleh: php artisan cbr:sync-feedback
     *
     * @param  array  $feedbacks  [{"recipe_id": int, "rating": int}, ...]
     * @return array
     */
    public function bulkFeedback(array $feedbacks): array
    {
        try {
            return $this->post('/api/cbr/feedback/bulk', ['feedbacks' => $feedbacks]);
        } catch (\Exception $e) {
            Log::error('NlpService::bulkFeedback error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Persist query results ke DB
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Simpan hasil NLP + CBR ke tabel user_queries dan matched_recipes.
     *
     * Tabel user_queries:
     *   user_id, query_text, intent, confidence, status, entities (JSON)
     *
     * Tabel matched_recipes:
     *   user_query_id, recipe_id, match_score, rank_position
     */
    protected function persistQueryResults(
        int $userId,
        string $queryText,
        array $entities,
        array $cbrResult
    ): void {
        DB::transaction(function () use ($userId, $queryText, $entities, $cbrResult) {
            // Simpan ke user_queries
            $queryId = DB::table('user_queries')->insertGetId([
                'user_id'    => $userId,
                'query_text' => $queryText,
                'intent'     => 'cari_resep',              // ditentukan dari NLP
                'confidence' => null,                       // diisi dari context jika ada
                'status'     => 'ok',
                'entities'   => json_encode($entities, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Simpan ke matched_recipes
            $rows = [];
            foreach ($cbrResult['matched_recipes'] as $match) {
                $rows[] = [
                    'user_query_id' => $queryId,
                    'recipe_id'     => $match['recipe_id'],
                    'match_score'   => $match['match_score'],    // 0-100
                    'rank_position' => $match['rank_position'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }

            if (!empty($rows)) {
                DB::table('matched_recipes')->insert($rows);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Ambil data resep untuk CBR index (Query DB di Laravel)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch semua resep approved beserta ingredients & health conditions
     * untuk dikirim ke Flask sebagai CBR case base.
     *
     * Query menyesuaikan struktur tabel:
     *   recipes → recipe_ingredients → ingredients
     *   recipes → recipe_suitability → health_conditions
     */
    protected function fetchRecipesForCbr(): array
    {
        // Ambil semua resep approved
        $recipes = DB::table('recipes')
            ->where('status', 'approved')
            ->select(['id', 'nama', 'waktu_masak', 'region', 'kategori', 'deskripsi', 'avg_rating', 'view_count'])
            ->get();

        if ($recipes->isEmpty()) {
            return [];
        }

        $recipeIds = $recipes->pluck('id')->toArray();

        // Ambil ingredients per resep (group by recipe_id)
        $ingredients = DB::table('recipe_ingredients')
            ->join('ingredients', 'recipe_ingredients.ingredient_id', '=', 'ingredients.id')
            ->whereIn('recipe_ingredients.recipe_id', $recipeIds)
            ->select([
                'recipe_ingredients.recipe_id',
                'recipe_ingredients.is_main',
                'ingredients.nama',
            ])
            ->get()
            ->groupBy('recipe_id');

        // Ambil health suitability per resep
        $suitability = DB::table('recipe_suitability')
            ->join('health_conditions', 'recipe_suitability.health_condition_id', '=', 'health_conditions.id')
            ->whereIn('recipe_suitability.recipe_id', $recipeIds)
            ->select([
                'recipe_suitability.recipe_id',
                'recipe_suitability.is_suitable',
                'health_conditions.nama',
            ])
            ->get()
            ->groupBy('recipe_id');

        // Gabungkan data
        $result = [];
        foreach ($recipes as $recipe) {
            $rid  = $recipe->id;
            $ings = $ingredients->get($rid, collect());
            $suit = $suitability->get($rid, collect());

            $result[] = [
                'id'              => $rid,
                'nama'            => $recipe->nama,
                'waktu_masak'     => $recipe->waktu_masak,
                'region'          => $recipe->region ?? '',
                'kategori'        => $recipe->kategori ?? '',
                'deskripsi'       => $recipe->deskripsi ?? '',
                'avg_rating'      => (float) ($recipe->avg_rating ?? 0),
                'view_count'      => (int) ($recipe->view_count ?? 0),

                // Bahan utama (is_main = 1)
                'ingredients_main' => $ings->where('is_main', 1)->pluck('nama')->values()->toArray(),
                // Semua bahan
                'ingredients_all'  => $ings->pluck('nama')->values()->toArray(),

                // Kondisi yang cocok (is_suitable = 1)
                'suitable_for'     => $suit->where('is_suitable', 1)->pluck('nama')->values()->toArray(),
                // Kondisi yang tidak cocok (is_suitable = 0)
                'not_suitable_for' => $suit->where('is_suitable', 0)->pluck('nama')->values()->toArray(),
            ];
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP helpers
    // ─────────────────────────────────────────────────────────────────────────

    protected function post(string $path, array $data, int $timeout = 0): array
    {
        $timeout = $timeout ?: $this->timeout;
        $resp = Http::withHeaders($this->headers())
            ->timeout($timeout)
            ->post($this->baseUrl . $path, $data);

        if ($resp->failed()) {
            throw new \RuntimeException("NLP HTTP {$resp->status()}: " . $resp->body());
        }

        return $resp->json() ?? [];
    }

    protected function get(string $path): array
    {
        $resp = Http::withHeaders($this->headers())
            ->timeout($this->timeout)
            ->get($this->baseUrl . $path);

        if ($resp->failed()) {
            throw new \RuntimeException("NLP HTTP {$resp->status()}");
        }

        return $resp->json() ?? [];
    }

    protected function delete(string $path): array
    {
        $resp = Http::withHeaders($this->headers())
            ->timeout($this->timeout)
            ->delete($this->baseUrl . $path);

        return $resp->json() ?? [];
    }

    protected function headers(): array
    {
        return array_filter([
            'X-Internal-Key' => $this->internalKey,
            'Accept'         => 'application/json',
            'Content-Type'   => 'application/json',
        ]);
    }

    protected function fallbackChatResponse(): array
    {
        return [
            'success'              => false,
            'intent'               => 'unknown',
            'confidence'           => 0.0,
            'status'               => 'fallback',
            'entities'             => [],
            'context_entities'     => [],
            'action'               => 'reject_input',
            'conversation_state'   => 'collecting',
            'bot_message'          => 'Maaf, sedang ada gangguan teknis. Silakan coba lagi. 😊',
            'quick_replies'        => ['Coba lagi', 'Mulai ulang'],
            'clarification_needed' => false,
            'turn_count'           => 0,
        ];
    }
}