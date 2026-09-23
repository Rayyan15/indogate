<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks out sessions of users deactivated after they logged in. The login
 * check alone (LoginRequest) would let an existing session live on.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // Strict false: a freshly created model without the DB default loaded reads null.
        if ($request->user()?->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        }

        return $next($request);
    }
}
