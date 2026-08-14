<?php

namespace Modules\CORE\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the module owner that their subscription has expired and module
 * access has been revoked.
 */
class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $planName,
        public readonly string $reason,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'plan_name' => $this->planName,
            'message' => "Your {$this->planName} subscription has expired. Access has been revoked. {$this->reason}",
            'url' => url('/pricing'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription expired — '.$this->planName)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("Your {$this->planName} subscription has expired and module access has been revoked.")
            ->line($this->reason)
            ->action('Resubscribe', url('/pricing'))
            ->line('Thank you for using Hisabiya.');
    }
}
