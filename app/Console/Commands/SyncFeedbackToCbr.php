<?php

namespace App\Console\Commands;

use App\Models\NlpFeedback;
use App\Services\NlpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * php artisan cbr:sync-feedback
 *
 * Kirim seluruh historical feedback ke Flask CBR engine untuk update case weights.
 *
 * Gunakan ini saat:
 *   - Deploy pertama (seeding initial weights dari feedback yang sudah ada di DB)
 *   - Setelah migrate dari sistem lama yang punya feedback data
 *   - Reset weights dan mulai ulang dari historical data
 *
 * Untuk feedback real-time, sistem sudah otomatis via endpoint /api/feedback
 * yang dipanggil dari ChatbotController::submitFeedback().
 */
class SyncFeedbackToCbr extends Command
{
    protected $signature = 'cbr:sync-feedback
                            {--days=90      : Ambil feedback dari N hari terakhir}
                            {--batch=100    : Kirim per batch N records}
                            {--dry-run      : Tampilkan stats tanpa kirim ke Flask}';

    protected $description = 'Sync historical feedback ke CBR engine untuk update case weights';

    public function handle(NlpService $nlp): int
    {
        $days    = (int) $this->option('days');
        $batch   = (int) $this->option('batch');
        $dryRun  = $this->option('dry-run');

        $this->info("Fetching feedback data (last {$days} days)...");

        $feedbacks = NlpFeedback::query()
            ->whereIn('rating', [1, -1])
            ->whereNotNull('recipe_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(['recipe_id', 'rating'])
            ->get();

        $this->info("  Found: {$feedbacks->count()} feedback records");

        // Statistik
        $positive = $feedbacks->where('rating', 1)->count();
        $negative = $feedbacks->where('rating', -1)->count();
        $this->line("  Positive (👍): {$positive}");
        $this->line("  Negative (👎): {$negative}");

        if ($feedbacks->isEmpty()) {
            $this->warn("  Tidak ada data feedback. Selesai.");
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info("\n  [DRY-RUN] Tidak mengirim ke Flask. Selesai.");
            return Command::SUCCESS;
        }

        // Kirim per batch ke Flask
        $chunks = $feedbacks->chunk($batch);
        $total  = 0;

        $bar = $this->output->createProgressBar($chunks->count());
        $bar->start();

        foreach ($chunks as $chunk) {
            $payload = $chunk->map(fn($f) => [
                'recipe_id' => $f->recipe_id,
                'rating'    => $f->rating,
            ])->values()->toArray();

            try {
                $result = $nlp->bulkFeedback($payload);
                if ($result['success'] ?? false) {
                    $total += $result['updated'] ?? 0;
                }
            } catch (\Exception $e) {
                Log::warning('cbr:sync-feedback batch error', ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Selesai! Total case weights updated: {$total}");

        return Command::SUCCESS;
    }
}