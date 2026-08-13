<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent when a loan's next payment date has passed and the loan is active.
 */
class LoanOverdueNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    public function __construct(
        public readonly string $loanName,
        public readonly float $remainingBalance,
        public readonly int $daysOverdue,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'loan_name' => $this->loanName,
            'remaining_balance' => $this->remainingBalance,
            'days_overdue' => $this->daysOverdue,
            'message' => "Your {$this->loanName} is {$this->daysOverdue} day(s) overdue (৳{$this->remainingBalance} remaining).",
            'url' => $this->urlFor('/loans'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Loan overdue: {$this->loanName}")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("Your {$this->loanName} payment is {$this->daysOverdue} day(s) overdue.")
            ->line('Remaining balance: ৳'.number_format($this->remainingBalance, 2).'.')
            ->action('View loans', $this->urlFor('/loans'))
            ->line('Thank you for using Hisabiya.');
    }
}
