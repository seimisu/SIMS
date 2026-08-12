<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class studentLandbankRequest extends Model
{
    protected $connection = 'scholars';

    protected $table = 'landbank_requests';

    public $timestamps = true;

    protected $fillable = [
        'scholar_id',
        'account_name_encrypted',
        'account_number_encrypted',
        'uploaded_type_encrypted',
        'uploaded_file_encrypted',
        'status',
        'reviewer_remarks_encrypted',
        'reviewed_at',
        'reviewed_by',
        'acc_name',
        'acc_no',
        'uploaded_file',
        'uploaded_type',
        'rejection_reason',
    ];

    protected $appends = [
        'spas_no',
        'acc_name',
        'acc_no',
        'uploaded_file',
        'uploaded_type',
        'request_purpose',
        'rejection_reason',
        'requested_at',
    ];

    protected function accName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->account_name_encrypted),
            set: fn ($value) => ['account_name_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function accNo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->account_number_encrypted),
            set: fn ($value) => ['account_number_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function uploadedType(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->uploaded_type_encrypted),
            set: fn ($value) => ['uploaded_type_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function uploadedFile(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->uploaded_file_encrypted),
            set: fn ($value) => ['uploaded_file_encrypted' => $this->encryptValue($value)],
        );
    }

    protected function rejectionReason(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decryptValue($this->reviewer_remarks_encrypted),
            set: fn ($value) => ['reviewer_remarks_encrypted' => $value],
        );
    }

    public function getSpasNoAttribute(): ?string
    {
        return $this->scholar?->spas_no;
    }

    public function getRequestPurposeAttribute(): ?string
    {
        return $this->rejection_reason ?: 'Landbank update request';
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

        $key = $this->encryptionKey();

        if ($key === null) {
            return null;
        }

        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted === false ? null : $decrypted;
    }

    private function encryptionKey(): ?string
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
