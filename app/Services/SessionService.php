<?php
/**
 * SessionService — Cache management untuk session chatbot
 *
 * Memusatkan semua operasi cache yang sebelumnya tersebar di
 * ChatbotController. Satu service = satu tempat untuk ubah TTL,
 * prefix key, dan strategi cache.
 *
 * Key patterns:
 *   chatbot_session_{id}_recipes   → array recipe_id terakhir
 *   chatbot_last_results:{id}      → array recipe lengkap terakhir
 *   chatbot_last_query_text:{id}   → teks query terakhir (untuk feedback)
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SessionService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Recipe cache
    // ─────────────────────────────────────────────────────────────────────────

    public function cacheRecipes(string $sessionId, array $recipes, int $ttlMinutes): void
    {
        $ids = collect($recipes)->pluck('id')->toArray();
        Cache::put("chatbot_session_{$sessionId}_recipes", $ids, now()->addMinutes($ttlMinutes));
        Cache::put("chatbot_last_results:{$sessionId}", $recipes, now()->addMinutes($ttlMinutes));
    }

    public function getCachedRecipes(string $sessionId): array
    {
        return Cache::get("chatbot_last_results:{$sessionId}", []);
    }

    public function getCachedRecipeIds(string $sessionId): array
    {
        return Cache::get("chatbot_session_{$sessionId}_recipes", []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query text cache (untuk feedback)
    // ─────────────────────────────────────────────────────────────────────────

    public function cacheLastQuery(string $sessionId, string $queryText, int $ttlMinutes): void
    {
        Cache::put("chatbot_last_query_text:{$sessionId}", $queryText, now()->addMinutes($ttlMinutes));
    }

    public function getLastQuery(string $sessionId): ?string
    {
        return Cache::get("chatbot_last_query_text:{$sessionId}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Clear session
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hapus SEMUA cache key yang berkaitan dengan session ini.
     * Penting: jika ada key baru ditambahkan, tambahkan juga di sini.
     */
    public function clearAll(string $sessionId): void
    {
        Cache::forget("chatbot_session_{$sessionId}");
        Cache::forget("chatbot_session_{$sessionId}_recipes");
        Cache::forget("chatbot_last_results:{$sessionId}");
        Cache::forget("chatbot_last_query_text:{$sessionId}");
    }
}