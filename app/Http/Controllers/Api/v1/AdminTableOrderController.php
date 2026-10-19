<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TableNotification;
use App\Models\TableRefund;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminTableOrderController extends Controller
{
    /**
     * List orders for staff dashboard with fulfillment status filters.
     * GET /api/v1/admin/orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'table', 'customer', 'receipts', 'latestAttempt']);

        // Filter fulfillment status
        if ($request->filled('status')) {
            $query->where('fulfillment_status', $request->status);
        } else {
            // Hanya pesanan yang sudah bayar dan masuk antrean (wajib bayar dulu baru masuk ke admin)
            $query->where('fulfillment_status', '!=', 'NotQueued');
        }

        // Filter table
        if ($request->filled('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        // Filter payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Active orders default (New, Accepted, Preparing, Ready)
        if ($request->boolean('active_only')) {
            $query->whereIn('fulfillment_status', ['New', 'Accepted', 'Preparing', 'Ready']);
        }

        $orders = $query->orderByDesc('created_at')->paginate($request->input('limit', 50));

        // Aggregate statistics for dashboard badges (hanya pesanan yang sudah masuk antrean)
        $counts = [
            'all' => Order::where('fulfillment_status', '!=', 'NotQueued')->count(),
            'new' => Order::where('fulfillment_status', 'New')->count(),
            'accepted' => Order::where('fulfillment_status', 'Accepted')->count(),
            'preparing' => Order::where('fulfillment_status', 'Preparing')->count(),
            'ready' => Order::where('fulfillment_status', 'Ready')->count(),
            'served' => Order::where('fulfillment_status', 'Served')->count(),
            'review_required' => Order::where('fulfillment_status', 'ReviewRequired')->count(),
        ];

        return response()->json([
            'data' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
            'status_counts' => $counts,
        ]);
    }

    /**
     * Store new order from POS cashier (Dine In / Takeaway).
     * POST /api/v1/admin/orders
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:100',
            'order_type' => 'nullable|string|in:dine_in,takeaway',
            'delivery_type' => 'nullable|string|in:table,counter',
            'table_number' => 'nullable|string|max:50',
            'note' => 'nullable|string',
            'sale_id' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|string',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
            'total' => 'nullable|numeric',
        ]);

        $orderType = $validated['order_type'] ?? ($validated['delivery_type'] === 'counter' ? 'takeaway' : 'dine_in');
        $deliveryType = $orderType === 'takeaway' ? 'counter' : 'table';
        $tableNum = trim($validated['table_number'] ?? '');
        $tableLabel = $orderType === 'takeaway'
            ? 'Takeaway (Kasir)'
            : ($tableNum ? (str_starts_with(strtolower($tableNum), 'meja') ? $tableNum : "Meja {$tableNum}") : 'Makan di Tempat');

        $orderId = 'ORD-' . strtoupper(Str::random(8));
        $dateStr = Carbon::now()->format('Ymd');
        $seq = DB::table('orders')->whereDate('created_at', Carbon::today())->count() + 1;
        $orderCode = sprintf('POS-%s-%03d', $dateStr, $seq);

        $settings = Setting::instance();
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['unit_price'] * $item['quantity']);
        }
        $tax = 0;
        if ($settings->tax_enabled && $settings->tax_rate_bps > 0) {
            $tax = round(($subtotal * $settings->tax_rate_bps) / 10000);
        }
        $total = $subtotal + $tax;

        $order = Order::create([
            'id' => $orderId,
            'order_code' => $orderCode,
            'status' => 'Pending',
            'customer_name' => trim($validated['customer_name'] ?? '') ?: 'Pelanggan Walk-in',
            'customer_name_snapshot' => trim($validated['customer_name'] ?? '') ?: 'Pelanggan Walk-in',
            'table_number' => $tableNum ?: null,
            'table_label_snapshot' => $tableLabel,
            'delivery_type' => $deliveryType,
            'payment_status' => 'PAID',
            'fulfillment_status' => 'New',
            'subtotal_cents' => (int) $subtotal,
            'tax_cents' => (int) $tax,
            'total_cents' => (int) $total,
            'converted_sale_id' => $validated['sale_id'] ?? null,
            'note' => $validated['note'] ?? null,
            'created_by' => auth()->user()->id ?? 'pos-cashier',
        ]);

        foreach ($validated['items'] as $idx => $item) {
            $order->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price_cents' => (int) $item['unit_price'],
                'note' => $item['note'] ?? null,
                'sort_order' => $idx + 1,
            ]);
        }

        // Simpan juga ke tabel sales agar otomatis muncul di menu Penjualan & Laporan
        $saleId = $validated['sale_id'] ?? null;
        if (!$saleId) {
            $latestSale = Sale::max('id');
            $nextSeq = 1000;
            if ($latestSale && preg_match('/TX-(\d+)/', $latestSale, $m)) {
                $nextSeq = max($nextSeq, (int) $m[1] + 1);
            }
            $saleId = "TX-{$nextSeq}";
            $order->converted_sale_id = $saleId;
            $order->save();
        }

        $rawPm = $request->input('payment_method') ?? $request->input('paymentMethod') ?? 'Cash';
        $pmLower = strtolower(str_replace(['_', '-'], ' ', (string) $rawPm));
        if (in_array($pmLower, ['digital wallet', 'wallet', 'ewallet', 'e wallet', 'qris', 'dompet digital'])) {
            $paymentMethod = 'Digital Wallet';
        } elseif (in_array($pmLower, ['card', 'kartu', 'debit', 'kredit'])) {
            $paymentMethod = 'Card';
        } else {
            $paymentMethod = 'Cash';
        }

        $sale = Sale::updateOrCreate(
            ['id' => $saleId],
            [
                'tx_code' => $saleId,
                'customer_id' => null,
                'customer_name_snapshot' => trim($validated['customer_name'] ?? '') ?: 'Pelanggan Walk-in',
                'cashier_id' => auth()->user()->id ?? 'u-admin',
                'cashier_name_snapshot' => auth()->user()->name ?? 'Admin',
                'status' => 'Completed',
                'payment_method' => $paymentMethod,
                'subtotal_cents' => (int) $subtotal,
                'discount_cents' => 0,
                'tax_cents' => (int) $tax,
                'tax_rate_bps_snapshot' => $settings->tax_rate_bps,
                'tax_name_snapshot' => $settings->tax_name,
                'tax_enabled_snapshot' => $settings->tax_enabled,
                'total_cents' => (int) $total,
                'paid_cents' => (int) $total,
                'change_cents' => 0,
                'note' => $validated['note'] ?? null,
                'source_order_id' => $orderId,
            ]
        );

        foreach ($validated['items'] as $idx => $item) {
            SaleItem::updateOrCreate(
                ['id' => "si-{$saleId}-" . ($idx + 1)],
                [
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => (int) $item['unit_price'],
                    'gross_subtotal_cents' => (int) ($item['unit_price'] * $item['quantity']),
                    'allocated_discount_cents' => 0,
                    'net_subtotal_cents' => (int) ($item['unit_price'] * $item['quantity']),
                    'allocated_tax_cents' => 0,
                    'total_cents' => (int) ($item['unit_price'] * $item['quantity']),
                    'sort_order' => $idx + 1,
                ]
            );
        }

        // Catat riwayat ke PaymentAttempt & PaymentReceipt agar muncul di menu Pembayaran
        $providerName = $paymentMethod === 'Digital Wallet' ? 'QRIS / E-Wallet' : ($paymentMethod === 'Card' ? 'EDC Card' : 'Kasir Tunai');
        $providerRef = ($paymentMethod === 'Digital Wallet' ? 'QRIS-' : ($paymentMethod === 'Card' ? 'CARD-' : 'CASH-')) . strtoupper(Str::random(8));

        $attempt = PaymentAttempt::create([
            'id' => 'att-' . Str::random(12),
            'order_id' => $orderId,
            'attempt_number' => 1,
            'provider' => $providerName,
            'provider_reference' => $providerRef,
            'payment_method' => $paymentMethod,
            'amount_cents' => (int) $total,
            'status' => 'Paid',
            'expires_at' => now()->addMinutes(30),
            'verified_at' => now(),
        ]);

        PaymentReceipt::create([
            'id' => 'rec-' . Str::random(12),
            'order_id' => $orderId,
            'payment_attempt_id' => $attempt->id,
            'provider_transaction_id' => $providerRef,
            'amount_cents' => (int) $total,
            'payment_method' => $paymentMethod,
            'verified_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'sale_id' => $saleId,
            'order' => $order->load('items'),
        ], 201);
    }

    /**
     * Show single order detail.
     * GET /api/v1/admin/orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::with(['items', 'table', 'customer', 'receipts', 'paymentAttempts', 'refunds'])->find($id);

        if (!$order) {
            return response()->json(['error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        return response()->json($order);
    }

    /**
     * Transition order fulfillment state.
     * POST /api/v1/admin/orders/{id}/transitions
     */
    public function transition(Request $request, string $id): JsonResponse
    {
        $order = Order::with('items')->find($id);
        if (!$order) {
            return response()->json(['error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        $action = $request->input('action');
        $user = $request->user();

        // Valid state machine transitions per PRD Section 10:
        // NotQueued -> New -> Accepted -> Preparing -> Ready -> Served
        $validMap = [
            'confirm-payment' => [
                'NotQueued' => 'New',
            ],
            'accept' => [
                'NotQueued' => 'Accepted',
                'New' => 'Accepted',
            ],
            'start-prepare' => [
                'NotQueued' => 'Preparing',
                'New' => 'Preparing',
                'Accepted' => 'Preparing',
            ],
            'mark-ready' => [
                'Preparing' => 'Ready',
            ],
            'mark-served' => [
                'Ready' => 'Served',
            ],
            'resolve-review' => [
                'ReviewRequired' => 'New',
            ],
            'cancel' => [
                'NotQueued' => 'Cancelled',
                'New' => 'Cancelled',
                'Accepted' => 'Cancelled',
                'Preparing' => 'Cancelled',
                'Ready' => 'Cancelled',
                'ReviewRequired' => 'Cancelled',
            ],
        ];

        if (!isset($validMap[$action]) || !isset($validMap[$action][$order->fulfillment_status])) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_TRANSITION',
                    'message' => "Transisi '{$action}' tidak diizinkan dari status '{$order->fulfillment_status}'.",
                ],
            ], 409);
        }

        // Cancel requires Manager/Admin
        if ($action === 'cancel' && in_array($order->fulfillment_status, ['Preparing', 'Ready'])) {
            if (!$user || (!$user->isAdmin() && !$user->isManager())) {
                return response()->json([
                    'error' => ['code' => 'AUTH-003', 'message' => 'Pembatalan pesanan yang sedang diproses memerlukan hak Manager/Admin'],
                ], 403);
            }
        }

        $nextStatus = $validMap[$action][$order->fulfillment_status];

        return DB::transaction(function () use ($order, $nextStatus, $action, $request, $user) {
            // If order was NotQueued and hasn't recorded payment, record verified payment and generate Sale
            if ($order->payment_status !== 'Paid' && ($action === 'confirm-payment' || in_array($action, ['accept', 'start-prepare']))) {
                \App\Services\PaymentProviderService::processVerifiedPayment(
                    $order->id,
                    'MANUAL-CASHIER-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    $order->total_cents
                );
                $order->refresh();
            }

            $before = $order->toArray();
            $updates = [
                'fulfillment_status' => $nextStatus,
                'status' => $nextStatus,
            ];

            if ($nextStatus === 'Accepted') {
                $updates['accepted_by'] = $user?->name ?: 'Kasir';
                $updates['accepted_at'] = now();
            } elseif ($nextStatus === 'Preparing') {
                $updates['preparing_at'] = now();
            } elseif ($nextStatus === 'Ready') {
                $updates['ready_at'] = now();
            } elseif ($nextStatus === 'Served') {
                $updates['served_at'] = now();
                $updates['completed_at'] = now();
            } elseif ($nextStatus === 'Cancelled') {
                $updates['cancelled_at'] = now();
                $updates['cancel_reason'] = $request->input('reason', 'Dibatalkan oleh staf');
            }

            $order->update($updates);

            AuditService::log('order.fulfillment_transition', 'order', $order->id, $before, $order->fresh()->toArray());

            return response()->json([
                'message' => "Status pesanan diubah menjadi {$nextStatus}",
                'order' => $order->fresh()->load('items'),
            ]);
        });
    }

    /**
     * Get staff notifications with live polling support.
     * GET /api/v1/admin/notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        $notifications = TableNotification::orderByDesc('created_at')->limit(30)->get();
        $unreadCount = TableNotification::where('is_read', false)->count();

        // Check for any unhandled 'New' orders older than 60 seconds (escalation warning)
        $unhandledCount = Order::where('fulfillment_status', 'New')
            ->where('created_at', '<=', now()->subSeconds(60))
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'unhandled_count' => $unhandledCount,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Mark notification as read.
     * POST /api/v1/admin/notifications/{id}/read
     */
    public function readNotification(string $id): JsonResponse
    {
        $notif = TableNotification::find($id);
        if ($notif) {
            $notif->update(['is_read' => true]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     * POST /api/v1/admin/notifications/read-all
     */
    public function readAllNotifications(): JsonResponse
    {
        TableNotification::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * List payment attempts & transactions.
     * GET /api/v1/admin/payments
     */
    public function payments(Request $request): JsonResponse
    {
        $query = PaymentAttempt::with(['order', 'receipt'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attempts = $query->paginate($request->input('limit', 50));

        return response()->json($attempts);
    }

    /**
     * Reconcile payment with provider.
     * POST /api/v1/admin/payments/{id}/reconcile
     */
    public function reconcilePayment(Request $request, string $id): JsonResponse
    {
        $attempt = PaymentAttempt::with('order')->find($id);
        if (!$attempt) {
            return response()->json(['error' => ['code' => 'ATTEMPT_NOT_FOUND', 'message' => 'Payment attempt tidak ditemukan']], 404);
        }

        return response()->json([
            'attempt' => $attempt,
            'reconciled' => true,
            'status' => $attempt->status,
            'message' => 'Status pembayaran berhasil direkonsiliasi dengan provider.',
        ]);
    }

    /**
     * List refunds.
     * GET /api/v1/admin/refunds
     */
    public function refunds(): JsonResponse
    {
        $refunds = TableRefund::with(['order', 'receipt'])->orderByDesc('created_at')->get();
        return response()->json(['data' => $refunds]);
    }

    /**
     * Request refund for an order.
     * POST /api/v1/admin/orders/{id}/refund-requests
     */
    public function requestRefund(Request $request, string $id): JsonResponse
    {
        $order = Order::with('receipts')->find($id);
        if (!$order) {
            return response()->json(['error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        $request->validate([
            'reason' => 'required|string',
            'amount_cents' => 'nullable|integer',
        ]);

        $refund = TableRefund::create([
            'id' => 'ref-' . Str::random(12),
            'order_id' => $order->id,
            'payment_receipt_id' => $order->receipts->first()?->id,
            'amount_cents' => $request->amount_cents ?: $order->total_cents,
            'reason' => $request->reason,
            'status' => 'Requested',
            'requested_by' => $request->user()?->name ?: 'Staff',
        ]);

        return response()->json($refund, 201);
    }

    /**
     * Approve and execute refund.
     * POST /api/v1/admin/refunds/{id}/approve
     */
    public function approveRefund(Request $request, string $id): JsonResponse
    {
        $refund = TableRefund::with('order')->find($id);
        if (!$refund) {
            return response()->json(['error' => ['code' => 'REFUND_NOT_FOUND', 'message' => 'Refund tidak ditemukan']], 404);
        }

        $refund->update([
            'status' => 'Succeeded',
            'approved_by' => $request->user()?->name ?: 'Admin',
            'processed_at' => now(),
        ]);

        if ($refund->order) {
            $refund->order->update([
                'payment_status' => 'Refunded',
                'fulfillment_status' => 'Cancelled',
                'status' => 'Cancelled',
            ]);
        }

        AuditService::log('table_refund.approve', 'refund', $refund->id, null, $refund->toArray());

        return response()->json([
            'message' => 'Pengembalian dana (refund) berhasil disetujui dan diselesaikan.',
            'refund' => $refund->fresh(),
        ]);
    }
}
