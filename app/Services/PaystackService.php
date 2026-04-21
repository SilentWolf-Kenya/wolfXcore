<?php

namespace Pterodactyl\Services;

use Illuminate\Support\Facades\Http;
use Pterodactyl\Models\Setting;

class PaystackService
{
    private string $secretKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = Setting::where('key', 'settings::payment:paystack_secret')->value('value') ?? '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    public function getPublicKey(): string
    {
        return Setting::where('key', 'settings::payment:paystack_public')->value('value') ?? '';
    }

    /**
     * Trigger an STK push / mobile money charge directly via Paystack's /charge endpoint.
     * No redirect — the STK push goes straight to the customer's phone.
     */
    public function chargeMobileMoney(
        string $email,
        float  $amount,
        string $currency,
        string $reference,
        string $phone,
        string $provider   // 'mpesa' or 'airtel'
    ): array {
        $payload = [
            'email'        => $email,
            'amount'       => (int) round($amount * 100),
            'currency'     => $currency,
            'reference'    => $reference,
            'mobile_money' => [
                'phone'    => $phone,
                'provider' => $provider,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/charge", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Mobile charge failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Poll the status of a charge/transaction by reference.
     * Works for both card (inline) and mobile money charges.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if (!$response->successful()) {
            throw new \RuntimeException('Paystack verification failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * List transactions from Paystack dashboard.
     * Returns up to $perPage records for the given $page.
     * Optional $status filter: 'success', 'failed', 'abandoned'.
     */
    public function listTransactions(int $perPage = 50, int $page = 1, string $status = ''): array
    {
        $params = ['perPage' => $perPage, 'page' => $page];
        if ($status !== '') {
            $params['status'] = $status;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->get("{$this->baseUrl}/transaction", $params);

        if (!$response->successful()) {
            throw new \RuntimeException('Paystack transaction list failed: ' . $response->body());
        }

        return $response->json();
    }
}
