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
            
            // Debug logging
            \Log::info('CheckSession middleware', [
                'url' => $request->url(),
                'session_id' => session()->getId(),
                'user_exists' => !is_null($user),
                'user_data' => $user ? ['id' => $user['id'], 'name' => $user['name']] : null,
                'cookies' => $request->cookies->all(),
                'headers' => $request->headers->all()
            ]);
            
            // If no session user, try to restore from database using session ID
            if (!$user) {
                $sessionId = session()->getId();
                
                // Check if there's a valid session in database with user data
                if (config('session.driver') === 'database') {
                    $sessionRecord = \DB::table('sessions')
                        ->where('id', $sessionId)
                        ->where('last_activity', '>', time() - config('session.lifetime') * 60)
                        ->first();
                    
                    if ($sessionRecord && $sessionRecord->payload) {
                        $sessionData = unserialize(base64_decode($sessionRecord->payload));
                        if (isset($sessionData['user'])) {
                            // Restore user to current session
                            session(['user' => $sessionData['user']]);
                            session()->save();
                            
                            \Log::info('Session restored from database', [
                                'user' => $sessionData['user'],
                                'session_id' => $sessionId
                            ]);
                            
                            return $next($request);
                        }
                    }
                }
                
                // If still no user, check for fallback authentication header
                $authUser = $request->header('X-Auth-User');
                if ($authUser) {
                    $userData = json_decode($authUser, true);
                    if ($userData && isset($userData['id'], $userData['name'], $userData['role'])) {
                        // Verify user exists in database
                        $dbUser = \App\Models\User::find($userData['id']);
                        if ($dbUser && $dbUser->name === $userData['name']) {
                            session(['user' => $userData]);
                            session()->save();
                            
                            \Log::info('Session restored from header', [
                                'user' => $userData,
                                'session_id' => session()->getId()
                            ]);
                            
                            return $next($request);
                        }
                    }
                }
                
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
