<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoHttpCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }

        return $response;
    }
}
