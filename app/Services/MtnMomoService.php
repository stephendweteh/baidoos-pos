<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MtnMomoService
{
    public function requestToPay(string $externalId, float $amount, string $msisdn, string $customerName = ''): array
    {
        $referenceId = (string) Str::uuid();
        $normalizedMsisdn = $this->normalizeGhanaMsisdn($msisdn);

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $this->resolveCurrency(),
            'externalId' => $externalId,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $normalizedMsisdn,
            ],
            'payerMessage' => 'Payment for Baidoos POS invoice #' . $externalId,
            'payeeNote' => config('services.mtn_momo.merchant_name', 'Baidoos POS') . ' (' . config('services.mtn_momo.merchant_id', '') . ')',
        ];

        $response = Http::baseUrl($this->baseUrl())
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken(),
                'X-Reference-Id' => $referenceId,
                'X-Target-Environment' => config('services.mtn_momo.target_environment', 'sandbox'),
                'Ocp-Apim-Subscription-Key' => config('services.mtn_momo.subscription_key'),
                'Content-Type' => 'application/json',
            ])
            ->post('/collection/v1_0/requesttopay', $payload);

        if (!$response->successful() && $response->status() !== 202) {
            throw new \RuntimeException('MTN RequestToPay failed: ' . $response->body());
        }

        return [
            'reference_id' => $referenceId,
            'msisdn' => $normalizedMsisdn,
            'http_status' => $response->status(),
        ];
    }

    public function getRequestStatus(string $referenceId): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken(),
                'X-Target-Environment' => config('services.mtn_momo.target_environment', 'sandbox'),
                'Ocp-Apim-Subscription-Key' => config('services.mtn_momo.subscription_key'),
            ])
            ->get('/collection/v1_0/requesttopay/' . $referenceId);

        if (!$response->successful()) {
            throw new \RuntimeException('MTN status check failed: ' . $response->body());
        }

        return $response->json();
    }

    private function accessToken(): string
    {
        $cacheKey = 'mtn_momo_access_token';
        $cached = Cache::get($cacheKey);
        if (!empty($cached)) {
            return $cached;
        }

        $response = Http::baseUrl($this->baseUrl())
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => config('services.mtn_momo.subscription_key'),
                'Authorization' => 'Basic ' . base64_encode(config('services.mtn_momo.api_user') . ':' . config('services.mtn_momo.api_key')),
            ])
            ->post('/collection/token/');

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to fetch MTN access token: ' . $response->body());
        }

        $token = (string) data_get($response->json(), 'access_token', '');
        $expiresIn = (int) data_get($response->json(), 'expires_in', 3600);

        if ($token === '') {
            throw new \RuntimeException('MTN access token missing in response.');
        }

        Cache::put($cacheKey, $token, now()->addSeconds(max(60, $expiresIn - 60)));

        return $token;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.mtn_momo.base_url', 'https://sandbox.momodeveloper.mtn.com'), '/');
    }

    private function normalizeGhanaMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        if (strpos($digits, '233') === 0 && strlen($digits) === 12) {
            return $digits;
        }

        if (strpos($digits, '0') === 0 && strlen($digits) === 10) {
            return '233' . substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '233' . $digits;
        }

        throw new \InvalidArgumentException('Invalid Ghana phone number for MTN MoMo. Use 055xxxxxxx format.');
    }

    private function resolveCurrency(): string
    {
        $targetEnvironment = strtolower((string) config('services.mtn_momo.target_environment', 'sandbox'));
        if ($targetEnvironment === 'sandbox') {
            return 'EUR';
        }

        return (string) config('services.mtn_momo.currency', 'GHS');
    }
}
