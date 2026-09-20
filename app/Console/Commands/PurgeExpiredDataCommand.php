<?php

namespace App\Console\Commands;

use App\Domain\Security\Services\DataRetentionService;
use Illuminate\Console\Command;

class PurgeExpiredDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:purge-expired-data {--days=90 : Days after completion to retain identity documents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge expired passport documents and guest identities for completed or cancelled bookings';

    /**
     * Execute the console command.
     */
    public function handle(DataRetentionService $retentionService): int
    {
        $days = (int) $this->option('days');
        $this->info("Purging identity documents for bookings completed/cancelled more than {$days} days ago...");

        $purgedCount = $retentionService->purgeExpiredGuestDocuments($days);

        $this->info("Successfully purged {$purgedCount} expired identity documents.");

        return Command::SUCCESS;
    }
}
