<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\OtpService;
use App\Services\OtpThrottledException;
use App\Services\Sms\SmsDeliveryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function requestOtp(Request $request, OtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        if (! AppSetting::current()->otp_login_enabled) {
            return $this->json([
                'success' => false,
                'message' => 'OTP login is currently unavailable.',
                'data' => ['otp_login_enabled' => false],
            ], 403);
        }

        try {
            $result = $otp->request($validated['phone']);
        } catch (SmsDeliveryException $exception) {
            $this->logSecurity('OTP delivery failed', $request, $validated['phone']);

            return $this->json([
                'success' => false,
                'message' => 'Unable to send the verification code. Please try again later.',
            ], 503);
        } catch (OtpThrottledException $exception) {
            $this->logSecurity('OTP request throttled', $request, $validated['phone']);

            return $this->throttledResponse($exception);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('OTP request failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->logSecurity('OTP requested', $request, $result['phone']);

        return $this->json([
            'success' => true,
            'message' => 'Verification code sent.',
            'data' => $result,
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string', 'digits_between:4,8'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);

        try {
            $result = $otp->verify(
                $validated['phone'],
                $validated['code'],
                $validated['name'] ?? null,
                $validated['email'] ?? null,
            );
        } catch (OtpThrottledException $exception) {
            $this->logSecurity('OTP verification throttled', $request, $validated['phone']);

            return $this->throttledResponse($exception);
        } catch (ValidationException $exception) {
            $this->logSecurity('OTP verification failed', $request, $validated['phone']);

            throw $exception;
        }

        $this->logSecurity('OTP verified', $request, $result['user']->phone);

        return $this->json([
            'success' => true,
            'message' => 'Authenticated successfully.',
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => $this->userPayload($result['user']),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        $this->logSecurity('Token revoked', $request, $request->user()?->phone);

        return $this->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /** @return array<string, mixed> */
    private function userPayload(\App\Models\User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ];
    }

    private function throttledResponse(OtpThrottledException $exception): JsonResponse
    {
        $response = $this->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'retry_after_seconds' => $exception->retryAfterSeconds,
        ], 429);

        if ($exception->retryAfterSeconds !== null) {
            $response->header('Retry-After', (string) $exception->retryAfterSeconds);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function json(array $payload, int $status = 200): JsonResponse
    {
        // Auth payloads can contain bearer tokens; never let a proxy cache them.
        return response()
            ->json($payload, $status)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function logSecurity(string $event, Request $request, ?string $phone): void
    {
        Log::channel('security')->info($event, [
            'phone' => $phone === null ? null : Str::mask($phone, '*', 4),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250),
        ]);
    }
}
