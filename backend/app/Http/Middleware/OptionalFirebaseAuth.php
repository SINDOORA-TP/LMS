<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalFirebaseAuth
{
    /**
     * Optionally authenticate — don't fail if no token.
     *
     * Used for endpoints that work for both guests and authenticated users
     * (e.g., course listing shows enrollment status if logged in).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if ($header && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
            $firebaseAuth = app(\App\Services\FirebaseAuthService::class);
            $user = $firebaseAuth->verifyTokenAndGetUser($token);

            if ($user) {
                $request->attributes->set('user', $user);
            }
        }

        return $next($request);
    }
}
