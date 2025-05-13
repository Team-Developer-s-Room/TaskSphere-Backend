<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaborationInvite extends Notification
{
    use Queueable;

    protected $subject = " - Collaboration Invite";
    protected $message = " has invited you to collaborate on their project. Join the team to start collaborating!";

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected $project_name, 
        protected $invite_url
        // protected $notification_url // Accept notification page url (Link that will lead to a page that shows all invite)
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
            ->greeting("Hello {$notifiable->username},")
            ->line($this->message)
            ->action('Accept Invite', $this->invite_url)
            ->line('Note: The invite link will expire in 7 days.')
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
            'url' => $this->invite_url,
        ];
    }
}
