<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire registers its own update endpoint (/livewire/{fingerprint}/update)
 * with the 'web' middleware group, but OUTSIDE this app's {locale} route
 * prefix group — so App\Http\Middleware\SetLocale never runs for it, and
 * URL::defaults(['locale' => ...]) is never set on that request. Any
 * route('some.route.in.the.locale.group') call inside a Livewire
 * component's re-rendered view then throws UrlGenerationException
 * ("Missing parameter: locale"), even though the exact same route() call
 * works fine on a normal full-page GET.
 *
 * This middleware is the safety net for that gap: it fills the same
 * default from the session value SetLocale already stores, on every 'web'
 * request (Livewire's included), without needing a {locale} route segment.
 * SetLocale still runs first on prefixed routes and sets the *correct*
 * locale for that request; this only fills the gap when it didn't run.
 */
class EnsureLocaleUrlDefault
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! array_key_exists('locale', URL::getDefaultParameters())) {
            URL::defaults(['locale' => session('locale', app()->getLocale())]);
        }

        return $next($request);
    }
}
