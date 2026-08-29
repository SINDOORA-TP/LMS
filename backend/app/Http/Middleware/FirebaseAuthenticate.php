<?php

namespace App\Http\Middleware;

use App\Services\FirebaseAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirebaseAuthenticate
{
    protected FirebaseAuthService $firebaseAuth;

    public function __construct(FirebaseAuthService $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    /**
     * Verify Firebase ID token on every API request.
     *
     * On success, attaches the local User model to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->firebaseAuth->extractToken($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token required',
            ], 401);
        }

        $user = $this->firebaseAuth->verifyTokenAndGetUser($token);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated',
            ], 403);
        }

        // Attach user to request for controllers to use
        $request->attributes->set('user', $user);

        return $next($request);
    }
}
