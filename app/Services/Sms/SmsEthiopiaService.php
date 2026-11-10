<?php

namespace App\Services\Sms;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsEthiopiaService
{
    /**
     * Send a verification SMS using the SMSEthiopia HTTP API (v2).
     *
     * Docs: POST https://smsethiopia.com/api/v2/sms/send
     * Auth: KEY header. Body: {"msisdn":"2519XXXXXXXX","text":"..."}.
     * A successful response includes {"sent": true, ...}.
     *
     * Credentials come exclusively from the database-backed admin settings,
     * so the next send always reflects the latest configuration.
     *
     * @throws SmsDeliveryException
     */
    public function send(string $phone, string $message, AppSetting $settings): void
    {
        $apiKey = trim((string) $settings->sms_api_key);
        if ($apiKey === '') {
            throw new SmsDeliveryException('SMS provider is not configured.');
        }

        $endpoint = (string) config(
            'services.smsethiopia.url',
            'https://smsethiopia.com/api/v2/sms/send',
        );

        $msisdn = $this->formatMsisdn($phone);
        $payload = [
            'msisdn' => $msisdn,
            'text' => $message,
        ];

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['KEY' => $apiKey])
                ->post($endpoint, $payload);
        } catch (Throwable $exception) {
            Log::channel('security')->warning('SMSEthiopia request failed', [
                'exception' => $exception::class,
            ]);

            throw new SmsDeliveryException('Unable to reach the SMS provider.');
        }

        $body = $response->json();

        if (! $response->successful()) {
            Log::channel('security')->warning('SMSEthiopia send failed', [
                'status' => $response->status(),
                'error' => is_array($body) ? ($body['error_message'] ?? $body) : null,
            ]);

            throw new SmsDeliveryException($this->userMessageForFailure($response->status(), $body));
        }

        if (! is_array($body) || ($body['sent'] ?? false) !== true) {
            Log::channel('security')->warning('SMSEthiopia unexpected response', [
                'body' => $body,
            ]);

            throw new SmsDeliveryException('SMS provider rejected the request.');
        }
    }

    /**
     * Convert to provider format: 12 digits starting with 2519 (no plus).
     * Accepts E.164 (+251...) or local/national forms.
     */
    private function formatMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            $digits = '251'.$digits;
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '09')) {
            $digits = '251'.substr($digits, 1);
        } elseif (str_starts_with($digits, '251')) {
            // already in country format
        } elseif (strlen($digits) > 0 && ! str_starts_with($digits, '251')) {
            $digits = '251'.ltrim($digits, '0');
        }

        return $digits;
    }

    /**
     * Map provider/business errors to a safe client-facing message.
     */
    private function userMessageForFailure(int $status, mixed $body): string
    {
        $error = is_array($body) ? (string) ($body['error_message'] ?? '') : '';

        if (str_contains($error, '10007')) {
            return 'SMS could not be delivered to this number (recipient not allowed for this campaign).';
        }
        if (str_contains($error, '10005')) {
            return 'SMS service is temporarily unavailable. Please try again later.';
        }
        if (str_contains($error, '10006') || str_contains($error, '10002')) {
            return 'SMS service is not ready. Please contact support.';
        }
        if (str_contains($error, '10000') || isset($body['msisdn'])) {
            return 'Please provide a valid Ethiopian mobile number.';
        }
        if ($status === 401 || $status === 403) {
            return 'SMS service is not configured correctly. Please contact support.';
        }

        return 'SMS provider rejected the request.';
    }
}
