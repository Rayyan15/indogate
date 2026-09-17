<?php

namespace App\Support\Branch;

use App\Models\Branch;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (! $model->branch_id) {
                $model->branch_id = CurrentBranch::id();
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
