<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For API routes, check session (except login/logout routes)
        if ($request->is('api/*') && !$request->is('api/login') && !$request->is('api/logout') && !$request->is('api/user')) {
            $user = session('user');
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }
        }
        
        return $next($request);
    }
}
