<?php

namespace App\Http\Middleware;

use App\Support\Branch\CurrentBranch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves CurrentBranch::id() once per request and drops the session
 * override for anyone who is no longer allowed to hold one — e.g. a
 * demoted Super Admin should fall back to their own branch, not keep
 * roaming on a stale session value.
 */
class SetActiveBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->can('branch.switch')) {
            CurrentBranch::clearOverride();
        }

        return $next($request);
    }
}
