<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateSemesterCoordinatorNotification extends Notification
{
    use Queueable;

    public $coordinator;

    public $school;

    public function __construct($coordinator, $school)
    {
        $this->coordinator = $coordinator;
        $this->school = $school;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Updated Semester',
            'message' => "The semester details for {$this->school} have been updated by {$this->coordinator}.",
            'type' => 'updated_semester',
        ];
    }
}
