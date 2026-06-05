<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeaveRequestCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public $leaveRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $protocol = rtrim(config('app.protocol', 'http'), ':/');
        $domain = rtrim(config('app.domain', 'localhost:8000'), '/');
        $url = "{$protocol}://{$domain}/";

        $employeeName = $this->leaveRequest->user->name;
        $leaveTypeName = $this->leaveRequest->leaveType->name;

        return (new MailMessage)
            ->subject("Leave Request Cancelled: {$employeeName}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("An employee under your supervision, {$employeeName}, has cancelled their leave request for {$leaveTypeName}.")
            ->line("Dates: {$this->leaveRequest->start_date->format('M d, Y')} to {$this->leaveRequest->end_date->format('M d, Y')} ({$this->leaveRequest->days_requested} working days)")
            ->line("Reason: \"{$this->leaveRequest->reason}\"")
            ->action('Go to Application', $url)
            ->line('Thank you for using the ELMS application!');
    }
}
