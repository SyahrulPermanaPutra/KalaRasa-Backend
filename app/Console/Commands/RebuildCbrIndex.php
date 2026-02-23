<?php

namespace App\Console\Commands;

use App\Services\NlpService;
use Illuminate\Console\Command;

/**
 * php artisan cbr:rebuild-index
 *
 * Kirim semua resep aktif ke Flask untuk membangun ulang CBR index.
 * Jalankan setelah deploy atau saat ada batch update resep.
 */

class RebuildCbrIndex extends Command
{
    protected $signature   = 'cbr:rebuild-index {--force : Paksa rebuild meski hash sama}';
    protected $description = 'Rebuild CBR recipe index di Flask NLP Service';

    public function handle(NlpService $nlp): int
    {
        $this->info('Building CBR index...');
        $result = $nlp->buildCbrIndex();

        if ($result['success'] ?? false) {
            $this->info("✓ Index built: {$result['cases_indexed']} cases");
            $this->line("  Hash: {$result['index_hash']}");
            return Command::SUCCESS;
        }

        $this->error('✗ Failed: ' . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}