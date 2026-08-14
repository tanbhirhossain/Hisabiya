<?php

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CORE\Models\CoreSetting;
use Modules\CORE\Services\MailSettingsService;

uses(RefreshDatabase::class);

function tfaUser(): User
{
    return User::factory()->create(['email' => '2fa@example.com', 'password' => bcrypt('secret1234')]);
}

// ---------- Two-factor auth service ----------

test('totp verify accepts a code computed for the same secret', function () {
    $svc = app(TwoFactorAuthService::class);
    $secret = 'JBSWY3DPEHPK3PXP'; // RFC-valid Base32 secret

    // Compute the current TOTP code using the standard RFC 6238 algorithm.
    $interval = (int) floor(time() / 30);
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($secret) as $char) {
        $binary .= str_pad(decbin(strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
    }
    $key = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $key .= chr(bindec($chunk));
        }
    }
    $hash = hash_hmac('sha1', pack('N2', 0, $interval), $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
    $binaryNum = ((ord($hash[$offset]) & 0x7F) << 24)
        | ((ord($hash[$offset + 1]) & 0xFF) << 16)
        | ((ord($hash[$offset + 2]) & 0xFF) << 8)
        | (ord($hash[$offset + 3]) & 0xFF);
    $code = str_pad((string) ($binaryNum % 1000000), 6, '0', STR_PAD_LEFT);

    expect($svc->verify($code, $secret))->toBeTrue();
    expect($svc->verify('999999', $secret))->toBeFalse();
});

test('provisioning uri is well formed', function () {
    $svc = app(TwoFactorAuthService::class);
    $uri = $svc->provisioningUri('ABC234', 'user@example.com');

    expect($uri)->toContain('otpauth://totp/');
    expect($uri)->toContain('secret=ABC234');
    expect($uri)->toContain('issuer=Hisabiya');
});

test('two factor setup persists a secret and recovery codes', function () {
    $user = tfaUser();
    $svc = app(TwoFactorAuthService::class);

    $secret = $svc->generateSecret();
    $codes = $svc->generateRecoveryCodes();

    expect(count($codes))->toBe(10);
    $svc->enable($user, $secret, $codes);

    expect($svc->enabled($user))->toBeTrue();
    expect(decrypt($user->two_factor_secret))->toBe($secret);

    // A valid recovery code is accepted and consumed.
    expect($svc->validateRecoveryCode($user, $codes[0]))->toBeTrue();
    $remaining = json_decode($user->two_factor_recovery_codes, true);
    expect(count($remaining))->toBe(9);
});

test('totp verify rejects an invalid code', function () {
    $svc = app(TwoFactorAuthService::class);
    $secret = $svc->generateSecret();

    expect($svc->verify('000000', $secret))->toBeFalse();
    expect($svc->verify('12345', $secret))->toBeFalse();
    expect($svc->verify('abcdef', $secret))->toBeFalse();
});

// ---------- 2FA login flow ----------

test('user with 2fa enabled is sent to the challenge after login', function () {
    $user = tfaUser();
    $svc = app(TwoFactorAuthService::class);
    $svc->enable($user, $svc->generateSecret(), $svc->generateRecoveryCodes());

    $this->post('/login', ['email' => '2fa@example.com', 'password' => 'secret1234'])
        ->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();
});

test('2fa challenge completes with a recovery code and logs the user in', function () {
    $user = tfaUser();
    $svc = app(TwoFactorAuthService::class);
    $codes = $svc->generateRecoveryCodes();
    $svc->enable($user, $svc->generateSecret(), $codes);

    $this->post('/login', ['email' => '2fa@example.com', 'password' => 'secret1234'])
        ->assertRedirect(route('two-factor.challenge'));

    $this->post(route('two-factor.challenge.confirm'), ['code' => $codes[0]])
        ->assertRedirect('/dashboard');

    $this->actingAs($user);
    $this->assertAuthenticated();
});

test('user without 2fa logs in directly', function () {
    tfaUser();

    $this->post('/login', ['email' => '2fa@example.com', 'password' => 'secret1234'])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticated();
});

// ---------- Mail settings ----------

test('mail settings can be saved and read back', function () {
    $svc = app(MailSettingsService::class);
    $svc->save([
        'enabled' => true,
        'driver' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'u',
        'password' => 'p',
        'encryption' => 'tls',
        'from_address' => 'no-reply@example.com',
        'from_name' => 'Hisabiya',
    ]);

    $config = $svc->all();
    expect($config['enabled'])->toBeTrue();
    expect($config['host'])->toBe('smtp.example.com');
    expect($config['port'])->toBe(587);
    expect($svc->smtpConfigured())->toBeTrue();
});

test('mail settings are not considered configured until enabled with host', function () {
    $svc = app(MailSettingsService::class);
    $svc->save(['enabled' => false, 'driver' => 'log']);

    expect($svc->smtpConfigured())->toBeFalse();
});

test('applies smtp config to the laravel mail default', function () {
    $svc = app(MailSettingsService::class);
    $svc->save([
        'enabled' => true,
        'driver' => 'smtp',
        'host' => 'smtp.test.dev',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'user',
        'password' => 'pass',
        'from_address' => 'a@b.c',
        'from_name' => 'N',
    ]);

    $svc->applyConfig();

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.test.dev');
    expect(config('mail.from.address'))->toBe('a@b.c');
});

test('admin can view and update mail settings via the endpoint', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin = User::factory()->create()->assignRole('super-admin');

    $this->actingAs($admin)
        ->get(route('settings.mail'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('settings.mail.update'), [
            'enabled' => 1,
            'driver' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'from_address' => 'a@b.c',
            'from_name' => 'N',
        ])
        ->assertRedirect(route('settings.mail'));

    expect(CoreSetting::get('mail_host'))->toBe('smtp.example.com');
});

test('security headers middleware sets expected headers', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Content-Security-Policy');
});
