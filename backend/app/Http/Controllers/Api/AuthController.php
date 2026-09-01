<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected FirebaseAuthService $firebaseAuth;

    public function __construct(FirebaseAuthService $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    /**
     * Sync Firebase user with local database and return user data.
     *
     * Called after Firebase login/register on the Flutter side.
     * Creates a local user if one doesn't exist, or updates the existing one.
     *
     * POST /api/auth/sync
     */
    public function sync(Request $request): JsonResponse
    {
        $token = $this->firebaseAuth->extractToken($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No authorization token provided',
            ], 401);
        }

        $user = $this->firebaseAuth->verifyTokenAndGetUser($token);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'User synced successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Get the currently authenticated user.
     *
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Update user profile.
     *
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Report a security violation (screenshot/screen recording).
     *
     * POST /api/security-violation
     */
    public function reportSecurityViolation(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $violationType = $request->input('violation_type', 'Screenshot');

        // Increment the counter on the user
        $user->increment('security_violations_count');

        // Log individual violation record for admin panel
        \App\Models\SecurityViolation::create([
            'user_id'        => $user->id,
            'user_name'      => $user->name,
            'user_email'     => $user->email,
            'violation_type' => $violationType,
            'ip_address'     => $request->ip(),
            'device_info'    => $request->header('User-Agent'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Security violation reported',
            'data' => [
                'violations' => $user->security_violations_count,
            ],
        ]);
    }
}
