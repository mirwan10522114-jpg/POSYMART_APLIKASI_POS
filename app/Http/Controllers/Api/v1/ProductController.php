<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::whereNull('archived_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $products = $query->orderBy('name')->paginate($request->input('limit', 50));

        return response()->json($products);
    }

    public function categories(): JsonResponse
    {
        $categories = \App\Models\Category::orderBy('sort_order')->get();
        return response()->json([
            'data' => $categories,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with(['movements' => fn ($q) => $q->latest()->limit(5)])->find($id);

        if (!$product) {
            return response()->json([
                'error' => [
                    'code' => 'POS-090',
                    'message' => 'Produk tidak ditemukan',
                ],
            ], 404);
        }

        return response()->json([
            'data' => $product,
            'recent_movements' => $product->movements,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'description' => 'nullable|string',
            'category_id' => 'nullable|string',
            'category' => 'nullable|string',
            'price_cents' => 'required|integer|min:0',
            'cost_cents' => 'nullable|integer|min:0',
            'tax_rate_bps' => 'nullable|integer|min:0|max:10000',
            'status' => 'nullable|in:Active,Inactive,OutOfStock',
            'stock_qty' => 'nullable|integer|min:0',
            'track_stock' => 'nullable|boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string',
        ]);

        if (array_key_exists('low_stock_threshold', $validated) && $validated['low_stock_threshold'] === null) {
            $validated['low_stock_threshold'] = 0;
        }
        if (array_key_exists('stock_qty', $validated) && $validated['stock_qty'] === null) {
            $validated['stock_qty'] = 0;
        }

        $id = $request->input('id') ?: 'p-' . Str::slug($validated['name']) . '-' . Str::random(4);

        $product = Product::create(array_merge($validated, ['id' => $id]));

        AuditService::log('product.create', 'product', $product->id, null, $product->toArray());

        return response()->json($product, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Produk tidak ditemukan'],
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => "sometimes|nullable|string|unique:products,sku,{$id}",
            'description' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|nullable|string',
            'category' => 'sometimes|nullable|string',
            'price_cents' => 'sometimes|integer|min:0',
            'cost_cents' => 'sometimes|nullable|integer|min:0',
            'tax_rate_bps' => 'sometimes|integer|min:0|max:10000',
            'status' => 'sometimes|in:Active,Inactive,OutOfStock',
            'stock_qty' => 'sometimes|nullable|integer|min:0',
            'track_stock' => 'sometimes|boolean',
            'low_stock_threshold' => 'sometimes|nullable|integer|min:0',
            'image_url' => 'sometimes|nullable|string',
        ]);

        if (array_key_exists('low_stock_threshold', $validated) && $validated['low_stock_threshold'] === null) {
            $validated['low_stock_threshold'] = 0;
        }
        if (array_key_exists('stock_qty', $validated) && $validated['stock_qty'] === null) {
            $validated['stock_qty'] = 0;
        }

        $before = $product->toArray();
        $product->update($validated);
        $after = $product->fresh()->toArray();

        AuditService::log('product.update', 'product', $product->id, $before, $after);

        return response()->json($product);
    }

    public function adjustStock(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Produk tidak ditemukan'],
            ], 404);
        }

        $request->validate([
            'delta_qty' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        $delta = (int) $request->delta_qty;
        $newStock = $product->stock_qty + $delta;
        if ($newStock < 0) {
            return response()->json([
                'error' => [
                    'code' => 'POS-010',
                    'message' => 'Penyesuaian stok akan menghasilkan stok negatif',
                ],
            ], 409);
        }

        $movementId = (string) Str::uuid();
        InventoryMovement::create([
            'movement_id' => $movementId,
            'product_id' => $product->id,
            'type' => 'ManualAdjust',
            'delta_qty' => $delta,
            'reason' => $request->reason ?: 'Penyesuaian stok manual',
            'created_by' => $request->user()?->id,
        ]);

        $before = ['stock_qty' => $product->stock_qty];
        $product->update(['stock_qty' => $newStock]);
        $after = ['stock_qty' => $newStock, 'delta_qty' => $delta];

        AuditService::log('product.adjust_stock', 'product', $product->id, $before, $after);

        return response()->json([
            'product' => $product->fresh(),
            'movement_id' => $movementId,
        ]);
    }

    public function archive(string $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Produk tidak ditemukan'],
            ], 404);
        }

        $product->update(['archived_at' => now(), 'status' => 'Inactive']);
        AuditService::log('product.archive', 'product', $product->id, null, ['archived_at' => now()]);

        return response()->json(['message' => 'Produk berhasil diarsipkan']);
    }

    public function restore(string $id): JsonResponse
    {
        $product = Product::whereNotNull('archived_at')->find($id);
        if (!$product) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Produk tidak ditemukan'],
            ], 404);
        }

        $product->update(['archived_at' => null]);
        AuditService::log('product.restore', 'product', $product->id);

        return response()->json(['message' => 'Produk berhasil dipulihkan', 'product' => $product]);
    }
}
