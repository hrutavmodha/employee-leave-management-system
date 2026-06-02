<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $leaveRequest;

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
        $status = $this->leaveRequest->status;
        $managerComment = $this->leaveRequest->manager_comment;

        // Dynamically build the URL from env variables: Protocol://Domain/Route
        $protocol = rtrim(env('APP_PROTOCOL', 'http'), ':/');
        $domain = rtrim(env('APP_DOMAIN', 'localhost:8000'), '/');
        $route = ltrim(env('APP_LEAVES_ROUTE', 'leaves'), '/');
        
        $url = "{$protocol}://{$domain}/{$route}";

        return (new MailMessage)
            ->subject("Leave Request Status Updated: {$status}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("Your leave request for {$this->leaveRequest->leaveType->name} has been {$status}.")
            ->line("Dates: {$this->leaveRequest->start_date->format('M d, Y')} to {$this->leaveRequest->end_date->format('M d, Y')}")
            ->line($managerComment ? "Manager Comment: {$managerComment}" : "No comments provided.")
            ->action('View My Leaves', $url)
            ->line('Thank you for using the ELMS application!');
    }
}
