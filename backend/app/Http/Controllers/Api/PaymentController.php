<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PhonePeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PhonePeService $phonePeService;

    public function __construct(PhonePeService $phonePeService)
    {
        $this->phonePeService = $phonePeService;
    }

    /**
     * PhonePe payment callback (redirect after payment).
     *
     * POST /api/payments/callback
     */
    public function callback(Request $request): JsonResponse
    {
        $merchantTransactionId = $request->input('transactionId')
            ?? $request->input('merchantTransactionId');

        if (!$merchantTransactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID not found',
            ], 400);
        }

        $result = $this->phonePeService->verifyPayment($merchantTransactionId);

        return response()->json($result);
    }

    /**
     * Check payment status (called from Flutter to poll status).
     *
     * GET /api/payments/{merchantTransactionId}/status
     */
    public function status(string $merchantTransactionId): JsonResponse
    {
        $result = $this->phonePeService->verifyPayment($merchantTransactionId);

        return response()->json($result);
    }
}
