<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Attempts allowed for a single username + IP pair. */
    private const MAX_ATTEMPTS_PER_ACCOUNT = 5;

    /** Attempts allowed for a single IP across every account. */
    private const MAX_ATTEMPTS_PER_IP = 20;

    /** How long (seconds) a failed attempt counts against the limiter. */
    private const DECAY_SECONDS = 900;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Only dashboard users (admin roles) are authenticated, and failures always
     * return the same generic message so valid accounts cannot be enumerated.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $authenticated = Auth::attemptWhen(
            $this->only('username', 'password'),
            static fn ($user): bool => method_exists($user, 'isAdmin') ? $user->isAdmin() : (bool) ($user->is_admin ?? false),
            false,
        );

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
            RateLimiter::hit($this->ipThrottleKey(), self::DECAY_SECONDS);

            $this->logFailure();

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->ipThrottleKey());

        Log::channel('security')->info('Admin login succeeded', [
            'user_id' => Auth::id(),
            'username' => Str::lower((string) $this->input('username')),
            'ip' => $this->ip(),
            'user_agent' => Str::limit((string) $this->userAgent(), 250),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $limits = [
            [$this->throttleKey(), self::MAX_ATTEMPTS_PER_ACCOUNT],
            [$this->ipThrottleKey(), self::MAX_ATTEMPTS_PER_IP],
        ];

        foreach ($limits as [$key, $maxAttempts]) {
            if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                continue;
            }

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($key);

            Log::channel('security')->warning('Admin login locked out', [
                'username' => Str::lower((string) $this->input('username')),
                'ip' => $this->ip(),
                'retry_after' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'username' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    /**
     * Get the rate limiting throttle key for the username + IP pair.
     */
    public function throttleKey(): string
    {
        return 'admin-login:'.Str::transliterate(
            Str::lower((string) $this->string('username')).'|'.$this->ip()
        );
    }

    /**
     * Get the rate limiting throttle key for the source IP.
     */
    public function ipThrottleKey(): string
    {
        return 'admin-login-ip:'.$this->ip();
    }

    private function logFailure(): void
    {
        Log::channel('security')->warning('Admin login failed', [
            'username' => Str::lower((string) $this->input('username')),
            'ip' => $this->ip(),
            'user_agent' => Str::limit((string) $this->userAgent(), 250),
        ]);
    }
}
