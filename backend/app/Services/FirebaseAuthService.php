<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Illuminate\Http\Request;

class FirebaseAuthService
{
    protected FirebaseAuth $firebaseAuth;

    public function __construct(FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    /**
     * Verify Firebase ID token and return/create local user.
     *
     * @param string $idToken Firebase ID token from Flutter
     * @return User|null
     */
    public function verifyTokenAndGetUser(string $idToken): ?User
    {
        try {
            // Verify the Firebase token with a clock skew leeway of 6 minutes (360 seconds)
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken, false, 360);

            $firebaseUid = $verifiedToken->claims()->get('sub');
            $email = $verifiedToken->claims()->get('email');
            $name = $verifiedToken->claims()->get('name') ?? explode('@', $email)[0];

            // Find user by email first to handle seeded accounts (like admin), or fallback to firebase_uid
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'firebase_uid' => $firebaseUid,
                    'name' => $name,
                    'last_login_at' => now(),
                ]);
            } else {
                $user = User::updateOrCreate(
                    ['firebase_uid' => $firebaseUid],
                    [
                        'email' => $email,
                        'name' => $name,
                        'last_login_at' => now(),
                    ]
                );
            }

            return $user ? $user->fresh() : null;
        } catch (FailedToVerifyToken $e) {
            \Log::error('Firebase Token Verification Failed: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            \Log::error('Firebase General Error: ' . $e->getMessage());
            report($e);
            return null;
        }
    }

    /**
     * Extract token from Authorization header.
     *
     * @param Request $request
     * @return string|null
     */
    public function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
