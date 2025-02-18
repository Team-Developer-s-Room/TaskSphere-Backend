<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    protected $subject = ' - New Task Assigned';
    protected $message = '\'(s) admin assigned you a new task. Check it out to stay updated!';

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected $project_name,
        protected $receiver_name,
        protected $notification_url // Project page url
    )
    {
        $this->subject = ucwords($project_name) . $this->subject;
        $this->message = ucwords($project_name) . $this->message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting("Hello {$this->receiver_name},")
            ->line($this->message)
            ->action('View Task', $this->notification_url)
            ->salutation('Thank you for using ' . env('APP_NAME') . '!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->subject,
            'message' => $this->message,
            'url' => $this->notification_url,
        ];
    }
}
