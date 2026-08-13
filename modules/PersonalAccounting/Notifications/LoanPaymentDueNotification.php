<?php

namespace Modules\PersonalAccounting\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Modules\PersonalAccounting\Notifications\Concerns\NotifiesViaDatabaseAndMail;

/**
 * Sent 3 days before a loan's next payment date.
 */
class LoanPaymentDueNotification extends Notification
{
    use NotifiesViaDatabaseAndMail, SerializesModels;

    public function __construct(
        public readonly string $loanName,
        public readonly float $amountDue,
        public readonly string $dueDate,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'loan_name' => $this->loanName,
            'amount_due' => $this->amountDue,
            'due_date' => $this->dueDate,
            'message' => "Your {$this->loanName} payment of ৳{$this->amountDue} is due on {$this->dueDate}.",
            'url' => $this->urlFor('/loans'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Loan payment due: {$this->loanName}")
            ->greeting('Hello '.$notifiable->name.'!')
            ->line("A payment of ৳".number_format($this->amountDue, 2)." for {$this->loanName} is due on {$this->dueDate}.")
            ->action('View loans', $this->urlFor('/loans'))
            ->line('Thank you for using Hisabiya.');
    }
}
