<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhonePeService
{
    protected string $merchantId;
    protected string $saltKey;
    protected int $saltIndex;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('services.phonepe.merchant_id');
        $this->saltKey = config('services.phonepe.salt_key');
        $this->saltIndex = config('services.phonepe.salt_index', 1);

        // UAT or PRODUCTION
        $this->baseUrl = config('services.phonepe.env') === 'PRODUCTION'
            ? 'https://api.phonepe.com/apis/hermes'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    /**
     * Initiate a payment for course enrollment.
     *
     * @param User $user
     * @param Course $course
     * @return array ['success' => bool, 'redirect_url' => string|null, 'merchant_transaction_id' => string]
     */
    public function initiatePayment(User $user, Course $course): array
    {
        $merchantTransactionId = 'LMS_' . Str::uuid();
        $amountInPaise = (int) ($course->price * 100);

        // Create payment record
        Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'merchant_transaction_id' => $merchantTransactionId,
            'amount' => $course->price,
            'status' => 'initiated',
        ]);

        $payload = [
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => 'USER_' . $user->id,
            'amount' => $amountInPaise,
            'redirectUrl' => config('app.url') . '/api/payments/callback',
            'redirectMode' => 'POST',
            'callbackUrl' => config('app.url') . '/api/payments/callback',
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

        $encodedPayload = base64_encode(json_encode($payload));
        $checksum = $this->generateChecksum($encodedPayload, '/pg/v1/pay');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
            ])->post($this->baseUrl . '/pg/v1/pay', [
                'request' => $encodedPayload,
            ]);

            $data = $response->json();

            if ($data['success'] ?? false) {
                $redirectUrl = $data['data']['instrumentResponse']['redirectInfo']['url'] ?? null;

                Payment::where('merchant_transaction_id', $merchantTransactionId)
                    ->update(['status' => 'pending']);

                return [
                    'success' => true,
                    'redirect_url' => $redirectUrl,
                    'merchant_transaction_id' => $merchantTransactionId,
                ];
            }

            Log::error('PhonePe payment initiation failed', ['response' => $data]);

            return [
                'success' => false,
                'redirect_url' => null,
                'merchant_transaction_id' => $merchantTransactionId,
            ];

        } catch (\Exception $e) {
            Log::error('PhonePe payment error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'redirect_url' => null,
                'merchant_transaction_id' => $merchantTransactionId,
            ];
        }
    }

    /**
     * Verify payment status and enroll student if successful.
     *
     * @param string $merchantTransactionId
     * @return array ['success' => bool, 'message' => string]
     */
    public function verifyPayment(string $merchantTransactionId): array
    {
        $payment = Payment::where('merchant_transaction_id', $merchantTransactionId)->first();

        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        $endpoint = "/pg/v1/status/{$this->merchantId}/{$merchantTransactionId}";
        $checksum = $this->generateChecksum('', $endpoint);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'X-MERCHANT-ID' => $this->merchantId,
            ])->get($this->baseUrl . $endpoint);

            $data = $response->json();

            if (($data['success'] ?? false) && ($data['code'] ?? '') === 'PAYMENT_SUCCESS') {
                $payment->update([
                    'status' => 'success',
                    'phonepe_transaction_id' => $data['data']['transactionId'] ?? null,
                    'payment_instrument' => $data['data']['paymentInstrument']['type'] ?? null,
                    'response_data' => $data,
                ]);

                // Auto-enroll student
                Enrollment::firstOrCreate(
                    [
                        'user_id' => $payment->user_id,
                        'course_id' => $payment->course_id,
                    ],
                    [
                        'status' => 'active',
                        'payment_id' => $merchantTransactionId,
                        'amount_paid' => $payment->amount,
                        'enrolled_at' => now(),
                    ]
                );

                return ['success' => true, 'message' => 'Payment successful, enrolled in course'];
            }

            $payment->update([
                'status' => 'failed',
                'response_data' => $data,
            ]);

            return ['success' => false, 'message' => 'Payment failed or pending'];

        } catch (\Exception $e) {
            Log::error('PhonePe verification error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Payment verification failed'];
        }
    }

    /**
     * Generate PhonePe checksum.
     *
     * @param string $payload
     * @param string $endpoint
     * @return string
     */
    protected function generateChecksum(string $payload, string $endpoint): string
    {
        $hashInput = $payload . $endpoint . $this->saltKey;
        $hash = hash('sha256', $hashInput);
        return $hash . '###' . $this->saltIndex;
    }
}
