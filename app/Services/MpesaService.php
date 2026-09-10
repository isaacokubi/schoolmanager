<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaService
{
    private function baseUrl(): string
    {
        return env('MPESA_ENV', 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function accessToken(): string
    {
        $key = trim((string) env('MPESA_CONSUMER_KEY'));
        $secret = trim((string) env('MPESA_CONSUMER_SECRET'));
        if (!$key || !$secret) {
            throw new RuntimeException('M-Pesa consumer credentials are not configured. Set MPESA_CONSUMER_KEY and MPESA_CONSUMER_SECRET.');
        }

        try {
            $response = Http::timeout(20)
                ->withBasicAuth($key, $secret)
                ->get($this->baseUrl() . '/oauth/v1/generate?grant_type=client_credentials');

            if (!$response->successful()) {
                throw new RuntimeException('M-Pesa OAuth failed (HTTP ' . $response->status() . '): ' . substr((string) $response->body(), 0, 500));
            }

            $token = (string) $response->json('access_token');
            if (!$token) {
                throw new RuntimeException('M-Pesa OAuth response did not contain an access token.');
            }

            return $token;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException('Unable to connect to the M-Pesa OAuth service: ' . $e->getMessage(), 0, $e);
        }
    }

    public function stkPush(string $phone, float $amount, string $accountReference, string $description = 'School fees')
    {
        $shortcode = trim((string) env('MPESA_SHORTCODE'));
        $passkey = trim((string) env('MPESA_PASSKEY'));
        if (!$shortcode || !$passkey) {
            throw new RuntimeException('M-Pesa shortcode/passkey are not configured. Set MPESA_SHORTCODE and MPESA_PASSKEY.');
        }

        $callbackUrl = trim((string) env('MPESA_CALLBACK_URL'));
        if (!$callbackUrl) {
            $callbackUrl = rtrim((string) env('APP_URL'), '/') . '/api/mpesa/callback';
        }
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('M-Pesa callback URL is invalid. Set MPESA_CALLBACK_URL to a valid public URL.');
        }
        if (env('MPESA_ENV', 'sandbox') === 'production' && stripos($callbackUrl, 'https://') !== 0) {
            throw new RuntimeException('Production M-Pesa requires an HTTPS callback URL.');
        }

        $phone = preg_replace('/^\+/', '', trim($phone));
        if (preg_match('/^07\d{8}$/', $phone)) $phone = '254' . substr($phone, 1);
        elseif (preg_match('/^7\d{8}$/', $phone)) $phone = '254' . $phone;
        if (!preg_match('/^2547\d{8}$/', $phone)) {
            throw new RuntimeException('Use a valid Kenyan Safaricom number.');
        }
        if ($amount < 1) {
            throw new RuntimeException('M-Pesa amount must be at least KES 1.');
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        try {
            $response = Http::timeout(30)
                ->withToken($this->accessToken())
                ->post($this->baseUrl() . '/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
                    'Amount' => (int) round($amount),
                    'PartyA' => $phone,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $callbackUrl,
                    'AccountReference' => substr($accountReference, 0, 12),
                    'TransactionDesc' => substr($description, 0, 13),
                ]);

            if (!$response->successful()) {
                throw new RuntimeException('M-Pesa STK request failed (HTTP ' . $response->status() . '): ' . substr((string) $response->body(), 0, 800));
            }

            $payload = $response->json();
            if (!empty($payload['ResponseCode']) && (string) $payload['ResponseCode'] !== '0') {
                throw new RuntimeException('M-Pesa rejected the STK request: ' . ($payload['ResponseDescription'] ?? $payload['CustomerMessage'] ?? 'Unknown M-Pesa error.'));
            }
            if (empty($payload['CheckoutRequestID'])) {
                throw new RuntimeException('M-Pesa did not return a CheckoutRequestID: ' . substr((string) $response->body(), 0, 800));
            }

            return $payload;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException('Unable to connect to the M-Pesa STK service: ' . $e->getMessage(), 0, $e);
        }
    }
}
