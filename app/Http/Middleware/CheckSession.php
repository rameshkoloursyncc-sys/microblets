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
        if ($request->is('api/*') && !$request->is('api/login') && !$request->is('api/logout') && !$request->is('api/user') && !$request->is('api/ping')) {
            $user = session('user');
            
            // Debug logging (remove in production)
            \Log::info('CheckSession middleware', [
                'url' => $request->url(),
                'session_id' => session()->getId(),
                'user_exists' => !is_null($user),
                'user_data' => $user ? ['id' => $user['id'], 'name' => $user['name']] : null
            ]);
            
            if (!$user) {
                return response()->json([
                    'message' => 'Session expired. Please login again.',
                    'error' => 'session_expired',
                    'redirect' => '/login'
                ], 401);
            }
        }
        
        return $next($request);
    }
}
