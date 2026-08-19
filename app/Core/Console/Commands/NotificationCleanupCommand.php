<?php

namespace App\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotificationCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup 
                            {--days=180 : Prune sent outbox records older than this number of days}
                            {--chunk=1000 : Chunk size for batch deletion to prevent lock contention}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old sent outbox records while preserving failed and dead records for audit';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $chunkSize = (int) $this->option('chunk');
        $cutoffDate = now()->subDays($days);

        $this->info("Pruning sent notification outbox records older than {$days} days (before {$cutoffDate->toDateTimeString()})...");

        $totalDeleted = 0;

        do {
            $ids = DB::table('notification_outbox')
                ->where('status', 'sent')
                ->where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->pluck('id')
                ->toArray();

            if (empty($ids)) {
                break;
            }

            $deletedCount = DB::table('notification_outbox')
                ->whereIn('id', $ids)
                ->delete();

            $totalDeleted += $deletedCount;
            $this->line("Deleted chunk of {$deletedCount} records (Total so far: {$totalDeleted}).");
        } while (count($ids) >= $chunkSize);

        $this->info("Cleanup completed successfully. Total pruned records: {$totalDeleted}.");

        return 0;
    }
}
