<?php

namespace App\Services\Scholar\Management;

use App\Mail\activationLinkMail;
use App\Models\Scholars;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ScholarActivationService
{
    public function sendActivationLink(int $scholarId): array
    {
        $scholar = Scholars::with(['profile'])->findOrFail($scholarId);

        if (! $scholar->profile?->email) {
            throw new Exception('User has no email address.');
        }

        $activation = Str::random(60);

        $scholar->update([
            'activation_token' => $activation,
        ]);

        $url = 'http://172.16.8.98:85/activation?token='.$activation;
        Mail::to($scholar->profile->email)
            ->send(new activationLinkMail($url));

        return [
            'status' => 'success',
            'title' => 'Activation Link!',
            'message' => 'The link has been successfully send.',
        ];
    }
}
