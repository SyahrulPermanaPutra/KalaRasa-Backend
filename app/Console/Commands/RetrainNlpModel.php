<?php

namespace App\Console\Commands;

use App\Models\UserQuery;
use App\Services\NlpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * php artisan nlp:retrain
 *
 * Kirim conversation history ke Flask untuk retrain intent classifier.
 * Jalankan setelah cukup data user_queries terkumpul (disarankan: mingguan).
 *
 * Workflow feedback loop:
 *   1. Ambil user_queries dengan status='ok' dan confidence tinggi
 *   2. POST ke Flask /api/nlp/retrain
 *   3. Flask retrain model, simpan ke disk, langsung aktif
 *
 * Lifecycle CBR & NLP Index:
 *   BUILD    → cbr:rebuild-index (saat deploy awal / resep baru approved)
 *   UPDATE   → cbr:rebuild-index (scheduled setiap X jam)
 *   RETRAIN  → nlp:retrain (mingguan, setelah data terkumpul)
 *   FEEDBACK → otomatis via /api/feedback setiap user vote
 *   OPTIMIZE → scripts/optimize_weights.py (bulanan, grid search)
 */
class RetrainNlpModel extends Command
{
    protected $signature = 'nlp:retrain
                            {--min-confidence=0.75 : Minimum confidence untuk include sample}
                            {--min-samples=20      : Minimum jumlah sample sebelum retrain}
                            {--days=30             : Ambil data dari N hari terakhir}
                            {--dry-run             : Tampilkan stats tanpa kirim ke Flask}';

    protected $description = 'Retrain intent classifier dari conversation history (user_queries)';

    public function handle(NlpService $nlp): int
    {
        $minConf    = (float) $this->option('min-confidence');
        $minSamples = (int)   $this->option('min-samples');
        $days       = (int)   $this->option('days');

        $this->info("Fetching conversation history (last {$days} days, confidence >= {$minConf})...");

        // Ambil data dari user_queries
        $queries = UserQuery::query()
            ->where('status', 'ok')
            ->where('confidence', '>=', $minConf)
            ->whereNotIn('intent', ['unknown', 'error'])
            ->whereNotNull('intent')
            ->whereNotNull('query_text')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(['query_text', 'intent', 'confidence'])
            ->get();

        $this->info("  Found: {$queries->count()} valid samples");

        if ($queries->count() < $minSamples) {
            $this->warn("  ⚠ Terlalu sedikit sample ({$queries->count()} < {$minSamples}).");
            $this->warn("  Retrain ditunda. Kumpulkan lebih banyak data dulu.");
            $this->line("  Tips: Jalankan ulang setelah lebih banyak user berinteraksi,");
            $this->line("        atau turunkan --min-samples.");
            return Command::SUCCESS;
        }

        // Statistik per intent
        $intentStats = $queries->groupBy('intent')->map->count()->sortDesc();
        $this->line("\n  Intent distribution:");
        foreach ($intentStats as $intent => $count) {
            $this->line("    {$intent}: {$count}");
        }

        if ($this->option('dry-run')) {
            $this->info("\n  [DRY-RUN] Tidak mengirim ke Flask. Selesai.");
            return Command::SUCCESS;
        }

        // Format untuk Flask
        $history = $queries->map(fn($q) => [
            'query_text' => $q->query_text,
            'intent'     => $q->intent,
            'confidence' => (float) $q->confidence,
        ])->values()->toArray();

        $this->info("\n  Sending to Flask NLP service...");

        try {
            $result = $nlp->retrainIntentClassifier($history, $minConf);

            if ($result['success'] ?? false) {
                $this->info("✓ Retrain selesai!");
                $this->line("  Train accuracy : " . number_format($result['train_score'] ?? 0, 4));
                $this->line("  Test  accuracy : " . number_format($result['test_score']  ?? 0, 4));
                $this->line("  New samples    : " . ($result['new_samples'] ?? 0));

                Log::info('NLP retrain completed', [
                    'train_score' => $result['train_score'] ?? 0,
                    'test_score'  => $result['test_score']  ?? 0,
                    'new_samples' => $result['new_samples'] ?? 0,
                ]);

                return Command::SUCCESS;
            } else {
                $this->error("✗ Retrain gagal: " . ($result['error'] ?? 'Unknown'));
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            Log::error('NLP retrain failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}