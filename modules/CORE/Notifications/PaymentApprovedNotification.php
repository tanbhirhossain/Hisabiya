<?php

namespace Modules\CORE\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies a user when their manual payment has been approved and the
 * subscription activated.
 */
class PaymentApprovedNotification extends Notification
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $planName,
        public readonly float $amount,
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
            'amount' => $this->amount,
            'message' => "Your payment of ৳{$this->amount} was approved and your {$this->planName} subscription is active.",
            'url' => url('/dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment approved — subscription active')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("Your payment of ৳".number_format($this->amount, 2)." was approved.")
            ->line("Your {$this->planName} subscription is now active.")
            ->action('Go to dashboard', url('/dashboard'))
            ->line('Thank you for using Hisabiya.');
    }
}
