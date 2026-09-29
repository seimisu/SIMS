<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScholarUploadedNotification extends Notification
{
    use Queueable;

    public $regionStaff;

    public $region;

    public function __construct($regionStaff, $region)
    {
        $this->regionStaff = $regionStaff;
        $this->region = $region;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New File Uploaded',
            'message' => "{$this->regionStaff} has uploaded new files for the {$this->region} scholars.",
            'type' => 'scholar_upload',
        ];
    }
}
