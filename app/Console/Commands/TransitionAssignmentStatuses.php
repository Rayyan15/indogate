<?php

namespace App\Console\Commands;

use App\Domain\Fleet\Models\DriverAssignment;
use App\Models\Branch;
use App\Support\Branch\BranchScope;
use Illuminate\Console\Command;

class TransitionAssignmentStatuses extends Command
{
    protected $signature = 'fleet:transition-assignments';

    protected $description = 'Auto-advance driver assignments by date (assigned -> in_progress -> completed) in each branch timezone';

    public function handle(): int
    {
        $advanced = 0;

        foreach (Branch::all() as $branch) {
            $today = now($branch->timezone ?: 'Asia/Jakarta')->toDateString();

            // Completed first so an assignment that already ended jumps straight
            // to completed instead of passing through in_progress.
            $advanced += $this->advance($branch->id, [DriverAssignment::STATUS_ASSIGNED, DriverAssignment::STATUS_IN_PROGRESS],
                fn ($q) => $q->whereDate('date_to', '<', $today), DriverAssignment::STATUS_COMPLETED);

            $advanced += $this->advance($branch->id, [DriverAssignment::STATUS_ASSIGNED],
                fn ($q) => $q->whereDate('date_from', '<=', $today), DriverAssignment::STATUS_IN_PROGRESS);
        }

        $this->info("Advanced {$advanced} assignment(s).");

        return self::SUCCESS;
    }

    private function advance(int $branchId, array $from, callable $filter, string $to): int
    {
        $count = 0;

        $filter(DriverAssignment::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('status', $from))
            ->chunkById(200, function ($chunk) use ($to, &$count) {
                foreach ($chunk as $assignment) {
                    $assignment->update(['status' => $to]);
                    $count++;
                }
            });

        return $count;
    }
}
