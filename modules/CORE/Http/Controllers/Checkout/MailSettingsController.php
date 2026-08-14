<?php

namespace Modules\CORE\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Services\MailSettingsService;

/**
 * Lets the CORE super admin configure outbound transactional email (SMTP) from
 * the UI and send a test message to verify it works.
 */
class MailSettingsController extends Controller
{
    public function __construct(private readonly MailSettingsService $mail)
    {
    }

    public function index(): Response
    {
        $config = $this->mail->all();
        unset($config['password']); // never expose the stored password.

        return Inertia::render('CORE::Settings/Mail', [
            'settings' => $config,
            'configured' => $this->mail->smtpConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'driver' => ['required', 'string', 'in:smtp,log'],
            'host' => ['nullable', 'string', 'max:191'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:191'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'from_address' => ['nullable', 'email', 'max:191'],
            'from_name' => ['nullable', 'string', 'max:191'],
        ]);

        $this->mail->save($data);

        return redirect()->route('settings.mail')
            ->with('success', 'Mail settings saved.');
    }

    public function test(Request $request): RedirectResponse
    {
        $to = $request->validate([
            'email' => ['required', 'email'],
        ])['email'];

        $ok = $this->mail->sendTest($to);

        return redirect()->route('settings.mail')
            ->with($ok ? 'success' : 'error', $ok ? "Test email sent to {$to}." : 'Test email failed. Check your SMTP settings.');
    }
}
