<?php

namespace App\Support\Branch;

use App\Models\Branch;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Single source of truth for "which branch is this request operating as".
 *
 * Resolution order: an explicit session override (set only via
 * switchTo(), which the caller must gate behind the `branch.switch`
 * permission) falls back to the authenticated user's home branch.
 * Every branch-scoped query and policy check reads through here so
 * there is exactly one place that decides the active branch.
 */
class CurrentBranch
{
    private const SESSION_KEY = 'current_branch_id';

    public static function id(): ?int
    {
        return Session::get(self::SESSION_KEY) ?? Auth::user()?->branch_id;
    }

    public static function model(): ?Branch
    {
        $id = self::id();

        return $id ? Branch::find($id) : null;
    }

    public static function switchTo(int $branchId): void
    {
        Session::put(self::SESSION_KEY, $branchId);
    }

    public static function clearOverride(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /** Display a stored (UTC) timestamp in the active branch's timezone. */
    public static function local(?CarbonInterface $date): ?CarbonInterface
    {
        return $date?->copy()->setTimezone(self::model()?->timezone ?: 'Asia/Jakarta');
    }
}
