<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent when a recurring transaction template fails to process.
 */
class RecurringTransactionFailedNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    public function __construct(
        public readonly string $recurringName,
        public readonly string $errorMessage,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'recurring_name' => $this->recurringName,
            'error_message' => $this->errorMessage,
            'message' => "A recurring transaction failed: {$this->recurringName}.",
            'url' => $this->urlFor('/transactions'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Recurring transaction failed: {$this->recurringName}")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("We couldn't process your recurring transaction '{$this->recurringName}'.")
            ->line('Error: '.$this->errorMessage)
            ->action('Review transactions', $this->urlFor('/transactions'))
            ->line('Thank you for using Hisabiya.');
    }
}
