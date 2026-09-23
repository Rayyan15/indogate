<?php

namespace App\Support\Branch;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Filters every query on a branch-scoped model to CurrentBranch::id().
 *
 * This alone is not sufficient for authorization: a direct-access route
 * (e.g. GET /admin/hotels/{hotel}) must 403 on a cross-branch record, not
 * silently 404 it. Policies re-fetch withoutGlobalScope(self::class) and
 * compare branch_id explicitly for that reason — see BookingPolicy /
 * PaymentPolicy.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($branchId = CurrentBranch::id()) {
            $builder->where($model->qualifyColumn('branch_id'), $branchId);

            return;
        }

        // Fail closed: a staff account with no branch must see nothing,
        // not every branch. Guests/customers stay unscoped (public storefront).
        if (Auth::user()?->hasAnyRole(['Super Admin', 'CS Admin', 'Finance Admin'])) {
            $builder->whereRaw('1 = 0');
        }
    }
}
