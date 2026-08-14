<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Two-factor authentication setup + login challenge.
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorAuthService $twoFactor)
    {
    }

    /**
     * Settings page — start setup, or confirm an in-progress setup, or disable.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $enabled = $this->twoFactor->enabled($user);

        $setup = false;
        $qrUri = null;
        $secret = null;
        $recoveryCodes = [];

        if (! $enabled) {
            // If a secret already exists (setup in progress), reuse it.
            if ($user->two_factor_secret) {
                $secret = decrypt($user->two_factor_secret);
                $setup = true;
                $qrUri = $this->twoFactor->provisioningUri($secret, (string) $user->email);
            }
        }

        return Inertia::render('auth/TwoFactor', [
            'enabled' => $enabled,
            'setup' => $setup,
            'qr_uri' => $qrUri,
            'secret' => $secret,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Begin enabling 2FA: generate + persist a secret and recovery codes, and
     * render the confirmation step with the QR code.
     */
    public function setup(Request $request): RedirectResponse
    {
        $user = $request->user();

        $secret = $this->twoFactor->generateSecret();
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => json_encode(
                collect($recoveryCodes)->map(fn (string $c) => bcrypt($c))->all()
            ),
            'two_factor_confirmed_at' => null,
        ])->save();

        // Show the plaintext recovery codes exactly once, on the confirm page.
        $request->session()->put('two_factor_recovery_codes', $recoveryCodes);

        return redirect()->route('two-factor.settings')->with('setup', true);
    }

    /**
     * Confirm the setup by verifying a TOTP code.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:6'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            throw ValidationException::withMessages(['code' => 'No 2FA setup in progress.']);
        }

        $secret = decrypt($user->two_factor_secret);

        if (! $this->twoFactor->verify((string) $request->string('code'), $secret)) {
            throw ValidationException::withMessages(['code' => 'The code is invalid or expired.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        // Pull the plaintext recovery codes for display (shown once).
        $recoveryCodes = $request->session()->pull('two_factor_recovery_codes', []);

        return redirect()->route('two-factor.settings')
            ->with('two_factor_enabled', true)
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Disable two-factor authentication.
     */
    public function disable(Request $request): RedirectResponse
    {
        $this->twoFactor->disable($request->user());

        return redirect()->route('two-factor.settings')
            ->with('success', 'Two-factor authentication disabled.');
    }

    // ---------- Login challenge ----------

    /**
     * Show the 2FA challenge page after a successful password login.
     */
    public function challenge(Request $request): Response|RedirectResponse
    {
        $pending = $request->session()->get('two_factor_login');

        if (! $pending) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/TwoFactorChallenge');
    }

    /**
     * Verify the TOTP or recovery code and finish the login.
     */
    public function confirmChallenge(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $pending = $request->session()->get('two_factor_login');
        abort_unless($pending && isset($pending['id']), 422);

        /** @var User $user */
        $user = User::findOrFail($pending['id']);

        $code = (string) $request->string('code');

        $valid = $this->twoFactor->verify($code, decrypt($user->two_factor_secret))
            || $this->twoFactor->validateRecoveryCode($user, $code);

        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'The code is invalid or expired.']);
        }

        // Finish the login.
        Auth::login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->regenerate();
        $request->session()->forget('two_factor_login');

        $route = app(\Modules\CORE\Services\SubscriptionActivationService::class)
            ->routeForUser((int) $user->id);

        return redirect()->intended($route);
    }
}
