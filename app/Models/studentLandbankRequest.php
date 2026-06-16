<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class studentLandbankRequest extends Model
{
    protected $connection = 'scholars';

    protected $table = 'lbp_requests';

    public $timestamps = false;

    protected $fillable = [
        'request_type',
        'acc_name',
        'acc_no',
        'uploaded_file',
        'uploaded_type',
        'request_purpose',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
        'status',
    ];

    protected $guarded = [
        'spas_no',
    ];

    protected function accName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function accNo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            set: fn ($value) => $this->encryptValue($value),
        );
    }

    protected function uploadedFile(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptValue($value),
            set: fn ($value) => $this->encryptValue($value),
        );
    }

    private function decryptValue($value)
    {
        $data = base64_decode($value);
        $iv = substr($data, 0, openssl_cipher_iv_length('AES-256-CBC'));

        $encrypted = substr($data, openssl_cipher_iv_length('AES-256-CBC'));

        return openssl_decrypt($encrypted, 'AES-256-CBC', env('ENCRYPTION_KEY', null), false, $iv);
    }

    private function encryptValue($value)
    {
        $cipher = 'AES-256-CBC';
        $key = env('ENCRYPTION_KEY', null);

        $iv = random_bytes(openssl_cipher_iv_length($cipher));

        $encrypted = openssl_encrypt(
            $value,
            $cipher,
            $key,
            false,
            $iv
        );

        return base64_encode($iv.$encrypted);
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'spas_no', 'spas_no');
    }
}
