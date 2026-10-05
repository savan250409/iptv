<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Protects the public APIs with a static bearer token.
 * Clients must send:  Authorization: Bearer <API_TOKEN>
 */
class ApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('app.api_token');
        $provided = (string) $request->bearerToken();

        if ($provided === '') {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token is required.',
            ], 401);
        }

        if ($expected === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authorization token.',
            ], 401);
        }

        return $next($request);
    }
}
