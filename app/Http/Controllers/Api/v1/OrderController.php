<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnitAllocation;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\MoneyAllocator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'customer', 'convertedSale']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $orders = $query->orderByDesc('created_at')->paginate($request->input('limit', 50));

        return response()->json($orders);
    }

    public function show(string $id): JsonResponse
    {
        $order = Order::with(['items', 'customer', 'convertedSale'])->find($id);

        if (!$order) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Pesanan tidak ditemukan'],
            ], 404);
        }

        return response()->json($order);
    }

    public function store(Request $request): JsonResponse
    {
        $rawItems = $request->input('items', []);
        $items = [];
        foreach ($rawItems as $it) {
            $pid = $it['product_id'] ?? $it['productId'] ?? null;
            $qty = (int) ($it['quantity'] ?? 1);
            if ($pid) {
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'note' => $it['note'] ?? null,
                ];
            }
        }

        if (empty($items)) {
            return response()->json([
                'error' => ['code' => 'POS-001', 'message' => 'Pesanan harus memiliki minimal 1 item'],
            ], 422);
        }

        $customerId = $request->input('customer_id') ?? $request->input('customerId');
        $customerName = $request->input('customer_name') ?? $request->input('customerName') ?? 'Walk-in Customer';
        if ($customerId) {
            $cust = Customer::find($customerId);
            if ($cust) {
                $customerName = $cust->name;
            } else {
                $customerId = null;
            }
        }

        // Generate Order Code
        $latest = Order::max('id');
        $seq = 2008;
        if ($latest && preg_match('/ORD-(\d+)/', $latest, $m)) {
            $seq = max($seq, (int) $m[1] + 1);
        }
        $orderCode = "ORD-{$seq}";

        return DB::transaction(function () use ($items, $orderCode, $customerId, $customerName, $request) {
            $order = Order::create([
                'id' => $orderCode,
                'order_code' => $orderCode,
                'status' => 'Pending',
                'customer_id' => $customerId,
                'customer_name_snapshot' => $customerName,
                'note' => $request->input('note'),
                'table_number' => $request->input('table_number') ?? $request->input('tableNumber'),
                'created_by' => $request->user()?->id ?: 'user-john-doe',
            ]);

            foreach ($items as $idx => $it) {
                $p = Product::find($it['product_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $it['product_id'],
                    'name' => $p ? $p->name : 'Produk',
                    'quantity' => $it['quantity'],
                    'unit_price_cents' => $p ? $p->price_cents : 0,
                    'note' => $it['note'] ?? null,
                    'sort_order' => $idx,
                ]);
            }

            AuditService::log('order.create', 'order', $order->id, null, $order->toArray());

            $loaded = $order->load('items');
            return response()->json(array_merge($loaded->toArray(), [
                'order' => $loaded,
            ]), 201);
        });
    }

    public function updateItems(Request $request, string $id): JsonResponse
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        if ($order->status !== 'Draft') {
            return response()->json([
                'error' => ['code' => 'POS-070', 'message' => 'Hanya pesanan status Draft yang dapat diubah itemnya'],
            ], 409);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($order, $request) {
            $order->items()->delete();
            foreach ($request->items as $idx => $it) {
                $p = Product::find($it['product_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $it['product_id'],
                    'name' => $p ? $p->name : 'Produk',
                    'quantity' => $it['quantity'],
                    'unit_price_cents' => $p ? $p->price_cents : 0,
                    'note' => $it['note'] ?? null,
                    'sort_order' => $idx,
                ]);
            }

            return response()->json($order->fresh()->load('items'));
        });
    }

    public function transition(Request $request, string $id): JsonResponse
    {
        $order = Order::with('items')->find($id);
        if (!$order) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        $action = $request->input('action');
        if (!$action && $request->filled('status')) {
            $st = strtolower($request->status);
            $action = match ($st) {
                'pending' => 'submit',
                'processing', 'preparing' => 'start-prepare',
                'ready' => 'mark-ready',
                'completed' => 'complete',
                'cancelled' => 'cancel',
                default => $st,
            };
        }

        $user = $request->user();

        // Valid transitions mapping
        $validMap = [
            'submit' => ['Draft' => 'Pending'],
            'start-prepare' => ['Pending' => 'Preparing'],
            'mark-ready' => ['Preparing' => 'Ready'],
            'complete' => ['Ready' => 'Completed', 'Preparing' => 'Completed', 'Pending' => 'Completed'],
            'cancel' => ['Draft' => 'Cancelled', 'Pending' => 'Cancelled', 'Preparing' => 'Cancelled', 'Ready' => 'Cancelled'],
        ];

        if (!isset($validMap[$action]) || !isset($validMap[$action][$order->status])) {
            return response()->json([
                'error' => [
                    'code' => 'POS-070',
                    'message' => "Transisi aksi '{$action}' tidak valid dari status '{$order->status}'.",
                ],
            ], 409);
        }

        // Cancel role requirement
        if ($action === 'cancel' && in_array($order->status, ['Preparing', 'Ready'])) {
            if (!$user->isManager()) {
                return response()->json([
                    'error' => ['code' => 'AUTH-003', 'message' => 'Pembatalan pesanan yang sedang diproses memerlukan hak Manager atau Admin'],
                ], 403);
            }
        }

        $nextStatus = $validMap[$action][$order->status];

        return DB::transaction(function () use ($order, $nextStatus, $action, $request, $user) {
            $before = $order->toArray();

            if ($nextStatus === 'Cancelled') {
                $order->update([
                    'status' => 'Cancelled',
                    'cancelled_at' => now(),
                    'cancel_reason' => $request->input('cancel_reason', 'Dibatalkan oleh staf'),
                ]);
            } elseif ($nextStatus === 'Completed') {
                // Check if already has converted sale
                if ($order->converted_sale_id) {
                    $existingSale = Sale::find($order->converted_sale_id);
                    return response()->json([
                        'order' => $order,
                        'sale' => $existingSale,
                    ], 200);
                }

                // Create Converted Sale from Order
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

                $totals = MoneyAllocator::computeTotals($itemsForAlloc, 0, $settings->tax_enabled, $settings->tax_rate_bps);
                $allocs = MoneyAllocator::allocateToItems($itemsForAlloc, 0, $totals['tax_cents']);

                $latestSale = Sale::max('id');
                $nextSeq = 1144;
                if ($latestSale && preg_match('/TX-(\d+)/', $latestSale, $m)) {
                    $nextSeq = max($nextSeq, (int) $m[1] + 1);
                }
                $txCode = "TX-{$nextSeq}";

                $paymentMethod = $request->input('payment_method', 'Cash');

                $sale = Sale::create([
                    'id' => $txCode,
                    'tx_code' => $txCode,
                    'customer_id' => $order->customer_id,
                    'customer_name_snapshot' => $order->customer_name_snapshot,
                    'cashier_id' => $user->id,
                    'cashier_name_snapshot' => $user->name,
                    'status' => 'Completed',
                    'payment_method' => $paymentMethod,
                    'subtotal_cents' => $totals['gross_subtotal_cents'],
                    'discount_cents' => 0,
                    'taxable_subtotal_cents' => $totals['taxable_subtotal_cents'],
                    'tax_cents' => $totals['tax_cents'],
                    'tax_rate_bps_snapshot' => $settings->tax_rate_bps,
                    'tax_name_snapshot' => $settings->tax_name,
                    'tax_enabled_snapshot' => $settings->tax_enabled,
                    'total_cents' => $totals['total_cents'],
                    'paid_cents' => $totals['total_cents'],
                    'source_order_id' => $order->id,
                ]);

                foreach ($order->items as $i => $it) {
                    $alloc = $allocs[$i];
                    $saleItem = SaleItem::create([
                        'id' => "si-{$sale->id}-" . ($i + 1),
                        'sale_id' => $sale->id,
                        'product_id' => $it->product_id,
                        'name' => $it->name,
                        'quantity' => $it->quantity,
                        'unit_price_cents' => $it->unit_price_cents,
                        'gross_subtotal_cents' => $alloc['gross_subtotal_cents'],
                        'allocated_discount_cents' => 0,
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
                }

                // If customer present, update customer aggregate totals
                if ($order->customer_id) {
                    Customer::where('id', $order->customer_id)->incrementEach([
                        'total_spent_cents' => $totals['total_cents'],
                        'total_orders' => 1,
                    ]);
                }

                $order->update([
                    'status' => 'Completed',
                    'converted_sale_id' => $sale->id,
                    'completed_at' => now(),
                ]);
            } else {
                $order->update(['status' => $nextStatus]);
            }

            AuditService::log('order.transition', 'order', $order->id, $before, $order->fresh()->toArray());

            $loaded = $order->fresh()->load(['items', 'convertedSale']);
            return response()->json(array_merge($loaded->toArray(), [
                'order' => $loaded,
            ]));
        });
    }
}
