<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent when actual spending reaches a configurable % (default 70%) of the limit.
 */
class BudgetWarningNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    public function __construct(
        public readonly string $categoryName,
        public readonly float $spent,
        public readonly float $limit,
        public readonly int $thresholdPercent,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'category_name' => $this->categoryName,
            'spent' => $this->spent,
            'limit' => $this->limit,
            'threshold_percent' => $this->thresholdPercent,
            'message' => "You've used {$this->thresholdPercent}% of your {$this->categoryName} budget (৳{$this->spent} of ৳{$this->limit}).",
            'url' => $this->urlFor('/budgets'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Budget warning: {$this->categoryName}")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("You've used {$this->thresholdPercent}% of your {$this->categoryName} budget.")
            ->line('Current spending: ৳'.number_format($this->spent, 2).' of ৳'.number_format($this->limit, 2).'.')
            ->action('Review budgets', $this->urlFor('/budgets'))
            ->line('Thank you for using Hisabiya.');
    }
}
