<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the {locale} route parameter into App::setLocale() and stores it
 * in the session so the next unprefixed request (e.g. after login redirect
 * logic elsewhere) can still recover the chosen locale.
 *
 * Deliberately NOT using mcamara/laravel-localization's own
 * Route::group(['prefix' => LaravelLocalization::setLocale()]) pattern:
 * under Laravel 12's Application::configure()->withRouting(), route files
 * load before the real per-request Request is bound, so that call always
 * resolves null — verified empirically (see docs/progress/M2-*.md). A
 * genuine {locale} route parameter is resolved by the router per-request
 * and doesn't have that problem.
 */
/**
 * Must run before Illuminate\Auth\Middleware\Authenticate — bootstrap/app.php
 * pins this into Laravel's middlewarePriority list ahead of
 * AuthenticatesRequests. Without that, the framework's default priority
 * list (SetLocale isn't in it) silently reorders 'auth' ahead of
 * 'setLocale' regardless of the order they're written in routes/web.php,
 * so URL::defaults(['locale' => ...]) never runs before an unauthenticated
 * request's redirect-to-login tries route('login') — a 500
 * (UrlGenerationException: missing 'locale') instead of a clean redirect.
 * Reproduced empirically via Kernel::handle() in tinker, see M3 progress doc.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        app()->setLocale($locale);
        session(['locale' => $locale]);

        // Controller dispatch matches route params to method args
        // POSITIONALLY (Illuminate\Routing\ResolvesRouteDependencies),
        // not by name. Leaving 'locale' in the route's parameter bag means
        // every controller action taking a bound model (e.g. show(Booking
        // $booking)) silently receives the locale string as its first
        // positional arg instead of the model — a TypeError, not a route
        // "missing parameter" — verified empirically (see docs/progress/
        // M2-*.md). Drop it once SetLocale + URL::defaults have used it.
        $request->route()->forgetParameter('locale');

        // Every route in this group requires {locale}; without this,
        // route('login')/route('admin.dashboard') etc. (called with no
        // explicit locale everywhere in the app) would throw "missing
        // required parameter" since Laravel doesn't infer route
        // parameters from the current request on its own.
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
