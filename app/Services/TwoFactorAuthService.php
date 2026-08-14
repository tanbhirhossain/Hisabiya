<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * RFC 6238 Time-based One-Time Password (TOTP) two-factor authentication.
 * Self-contained (no external package) so it works with Google Authenticator,
 * Authy, 1Password and other standard TOTP apps.
 */
class TwoFactorAuthService
{
    /** Application label shown in the authenticator app. */
    private const ISSUER = 'Hisabiya';

    /**
     * Generate a new Base32 random secret for TOTP.
     */
    public function generateSecret(int $length = 32): string
    {
        $randomBytes = random_bytes(ceil($length * 5 / 8));

        return $this->base32Encode($randomBytes);
    }

    /**
     * The otpauth:// provisioning URI used to generate a QR code.
     */
    public function provisioningUri(string $secret, string $email): string
    {
        return 'otpauth://totp/'.rawurlencode(self::ISSUER.':'.$email)
            .'?secret='.$secret
            .'&issuer='.rawurlencode(self::ISSUER)
            .'&algorithm=SHA1&digits=6&period=30';
    }

    /**
     * Verify a 6-digit TOTP code against a secret, allowing a small window for
     * clock drift (±1 interval).
     */
    public function verify(string $code, ?string $secret): bool
    {
        if (! $secret || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $secret = $this->normalizeBase32($secret);
        $currentInterval = (int) floor(time() / 30);

        foreach (range(-1, 1) as $drift) {
            $expected = $this->totp($secret, $currentInterval + $drift);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate an array of recovery codes (8x4 groups). Stored hashed.
     *
     * @return array<int, string> plain-text codes (shown once to the user)
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(substr(Str::uuid()->toString(), 0, 4))
                .'-'.strtoupper(substr(Str::uuid()->toString(), 0, 4));
        }

        return $codes;
    }

    /**
     * Enable 2FA for a user: store the (encrypted) secret and the hashed
     * recovery codes, mark confirmed.
     *
     * @param  array<int, string>  $recoveryCodes
     */
    public function enable(User $user, string $secret, array $recoveryCodes): void
    {
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => json_encode(
                collect($recoveryCodes)->map(fn (string $c) => bcrypt($c))->all()
            ),
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Whether 2FA is active for the user.
     */
    public function enabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null && $user->two_factor_secret !== null;
    }

    /**
     * Validate a recovery code against the user's stored (hashed) codes. A valid
     * code is consumed (removed) after use.
     */
    public function validateRecoveryCode(User $user, string $code): bool
    {
        $codes = json_decode((string) $user->two_factor_recovery_codes, true) ?: [];
        $normalized = strtoupper(trim($code));

        foreach ($codes as $i => $hashed) {
            if (password_verify($normalized, $hashed)) {
                unset($codes[$i]);
                $user->forceFill([
                    'two_factor_recovery_codes' => json_encode(array_values($codes)),
                ])->save();

                return true;
            }
        }

        return false;
    }

    // ---------- TOTP internals ----------

    private function totp(string $base32Secret, int $interval): string
    {
        $key = $this->base32Decode($base32Secret);
        $time = pack('N2', 0, $interval);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function normalizeBase32(string $secret): string
    {
        return strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $out = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        foreach (str_split($binary, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $out .= $alphabet[bindec($chunk)];
        }

        return $out;
    }

    private function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $out = '';

        foreach (str_split($base32) as $char) {
            $index = strpos($alphabet, $char);
            if ($index === false) {
                continue;
            }
            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }
            $out .= chr(bindec($chunk));
        }

        return $out;
    }
}
