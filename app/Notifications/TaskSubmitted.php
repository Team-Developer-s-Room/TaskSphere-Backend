<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TaskSubmitted extends Notification
{
    use Queueable;

    protected $subject = 'Task Submitted';
    protected $message = 'Your task has been submitted and the admin has been notified.';
    protected $url = '';

    /**
     * Create a new notification instance.
     */
    public function __construct(protected $task)
    {
        //
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
            ->greeting('Hello ' . Auth::user()->username . '!')
            ->line($this->message)
            ->action('View Task', $this->url)
            ->line("Thank you for using " . env('APP_NAME') . "!");
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
            'url' => $this->url,
        ];
    }
}
