<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AppUser;
use App\Models\OtpCode;
use App\Services\Sms\SmsEthiopiaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class OtpService
{
    /** Maximum successful code requests per phone number per hour. */
    private const MAX_REQUESTS_PER_PHONE = 5;

    /** Window (seconds) for the per-phone request limit. */
    private const REQUEST_WINDOW_SECONDS = 3600;

    /** Maximum failed verifications per phone number per window. */
    private const MAX_FAILURES_PER_PHONE = 10;

    /** Window (seconds) for the per-phone failure limit. */
    private const FAILURE_WINDOW_SECONDS = 900;

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

        // Hard cap on SMS volume per phone, independent of source IP. This
        // prevents an attacker rotating IPs from draining SMS credit.
        $requestKey = 'otp-request:'.$normalized;
        if (RateLimiter::tooManyAttempts($requestKey, self::MAX_REQUESTS_PER_PHONE)) {
            throw new OtpThrottledException(
                'Too many code requests. Please try again later.',
                RateLimiter::availableIn($requestKey),
            );
        }

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
            // from retrying immediately, nor count against the phone quota.
            $record->delete();

            throw $exception;
        }

        RateLimiter::hit($requestKey, self::REQUEST_WINDOW_SECONDS);

        return [
            'phone' => $normalized,
            'expires_in' => (int) $settings->otp_expiry_seconds,
            'resend_cooldown_seconds' => (int) $settings->resend_cooldown_seconds,
            'otp_length' => $length,
        ];
    }

    /**
     * Verify an OTP and issue a Sanctum bearer token for the app user.
     *
     * @return array{user:AppUser, token:string}
     */
    public function verify(
        string $phone,
        string $code,
        ?string $name = null,
        ?string $email = null,
        ?string $password = null,
    ): array {
        $settings = AppSetting::current();

        if (! $settings->otp_login_enabled) {
            throw ValidationException::withMessages([
                'code' => 'OTP login is currently unavailable.',
            ]);
        }

        $normalized = $this->normalizePhone($phone, $settings);

        // Cross-record failure limit. Requesting a fresh code resets the
        // per-record attempt counter, so this prevents endless guessing.
        $failureKey = 'otp-fail:'.$normalized;
        if (RateLimiter::tooManyAttempts($failureKey, self::MAX_FAILURES_PER_PHONE)) {
            throw new OtpThrottledException(
                'Too many failed attempts. Please try again later.',
                RateLimiter::availableIn($failureKey),
            );
        }

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
            RateLimiter::hit($failureKey, self::FAILURE_WINDOW_SECONDS);

            $remaining = max(0, (int) $record->max_attempts - (int) $record->attempts);
            $message = $remaining > 0
                ? 'The verification code is incorrect.'
                : 'Too many attempts. Please request a new code.';

            throw ValidationException::withMessages(['code' => $message]);
        }

        RateLimiter::clear($failureKey);

        $record->forceFill(['verified_at' => now()])->save();

        OtpCode::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->where('id', '!=', $record->id)
            ->delete();

        $cleanName = $this->cleanName($name);
        $cleanEmail = $this->cleanEmail($email);

        // Never let a verification overwrite an email that belongs to another
        // account. Do this before persisting profile attributes to the user.
        if ($cleanEmail !== null
            && AppUser::query()->where('email', $cleanEmail)->exists()) {
            $ownerPhone = AppUser::query()
                ->where('email', $cleanEmail)
                ->value('phone');

            if ($ownerPhone !== $normalized) {
                throw ValidationException::withMessages([
                    'email' => 'That email address is already in use.',
                ]);
            }
        }

        $user = AppUser::query()->firstOrCreate(
            ['phone' => $normalized],
            ['name' => $cleanName ?? 'Passenger'],
        );

        $attributes = [];
        if ($cleanName !== null) {
            $attributes['name'] = $cleanName;
        }
        if ($cleanEmail !== null) {
            $attributes['email'] = $cleanEmail;
        }
        $cleanPassword = $this->cleanPassword($password);
        if ($cleanPassword !== null) {
            $attributes['password'] = Hash::make($cleanPassword);
        }
        if ($attributes !== []) {
            $user->forceFill($attributes)->save();
        }

        $token = $user->createToken('mobile', ['mobile'])->plainTextToken;

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

    private function cleanPassword(?string $password): ?string
    {
        $password = (string) $password;

        return $password === '' ? null : $password;
    }

    /**
     * Request an OTP for password reset. Only for existing accounts.
     *
     * @return array{phone:string, expires_in:int, resend_cooldown_seconds:int, otp_length:int}
     */
    public function requestPasswordReset(string $phone): array
    {
        $settings = AppSetting::current();
        $normalized = $this->normalizePhone($phone, $settings);

        $user = AppUser::query()->where('phone', $normalized)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'No account found with this phone number.',
            ]);
        }

        $requestKey = 'otp-request:'.$normalized;
        if (RateLimiter::tooManyAttempts($requestKey, self::MAX_REQUESTS_PER_PHONE)) {
            throw new OtpThrottledException(
                'Too many code requests. Please try again later.',
                RateLimiter::availableIn($requestKey),
            );
        }

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
            $record->delete();
            throw $exception;
        }

        RateLimiter::hit($requestKey, self::REQUEST_WINDOW_SECONDS);

        return [
            'phone' => $normalized,
            'expires_in' => (int) $settings->otp_expiry_seconds,
            'resend_cooldown_seconds' => (int) $settings->resend_cooldown_seconds,
            'otp_length' => $length,
        ];
    }

    /**
     * Verify a password reset OTP and set a new password.
     *
     * @return array{user:AppUser, token:string}
     */
    public function resetPassword(string $phone, string $code, string $password): array
    {
        $settings = AppSetting::current();
        $normalized = $this->normalizePhone($phone, $settings);

        $user = AppUser::query()->where('phone', $normalized)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'No account found with this phone number.',
            ]);
        }

        $failureKey = 'otp-fail:'.$normalized;
        if (RateLimiter::tooManyAttempts($failureKey, self::MAX_FAILURES_PER_PHONE)) {
            throw new OtpThrottledException(
                'Too many failed attempts. Please try again later.',
                RateLimiter::availableIn($failureKey),
            );
        }

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

        $record->increment('attempts');
        $record->refresh();

        if (! Hash::check(trim($code), $record->code_hash)) {
            RateLimiter::hit($failureKey, self::FAILURE_WINDOW_SECONDS);

            $remaining = max(0, (int) $record->max_attempts - (int) $record->attempts);
            $message = $remaining > 0
                ? 'The verification code is incorrect.'
                : 'Too many attempts. Please request a new code.';

            throw ValidationException::withMessages(['code' => $message]);
        }

        RateLimiter::clear($failureKey);

        $record->forceFill(['verified_at' => now()])->save();

        OtpCode::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->where('id', '!=', $record->id)
            ->delete();

        $cleanPassword = $this->cleanPassword($password);
        if ($cleanPassword === null || strlen($cleanPassword) < 6) {
            throw ValidationException::withMessages([
                'password' => 'Password must be at least 6 characters.',
            ]);
        }

        $user->forceFill(['password' => Hash::make($cleanPassword)])->save();

        // Revoke existing tokens for security after password reset.
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('mobile', ['mobile'])->plainTextToken;

        return ['user' => $user->refresh(), 'token' => $token];
    }

    /**
     * Check if a phone number is registered.
     *
     * @return array{phone:string, exists:bool, has_password:bool}
     */
    public function checkPhone(string $phone): array
    {
        $settings = AppSetting::current();
        $normalized = $this->normalizePhone($phone, $settings);

        $user = AppUser::query()->where('phone', $normalized)->first();

        return [
            'phone' => $normalized,
            'exists' => $user !== null,
            'has_password' => $user !== null && filled($user->password),
        ];
    }

    /**
     * Authenticate with phone and password.
     *
     * @return array{user:AppUser, token:string}
     */
    public function loginWithPassword(string $phone, string $password): array
    {
        $settings = AppSetting::current();
        $normalized = $this->normalizePhone($phone, $settings);

        $throttleKey = 'pwd-login:'.$normalized;
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw new OtpThrottledException(
                'Too many login attempts. Please try again later.',
                RateLimiter::availableIn($throttleKey),
            );
        }

        $user = AppUser::query()->where('phone', $normalized)->first();

        if (! $user || empty($user->password) || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 900);

            if ($user && empty($user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'No password set for this account. Please reset your password.',
                ]);
            }

            throw ValidationException::withMessages([
                'phone' => 'Invalid phone number or password.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken('mobile', ['mobile'])->plainTextToken;

        return ['user' => $user->refresh(), 'token' => $token];
    }
}
