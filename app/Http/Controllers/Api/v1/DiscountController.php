<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Discount::whereNull('archived_at');

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $discounts = $query->orderBy('name')->get();

        return response()->json($discounts);
    }

    public function show(string $id): JsonResponse
    {
        $discount = Discount::find($id);
        if (!$discount) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Diskon tidak ditemukan']], 404);
        }

        return response()->json($discount);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:discounts,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,category_percentage',
            'scope' => 'nullable|in:all,categories,products,customers',
            'value_bps' => 'nullable|integer|min:0|max:10000',
            'value_cents' => 'nullable|integer|min:0',
            'target_categories' => 'nullable|array',
            'target_products' => 'nullable|array',
            'target_customers' => 'nullable|array',
            'min_subtotal_cents' => 'nullable|integer|min:0',
            'max_discount_cents' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'published' => 'nullable|boolean',
        ]);

        $id = 'd-' . Str::slug($validated['code']);
        $discount = Discount::create(array_merge($validated, ['id' => $id, 'code' => strtoupper($validated['code'])]));

        AuditService::log('discount.create', 'discount', $discount->id, null, $discount->toArray());

        return response()->json($discount, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $discount = Discount::find($id);
        if (!$discount) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Diskon tidak ditemukan']], 404);
        }

        $validated = $request->validate([
            'code' => "sometimes|string|unique:discounts,code,{$id}",
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'type' => 'sometimes|in:percentage,fixed,category_percentage',
            'scope' => 'sometimes|in:all,categories,products,customers',
            'value_bps' => 'sometimes|nullable|integer|min:0|max:10000',
            'value_cents' => 'sometimes|nullable|integer|min:0',
            'target_categories' => 'sometimes|nullable|array',
            'target_products' => 'sometimes|nullable|array',
            'target_customers' => 'sometimes|nullable|array',
            'min_subtotal_cents' => 'sometimes|integer|min:0',
            'max_discount_cents' => 'sometimes|nullable|integer|min:0',
            'usage_limit' => 'sometimes|nullable|integer|min:1',
            'start_at' => 'sometimes|nullable|date',
            'end_at' => 'sometimes|nullable|date',
            'is_active' => 'sometimes|boolean',
            'published' => 'sometimes|boolean',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $before = $discount->toArray();
        $discount->update($validated);
        $after = $discount->fresh()->toArray();

        AuditService::log('discount.update', 'discount', $discount->id, $before, $after);

        return response()->json($discount);
    }

    public function archive(string $id): JsonResponse
    {
        $discount = Discount::find($id);
        if (!$discount) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Diskon tidak ditemukan']], 404);
        }

        $discount->update(['archived_at' => now(), 'is_active' => false]);
        AuditService::log('discount.archive', 'discount', $discount->id);

        return response()->json(['message' => 'Diskon berhasil diarsipkan']);
    }
}
