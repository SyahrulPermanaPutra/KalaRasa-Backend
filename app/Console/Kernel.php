<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Lifecycle Management CBR Index & NLP Index
 *
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │  LIFECYCLE CBR & NLP INDEX                                          │
 * │                                                                     │
 * │  BUILD (awal):                                                      │
 * │    php artisan cbr:rebuild-index   → bangun CBR index dari DB       │
 * │    php artisan cbr:sync-feedback   → seed weights dari feedback DB  │
 * │                                                                     │
 * │  SCHEDULED:                                                         │
 * │    Setiap 6 jam  → cbr:rebuild-index (update data resep baru)       │
 * │    Setiap minggu → nlp:retrain (learning dari conversation history) │
 * │    Setiap bulan  → scripts/optimize_weights.py (grid search)        │
 * │                                                                     │
 * │  EVENT-DRIVEN (otomatis):                                           │
 * │    Recipe approved → forceRebuildCbrIndex() via observer/event      │
 * │    User feedback   → /api/feedback → cbr.apply_feedback()           │
 * │                                                                     │
 * │  MANUAL:                                                            │
 * │    php artisan cbr:rebuild-index --force                            │
 * │    php artisan nlp:retrain --days=60 --min-samples=30               │
 * │    php artisan cbr:sync-feedback --days=90                          │
 * └─────────────────────────────────────────────────────────────────────┘
 */
class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ── CBR Index: rebuild setiap 6 jam ───────────────────────────
        // Memastikan CBR index selalu sinkron dengan data resep terbaru
        // di database (resep baru approved, update bahan, dll.)
        $schedule->command('cbr:rebuild-index')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('CBR rebuild-index scheduled task failed');
            });

        // ── NLP Retrain: setiap minggu ────────────────────────────────
        // Melatih ulang intent classifier dari conversation history.
        // Hanya berjalan jika ada minimal 50 sample baru yang collected.
        $schedule->command('nlp:retrain --days=30 --min-samples=50')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/nlp-retrain.log'));

        // ── Popular Recipes Sync: setiap jam ──────────────────────────
        // Update cache resep populer di Flask untuk cold-start fallback
        $schedule->call(function () {
            app(\App\Services\NlpService::class)->syncPopularRecipes();
        })
            ->hourly()
            ->name('sync-popular-recipes')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}