<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $bearerToken = $authHeader ? trim(str_replace('Bearer', '', $authHeader)) : null;

        if($bearerToken !== config('app.api_key')) {
            return response()
                ->json(['message' => 'This action is unauthorized.'], 401);
        }

        return $next($request);
    }
}
