<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsEthiopiaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class OtpService
{
    public function __construct(private readonly SmsEthiopiaService $sms)
    {
    }

    /**
     * Normalize Ethiopian phone input to E.164 using the admin-configured
     * country code. The database always stores the canonical form.
     */
    public function normalizePhone(string $phone, ?AppSetting $settings = null): string
    {
        $settings ??= AppSetting::current();
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $countryDigits = preg_replace('/\D+/', '', (string) $settings->phone_country_code) ?? '';

        if ($countryDigits === '') {
            $countryDigits = '251';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, $countryDigits)) {
            $digits = substr($digits, strlen($countryDigits));
        }

        $digits = ltrim($digits, '0');

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            throw ValidationException::withMessages([
                'phone' => 'Please provide a valid phone number.',
            ]);
        }

        return '+'.$countryDigits.$digits;
    }

    /**
     * Create an OTP, persist only its hash, and deliver it through the
     * currently configured provider.
     *
     * @return array{phone:string, expires_in:int, resend_cooldown_seconds:int, otp_length:int}
     */
    public function request(string $phone): array
    {
        $settings = AppSetting::current();

        if (! $settings->otp_login_enabled) {
            throw ValidationException::withMessages([
                'phone' => 'OTP login is currently unavailable.',
            ]);
        }

        $normalized = $this->normalizePhone($phone, $settings);
        $latest = OtpCode::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if ($latest && $latest->last_sent_at) {
            $waitSeconds = (int) ($latest->last_sent_at->getTimestamp()
                + (int) $settings->resend_cooldown_seconds
                - now()->getTimestamp());

            if ($waitSeconds > 0) {
                throw new OtpThrottledException(
                    'Please wait before requesting another code.',
                    $waitSeconds,
                );
            }
        }

        $length = max(4, min(8, (int) $settings->otp_length));
        $code = $this->generateCode($length);

        $record = OtpCode::query()->create([
            'phone' => $normalized,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds((int) $settings->otp_expiry_seconds),
            'last_sent_at' => now(),
            'attempts' => 0,
            'max_attempts' => (int) $settings->max_verify_attempts,
        ]);

        try {
            $this->sms->send(
                $normalized,
                $this->renderMessage($settings, $code),
                $settings,
            );
        } catch (Throwable $exception) {
            // A failed delivery must not start a cooldown that blocks the user
            // from retrying immediately.
            $record->delete();

            throw $exception;
        }

        return [
            'phone' => $normalized,
            'expires_in' => (int) $settings->otp_expiry_seconds,
            'resend_cooldown_seconds' => (int) $settings->resend_cooldown_seconds,
            'otp_length' => $length,
        ];
    }

    /**
     * Verify an OTP and issue a Sanctum bearer token for the passenger.
     *
     * @return array{user:User, token:string}
     */
    public function verify(
        string $phone,
        string $code,
        ?string $name = null,
        ?string $email = null,
    ): array {
        $settings = AppSetting::current();

        if (! $settings->otp_login_enabled) {
            throw ValidationException::withMessages([
                'code' => 'OTP login is currently unavailable.',
            ]);
        }

        $normalized = $this->normalizePhone($phone, $settings);

        $record = OtpCode::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'code' => 'No verification code found. Please request a new one.',
            ]);
        }

        if ($record->isExpired()) {
            throw ValidationException::withMessages([
                'code' => 'This verification code has expired. Please request a new one.',
            ]);
        }

        if ($record->attempts >= $record->max_attempts) {
            throw new OtpThrottledException(
                'Too many attempts. Please request a new code.',
            );
        }

        // Count the attempt before comparing the hash so brute-force attempts
        // are always persisted even if the request is interrupted.
        $record->increment('attempts');
        $record->refresh();

        if (! Hash::check(trim($code), $record->code_hash)) {
            $remaining = max(0, (int) $record->max_attempts - (int) $record->attempts);
            $message = $remaining > 0
                ? 'The verification code is incorrect.'
                : 'Too many attempts. Please request a new code.';

            throw ValidationException::withMessages(['code' => $message]);
        }

        $record->forceFill(['verified_at' => now()])->save();

        OtpCode::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->where('id', '!=', $record->id)
            ->delete();

        $user = User::query()->firstOrCreate(
            ['phone' => $normalized],
            [
                'name' => $this->cleanName($name) ?? 'Passenger',
                'email' => $this->cleanEmail($email),
                'is_admin' => false,
            ],
        );

        $attributes = [];
        if (($cleanName = $this->cleanName($name)) !== null) {
            $attributes['name'] = $cleanName;
        }
        if (($cleanEmail = $this->cleanEmail($email)) !== null) {
            $attributes['email'] = $cleanEmail;
        }
        if ($attributes !== []) {
            $user->forceFill($attributes)->save();
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return ['user' => $user->refresh(), 'token' => $token];
    }

    private function generateCode(int $length): string
    {
        $min = (int) pow(10, $length - 1);
        $max = (int) pow(10, $length) - 1;

        return (string) random_int($min, $max);
    }

    private function renderMessage(AppSetting $settings, string $code): string
    {
        $template = $settings->sms_message_template
            ?: 'Your {app_name} verification code is {code}. It expires in {minutes} minutes.';
        $minutes = max(1, (int) ceil(((int) $settings->otp_expiry_seconds) / 60));

        return strtr($template, [
            '{code}' => $code,
            '{app_name}' => (string) $settings->app_name,
            '{minutes}' => (string) $minutes,
        ]);
    }

    private function cleanName(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name === '' ? null : mb_substr($name, 0, 120);
    }

    private function cleanEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email === '' ? null : mb_substr($email, 0, 190);
    }
}
