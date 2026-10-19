<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDraft;
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

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['items', 'customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
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

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('tx_code', 'like', "%{$q}%")
                    ->orWhere('customer_name_snapshot', 'like', "%{$q}%")
                    ->orWhere('cashier_name_snapshot', 'like', "%{$q}%");
            });
        }

        $sales = $query->orderByDesc('created_at')->paginate($request->input('limit', 50));

        return response()->json($sales);
    }

    public function show(string $id): JsonResponse
    {
        $sale = Sale::with(['items.unitAllocations', 'discountUsage.discount', 'returns.items', 'customer'])
            ->where('id', $id)
            ->orWhere('tx_code', $id)
            ->first();

        if (!$sale) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Transaksi penjualan tidak ditemukan'],
            ], 404);
        }

        return response()->json($sale);
    }

    // --- Draft Cart Operations ---

    protected function getUserDraft(string $userId): array
    {
        $record = SaleDraft::find($userId);
        if ($record && !empty($record->draft_data)) {
            return $record->draft_data;
        }

        return [
            'items' => [],
            'customer_id' => null,
            'discount_code' => null,
            'note' => '',
        ];
    }

    protected function saveUserDraft(string $userId, array $data): void
    {
        SaleDraft::updateOrCreate(
            ['user_id' => $userId],
            ['draft_data' => $data]
        );
    }

    public function getDraft(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $draft = $this->getUserDraft($userId);

        return response()->json($this->formatDraftWithTotals($draft));
    }

    public function createDraft(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $empty = [
            'items' => [],
            'customer_id' => null,
            'discount_code' => null,
            'note' => '',
        ];
        $this->saveUserDraft($userId, $empty);

        return response()->json($this->formatDraftWithTotals($empty));
    }

    public function addItem(Request $request): JsonResponse
    {
        $productId = $request->input('product_id') ?? $request->input('productId');

        if (!$productId) {
            return response()->json([
                'error' => ['code' => 'POS-001', 'message' => 'Product ID wajib diisi'],
            ], 422);
        }

        $product = Product::whereNull('archived_at')->find($productId);
        if (!$product || $product->status !== 'Active') {
            return response()->json([
                'error' => ['code' => 'POS-011', 'message' => 'Produk tidak tersedia atau tidak aktif'],
            ], 409);
        }

        $userId = $request->user()->id;
        $draft = $this->getUserDraft($userId);

        $quantity = (int) ($request->input('quantity', 1) ?: 1);
        $found = false;

        foreach ($draft['items'] as &$it) {
            if ($it['product_id'] === $product->id) {
                $it['quantity'] += $quantity;
                if ($request->filled('note')) {
                    $it['note'] = $request->note;
                }
                $found = true;
                break;
            }
        }
        unset($it);

        if (!$found) {
            $draft['items'][] = [
                'item_id' => 'item-' . Str::random(8),
                'product_id' => $product->id,
                'name' => $product->name,
                'unit_price_cents' => $product->price_cents,
                'quantity' => $quantity,
                'note' => $request->note ?: '',
            ];
        }

        $this->saveUserDraft($userId, $draft);

        return response()->json($this->formatDraftWithTotals($draft));
    }

    public function updateItem(Request $request, string $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $draft = $this->getUserDraft($userId);

        foreach ($draft['items'] as &$it) {
            if (($it['item_id'] ?? null) === $itemId || ($it['product_id'] ?? null) === $itemId) {
                $it['quantity'] = (int) $request->quantity;
                if ($request->has('note')) {
                    $it['note'] = $request->note;
                }
                break;
            }
        }
        unset($it);

        $this->saveUserDraft($userId, $draft);

        return response()->json($this->formatDraftWithTotals($draft));
    }

    public function removeItem(Request $request, string $itemId): JsonResponse
    {
        $userId = $request->user()->id;
        $draft = $this->getUserDraft($userId);

        $draft['items'] = array_values(array_filter($draft['items'], function ($it) use ($itemId) {
            return ($it['item_id'] ?? null) !== $itemId && ($it['product_id'] ?? null) !== $itemId;
        }));

        $this->saveUserDraft($userId, $draft);

        return response()->json($this->formatDraftWithTotals($draft));
    }

    public function setCustomer(Request $request): JsonResponse
    {
        // customer_id boleh null untuk Walk-in Customer
        $customerId = $request->input('customer_id', $request->input('customerId'));

        if ($customerId !== null) {
            $cust = Customer::whereNull('archived_at')->find($customerId);
            if (!$cust) {
                return response()->json([
                    'error' => ['code' => 'POS-090', 'message' => 'Profil pelanggan tidak ditemukan atau diarsipkan'],
                ], 404);
            }
        }

        $userId = $request->user()->id;
        $draft = $this->getUserDraft($userId);
        $draft['customer_id'] = $customerId;

        $this->saveUserDraft($userId, $draft);

        return response()->json($this->formatDraftWithTotals($draft));
    }

    public function applyDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'discount_code' => 'required|string',
        ]);

        $discount = Discount::where('code', strtoupper($request->discount_code))
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->first();

        if (!$discount) {
            return response()->json([
                'error' => ['code' => 'POS-040', 'message' => 'Diskon tidak ditemukan atau tidak aktif'],
            ], 409);
        }

        if ($discount->end_at && $discount->end_at->isPast()) {
            return response()->json([
                'error' => ['code' => 'POS-040', 'message' => 'Diskon sudah kedaluwarsa'],
            ], 409);
        }

        if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
            return response()->json([
                'error' => ['code' => 'POS-041', 'message' => 'Batas penggunaan diskon sudah tercapai'],
            ], 409);
        }

        $userId = $request->user()->id;
        $draft = $this->getUserDraft($userId);
        $draft['discount_code'] = $discount->code;

        $this->saveUserDraft($userId, $draft);

        return response()->json($this->formatDraftWithTotals($draft));
    }

    public function removeDiscount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $draft = $this->getUserDraft($userId);
        $draft['discount_code'] = null;

        $this->saveUserDraft($userId, $draft);

        return response()->json($this->formatDraftWithTotals($draft));
    }

    protected function formatDraftWithTotals(array $draft): array
    {
        $settings = Setting::instance();
        $items = $draft['items'] ?? [];

        $discountCents = 0;
        $discountModel = null;
        if (!empty($draft['discount_code'])) {
            $discountModel = Discount::where('code', $draft['discount_code'])->first();
            if ($discountModel) {
                $grossSub = 0;
                $eligibleSub = 0;
                foreach ($items as $it) {
                    $itemGross = ($it['unit_price_cents'] ?? 0) * ($it['quantity'] ?? 0);
                    $grossSub += $itemGross;

                    $p = Product::find($it['product_id']);
                    $isEligible = true;
                    if ($discountModel->scope === 'categories' && !empty($discountModel->target_categories)) {
                        $isEligible = in_array($p?->category_id, $discountModel->target_categories) || in_array($p?->category, $discountModel->target_categories);
                    }
                    if ($isEligible) {
                        $eligibleSub += $itemGross;
                    }
                }

                if ($grossSub >= $discountModel->min_subtotal_cents && $eligibleSub > 0) {
                    if ($discountModel->type === 'fixed') {
                        $discountCents = min($discountModel->value_cents, $eligibleSub);
                    } elseif ($discountModel->type === 'percentage' || $discountModel->type === 'category_percentage') {
                        $discountCents = MoneyAllocator::roundHalfUp(($eligibleSub * $discountModel->value_bps) / 10000);
                    }
                    if ($discountModel->max_discount_cents) {
                        $discountCents = min($discountCents, $discountModel->max_discount_cents);
                    }
                }
            }
        }

        $totals = MoneyAllocator::computeTotals(
            $items,
            $discountCents,
            $settings->tax_enabled,
            $settings->tax_rate_bps
        );

        $customer = !empty($draft['customer_id']) ? Customer::find($draft['customer_id']) : null;

        return [
            'items' => $items,
            'customer_id' => $draft['customer_id'] ?? null,
            'customer_name' => $customer ? $customer->name : 'Walk-in Customer',
            'discount_code' => $draft['discount_code'] ?? null,
            'discount' => $discountModel,
            'note' => $draft['note'] ?? '',
            'totals' => $totals,
        ];
    }

    // --- Execute Payment (POST /sales/draft/pay) ---
    public function pay(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = Setting::instance();
        $draft = $request->input('draft') ?: $this->getUserDraft($user->id);

        if (empty($draft['items'])) {
            return response()->json([
                'error' => ['code' => 'POS-001', 'message' => 'Draft penjualan kosong, tambahkan minimal 1 produk.'],
            ], 422);
        }

        $rawPm = $request->input('payment_method') ?? $request->input('paymentMethod') ?? 'Cash';
        $pmLower = strtolower(str_replace(['_', '-'], ' ', (string) $rawPm));
        if ($pmLower === 'cash') {
            $paymentMethod = 'Cash';
        } elseif ($pmLower === 'card') {
            $paymentMethod = 'Card';
        } elseif (in_array($pmLower, ['digital wallet', 'qris', 'ewallet'])) {
            $paymentMethod = 'Digital Wallet';
        } else {
            $paymentMethod = ucfirst($pmLower);
        }

        if (!in_array($paymentMethod, ['Cash', 'Card', 'Digital Wallet'])) {
            return response()->json([
                'error' => ['code' => 'POS-002', 'message' => 'Metode pembayaran tidak valid.'],
            ], 422);
        }

        if ($request->has('customerId') || $request->has('customer_id')) {
            $draft['customer_id'] = $request->input('customer_id', $request->input('customerId'));
        }
        if ($request->has('customer_name') || $request->has('customerName')) {
            $draft['customer_name'] = $request->input('customer_name', $request->input('customerName'));
        }

        // Lock & Validate products in transaction
        return DB::transaction(function () use ($request, $user, $settings, $draft, $paymentMethod) {
            $productIds = array_column($draft['items'], 'product_id');
            sort($productIds);

            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($draft['items'] as $item) {
                $p = $products->get($item['product_id']);
                $pName = $p?->name ?? ($item['name'] ?? 'Produk');
                if (!$p || $p->status === 'Inactive' || $p->archived_at !== null) {
                    return response()->json([
                        'error' => ['code' => 'POS-011', 'message' => "Produk {$pName} tidak aktif atau telah diarsipkan."],
                    ], 409);
                }

                if ($p->status === 'OutOfStock') {
                    return response()->json([
                        'error' => ['code' => 'POS-026', 'message' => "Produk {$pName} sedang ditandai habis."],
                    ], 409);
                }

                if ($settings->track_inventory && $p->track_stock && $p->stock_qty < $item['quantity']) {
                    return response()->json([
                        'error' => [
                            'code' => 'POS-010',
                            'message' => "Stok produk {$p->name} tidak mencukupi (Tersedia: {$p->stock_qty}, Diminta: {$item['quantity']}).",
                        ],
                    ], 409);
                }
            }

            // Customer
            $customerId = $draft['customer_id'] ?? null;
            $customerName = trim($draft['customer_name'] ?? $draft['customer_name_snapshot'] ?? '') ?: 'Pelanggan Umum';
            if ($customerId) {
                $cust = Customer::find($customerId);
                if ($cust) {
                    $customerName = $cust->name;
                } else {
                    $customerId = null;
                }
            }

            // Calculate totals and allocations
            $draftWithTotals = $this->formatDraftWithTotals($draft);
            $totals = $draftWithTotals['totals'];
            $discountModel = $draftWithTotals['discount'];

            // Generate tx_code
            $latestId = Sale::max('id');
            $nextSeq = 1144;
            if ($latestId && preg_match('/TX-(\d+)/', $latestId, $m)) {
                $nextSeq = max($nextSeq, (int) $m[1] + 1);
            }
            $txCode = "TX-{$nextSeq}";

            $paidCents = $request->input('paid_cents') ?: $totals['total_cents'];
            $changeCents = max(0, $paidCents - $totals['total_cents']);

            // Insert Sale
            $sale = Sale::create([
                'id' => $txCode,
                'tx_code' => $txCode,
                'customer_id' => $customerId,
                'customer_name_snapshot' => $customerName,
                'cashier_id' => $user->id,
                'cashier_name_snapshot' => $user->name,
                'status' => 'Completed',
                'payment_method' => $paymentMethod,
                'subtotal_cents' => $totals['gross_subtotal_cents'],
                'discount_cents' => $totals['discount_cents'],
                'discount_id' => $discountModel?->id,
                'taxable_subtotal_cents' => $totals['taxable_subtotal_cents'],
                'tax_cents' => $totals['tax_cents'],
                'tax_rate_bps_snapshot' => $settings->tax_rate_bps,
                'tax_name_snapshot' => $settings->tax_name,
                'tax_enabled_snapshot' => $settings->tax_enabled,
                'total_cents' => $totals['total_cents'],
                'paid_cents' => $paidCents,
                'change_cents' => $changeCents,
                'note' => $draft['note'] ?? null,
                'source_order_id' => $request->input('source_order_id'),
            ]);

            // Item allocations
            $eligibleIds = null;
            if ($discountModel && $discountModel->scope === 'categories' && !empty($discountModel->target_categories)) {
                $eligibleIds = [];
                foreach ($draft['items'] as $it) {
                    $p = $products->get($it['product_id']);
                    if (in_array($p->category_id, $discountModel->target_categories) || in_array($p->category, $discountModel->target_categories)) {
                        $eligibleIds[] = $p->id;
                    }
                }
            }

            $allocs = MoneyAllocator::allocateToItems(
                $draft['items'],
                $totals['discount_cents'],
                $totals['tax_cents'],
                $eligibleIds
            );

            foreach ($draft['items'] as $i => $it) {
                $alloc = $allocs[$i];
                $saleItem = SaleItem::create([
                    'id' => "si-{$sale->id}-" . ($i + 1),
                    'sale_id' => $sale->id,
                    'product_id' => $it['product_id'],
                    'name' => $it['name'],
                    'quantity' => $it['quantity'],
                    'unit_price_cents' => $it['unit_price_cents'] ?? $it['unit_price'] ?? 0,
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
                    $it['quantity']
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

                // Decrement stock & insert movement
                $p = $products->get($it['product_id']);
                if ($settings->track_inventory && $p->track_stock) {
                    $p->decrement('stock_qty', $it['quantity']);
                    InventoryMovement::create([
                        'movement_id' => (string) Str::uuid(),
                        'product_id' => $p->id,
                        'type' => 'SaleDecrement',
                        'delta_qty' => -$it['quantity'],
                        'sale_id' => $sale->id,
                        'reason' => "Penjualan {$sale->tx_code}",
                        'created_by' => $user->id,
                    ]);
                }
            }

            // Discount usage
            if ($discountModel) {
                DiscountUsage::create([
                    'discount_id' => $discountModel->id,
                    'sale_id' => $sale->id,
                    'applied_cents' => $totals['discount_cents'],
                    'created_at' => now(),
                ]);
                $discountModel->increment('usage_count');
            }

            // Customer totals update
            if ($customerId) {
                Customer::where('id', $customerId)->incrementEach([
                    'total_spent_cents' => $totals['total_cents'],
                    'total_orders' => 1,
                ]);
            }

            AuditService::log('sale.pay', 'sale', $sale->id, null, $sale->toArray());

            // Clear draft
            $this->saveUserDraft($user->id, [
                'items' => [],
                'customer_id' => null,
                'discount_code' => null,
                'note' => '',
            ]);

            $loaded = $sale->load(['items.unitAllocations', 'customer']);
            return response()->json(array_merge($loaded->toArray(), [
                'sale' => $loaded,
            ]), 201);
        });
    }

    // --- Void Sale (POST /sales/{id}/void) ---
    public function void(Request $request, string $id): JsonResponse
    {
        $sale = Sale::with(['items', 'returns'])->find($id);
        if (!$sale) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Transaksi tidak ditemukan'],
            ], 404);
        }

        if ($sale->status === 'Voided') {
            return response()->json([
                'error' => ['code' => 'POS-020', 'message' => 'Transaksi penjualan sudah dibatalkan (Voided)'],
            ], 409);
        }

        // Check pending or completed returns
        $hasActiveReturns = $sale->returns->whereIn('status', ['Pending', 'Completed'])->isNotEmpty();
        if ($hasActiveReturns) {
            return response()->json([
                'error' => ['code' => 'POS-021', 'message' => 'Tidak dapat membatalkan transaksi yang memiliki retur aktif atau selesai.'],
            ], 409);
        }

        $user = $request->user();
        $reason = $request->input('reason', 'Pembatalan transaksi oleh staf');

        return DB::transaction(function () use ($sale, $user, $reason) {
            $before = $sale->toArray();

            // Restore stocks
            $settings = Setting::instance();
            if ($settings->track_inventory) {
                foreach ($sale->items as $it) {
                    if ($it->product_id) {
                        $p = Product::find($it->product_id);
                        if ($p && $p->track_stock) {
                            $p->increment('stock_qty', $it->quantity);
                            InventoryMovement::create([
                                'movement_id' => (string) Str::uuid(),
                                'product_id' => $p->id,
                                'type' => 'VoidRestore',
                                'delta_qty' => $it->quantity,
                                'sale_id' => $sale->id,
                                'reason' => "Void transaksi {$sale->tx_code}: {$reason}",
                                'created_by' => $user->id,
                            ]);
                        }
                    }
                }
            }

            // Revert discount usage if any
            if ($sale->discount_id) {
                $discount = Discount::find($sale->discount_id);
                if ($discount && $discount->usage_count > 0) {
                    $discount->decrement('usage_count');
                }
                DiscountUsage::where('sale_id', $sale->id)->delete();
            }

            // Revert customer totals
            if ($sale->customer_id) {
                $cust = Customer::find($sale->customer_id);
                if ($cust) {
                    $cust->decrement('total_spent_cents', min($cust->total_spent_cents, $sale->total_cents));
                    if ($cust->total_orders > 0) {
                        $cust->decrement('total_orders', 1);
                    }
                }
            }

            $sale->update([
                'status' => 'Voided',
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            AuditService::log('sale.void', 'sale', $sale->id, $before, $sale->fresh()->toArray());

            $loaded = $sale->fresh()->load('items');
            return response()->json(array_merge($loaded->toArray(), [
                'sale' => $loaded,
            ]));
        });
    }
}
