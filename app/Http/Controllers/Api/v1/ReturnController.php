<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\PosReturn;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnitAllocation;
use App\Models\Setting;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PosReturn::with(['items', 'sale', 'processor']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $returns = $query->orderByDesc('created_at')->paginate($request->input('limit', 50));

        return response()->json($returns);
    }

    public function show(string $id): JsonResponse
    {
        $posReturn = PosReturn::with(['items', 'sale.items.unitAllocations', 'processor'])->find($id);

        if (!$posReturn) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Data retur tidak ditemukan']], 404);
        }

        return response()->json($posReturn);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $sale = Sale::with('items')->find($request->sale_id);
        if (!$sale) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Transaksi penjualan tidak ditemukan']], 404);
        }

        if ($sale->status === 'Voided') {
            return response()->json(['error' => ['code' => 'POS-020', 'message' => 'Transaksi yang dibatalkan (Voided) tidak dapat diretur']], 409);
        }

        $latest = PosReturn::max('id');
        $seq = 1006;
        if ($latest && preg_match('/R-(\d+)/', $latest, $m)) {
            $seq = max($seq, (int) $m[1] + 1);
        }
        $code = "R-{$seq}";

        $posReturn = PosReturn::create([
            'id' => $code,
            'return_code' => $code,
            'sale_id' => $sale->id,
            'status' => 'Draft',
            'reason' => $request->reason,
            'submitted_at' => now(),
        ]);

        return response()->json($posReturn, 201);
    }

    public function addItem(Request $request, string $id): JsonResponse
    {
        $posReturn = PosReturn::find($id);
        if (!$posReturn) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Data retur tidak ditemukan']], 404);
        }

        if ($posReturn->status !== 'Draft') {
            return response()->json(['error' => ['code' => 'POS-070', 'message' => 'Item retur hanya dapat ditambahkan pada status Draft']], 409);
        }

        $request->validate([
            'sale_item_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'condition' => 'nullable|in:Unopened,Opened,Damaged',
            'restock' => 'nullable|boolean',
            'unit_indexes' => 'nullable|array',
        ]);

        $saleItem = SaleItem::with('unitAllocations')->find($request->sale_item_id);
        if (!$saleItem || $saleItem->sale_id !== $posReturn->sale_id) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Item transaksi tidak valid']], 404);
        }

        $quantity = (int) $request->quantity;
        $unrefundedUnits = $saleItem->unitAllocations->where('is_refunded', false);

        if ($unrefundedUnits->count() < $quantity) {
            return response()->json([
                'error' => [
                    'code' => 'POS-030',
                    'message' => "Jumlah unit yang dapat direfund tidak mencukupi (Tersisa: {$unrefundedUnits->count()})",
                ],
            ], 409);
        }

        $unitsToRefund = $unrefundedUnits->take($quantity);
        $selectedUnits = [];
        $itemNet = 0;
        $itemTax = 0;

        foreach ($unitsToRefund as $u) {
            $selectedUnits[] = [
                'unitIndex' => $u->unit_index,
                'netCents' => $u->net_cents,
                'taxCents' => $u->tax_cents,
            ];
            $itemNet += $u->net_cents;
            $itemTax += $u->tax_cents;
        }

        $returnItem = ReturnItem::create([
            'return_id' => $posReturn->id,
            'sale_item_id' => $saleItem->id,
            'product_id' => $saleItem->product_id,
            'name' => $saleItem->name,
            'quantity' => $quantity,
            'selected_units' => $selectedUnits,
            'restock' => $request->boolean('restock', true),
            'condition' => $request->input('condition', 'Unopened'),
            'refund_subtotal_cents' => $itemNet,
            'refund_tax_cents' => $itemTax,
            'refund_total_cents' => $itemNet + $itemTax,
        ]);

        // Update return total
        $posReturn->increment('subtotal_refund_cents', $itemNet);
        $posReturn->increment('tax_refund_cents', $itemTax);
        $posReturn->increment('total_refund_cents', $itemNet + $itemTax);

        return response()->json($posReturn->fresh()->load('items'));
    }

    public function submit(Request $request, string $id): JsonResponse
    {
        $posReturn = PosReturn::with('items')->find($id);
        if (!$posReturn) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Data retur tidak ditemukan']], 404);
        }

        if ($posReturn->items->isEmpty()) {
            return response()->json(['error' => ['code' => 'POS-001', 'message' => 'Pilih minimal 1 item untuk diajukan retur']], 422);
        }

        $posReturn->update([
            'status' => 'Pending',
            'submitted_at' => now(),
        ]);

        AuditService::log('return.submit', 'return', $posReturn->id);

        return response()->json($posReturn);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $posReturn = PosReturn::find($id);
        if (!$posReturn) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Data retur tidak ditemukan']], 404);
        }

        if ($posReturn->status !== 'Pending') {
            return response()->json(['error' => ['code' => 'POS-070', 'message' => 'Hanya retur status Pending yang dapat disetujui']], 409);
        }

        $posReturn->update([
            'processor_id' => $request->user()->id,
        ]);

        AuditService::log('return.approve', 'return', $posReturn->id);

        return response()->json($posReturn);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $posReturn = PosReturn::find($id);
        if (!$posReturn) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Data retur tidak ditemukan']], 404);
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $posReturn->update([
            'status' => 'Rejected',
            'processor_id' => $request->user()->id,
            'rejected_at' => now(),
            'reason' => $request->reason,
        ]);

        AuditService::log('return.reject', 'return', $posReturn->id);

        return response()->json($posReturn);
    }

    public function processRefund(Request $request, string $id): JsonResponse
    {
        $posReturn = PosReturn::with(['items', 'sale'])->find($id);
        if (!$posReturn) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Data retur tidak ditemukan']], 404);
        }

        if (!in_array($posReturn->status, ['Pending', 'Draft'])) {
            return response()->json(['error' => ['code' => 'POS-070', 'message' => 'Retur harus berstatus Pending untuk diproses refund']], 409);
        }

        $user = $request->user();
        $refundMethod = $request->input('refund_method', $posReturn->sale->payment_method ?: 'Cash');

        return DB::transaction(function () use ($posReturn, $user, $refundMethod) {
            $settings = Setting::instance();

            foreach ($posReturn->items as $ri) {
                // Mark unit allocations as refunded
                foreach ($ri->selected_units as $su) {
                    $uIdx = $su['unitIndex'];
                    SaleItemUnitAllocation::where('sale_item_id', $ri->sale_item_id)
                        ->where('unit_index', $uIdx)
                        ->update([
                            'is_refunded' => true,
                            'refunded_at' => now(),
                        ]);
                }

                // Restock condition: Unopened & restock=true
                if ($settings->track_inventory && $ri->restock && $ri->condition === 'Unopened') {
                    if ($ri->product_id) {
                        $p = Product::find($ri->product_id);
                        if ($p && $p->track_stock) {
                            $p->increment('stock_qty', $ri->quantity);
                            InventoryMovement::create([
                                'movement_id' => (string) Str::uuid(),
                                'product_id' => $p->id,
                                'type' => 'ReturnRestock',
                                'delta_qty' => $ri->quantity,
                                'return_id' => $posReturn->id,
                                'reason' => "Restock dari retur {$posReturn->return_code}",
                                'created_by' => $user->id,
                            ]);
                        }
                    }
                }
            }

            // Customer total spent adjustment (only if customer_id != null)
            if ($posReturn->sale->customer_id) {
                $cust = Customer::find($posReturn->sale->customer_id);
                if ($cust) {
                    $cust->decrement('total_spent_cents', min($cust->total_spent_cents, $posReturn->total_refund_cents));
                }
            }

            $posReturn->update([
                'status' => 'Completed',
                'completed_at' => now(),
                'processor_id' => $user->id,
                'refund_method' => $refundMethod,
            ]);

            AuditService::log('return.complete', 'return', $posReturn->id, null, $posReturn->fresh()->toArray());

            return response()->json($posReturn->fresh()->load('items'));
        });
    }
}
