<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent when a savings goal's current amount reaches the target.
 */
class SavingsGoalReachedNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    public function __construct(
        public readonly string $goalName,
        public readonly float $targetAmount,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'goal_name' => $this->goalName,
            'target_amount' => $this->targetAmount,
            'message' => "Congratulations! You've reached your savings goal: {$this->goalName} (৳{$this->targetAmount}).",
            'url' => $this->urlFor('/goals'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Savings goal reached: {$this->goalName} 🎉")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("Congratulations! You've reached your savings goal of ৳".number_format($this->targetAmount, 2)." for {$this->goalName}.")
            ->action('View goals', $this->urlFor('/goals'))
            ->line('Keep up the great work!');
    }
}
