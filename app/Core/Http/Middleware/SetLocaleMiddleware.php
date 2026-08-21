<?php

namespace App\Core\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request and set application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('localization.supported_locales', ['de' => [], 'en' => []]));
        $defaultLocale = config('localization.default_locale', 'de');

        $resolvedLocale = null;

        // 1. Explicit document export / query parameter override (?locale=de or ?locale=en)
        if ($request->has('locale')) {
            $queryLocale = strtolower(substr((string) $request->query('locale'), 0, 2));
            if (in_array($queryLocale, $supported, true)) {
                $resolvedLocale = $queryLocale;
            }
        }

        // 2. Authenticated user DB preference
        if (!$resolvedLocale && $request->user() && !empty($request->user()->preferred_locale)) {
            $userLocale = strtolower(substr((string) $request->user()->preferred_locale, 0, 2));
            if (in_array($userLocale, $supported, true)) {
                $resolvedLocale = $userLocale;
            }
        }

        // 3. HTTP Standard Accept-Language header (e.g. "de,de-DE;q=0.9,en;q=0.8")
        if (!$resolvedLocale && $request->header('Accept-Language')) {
            $preferred = $request->getPreferredLanguage($supported);
            if ($preferred) {
                $preferredClean = strtolower(substr($preferred, 0, 2));
                if (in_array($preferredClean, $supported, true)) {
                    $resolvedLocale = $preferredClean;
                }
            }
        }

        // 4. Default Fallback
        if (!$resolvedLocale || !in_array($resolvedLocale, $supported, true)) {
            $resolvedLocale = $defaultLocale;
        }

        // Apply locale across runtime
        app()->setLocale($resolvedLocale);
        Carbon::setLocale($resolvedLocale);

        // Propagate locale through Laravel Context across Queue boundaries
        if (class_exists(Context::class)) {
            Context::add('locale', $resolvedLocale);
        }

        $response = $next($request);

        // Return Content-Language header on response
        $response->headers->set('Content-Language', $resolvedLocale);

        return $response;
    }
}
