<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class StudentProfileRequest extends Model
{
    protected $connection = 'scholars';

    protected $table = 'profile_requests';

    public $timestamps = true;

    protected $fillable = [
        'scholar_id',
        'first_name_encrypted',
        'middle_name_encrypted',
        'last_name_encrypted',
        'suffix_encrypted',
        'civil_status_encrypted',
        'contact_number_encrypted',
        'email_encrypted',
        'zip_code_encrypted',
        'address_line_encrypted',
        'region_encrypted',
        'province_encrypted',
        'city_encrypted',
        'barangay_encrypted',
        'additional_proof_encrypted',
        'status',
        'reviewer_remarks_encrypted',
        'reviewed_at',
        'reviewed_by',
        'civil_status',
        'email',
        'contact_no',
        'address',
        'municipality',
        'province',
        'region',
        'barangay',
        'remarks',
    ];

    protected $appends = [
        'spas_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'civil_status',
        'email',
        'contact_no',
        'address',
        'municipality',
        'province',
        'region',
        'barangay',
        'proof',
        'proof_type',
        'remarks',
        'purpose',
        'requested_at',
    ];

    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->first_name_encrypted),
            set: fn ($value) => ['first_name_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function middleName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->middle_name_encrypted),
            set: fn ($value) => ['middle_name_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->last_name_encrypted),
            set: fn ($value) => ['last_name_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function suffix(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->suffix_encrypted),
            set: fn ($value) => ['suffix_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function civilStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->civil_status_encrypted),
            set: fn ($value) => ['civil_status_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->email_encrypted),
            set: fn ($value) => ['email_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function contactNo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->contact_number_encrypted),
            set: fn ($value) => ['contact_number_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->address_line_encrypted),
            set: fn ($value) => ['address_line_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function barangay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->barangay_encrypted),
            set: fn ($value) => ['barangay_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function municipality(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->city_encrypted),
            set: fn ($value) => ['city_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function province(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->province_encrypted),
            set: fn ($value) => ['province_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function region(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->region_encrypted),
            set: fn ($value) => ['region_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function remarks(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->reviewer_remarks_encrypted),
            set: fn ($value) => ['reviewer_remarks_encrypted' => $this->encryptValue($value)],
        );
    }

    public function getSpasNoAttribute(): ?string
    {
        return $this->scholar?->spas_no;
    }

    public function getProofAttribute(): ?string
    {
        return $this->decryptValue($this->additional_proof_encrypted);
    }

    public function getProofTypeAttribute(): ?string
    {
        return $this->additional_proof_encrypted ? 'additional_proof' : null;
    }

    public function getPurposeAttribute(): ?string
    {
        return $this->remarks ?: 'Profile update request';
    }

    public function getRequestedAtAttribute()
    {
        return $this->created_at;
    }

    private function decryptValue($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $gcmDecrypted = $this->decryptGcmValue($value);

        if ($gcmDecrypted !== null) {
            return $gcmDecrypted;
        }

        $data = base64_decode($value, true);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');

        if ($data === false || strlen($data) <= $ivLength) {
            return $value;
        }

        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', env('ENCRYPTION_KEY'), false, $iv);

        return $decrypted === false ? $value : $decrypted;
    }

    private function decryptGcmValue(string $value): ?string
    {
        $parts = explode('.', $value);

        if (count($parts) === 3) {
            [$iv, $tag, $encrypted] = array_map(
                fn ($part) => base64_decode($part, true),
                $parts
            );
        } else {
            $data = base64_decode($value, true);

            if ($data === false || strlen($data) <= 28) {
                return null;
            }

            $iv = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $encrypted = substr($data, 28);
        }

        if ($iv === false || $tag === false || $encrypted === false) {
            return null;
        }

        $key = $this->profileRequestEncryptionKey();

        if ($key === null) {
            return null;
        }

        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted === false ? null : $decrypted;
    }

    private function profileRequestEncryptionKey(): ?string
    {
        $secret = base64_decode(config('app.external_encryption_secret', ''), true);
        $salt = base64_decode(config('app.external_encryption_salt', ''), true);

        if ($secret === false || strlen($secret) < 32 || $salt === false || strlen($salt) < 16) {
            return null;
        }

        return hash_pbkdf2('sha256', $secret, $salt, 100000, 32, true);
    }

    private function encryptValue($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $cipher = 'AES-256-CBC';
        $iv = random_bytes(openssl_cipher_iv_length($cipher));
        $encrypted = openssl_encrypt($value, $cipher, env('ENCRYPTION_KEY'), false, $iv);

        return base64_encode($iv.$encrypted);
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id', 'id');
    }
}
