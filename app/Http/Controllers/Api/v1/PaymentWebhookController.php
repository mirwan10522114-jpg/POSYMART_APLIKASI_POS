<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\PaymentProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    /**
     * Provider webhook notification handler (e.g. Midtrans / QRIS provider).
     * POST /api/v1/webhooks/payments/{provider}
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        // Example Midtrans payload structure or generic payload:
        // {
        //   "order_id": "ord-...",
        //   "transaction_status": "settlement",
        //   "transaction_id": "trx-midtrans-...",
        //   "gross_amount": "68000.00"
        // }

        $orderId = $request->input('order_id');
        $status = $request->input('transaction_status') ?? $request->input('status');
        $trxId = $request->input('transaction_id') ?? $request->input('id');
        $grossAmount = (int) round((float) ($request->input('gross_amount') ?? $request->input('amount') ?? 0));

        // If order_id not direct, look up by provider_reference
        if (!$orderId && $request->filled('provider_reference')) {
            $attempt = PaymentAttempt::where('provider_reference', $request->provider_reference)->first();
            $orderId = $attempt?->order_id;
        }

        if (!$orderId) {
            return response()->json(['error' => 'MISSING_ORDER_REFERENCE'], 400);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'ORDER_NOT_FOUND'], 404);
        }

        // Check success statuses: 'settlement' (Midtrans QRIS) or 'capture' (card) or 'paid'
        $isSuccess = in_array(strtolower($status), ['settlement', 'capture', 'paid', 'success']);

        if ($isSuccess) {
            $result = PaymentProviderService::processVerifiedPayment(
                $orderId,
                $trxId ?: ('trx-' . uniqid()),
                $grossAmount ?: $order->total_cents,
                null,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'status' => 'OK',
                'message' => 'Payment processed successfully',
                'result' => $result,
            ], 200);
        }

        // If expired or failed
        if (in_array(strtolower($status), ['expire', 'expired'])) {
            $order->update(['payment_status' => 'Expired']);
            $order->latestAttempt?->update(['status' => 'Expired']);
        } elseif (in_array(strtolower($status), ['cancel', 'cancelled', 'deny', 'failure'])) {
            $order->update(['payment_status' => 'Failed']);
            $order->latestAttempt?->update(['status' => 'Failed']);
        }

        return response()->json(['status' => 'OK', 'message' => "Order marked as {$status}"], 200);
    }
}
