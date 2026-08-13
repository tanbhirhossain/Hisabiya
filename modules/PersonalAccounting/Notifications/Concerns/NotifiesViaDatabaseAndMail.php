<?php

namespace Modules\PersonalAccounting\Notifications\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Shared wiring for Personal Accounting notifications:
 * - queued
 * - persisted to the database (toDatabase) and sent by mail (toMail)
 */
trait NotifiesViaDatabaseAndMail
{
    use Queueable;

    /**
     * DB notification payload (sub-classes override).
     *
     * @return array<string, mixed>
     */
    abstract public function toDatabase(object $notifiable): array;

    /**
     * Mail message (sub-classes override).
     */
    abstract public function toMail(object $notifiable): MailMessage;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Return a URL the notification should deep-link to.
     */
    protected function urlFor(string $path): string
    {
        return url('/personal'.$path);
    }
}
