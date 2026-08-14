<?php

namespace Modules\CORE\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the module owner that their subscription term has ended and a
 * renewal payment is due to continue access.
 */
class SubscriptionRenewalNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $planName,
        public readonly float $amount,
        public readonly int $paymentId,
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
            'message' => "Your {$this->planName} subscription renewal of ৳{$this->amount} is due. Pay within the grace period to keep access.",
            'url' => url('/billing'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Renewal payment due — '.$this->planName)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("Your {$this->planName} subscription term has ended.")
            ->line("A renewal payment of ৳".number_format($this->amount, 2)." is due to keep your access active.")
            ->line('You have a short grace period to pay before access is revoked.')
            ->action('Pay renewal', url('/billing'))
            ->line('Thank you for using Hisabiya.');
    }
}
