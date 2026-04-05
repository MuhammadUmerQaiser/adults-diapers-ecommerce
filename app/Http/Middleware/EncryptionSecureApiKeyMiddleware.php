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
        // $apiKey = $request->header('X-API-KEY');
        // $expectedKey = config('secure.encryption_api_key');

        // \Log::info('Received Key: ' . $apiKey);
        // \Log::info('Expected Key: ' . $expectedKey);

        // if (!$apiKey || $apiKey !== $expectedKey) {
        //     return api_error('Invalid or missing API key.', 401);
        // }

        return $next($request);
    }
}
