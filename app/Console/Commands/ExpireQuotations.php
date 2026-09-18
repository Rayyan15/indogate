<?php

namespace App\Console\Commands;

use App\Domain\Lead\Models\Quotation;
use App\Enums\QuotationStatus;
use App\Support\Branch\BranchScope;
use Illuminate\Console\Command;

class ExpireQuotations extends Command
{
    protected $signature = 'quotations:expire';

    protected $description = 'Mark quotations past their valid_until as expired (PRD M6 step 11)';

    public function handle(): int
    {
        // Console has no CurrentBranch context — must run across all branches.
        $count = Quotation::query()
            ->withoutGlobalScope(BranchScope::class)
            ->whereIn('status', [QuotationStatus::DRAFT, QuotationStatus::SENT])
            ->where('valid_until', '<', now())
            ->update(['status' => QuotationStatus::EXPIRED]);

        $this->info("Expired {$count} quotation(s).");

        return self::SUCCESS;
    }
}
