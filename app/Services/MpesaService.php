<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaService
{
    public function accessToken(): string
    {
        $key = env('MPESA_CONSUMER_KEY');
        $secret = env('MPESA_CONSUMER_SECRET');
        if (!$key || !$secret) {
            throw new RuntimeException('M-Pesa consumer credentials are not configured.');
        }

        $base = env('MPESA_ENV', 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $response = Http::withBasicAuth($key, $secret)->get($base . '/oauth/v1/generate?grant_type=client_credentials');
        $response->throw();
        return (string) $response->json('access_token');
    }

    public function stkPush(string $phone, float $amount, string $accountReference, string $description = 'School fees')
    {
        $shortcode = env('MPESA_SHORTCODE');
        $passkey = env('MPESA_PASSKEY');
        if (!$shortcode || !$passkey) {
            throw new RuntimeException('M-Pesa shortcode/passkey are not configured.');
        }

        $phone = preg_replace('/^\+/', '', trim($phone));
        if (preg_match('/^07\d{8}$/', $phone)) $phone = '254' . substr($phone, 1);
        if (!preg_match('/^2547\d{8}$/', $phone)) {
            throw new RuntimeException('Use a valid Kenyan Safaricom number.');
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);
        $base = env('MPESA_ENV', 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        return Http::withToken($this->accessToken())->post($base . '/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
            'Amount' => (int) round($amount),
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => rtrim(env('APP_URL'), '/') . '/api/mpesa/callback',
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($description, 0, 13),
        ])->throw()->json();
    }
}
