<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UpdateCurriculumNotification extends Notification
{
    use Queueable;

    public $coordinator;

    public $program;

    public $school;

    /**
     * Create a new notification instance.
     */
    public function __construct($coordinator, $program, $school)
    {
        $this->coordinator = $coordinator;
        $this->program = $program;
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

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Curriculum Updated',
            'message' => "{$this->coordinator} updated the curriculum for {$this->program} at {$this->school}.",
            'type' => 'curriculum_updated',
        ];
    }
}
