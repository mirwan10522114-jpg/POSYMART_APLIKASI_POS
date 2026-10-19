<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\GuestSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use App\Models\TableNotification;
use App\Models\TableQr;
use App\Services\MoneyAllocator;
use App\Services\PaymentProviderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuestTableOrderController extends Controller
{
    /**
     * Resolve public QR token to table context.
     * GET /api/v1/qr/{publicToken}
     */
    public function resolveQr(string $publicToken): JsonResponse
    {
        $qr = TableQr::with('table')->where('public_token', $publicToken)->first();

        if (!$qr) {
            return response()->json([
                'error' => [
                    'code' => 'QR_INVALID',
                    'message' => 'Kode QR meja tidak dikenali atau telah kedaluwarsa.',
                ],
            ], 404);
        }

        if ($qr->status !== 'Active') {
            return response()->json([
                'error' => [
                    'code' => 'QR_INACTIVE',
                    'message' => 'Meja ini sedang tidak aktif menerima pesanan. Silakan hubungi staf kafe.',
                ],
                'table' => $qr->table,
                'qr_status' => $qr->status,
            ], 403);
        }

        $table = $qr->table;
        if (!$table || !$table->is_active) {
            return response()->json([
                'error' => [
                    'code' => 'TABLE_INACTIVE',
                    'message' => 'Meja sedang tidak aktif. Silakan hubungi staf kafe.',
                ],
            ], 403);
        }

        // Increment scan count
        $qr->increment('scan_count');

        $settings = Setting::instance();

        return response()->json([
            'valid' => true,
            'qr_id' => $qr->id,
            'public_token' => $qr->public_token,
            'table_id' => $table->id,
            'table_code' => $table->table_code,
            'table_label' => $table->label,
            'area' => $table->area,
            'store_name' => $settings->store_name,
            'is_open' => true,
        ]);
    }

    /**
     * Create or resume guest session from public QR token.
     * POST /api/v1/guest-sessions
     */
    public function createSession(Request $request): JsonResponse
    {
        $request->validate([
            'public_token' => 'required|string',
            'customer_name' => 'nullable|string|max:50',
        ]);

        $qr = TableQr::with('table')->where('public_token', $request->public_token)->first();
        if (!$qr || $qr->status !== 'Active' || !$qr->table->is_active) {
            return response()->json([
                'error' => ['code' => 'QR_INVALID', 'message' => 'QR Meja tidak valid atau nonaktif'],
            ], 403);
        }

        $sessionId = 'sess-' . Str::random(16);
        $session = GuestSession::create([
            'id' => $sessionId,
            'table_id' => $qr->table->id,
            'table_qr_id' => $qr->id,
            'customer_name' => $request->input('customer_name'),
            'expires_at' => now()->addHours(2),
        ]);

        return response()->json([
            'session_id' => $session->id,
            'table_id' => $qr->table->id,
            'table_code' => $qr->table->table_code,
            'table_label' => $qr->table->label,
            'customer_name' => $session->customer_name,
            'expires_at' => $session->expires_at,
        ], 201);
    }

    /**
     * Customer login / lookup by phone number and password for table ordering.
     * POST /api/v1/guest/login
     */
    public function loginCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:6|max:25',
            'password' => 'required|string|min:4',
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $validated['phone']);

        $customer = Customer::where('phone', $phone)->first();

        if (!$customer) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($digits, '62') && strlen($digits) >= 10) {
                $alt1 = '0' . substr($digits, 2);
                $customer = Customer::where('phone', $alt1)->orWhere('phone', 'like', "%{$alt1}%")->first();
            } elseif (str_starts_with($digits, '0') && strlen($digits) >= 9) {
                $alt2 = '62' . substr($digits, 1);
                $customer = Customer::where('phone', $alt2)
                    ->orWhere('phone', '+' . $alt2)
                    ->orWhere('phone', 'like', "%{$alt2}%")
                    ->first();
            }
        }

        if (!$customer) {
            $cleanDigits = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanDigits) >= 8) {
                $customer = Customer::whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$cleanDigits}%"])->first();
            }
        }

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp / HP belum terdaftar di PosyMart. Silakan buat akun baru.',
            ], 404);
        }

        // Verify password
        if (empty($customer->password)) {
            // First time login for legacy/POS customer record without password set yet
            $customer->password = Hash::make($validated['password']);
            $customer->save();
        } elseif (!Hash::check($validated['password'], $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi / password salah. Silakan periksa kembali.',
            ], 401);
        }

        $orderCount = Order::where('customer_id', $customer->id)->count();

        return response()->json([
            'success' => true,
            'message' => "Selamat datang kembali, {$customer->name}!",
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'total_orders' => $orderCount,
                'joined_at' => $customer->joined_at,
            ],
        ]);
    }

    /**
     * Customer registration / identification for table ordering.
     * POST /api/v1/guest/register
     */
    public function registerCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'phone' => 'required|string|min:8|max:20',
            'password' => 'required|string|min:4|max:100',
            'email' => 'required|email|max:100',
            'table_id' => 'nullable|string',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi (minimal 4 karakter).',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $validated['phone']);
        $email = strtolower(trim($validated['email']));

        $customer = Customer::where('phone', $phone)->first();

        if (!$customer) {
            $count = Customer::count();
            $customerId = 'CUS-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
            while (Customer::where('id', $customerId)->exists()) {
                $customerId = 'CUS-' . Str::random(5);
            }

            $customer = Customer::create([
                'id' => $customerId,
                'name' => $validated['name'],
                'phone' => $phone,
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'status' => 'Active',
                'joined_at' => now(),
                'notes' => 'Terdaftar via scan QR Meja',
            ]);
        } else {
            $customer->update([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make($validated['password']),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran pelanggan berhasil',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
            ],
        ]);
    }

    /**
     * Get menu for guest customer.
     * GET /api/v1/guest/menu
     */
    public function getMenu(Request $request): JsonResponse
    {
        $query = Product::whereNull('archived_at')->whereIn('status', ['Active', 'OutOfStock']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $products = $query->orderBy('name')->get();
        $categories = \App\Models\Category::orderBy('sort_order')->get();
        $tables = Table::where('is_active', true)
            ->orderByRaw("CAST(SUBSTR(table_code, 2) AS INTEGER) ASC")
            ->get(['id', 'table_code', 'label', 'area']);
        $settings = Setting::instance();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'tables' => $tables,
            'settings' => [
                'table_service_fee_enabled' => (bool)$settings->table_service_fee_enabled,
                'table_service_fee_cents' => (int)($settings->table_service_fee_cents ?? 2000),
                'tax_enabled' => (bool)$settings->tax_enabled,
                'tax_rate_bps' => (int)$settings->tax_rate_bps,
                'tax_name' => $settings->tax_name ?: 'Pajak',
            ],
        ]);
    }

    /**
     * Save/update guest cart with optimistic locking version.
     * POST /api/v1/guest/carts
     */
    public function saveCart(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|string',
            'session_id' => 'nullable|string',
            'cart_id' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount_code' => 'nullable|string',
        ]);

        $table = Table::find($request->table_id);
        if (!$table || !$table->is_active) {
            return response()->json(['error' => ['code' => 'TABLE_INACTIVE', 'message' => 'Meja tidak aktif']], 403);
        }

        $cartId = $request->cart_id ?: ('cart-' . Str::random(12));
        $cart = Cart::find($cartId);

        $nextVersion = $cart ? $cart->version + 1 : 1;

        $cart = Cart::updateOrCreate(
            ['id' => $cartId],
            [
                'guest_session_id' => $request->session_id,
                'table_id' => $table->id,
                'version' => $nextVersion,
                'items_data' => $request->items,
                'discount_code' => $request->discount_code,
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'cart_id' => $cart->id,
            'version' => $cart->version,
            'items' => $cart->items_data,
            'notes' => $cart->notes,
        ]);
    }

    /**
     * Checkout: Create Order & QRIS Payment Attempt from Cart.
     * POST /api/v1/guest/checkouts
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'nullable|string',
            'table_number' => 'nullable|string',
            'delivery_type' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $deliveryType = $request->input('delivery_type', 'table') === 'counter' ? 'counter' : 'table';
        $table = null;

        // 1. Try finding by table_id (e.g. T017 or T003)
        if ($request->filled('table_id')) {
            $table = Table::find($request->table_id);
            if (!$table) {
                $table = Table::where('table_code', strtoupper($request->table_id))->first();
            }
        }

        // 2. Try finding by table_number (e.g. '17', 'Meja 17', 'T017')
        if (!$table && $request->filled('table_number')) {
            $rawNumber = trim((string)$request->table_number);
            $cleanNum = ltrim(preg_replace('/[^0-9]/', '', $rawNumber), '0');
            if ($cleanNum !== '') {
                $code = 'T' . str_pad($cleanNum, 3, '0', STR_PAD_LEFT);
                $table = Table::where('table_code', $code)->orWhere('id', $code)->first();
                if (!$table) {
                    $table = Table::where('label', 'like', "%{$cleanNum}%")->first();
                }
            }
            if (!$table) {
                $table = Table::where('label', 'like', "%{$rawNumber}%")->first();
            }
        }

        // 3. Fallbacks
        if (!$table) {
            if ($deliveryType === 'counter') {
                $table = Table::where('is_active', true)->first();
            } else {
                return response()->json([
                    'error' => [
                        'code' => 'TABLE_REQUIRED',
                        'message' => 'Silakan pilih atau masukkan nomor meja Anda untuk pengantaran.',
                    ],
                ], 422);
            }
        }

        if ($table && !$table->is_active && $deliveryType === 'table') {
            return response()->json([
                'error' => ['code' => 'TABLE_INACTIVE', 'message' => 'Meja yang dipilih sedang tidak aktif'],
            ], 403);
        }

        // Verify products & calculate exact server-side prices
        $productIds = array_column($request->items, 'product_id');
        $products = Product::whereIn('id', $productIds)->whereNull('archived_at')->get()->keyBy('id');

        $orderItemsData = [];
        $grossSubtotal = 0;

        foreach ($request->items as $it) {
            $p = $products->get($it['product_id']);
            if (!$p || $p->status === 'Inactive' || $p->archived_at !== null) {
                return response()->json([
                    'error' => [
                        'code' => 'ITEM_UNAVAILABLE',
                        'message' => "Produk tidak tersedia atau telah dinonaktifkan.",
                    ],
                ], 409);
            }

            if ($p->status === 'OutOfStock') {
                return response()->json([
                    'error' => [
                        'code' => 'ITEM_OUT_OF_STOCK',
                        'message' => "Produk {$p->name} sedang habis.",
                    ],
                ], 409);
            }

            if ($p->track_stock && $p->stock_qty < $it['quantity']) {
                return response()->json([
                    'error' => [
                        'code' => 'INSUFFICIENT_STOCK',
                        'message' => "Stok {$p->name} tidak mencukupi (Tersedia: {$p->stock_qty}).",
                    ],
                ], 409);
            }

            $lineSubtotal = $p->price_cents * $it['quantity'];
            $grossSubtotal += $lineSubtotal;

            $orderItemsData[] = [
                'product' => $p,
                'quantity' => $it['quantity'],
                'unit_price_cents' => $p->price_cents,
                'notes' => $it['notes'] ?? null,
            ];
        }

        $settings = Setting::instance();
        $serviceFeeCents = 0;
        if ($request->filled('delivery_type') && $deliveryType === 'table' && (bool)$settings->table_service_fee_enabled) {
            $serviceFeeCents = (int)($settings->table_service_fee_cents ?? 2000);
        }

        $discountCents = 0;
        $voucherCode = strtoupper(trim((string)$request->input('voucher_code', '')));
        if ($voucherCode === 'NEWUSER20K' || $voucherCode === 'NEW_USER_20K') {
            // "VOUCHER SEMUA PRODUK JADI 20RB (KHUSUS UNTUK PENGGUNA PERTAMA)"
            foreach ($orderItemsData as $itData) {
                if ($itData['unit_price_cents'] > 20000) {
                    $discountCents += ($itData['unit_price_cents'] - 20000) * $itData['quantity'];
                }
            }
        }
        $discountCents = min($discountCents, $grossSubtotal + $serviceFeeCents);

        $taxableAmount = max(0, $grossSubtotal - $discountCents);
        $taxCents = 0;
        if ($settings->tax_enabled && $settings->tax_rate_bps > 0) {
            $taxCents = MoneyAllocator::roundHalfUp(($taxableAmount * $settings->tax_rate_bps) / 10000);
        }
        $totalCents = max(0, ($grossSubtotal + $serviceFeeCents + $taxCents) - $discountCents);

        return DB::transaction(function () use ($table, $request, $orderItemsData, $grossSubtotal, $discountCents, $serviceFeeCents, $taxCents, $totalCents, $deliveryType) {
            // Generate order code e.g. Q-20261011-041
            $datePrefix = 'Q-' . now()->format('Ymd');
            $latest = Order::where('order_code', 'like', "{$datePrefix}-%")->count();
            $seq = str_pad($latest + 1, 3, '0', STR_PAD_LEFT);
            $orderCode = "{$datePrefix}-{$seq}";
            $orderId = 'ord-' . Str::random(12);
            $guestToken = 'gtok-' . Str::random(24);

            $custName = $request->input('customer_name') ?: 'Pelanggan';
            $customerId = $request->input('customer_id');
            if (!$customerId && $request->filled('customer_phone')) {
                $c = Customer::where('phone', preg_replace('/[^0-9+]/', '', $request->input('customer_phone')))->first();
                if ($c) $customerId = $c->id;
            }

            $tableLabelSnapshot = $deliveryType === 'counter'
                ? 'Ambil di Kasir'
                : ($request->filled('table_number')
                    ? (str_starts_with((string)$request->table_number, 'Meja') ? $request->table_number : 'Meja ' . $request->table_number)
                    : ($table ? $table->label : 'Meja'));

            $order = Order::create([
                'id' => $orderId,
                'order_code' => $orderCode,
                'status' => 'NotQueued',
                'payment_status' => 'Pending',
                'fulfillment_status' => 'NotQueued',
                'delivery_type' => $deliveryType,
                'service_fee_cents' => $serviceFeeCents,
                'table_id' => $table?->id,
                'table_code_snapshot' => $table?->table_code ?? 'COUNTER',
                'table_label_snapshot' => $tableLabelSnapshot,
                'customer_id' => $customerId,
                'customer_name' => $custName,
                'customer_name_snapshot' => $custName,
                'guest_token' => $guestToken,
                'created_by' => 'guest',
                'subtotal_cents' => $grossSubtotal,
                'discount_cents' => $discountCents,
                'tax_cents' => $taxCents,
                'total_cents' => $totalCents,
                'note' => $request->input('notes'),
            ]);

            foreach ($orderItemsData as $idx => $d) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $d['product']->id,
                    'name' => $d['product']->name,
                    'quantity' => $d['quantity'],
                    'unit_price_cents' => $d['unit_price_cents'],
                    'note' => $d['notes'],
                    'sort_order' => $idx,
                ]);
            }

            // Create QRIS Payment Attempt
            $attempt = PaymentProviderService::createAttempt($order, 'QRIS');

            // Create persistent TableNotification for staff/cashier
            $tableDisplay = $tableLabelSnapshot ?: ($table?->label ?: 'Meja / Counter');
            $itemsSummary = collect($orderItemsData)->map(fn($d) => "{$d['quantity']}x {$d['product']->name}")->join(', ');
            $shortItems = Str::limit($itemsSummary, 80);

            TableNotification::create([
                'id' => 'notif-' . Str::random(12),
                'event_id' => 'evt-order-' . $order->id . '-' . Str::random(6),
                'type' => 'order',
                'order_id' => $order->id,
                'title' => "🛎️ Pesanan Baru via Scan QR: {$tableDisplay}",
                'message' => "Pesanan {$order->order_code} ({$custName}) senilai Rp " . number_format($order->total_cents, 0, ',', '.') . ($shortItems ? " ({$shortItems})" : ''),
                'data' => [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'table_label' => $tableDisplay,
                    'customer_name' => $custName,
                    'total_cents' => $order->total_cents,
                    'items_count' => count($orderItemsData),
                    'items_summary' => $shortItems,
                    'url' => '/orders',
                    'type' => 'qr_order',
                ],
                'is_read' => false,
            ]);

            return response()->json([
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'guest_token' => $order->guest_token,
                'delivery_type' => $order->delivery_type,
                'service_fee_cents' => $order->service_fee_cents,
                'table_label' => $table?->label ?? $tableLabelSnapshot,
                'customer_name' => $order->customer_name,
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'tax_cents' => $order->tax_cents,
                'total_cents' => $order->total_cents,
                'payment_status' => $order->payment_status,
                'fulfillment_status' => $order->fulfillment_status,
                'payment_attempt' => [
                    'id' => $attempt->id,
                    'provider_reference' => $attempt->provider_reference,
                    'payment_method' => $attempt->payment_method,
                    'qr_string' => $attempt->qr_string,
                    'payment_url' => $attempt->payment_url,
                    'expires_at' => $attempt->expires_at,
                ],
            ], 201);
        });
    }

    /**
     * Get order details for guest (secured via guest_token or query param).
     * GET /api/v1/guest/orders/{id}
     */
    public function getOrder(Request $request, string $id): JsonResponse
    {
        $order = Order::with(['items', 'latestAttempt', 'receipts'])->find($id);

        if (!$order) {
            return response()->json(['error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        // Validate guest access token if provided
        $token = $request->header('X-Guest-Token') ?: $request->input('guest_token');
        if ($token && $order->guest_token && $token !== $order->guest_token) {
            return response()->json(['error' => ['code' => 'ORDER_ACCESS_DENIED', 'message' => 'Akses pesanan ditolak']], 403);
        }

        return response()->json([
            'order' => $order,
            'items' => $order->items,
            'latest_attempt' => $order->latestAttempt,
            'receipt' => $order->receipts->first(),
        ]);
    }

    /**
     * Guest requests server to check status with payment provider.
     * POST /api/v1/guest/orders/{id}/payment-status-check
     */
    public function paymentStatusCheck(Request $request, string $id): JsonResponse
    {
        $order = Order::with(['latestAttempt', 'items'])->find($id);
        if (!$order) {
            return response()->json(['error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'is_paid' => $order->payment_status === 'Paid',
            'message' => $order->payment_status === 'Paid'
                ? 'Pembayaran berhasil terverifikasi.'
                : 'Menunggu konfirmasi pembayaran dari provider.',
        ]);
    }

    /**
     * Sandbox simulation: simulate guest paying QRIS.
     * POST /api/v1/guest/orders/{id}/simulate-pay
     */
    public function simulatePay(Request $request, string $id): JsonResponse
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Pesanan tidak ditemukan']], 404);
        }

        $trxId = 'SIM-QRIS-' . Str::random(12);
        $result = PaymentProviderService::processVerifiedPayment(
            $order->id,
            $trxId,
            $order->total_cents,
            $order->latestAttempt?->id,
            ['simulated' => true, 'timestamp' => now()->toISOString()]
        );

        return response()->json($result);
    }

    /**
     * Get recent orders for guest (by table, customer phone, or order IDs list).
     * GET /api/v1/guest/orders
     */
    public function listOrders(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'latestAttempt'])->latest();

        $scope = $request->input('scope');

        if ($scope === 'account' && ($request->filled('customer_id') || $request->filled('customer_phone'))) {
            $query->where(function ($q) use ($request) {
                if ($request->filled('customer_id')) {
                    $q->where('customer_id', $request->customer_id);
                }
                if ($request->filled('customer_phone')) {
                    $phone = preg_replace('/[^0-9+]/', '', (string)$request->customer_phone);
                    $c = Customer::where('phone', $phone)->first();
                    if (!$c) {
                        $digits = preg_replace('/[^0-9]/', '', $phone);
                        if (str_starts_with($digits, '62') && strlen($digits) >= 10) {
                            $alt = '0' . substr($digits, 2);
                            $c = Customer::where('phone', $alt)->orWhere('phone', 'like', "%{$alt}%")->first();
                        } elseif (str_starts_with($digits, '0') && strlen($digits) >= 9) {
                            $alt = '62' . substr($digits, 1);
                            $c = Customer::where('phone', $alt)->orWhere('phone', '+' . $alt)->orWhere('phone', 'like', "%{$alt}%")->first();
                        }
                    }
                    if ($c) {
                        $q->orWhere('customer_id', $c->id);
                    }
                    $q->orWhere('note', 'like', "%{$phone}%");
                }
            });
        } else {
            if ($request->filled('order_ids')) {
                $ids = is_array($request->order_ids) ? $request->order_ids : explode(',', (string)$request->order_ids);
                $ids = array_filter(array_map('trim', $ids));
                if (!empty($ids)) {
                    $query->where(function ($q) use ($ids, $request) {
                        $q->whereIn('id', $ids);
                        if ($request->filled('table_id')) {
                            $q->orWhere('table_id', $request->table_id);
                        }
                    });
                } elseif ($request->filled('table_id')) {
                    $query->where('table_id', $request->table_id);
                }
            } elseif ($request->filled('table_id')) {
                $query->where('table_id', $request->table_id);
            }

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('customer_phone')) {
                $phone = preg_replace('/[^0-9+]/', '', (string)$request->customer_phone);
                $c = Customer::where('phone', $phone)->first();
                if ($c) {
                    $query->where(function ($w) use ($c, $phone) {
                        $w->where('customer_id', $c->id)
                          ->orWhere('note', 'like', "%{$phone}%");
                    });
                }
            }
        }

        $orders = $query->limit(50)->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }
}
