<?php

namespace App\Services\Profile;

use Illuminate\Support\Facades\Auth;

class TwoFactoryAuthenticatorQR
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function getTwoFactorQrCodeUrl(): ?string
    {
        return $this->user->two_factor_secret
            ? $this->user->twoFactorQrCodeSvg()
            : null;
    }

    public function isTwoFactorEnabled(): bool
    {
        return ! is_null($this->user->two_factor_secret);
    }

    public function getRecorveryCodes(): ?array
    {
        return $this->user->two_factor_recovery_codes
            ? json_decode(decrypt($this->user->two_factor_recovery_codes), true)
            : null;
    }
}
