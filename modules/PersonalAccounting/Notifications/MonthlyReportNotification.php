<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent on the 1st of each month with the previous month's summary.
 */
class MonthlyReportNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public readonly array $summary,
        public readonly string $monthLabel,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'month' => $this->monthLabel,
            'summary' => $this->summary,
            'message' => "Your {$this->monthLabel} report is ready.",
            'url' => $this->urlFor('/reports'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $income = $this->summary['income'] ?? 0;
        $expense = $this->summary['expense'] ?? 0;
        $net = $this->summary['net'] ?? 0;

        $mail = (new MailMessage)
            ->subject("Your {$this->monthLabel} report is ready")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Here is your monthly summary:')
            ->line('**Income:** ৳'.number_format((float) $income, 2))
            ->line('**Expenses:** ৳'.number_format((float) $expense, 2))
            ->line('**Net:** ৳'.number_format((float) $net, 2))
            ->action('View full report', $this->urlFor('/reports'))
            ->line('Thank you for using Hisabiya.');

        return $mail;
    }
}
