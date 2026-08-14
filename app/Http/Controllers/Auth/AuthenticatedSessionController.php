<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Validate credentials without logging in so we can branch on 2FA.
        $user = $request->validateCredentials();

        $twoFactor = app(\App\Services\TwoFactorAuthService::class);

        // If the user has 2FA enabled, don't establish a session yet — send them
        // to the challenge step instead.
        if ($twoFactor->enabled($user)) {
            $request->session()->put('two_factor_login', [
                'id' => $user->id,
                'remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        $request->loginUser($user);

        // Route the user to their module dashboard based on active subscriptions.
        $route = app(\Modules\CORE\Services\SubscriptionActivationService::class)
            ->routeForUser((int) $user->id);

        return redirect()->intended($route);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
