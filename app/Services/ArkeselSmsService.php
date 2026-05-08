<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArkeselSmsService
{
    protected string $apiKey;
    protected string $senderId;
    protected string $baseUrl = 'https://sms.arkesel.com/api/v2/sms/send';

    public function __construct()
    {
        $this->apiKey   = config('services.arkesel.api_key', '');
        $this->senderId = config('services.arkesel.sender_id', 'BaidoosPOS');
    }

    /**
     * Send an SMS message to a single recipient.
     *
     * @param  string  $phone  E.g. "0244123456" or "233244123456"
     * @param  string  $message
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('Arkesel SMS: API key not configured.');
            return false;
        }

        // Normalise phone to international format (Ghana: 233xxxxxxxxx)
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '233' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '233')) {
            $phone = '233' . $phone;
        }

        try {
            $response = Http::withHeaders(['api-key' => $this->apiKey])
                ->post($this->baseUrl, [
                    'sender'     => $this->senderId,
                    'message'    => $message,
                    'recipients' => [$phone],
                ]);

            if ($response->successful() && ($response->json('status') === 'success')) {
                return true;
            }

            Log::warning('Arkesel SMS failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Arkesel SMS exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build and send a payment receipt SMS.
     */
    public function sendReceiptSms(string $phone, array $data): bool
    {
        $items = collect($data['items'])->map(fn ($i) => "{$i['item_name']} x{$i['quantity']} = GHS {$i['subtotal']}")->implode(', ');

        $message  = "Hi {$data['customer_name']}, your payment receipt from {$data['branch_name']}:\n";
        $message .= "Receipt #: {$data['sale_id']}\n";
        $message .= "Items: {$items}\n";
        if ($data['discount'] > 0) {
            $message .= "Discount: -GHS {$data['discount']}\n";
        }
        $message .= "TOTAL: GHS {$data['total']}\n";
        $message .= "Payment: " . strtoupper($data['payment_method']) . "\n";
        $message .= "Thank you!";

        return $this->send($phone, $message);
    }
}
