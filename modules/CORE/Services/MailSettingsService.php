<?php

namespace Modules\CORE\Services;

use Modules\CORE\Models\CoreSetting;
use Illuminate\Support\Facades\Mail;

/**
 * Reads/writes the outbound SMTP mail settings from the database (core_settings)
 * so a super admin can configure transactional email from the UI instead of
 * editing .env. The settings are applied to Laravel's mail config at runtime.
 */
class MailSettingsService
{
    public const PREFIX = 'mail_';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'enabled' => (bool) $this->get('enabled', false),
            'driver' => (string) $this->get('driver', 'smtp'), // smtp | log
            'host' => (string) $this->get('host', ''),
            'port' => (int) $this->get('port', 587),
            'username' => (string) $this->get('username', ''),
            'password' => (string) $this->get('password', ''),
            'encryption' => (string) $this->get('encryption', 'tls'), // tls | ssl | null
            'from_address' => (string) $this->get('from_address', 'no-reply@hisabiya.test'),
            'from_name' => (string) $this->get('from_name', 'Hisabiya'),
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return CoreSetting::get(self::PREFIX.$key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        CoreSetting::set(self::PREFIX.$key, $value);
    }

    /**
     * Persist a set of validated mail settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        foreach (['enabled', 'driver', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name'] as $key) {
            if (array_key_exists($key, $data)) {
                $this->set($key, $data[$key]);
            }
        }
    }

    /**
     * Whether real SMTP delivery is configured and enabled (vs the log driver).
     */
    public function smtpConfigured(): bool
    {
        $config = $this->all();

        return $config['enabled']
            && $config['driver'] === 'smtp'
            && $config['host'] !== '';
    }

    /**
     * Apply these settings onto Laravel's mail config so the default mailer uses
     * the configured SMTP. Called from a service provider's boot().
     */
    public function applyConfig(): void
    {
        $config = $this->all();

        if (! $config['enabled'] || $config['driver'] !== 'smtp' || $config['host'] === '') {
            // Fall back to the .env log driver.
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), [
                'transport' => 'smtp',
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'] === 'none' ? null : ($config['encryption'] ?: null),
                'username' => $config['username'] ?: null,
                'password' => $config['password'] ?: null,
                'timeout' => 30,
            ]),
            'mail.from.address' => $config['from_address'],
            'mail.from.name' => $config['from_name'],
        ]);
    }

    /**
     * Send a simple test email to prove the configured SMTP works.
     */
    public function sendTest(string $to): bool
    {
        try {
            Mail::raw(
                "This is a test email from Hisabiya. Your mail settings are working correctly.",
                fn ($message) => $message->subject('Hisabiya test email')->to($to)
            );

            return true;
        } catch (\Throwable $e) {
            logger()->error('Mail test failed.', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
