<?php

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Core\Registry\ModuleRegistry;

class EnsureModuleActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!ModuleRegistry::isEnabled($module)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MODULE_DISABLED',
                    'message' => "The requested module [{$module}] is currently disabled.",
                    'details' => []
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
