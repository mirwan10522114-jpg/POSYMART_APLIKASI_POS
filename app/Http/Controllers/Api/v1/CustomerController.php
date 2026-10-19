<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::whereNull('archived_at')
            ->withCount('orders')
            ->withSum(['orders as total_spent_cents' => function ($q) {
                $q->where('payment_status', 'Paid');
            }], 'total_cents');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('id', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->input('source') === 'qr') {
            $query->where('notes', 'like', '%scan QR%');
        }

        $sort = $request->input('sort', 'newest');
        if ($sort === 'newest') {
            $query->orderByDesc('joined_at')->orderByDesc('created_at');
        } elseif ($sort === 'most_orders') {
            $query->orderByDesc('orders_count');
        } elseif ($sort === 'spent_desc' || $sort === 'spent-desc') {
            $query->orderByDesc('total_spent_cents');
        } elseif ($sort === 'name-desc') {
            $query->orderByDesc('name');
        } else {
            $query->orderBy('name');
        }

        $customers = $query->paginate($request->input('limit', 100));

        return response()->json($customers);
    }

    public function show(string $id): JsonResponse
    {
        $customer = Customer::with(['orders' => function ($q) {
            $q->latest()->limit(15);
        }])
        ->withCount('orders')
        ->withSum(['orders as total_spent_cents' => function ($q) {
            $q->where('payment_status', 'Paid');
        }], 'total_cents')
        ->find($id);

        if (!$customer) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Pelanggan tidak ditemukan'],
            ], 404);
        }

        return response()->json($customer);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'error' => [
                    'code' => 'POS-100',
                    'message' => 'Profil pelanggan memerlukan minimal salah satu kontak: email atau nomor telepon.',
                ],
            ], 422);
        }

        $nextNum = Customer::count() + 1;
        $id = $request->input('id') ?: sprintf('CUS-%03d', $nextNum);

        $customer = Customer::create(array_merge($validated, [
            'id' => $id,
            'joined_at' => now(),
        ]));

        AuditService::log('customer.create', 'customer', $customer->id, null, $customer->toArray());

        return response()->json($customer, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Pelanggan tidak ditemukan'],
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|nullable|email|unique:customers,email,{$id}",
            'phone' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:Active,Inactive',
        ]);

        $before = $customer->toArray();
        $customer->update($validated);
        $after = $customer->fresh()->toArray();

        AuditService::log('customer.update', 'customer', $customer->id, $before, $after);

        return response()->json($customer);
    }

    public function activity(string $id): JsonResponse
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Pelanggan tidak ditemukan'],
            ], 404);
        }

        $sales = $customer->sales()->with('items')->latest()->get();

        return response()->json([
            'customer' => $customer,
            'sales' => $sales,
        ]);
    }

    public function archive(string $id): JsonResponse
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'error' => ['code' => 'POS-090', 'message' => 'Pelanggan tidak ditemukan'],
            ], 404);
        }

        $customer->update(['archived_at' => now(), 'status' => 'Inactive']);
        AuditService::log('customer.archive', 'customer', $customer->id);

        return response()->json(['message' => 'Pelanggan berhasil diarsipkan']);
    }
}
