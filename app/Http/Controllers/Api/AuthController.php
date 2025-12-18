<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check for hardcoded admin first
        if ($request->name === 'koloursyncc' && $request->password === 'kolorsync1010') {
            // Create a virtual admin user object for session
            $adminUser = new User([
                'id' => 0,
                'name' => 'koloursyncc',
                'role' => 'admin',
            ]);
            
            // Store in session
            session(['user' => [
                'id' => 0,
                'name' => 'koloursyncc',
                'role' => 'admin',
            ]]);

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => 0,
                    'name' => 'koloursyncc',
                    'role' => 'admin',
                ]
            ]);
        }

        // Check database users
        $user = User::where('name', $request->name)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'name' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Store in session
        session(['user' => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]]);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        session()->forget('user');
        session()->flush();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get current user
     */
    public function user(Request $request)
    {
        $user = session('user');

        if (!$user) {
            return response()->json([
                'message' => 'Not authenticated'
            ], 401);
        }

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Create new user (admin only)
     */
    public function createUser(Request $request)
    {
        $currentUser = session('user');
        
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ]
        ], 201);
    }

    /**
     * Get all users (admin only)
     */
    public function getUsers(Request $request)
    {
        $currentUser = session('user');
        
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $users = User::select('id', 'name', 'role', 'created_at')->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser(Request $request, $id)
    {
        $currentUser = session('user');
        
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}