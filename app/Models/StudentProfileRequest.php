<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class StudentProfileRequest extends Model
{
    protected $connection = 'scholars';

    protected $table = 'profile_requests';

    public $timestamps = false;

    protected $fillable = [
        'civil_status',
        'email',
        'contact_no',
        'address',
        'barangay',
        'municipality',
        'province',
        'region',
        'status',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'civil_status' => 'encrypted',
        'email' => 'encrypted',
        'contact_no' => 'encrypted',
        'address' => 'encrypted',
        'barangay' => 'encrypted',
        'municipality' => 'encrypted',
        'province' => 'encrypted',
        'region' => 'encrypted',
    ];

    protected function civilStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function contactNo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function barangay(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function municipality(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function province(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function region(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            // set: fn ($value) => $this->encryptValue($value),
        );
    }

    private function decryptValue($value)
    {
        $data = base64_decode($value);
        $iv = substr($data, 0, openssl_cipher_iv_length('AES-256-CBC'));

        $encrypted = substr($data, openssl_cipher_iv_length('AES-256-CBC'));

        return openssl_decrypt($encrypted, 'AES-256-CBC', env('ENCRYPTION_KEY', null), false, $iv);
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'spas_no', 'spas_no');
    }
}
