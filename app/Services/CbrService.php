<?php
/**
 * CbrService — CBR Recipe Matching & DB Fallback
 *
 * Memisahkan semua logic resep dari ChatbotController:
 *   • match()       → CBR via Flask /api/cbr/match
 *   • matchFromDb() → Fallback query DB langsung
 *   • searchByName() → Pencarian nama resep
 *   • hydrate()     → Ambil detail resep dari DB berdasarkan CBR result
 */

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CbrService
{
    private string $nlpApiUrl;
    private int    $timeout;

    public function __construct()
    {
        $this->nlpApiUrl = config('services.nlp.url', env('NLP_API_URL', 'http://127.0.0.1:5000'));
        $this->timeout   = (int) config('services.nlp.timeout', 30);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CBR Matching via Flask
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lakukan CBR recipe matching via Flask /api/cbr/match.
     *
     * CATATAN ENTITY:
     *   Parameter $entities HARUS berasal dari $nlp['context_entities']
     *   (bukan $nlp['entities'] mentah). context_entities sudah melewati
     *   topic-switch detection di Python. Jika controller mengirim entities
     *   yang salah, hasil matching akan salah.
     *
     * @param  string $sessionId
     * @param  int    $userId
     * @param  array  $entities   Dari nlp['context_entities'] — sudah bersih
     * @param  int    $topK
     * @return array{recipes: array, meta: array|null}
     */
    public function match(string $sessionId, int $userId, array $entities, int $topK = 5): array
    {
        $queryText = $this->buildQueryText($entities);

        try {
            $response = Http::withHeaders([
                'X-Internal-Key' => env('NLP_SERVICE_KEY'),
                'Content-Type'   => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post("{$this->nlpApiUrl}/api/cbr/match", [
                'session_id' => $sessionId,
                'user_id'    => (string) $userId,
                'query_text' => $queryText,
                'entities'   => $entities,
                'top_k'      => $topK,
            ]);

            if (!$response->successful()) {
                Log::error('CBR match API error', [
                    'status'     => $response->status(),
                    'session_id' => $sessionId,
                ]);
                return ['recipes' => [], 'meta' => null];
            }

            $data    = $response->json();
            $recipes = $this->hydrate($data['matched_recipes'] ?? []);
            $meta    = [
                'from_cache'       => $data['from_cache'] ?? false,
                'total_candidates' => $data['total_candidates'] ?? 0,
                'query_hash'       => $data['query_hash'] ?? null,
            ];

            return ['recipes' => $recipes, 'meta' => $meta];

        } catch (\Exception $e) {
            Log::error('CbrService::match exception: ' . $e->getMessage());
            return ['recipes' => [], 'meta' => null];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DB Fallback Matching
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fallback matching langsung ke database (tanpa CBR).
     * Digunakan saat conversation_state != 'ready' dan ada entity bermakna.
     *
     * CATATAN ENTITY:
     *   Parameter $entities di sini harus dari $nlp['entities'] (pesan saat ini),
     *   BUKAN context_entities, karena state belum 'ready' — kita hanya ingin
     *   preview quick results dari pesan ini saja.
     */
    public function matchFromDb(array $entities): array
    {
        $query = Recipe::query()
            ->where('status', 'approved')
            ->with(['recipeIngredients.ingredient', 'recipeSuitability.healthCondition']);

        // Filter bahan utama
        $mainIngredients = $entities['ingredients']['main'] ?? [];
        if (!empty($mainIngredients)) {
            $query->whereHas('recipeIngredients.ingredient', fn($q) =>
                $q->where(fn($inner) => collect($mainIngredients)->each(fn($ing) =>
                    $inner->orWhere('nama', 'LIKE', "%{$ing}%")
                ))
            );
        }

        // Exclude bahan yang dihindari
        $avoidIngredients = $entities['ingredients']['avoid'] ?? [];
        if (!empty($avoidIngredients)) {
            $query->whereDoesntHave('recipeIngredients.ingredient', fn($q) =>
                $q->where(fn($inner) => collect($avoidIngredients)->each(fn($ing) =>
                    $inner->orWhere('nama', 'LIKE', "%{$ing}%")
                ))
            );
        }

        // Filter kondisi kesehatan
        foreach ($entities['health_conditions'] ?? [] as $condName) {
            $query->whereHas('recipeSuitability', fn($q) =>
                $q->whereHas('healthCondition', fn($q2) =>
                    $q2->where('nama', 'LIKE', "%{$condName}%")
                )->where('is_suitable', true)
            );
        }

        // Filter waktu masak
        if ($time = $entities['time_constraint'] ?? null) {
            $query->where('waktu_masak', '<=', (int) $time);
        }

        // Filter region
        if ($region = $entities['region'] ?? null) {
            $query->where('region', 'LIKE', "%{$region}%");
        }

        return $query
            ->orderByDesc('avg_rating')
            ->orderByDesc('view_count')
            ->limit(8)
            ->get()
            ->map(fn($r) => $this->formatRecipe($r))
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Name Search
    // ─────────────────────────────────────────────────────────────────────────

    public function searchByName(
        string $searchName,
        int    $topK = 10,
        array  $healthConditions = [],
        ?int   $timeConstraint = null,
    ): array {
        $query = Recipe::where('nama', 'LIKE', "%{$searchName}%")
            ->where('status', 'approved')
            ->with(['recipeIngredients.ingredient', 'recipeSuitability.healthCondition']);

        if (!empty($healthConditions)) {
            $query->whereHas('recipeSuitability', fn($q) =>
                $q->whereHas('healthCondition', fn($q2) =>
                    $q2->whereIn('nama', $healthConditions)
                )->where('is_suitable', true)
            );
        }

        if ($timeConstraint !== null) {
            $query->where('waktu_masak', '<=', $timeConstraint);
        }

        return $query
            ->orderBy('avg_rating', 'desc')
            ->limit($topK)
            ->get()
            ->map(fn($r) => $this->formatRecipe($r))
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Hydration
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hydrate CBR matched_recipes dengan data lengkap dari DB.
     * CBR hanya menyimpan recipe_id + score, data detail diambil dari sini.
     */
    private function hydrate(array $matchedRecipes): array
    {
        if (empty($matchedRecipes)) return [];

        $recipeIds = array_column($matchedRecipes, 'recipe_id');
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
            $recipe = $dbRecipes->get($match['recipe_id']);
            if (!$recipe) continue;

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

    // ─────────────────────────────────────────────────────────────────────────
    // Formatters & helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function formatRecipe($recipe): array
    {
        return [
            'id'            => $recipe->id,
            'nama'          => $recipe->nama,
            'deskripsi'     => $recipe->deskripsi,
            'gambar'        => $recipe->gambar,
            'region'        => $recipe->region,
            'waktu_masak'   => $recipe->waktu_masak,
            'kategori'      => $recipe->kategori,
            'avg_rating'    => (float) $recipe->avg_rating,
            'total_ratings' => (int) $recipe->total_ratings,
            'view_count'    => (int) $recipe->view_count,
            'ingredients'   => $recipe->recipeIngredients
                ? $recipe->recipeIngredients
                    ->map(fn($ri) => [
                        'nama'    => $ri->ingredient->nama ?? null,
                        'jumlah'  => $ri->jumlah,
                        'satuan'  => $ri->satuan,
                        'is_main' => (bool) $ri->is_main,
                    ])
                    ->filter(fn($i) => $i['nama'])
                    ->values()
                    ->toArray()
                : [],
            'suitability'   => $recipe->recipeSuitability
                ? $recipe->recipeSuitability
                    ->map(fn($s) => [
                        'condition'   => $s->healthCondition->nama ?? null,
                        'is_suitable' => (bool) $s->is_suitable,
                        'notes'       => $s->notes,
                    ])
                    ->filter(fn($s) => $s['condition'])
                    ->values()
                    ->toArray()
                : [],
        ];
    }

    private function buildQueryText(array $entities): string
    {
        $parts = [];
        if ($ings = $entities['ingredients']['main'] ?? [])
            $parts[] = 'mau masak ' . implode(' ', $ings);
        if ($conds = $entities['health_conditions'] ?? [])
            $parts[] = 'untuk ' . implode(' ', $conds);
        if ($region = $entities['region'] ?? null)
            $parts[] = "masakan {$region}";
        if ($time = $entities['time_constraint'] ?? null)
            $parts[] = "kurang dari {$time} menit";
        return implode(' ', $parts) ?: 'resep masakan';
    }
}