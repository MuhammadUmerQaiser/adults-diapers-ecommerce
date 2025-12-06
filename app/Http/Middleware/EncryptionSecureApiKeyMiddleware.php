<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EncryptionSecureApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if ($request->header('X-API-KEY') !== env('SECURE_ENCRYPTION_API_KEY')) {
        //     return api_error('Invalid or missing API key.', 401);
        // }
        return $next($request);
    }
}
