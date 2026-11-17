<?php
/**
 * Paystack TEST API client for Shoppn Task 13.
 * No secret values are stored in this source file.
 * Configure PAYSTACK_SECRET_KEY in the PHP web-server environment later.
 * Use this only with a sk_test_ key; live payments intentionally blocked.
 */
final class PaystackClient
{
    private string $secretKey;
    private const BASE_URL = 'https://api.paystack.co';

    public function __construct()
    {
        $key = getenv('PAYSTACK_SECRET_KEY');
        if (!is_string($key) || !preg_match('/\Ask_test_[A-Za-z0-9_]+\z/D', $key)) {
            throw new RuntimeException('Paystack TEST secret key is not configured.');
        }
        $this->secretKey = $key;
    }

    /**
     * Start a hosted Paystack transaction and return access_code, reference,
     * and authorization_url to the calling server-side action.
     */
    public function initialize(string $email, int $amountPesewas, string $reference): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid checkout email.');
        }
        if ($amountPesewas < 1 || !preg_match('/\A[A-Za-z0-9._=-]{5,120}\z/D', $reference)) {
            throw new InvalidArgumentException('Invalid checkout amount or reference.');
        }

        $data = $this->request('POST', '/transaction/initialize', [
            'email' => $email,
            'amount' => (string) $amountPesewas,
            'currency' => 'GHS',
            'reference' => $reference,
        ]);
        if (($data['reference'] ?? null) !== $reference ||
            empty($data['access_code']) || empty($data['authorization_url'])) {
            throw new RuntimeException('Paystack initialization returned invalid data.');
        }
        return $data;
    }

    /**
     * Return Paystack's verified `data` object, not a browser-supplied result.
     * The caller must additionally verify data.status = success, currency,
     * amount, reference and customer before creating a paid order.
     */
    public function verify(string $reference): array
    {
        if (!preg_match('/\A[A-Za-z0-9._=-]{5,120}\z/D', $reference)) {
            throw new InvalidArgumentException('Invalid transaction reference.');
        }
        return $this->request('GET', '/transaction/verify/' . rawurlencode($reference));
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP cURL extension is required.');
        }
        $curl = curl_init(self::BASE_URL . $path);
        if ($curl === false) {
            throw new RuntimeException('Could not initialize Paystack request.');
        }
        try {
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->secretKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ];
            if ($method === 'POST') {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
            }
            curl_setopt_array($curl, $options);
            $body = curl_exec($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (!is_string($body) || $httpCode < 200 || $httpCode >= 300) {
                throw new RuntimeException('Paystack request was unsuccessful.');
            }
            $response = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($response) || ($response['status'] ?? null) !== true ||
                !isset($response['data']) || !is_array($response['data'])) {
                throw new RuntimeException('Paystack returned an unsuccessful or invalid response.');
            }
            return $response['data'];
        } finally {
            curl_close($curl);
        }
    }
}
