<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnitAllocation;
use App\Models\Setting;
use App\Models\TableNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentProviderService
{
    /**
     * Create a payment attempt for an order (QRIS).
     */
    public static function createAttempt(Order $order, string $method = 'QRIS'): PaymentAttempt
    {
        // Cancel/Expire any existing pending attempts for this order
        PaymentAttempt::where('order_id', $order->id)
            ->where('status', 'Pending')
            ->update(['status' => 'Cancelled']);

        $nextAttemptNum = (PaymentAttempt::where('order_id', $order->id)->max('attempt_number') ?? 0) + 1;
        $providerRef = "QRIS-POSY-{$order->id}-{$nextAttemptNum}";
        $expiresAt = now()->addMinutes(15);

        // Standard QRIS sample payload format
        $qrPayload = sprintf(
            "00020101021226580016ID.CO.POSYMART.WWW011893600999000000000052045812530336054%08d5802ID5912POSYMART6007JAKARTA62070703A016304ABCD",
            $order->total_cents
        );

        $attempt = PaymentAttempt::create([
            'id' => 'att-' . Str::random(12),
            'order_id' => $order->id,
            'attempt_number' => $nextAttemptNum,
            'provider' => 'qris_simulated',
            'provider_reference' => $providerRef,
            'payment_method' => $method,
            'amount_cents' => $order->total_cents,
            'status' => 'Pending',
            'qr_string' => $qrPayload,
            'payment_url' => url("/m/pay/{$order->id}/{$providerRef}"),
            'expires_at' => $expiresAt,
        ]);

        $order->update([
            'payment_status' => 'Pending',
            'fulfillment_status' => 'NotQueued',
        ]);

        return $attempt;
    }

    /**
     * Process a verified payment from provider (webhook or simulated verification).
     * Strictly idempotent: single sale, single stock decrement, single notification.
     */
    public static function processVerifiedPayment(
        string $orderId,
        string $providerTransactionId,
        ?int $amountCents = null,
        ?string $attemptId = null,
        ?array $rawPayload = null
    ): array {
        return DB::transaction(function () use ($orderId, $providerTransactionId, $amountCents, $attemptId, $rawPayload) {
            $order = Order::with('items')->lockForUpdate()->find($orderId);
            if (!$order) {
                return ['success' => false, 'error' => 'ORDER_NOT_FOUND'];
            }

            // Check if provider_transaction_id already processed (Dedup invariant)
            $existingReceipt = PaymentReceipt::where('provider_transaction_id', $providerTransactionId)->first();
            if ($existingReceipt) {
                return [
                    'success' => true,
                    'message' => 'Payment already processed (idempotent)',
                    'order' => $order,
                    'receipt' => $existingReceipt,
                ];
            }

            // If order already paid, record receipt but don't duplicate sale
            if ($order->payment_status === 'Paid') {
                $receipt = PaymentReceipt::create([
                    'id' => 'rcpt-' . Str::random(12),
                    'order_id' => $order->id,
                    'payment_attempt_id' => $attemptId ?: ($order->latestAttempt?->id ?: 'att-unknown'),
                    'provider_transaction_id' => $providerTransactionId,
                    'amount_cents' => $amountCents ?: $order->total_cents,
                    'payment_method' => 'QRIS',
                    'verified_at' => now(),
                    'raw_payload' => $rawPayload ?: ['status' => 'settlement', 'duplicate' => true],
                ]);

                return [
                    'success' => true,
                    'message' => 'Additional payment receipt recorded (excess handling)',
                    'order' => $order,
                    'receipt' => $receipt,
                ];
            }

            $attempt = $attemptId ? PaymentAttempt::find($attemptId) : $order->latestAttempt;
            if ($attempt) {
                $attempt->update([
                    'status' => 'Paid',
                    'verified_at' => now(),
                ]);
            }

            // 1. Create PaymentReceipt
            $receipt = PaymentReceipt::create([
                'id' => 'rcpt-' . Str::random(12),
                'order_id' => $order->id,
                'payment_attempt_id' => $attempt ? $attempt->id : 'att-' . Str::random(8),
                'provider_transaction_id' => $providerTransactionId,
                'amount_cents' => $amountCents ?: $order->total_cents,
                'payment_method' => 'QRIS',
                'verified_at' => now(),
                'raw_payload' => $rawPayload ?: ['status' => 'settlement'],
            ]);

            // 2. Generate exactly one Sale & SaleItems
            $settings = Setting::instance();
            $itemsForAlloc = [];
            foreach ($order->items as $it) {
                $itemsForAlloc[] = [
                    'product_id' => $it->product_id,
                    'name' => $it->name,
                    'quantity' => $it->quantity,
                    'unit_price_cents' => $it->unit_price_cents,
                ];
            }

            $totals = MoneyAllocator::computeTotals($itemsForAlloc, $order->discount_cents, $settings->tax_enabled, $settings->tax_rate_bps);
            $allocs = MoneyAllocator::allocateToItems($itemsForAlloc, $order->discount_cents, $totals['tax_cents']);

            $latestSale = Sale::max('id');
            $nextSeq = 1144;
            if ($latestSale && preg_match('/TX-(\d+)/', $latestSale, $m)) {
                $nextSeq = max($nextSeq, (int) $m[1] + 1);
            }
            $txCode = "TX-{$nextSeq}";

            $sale = Sale::create([
                'id' => $txCode,
                'tx_code' => $txCode,
                'customer_id' => $order->customer_id,
                'customer_name_snapshot' => $order->customer_name ?: 'Walk-in Customer',
                'cashier_id' => 'sys-qris-provider',
                'cashier_name_snapshot' => 'QRIS Provider Server',
                'status' => 'Completed',
                'payment_method' => 'Digital Wallet',
                'subtotal_cents' => $totals['gross_subtotal_cents'],
                'discount_cents' => $order->discount_cents,
                'taxable_subtotal_cents' => $totals['taxable_subtotal_cents'],
                'tax_cents' => $totals['tax_cents'],
                'tax_rate_bps_snapshot' => $settings->tax_rate_bps,
                'tax_name_snapshot' => $settings->tax_name,
                'tax_enabled_snapshot' => $settings->tax_enabled,
                'total_cents' => $order->total_cents ?? ($totals['total_cents'] + (int)($order->service_fee_cents ?? 0)),
                'paid_cents' => $order->total_cents ?? ($totals['total_cents'] + (int)($order->service_fee_cents ?? 0)),
                'source_order_id' => $order->id,
            ]);

            // 3. Insert SaleItems and decrement inventory once
            $insufficientStock = false;
            foreach ($order->items as $i => $it) {
                $alloc = $allocs[$i] ?? [
                    'gross_subtotal_cents' => $it->unit_price_cents * $it->quantity,
                    'allocated_discount_cents' => 0,
                    'net_subtotal_cents' => $it->unit_price_cents * $it->quantity,
                    'allocated_tax_cents' => 0,
                    'total_cents' => $it->unit_price_cents * $it->quantity,
                ];

                $saleItem = SaleItem::create([
                    'id' => "si-{$sale->id}-" . ($i + 1),
                    'sale_id' => $sale->id,
                    'product_id' => $it->product_id,
                    'name' => $it->name,
                    'quantity' => $it->quantity,
                    'unit_price_cents' => $it->unit_price_cents,
                    'gross_subtotal_cents' => $alloc['gross_subtotal_cents'],
                    'allocated_discount_cents' => $alloc['allocated_discount_cents'],
                    'net_subtotal_cents' => $alloc['net_subtotal_cents'],
                    'allocated_tax_cents' => $alloc['allocated_tax_cents'],
                    'total_cents' => $alloc['total_cents'],
                    'sort_order' => $i,
                ]);

                $unitAllocs = MoneyAllocator::buildUnitAllocations(
                    $alloc['net_subtotal_cents'],
                    $alloc['allocated_tax_cents'],
                    $it->quantity
                );

                foreach ($unitAllocs as $u) {
                    SaleItemUnitAllocation::create([
                        'sale_item_id' => $saleItem->id,
                        'unit_index' => $u['unit_index'],
                        'net_cents' => $u['net_cents'],
                        'tax_cents' => $u['tax_cents'],
                        'total_cents' => $u['total_cents'],
                    ]);
                }

                if ($settings->track_inventory && $it->product_id) {
                    $p = Product::find($it->product_id);
                    if ($p && $p->track_stock) {
                        if ($p->stock_qty < $it->quantity) {
                            $insufficientStock = true;
                        }
                        $p->decrement('stock_qty', $it->quantity);
                        InventoryMovement::create([
                            'movement_id' => (string) Str::uuid(),
                            'product_id' => $p->id,
                            'type' => 'SaleDecrement',
                            'delta_qty' => -$it->quantity,
                            'sale_id' => $sale->id,
                            'reason' => "Pemesanan Meja {$order->table_label_snapshot} ({$order->order_code})",
                            'created_by' => 'sys-qris-provider',
                        ]);
                    }
                }
            }

            // 4. Update order status: If stock was insufficient, set ReviewRequired, else New!
            $nextFulfillment = $insufficientStock ? 'ReviewRequired' : 'New';

            $order->update([
                'payment_status' => 'Paid',
                'fulfillment_status' => $nextFulfillment,
                'status' => $nextFulfillment,
                'converted_sale_id' => $sale->id,
            ]);

            // 5. Create persistent TableNotification for staff dashboard
            $tableLabel = $order->table_label_snapshot ?: 'Meja';
            $custName = $order->customer_name ?: 'Pelanggan';
            $notifTitle = $nextFulfillment === 'ReviewRequired'
                ? "⚠️ Perlu Ditinjau: {$tableLabel} - {$order->order_code}"
                : "💳 Pembayaran Diterima: {$tableLabel} ({$custName})";
            $notifMsg = "Pesanan {$order->order_code} senilai Rp " . number_format($order->total_cents, 0, ',', '.') . " telah dibayar via QRIS.";

            TableNotification::create([
                'id' => 'notif-' . Str::random(12),
                'event_id' => 'evt-paid-' . $order->id . '-' . Str::random(6),
                'type' => $nextFulfillment === 'ReviewRequired' ? 'ReviewRequired' : 'payment',
                'order_id' => $order->id,
                'title' => $notifTitle,
                'message' => $notifMsg,
                'data' => [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'table_label' => $tableLabel,
                    'customer_name' => $custName,
                    'total_cents' => $order->total_cents,
                    'items_count' => $order->items->count(),
                    'url' => '/orders',
                    'type' => 'payment',
                ],
                'is_read' => false,
            ]);

            AuditService::log('table_order.paid', 'order', $order->id, null, [
                'order_code' => $order->order_code,
                'table' => $tableLabel,
                'sale_id' => $sale->id,
                'total_cents' => $order->total_cents,
            ]);

            return [
                'success' => true,
                'order' => $order->fresh()->load('items'),
                'receipt' => $receipt,
                'sale' => $sale,
            ];
        });
    }
}
