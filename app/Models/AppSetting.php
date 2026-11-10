<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'app_name',
        'otp_login_enabled',
        'phone_country_code',
        'otp_length',
        'otp_expiry_seconds',
        'resend_cooldown_seconds',
        'max_verify_attempts',
        'sms_provider',
        'sms_api_key',
        'sms_sender_id',
        'sms_message_template',
    ];

    /** @var list<string> */
    protected $hidden = [
        'sms_api_key',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'otp_login_enabled' => 'boolean',
            'otp_length' => 'integer',
            'otp_expiry_seconds' => 'integer',
            'resend_cooldown_seconds' => 'integer',
            'max_verify_attempts' => 'integer',
            'sms_api_key' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'app_name' => 'MkRide',
            'otp_login_enabled' => true,
            'phone_country_code' => '+251',
            'otp_length' => 6,
            'otp_expiry_seconds' => 300,
            'resend_cooldown_seconds' => 60,
            'max_verify_attempts' => 5,
            'sms_provider' => 'smsethiopia',
            'sms_message_template' => 'Your {app_name} verification code is {code}. It expires in {minutes} minutes.',
        ]);
    }

    /** @return array<string, mixed> */
    public function toPublicConfig(): array
    {
        return [
            'otp_login_enabled' => (bool) $this->otp_login_enabled,
            'phone_country_code' => (string) $this->phone_country_code,
            'otp_length' => (int) $this->otp_length,
            'otp_expiry_seconds' => (int) $this->otp_expiry_seconds,
            'resend_cooldown_seconds' => (int) $this->resend_cooldown_seconds,
            'app_name' => (string) $this->app_name,
        ];
    }
}
