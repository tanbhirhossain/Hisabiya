<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent when actual spending has reached or exceeded the budget limit.
 */
class BudgetExceededNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    public function __construct(
        public readonly string $categoryName,
        public readonly float $spent,
        public readonly float $limit,
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
            'message' => "You've exceeded your {$this->categoryName} budget (৳{$this->spent} of ৳{$this->limit}).",
            'url' => $this->urlFor('/budgets'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Budget exceeded: {$this->categoryName}")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("You've spent ৳".number_format($this->spent, 2)." in {$this->categoryName}.")
            ->line('Your budget limit for this category is ৳'.number_format($this->limit, 2).'.')
            ->action('Review budgets', $this->urlFor('/budgets'))
            ->line('Thank you for using Hisabiya.');
    }
}
