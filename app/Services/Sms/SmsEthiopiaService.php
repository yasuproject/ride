<?php

namespace App\Services\Sms;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsEthiopiaService
{
    /**
     * Send a verification SMS using the SMSEthiopia HTTP API.
     *
     * Credentials come exclusively from the database-backed admin settings,
     * so the next send always reflects the latest configuration without any
     * client-side changes.
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
            'https://api.smsethiopia.com/api/send',
        );
        $senderId = $settings->sms_sender_id;

        // SMSEthiopia deployments differ in their accepted parameter names.
        // Sending the common aliases keeps the integration compatible while
        // the provider still only receives the configured credentials.
        $payload = array_filter([
            'token' => $apiKey,
            'key' => $apiKey,
            'to' => $phone,
            'phone' => $phone,
            'msg' => $message,
            'message' => $message,
            'text' => $message,
            'sender_id' => $senderId,
            'from' => $senderId,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asForm()
                ->post($endpoint, $payload);
        } catch (Throwable $exception) {
            Log::warning('SMSEthiopia request failed', [
                'exception' => $exception::class,
            ]);

            throw new SmsDeliveryException('Unable to reach the SMS provider.');
        }

        if (! $response->successful()) {
            Log::warning('SMSEthiopia send failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new SmsDeliveryException('SMS provider rejected the request.');
        }

        $body = $response->json();
        if (is_array($body)) {
            $status = $body['success'] ?? $body['status'] ?? null;
            $failed = $status === false
                || $status === 0
                || $status === '0'
                || $status === 'error'
                || $status === 'failed'
                || (isset($body['error']) && $body['error']);

            if ($failed) {
                Log::warning('SMSEthiopia reported an error', ['body' => $body]);

                throw new SmsDeliveryException('SMS provider rejected the request.');
            }
        }
    }
}
