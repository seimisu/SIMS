<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;


    public $user;
    public $activation;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $activation)
    {
        $this->user = $user;
        $this->activation = $activation;
    }

    public function build()
    {
        $url = rtrim(config('app.url'), '/').'/activate/'.$this->activation;

        return $this->subject('Welcome to SIMS')
            ->view(('UserMail'))
            ->with([
                'user' => $this->user,
                'url' => $url,
            ]);
    }
}
