<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent at 25%, 50% and 75% milestones of a savings goal.
 */
class SavingsGoalMilestoneNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    public function __construct(
        public readonly string $goalName,
        public readonly float $currentAmount,
        public readonly float $targetAmount,
        public readonly int $percent,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'goal_name' => $this->goalName,
            'current_amount' => $this->currentAmount,
            'target_amount' => $this->targetAmount,
            'percent' => $this->percent,
            'message' => "You're {$this->percent}% of the way to your {$this->goalName} goal!",
            'url' => $this->urlFor('/goals'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Savings milestone: {$this->percent}% toward {$this->goalName}")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("You're {$this->percent}% of the way to your {$this->goalName} savings goal.")
            ->line('Current: ৳'.number_format($this->currentAmount, 2).' of ৳'.number_format($this->targetAmount, 2).'.')
            ->action('View goals', $this->urlFor('/goals'))
            ->line('Keep it going!');
    }
}
