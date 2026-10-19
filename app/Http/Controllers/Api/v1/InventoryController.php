<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function movements(Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['product', 'sale']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $movements = $query->latest('created_at')->paginate($request->input('limit', 50));

        return response()->json($movements);
    }

    public function productMovements(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Produk tidak ditemukan']], 404);
        }

        $movements = InventoryMovement::where('product_id', $id)
            ->latest('created_at')
            ->paginate($request->input('limit', 50));

        return response()->json([
            'product' => $product,
            'movements' => $movements,
        ]);
    }
}
