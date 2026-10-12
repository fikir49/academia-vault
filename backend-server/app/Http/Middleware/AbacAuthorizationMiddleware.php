<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AbacAuthorizationMiddleware
{
    /**
     * Inject ABAC request attributes into the HTTP Request pipeline.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $claims = \Illuminate\Support\Facades\DB::table('user_claims')
                ->where('user_id', $user->id)
                ->pluck('claim_value', 'claim_type')
                ->toArray();

            $request->attributes->set('user_department', $claims['department'] ?? 'General');
            $request->attributes->set('user_clearance', (int) ($claims['clearance_level'] ?? 1));
        } else {
            // Fallback for unauthenticated requests using headers if present
            $request->attributes->set('user_department', $request->header('X-Department', 'General'));
            $request->attributes->set('user_clearance', 1);
        }

        return $next($request);
    }
}