<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            // Manually flush the user from the auth cache.
            $cacheKey = 'auth:user:' . $request->user()->getAuthIdentifier();
            $cacheStore = config('auth.providers.users.cache_store');
            Cache::store($cacheStore)->forget($cacheKey);
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }
}
