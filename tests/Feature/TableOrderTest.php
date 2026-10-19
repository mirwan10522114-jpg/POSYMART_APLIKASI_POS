<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Table;
use App\Models\TableNotification;
use App\Models\TableQr;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        \App\Models\Setting::instance()->update(['tax_enabled' => false]);
    }

    /**
     * Q01: Verify minimum 50 table records T001..T050 and 50 QRs QR-T001..QR-T050 exist.
     */
    public function test_q01_minimum_50_tables_and_qrs_seeded(): void
    {
        $this->assertGreaterThanOrEqual(50, Table::count());
        $this->assertGreaterThanOrEqual(50, TableQr::count());

        for ($i = 1; $i <= 50; $i++) {
            $numPad = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $numPad3 = str_pad((string)$i, 3, '0', STR_PAD_LEFT);

            $this->assertDatabaseHas('tables', [
                'table_code' => "T{$numPad3}",
                'label' => "Meja {$numPad}",
            ]);

            $this->assertDatabaseHas('table_qrs', [
                'id' => "QR-T{$numPad3}",
                'status' => 'Active',
            ]);
        }
    }

    /**
     * Q02: Scanning QR token correctly resolves table context without cross-mapping.
     */
    public function test_q02_qr_resolves_exact_table_context(): void
    {
        // Meja 17 canonical resolution
        $res = $this->getJson('/api/v1/qr/qr-meja-17');
        $res->assertOk()
            ->assertJson([
                'valid' => true,
                'table_code' => 'T017',
                'table_label' => 'Meja 17',
            ]);

        // Meja 01
        $res1 = $this->getJson('/api/v1/qr/qr-meja-01');
        $res1->assertOk()
            ->assertJson([
                'valid' => true,
                'table_code' => 'T001',
                'table_label' => 'Meja 01',
            ]);
    }

    /**
     * Q03: Admin can add 51st table and beyond (50 is not hard limit).
     */
    public function test_q03_can_add_table_51_and_beyond(): void
    {
        $admin = User::where('role', 'Admin')->first();

        $res = $this->actingAs($admin)->postJson('/api/v1/admin/tables', [
            'label' => 'Meja 51',
            'area' => 'Outdoor Garden',
        ]);

        $res->assertCreated();
        $this->assertDatabaseHas('tables', ['label' => 'Meja 51']);
        $this->assertDatabaseHas('table_qrs', ['id' => 'QR-T051']);
    }

    /**
     * Q05: Scan inactive / revoked QR rejects checkout and returns guidance.
     */
    public function test_q05_inactive_qr_rejects_session_and_checkout(): void
    {
        $qr = TableQr::where('id', 'QR-T020')->first();
        $qr->update(['status' => 'Inactive']);

        $res = $this->getJson("/api/v1/qr/{$qr->public_token}");
        $res->assertStatus(403)
            ->assertJsonPath('error.code', 'QR_INACTIVE');

        $sessionRes = $this->postJson('/api/v1/guest-sessions', [
            'public_token' => $qr->public_token,
        ]);
        $sessionRes->assertStatus(403);
    }

    /**
     * Q06: Rotate QR revokes old token and issues v2 token.
     */
    public function test_q06_rotate_qr_revokes_old_and_creates_v2(): void
    {
        $admin = User::where('role', 'Admin')->first();
        $table = Table::where('table_code', 'T008')->first();
        $oldQr = $table->activeQr;

        $res = $this->actingAs($admin)->postJson("/api/v1/admin/table-qrs/{$table->id}/rotate");
        $res->assertOk();

        // Old QR should now be Revoked
        $this->assertDatabaseHas('table_qrs', [
            'id' => $oldQr->id,
            'status' => 'Revoked',
        ]);

        // New QR v2 should exist and be Active
        $this->assertDatabaseHas('table_qrs', [
            'table_id' => $table->id,
            'version' => 2,
            'status' => 'Active',
        ]);
    }

    /**
     * Q07 & Q08: Guest can browse menu, checkout without account, and receive order with guest token.
     */
    public function test_q07_guest_checkout_without_account_canonical_meja_17(): void
    {
        $table17 = Table::where('table_code', 'T017')->first();
        $cap = Product::find('p-cappuccino');
        $bagel = Product::find('p-bagel');

        // Canonical order: 2x Cappuccino (2x 25.000) + 1x Bagel (1x 18.000) = Rp 68.000
        $res = $this->postJson('/api/v1/guest/checkouts', [
            'table_id' => $table17->id,
            'customer_name' => 'Rani',
            'items' => [
                ['product_id' => $cap->id, 'quantity' => 2, 'notes' => 'Less sugar'],
                ['product_id' => $bagel->id, 'quantity' => 1, 'notes' => 'Dipanaskan'],
            ],
        ]);

        $res->assertCreated()
            ->assertJsonPath('total_cents', 68000)
            ->assertJsonPath('table_label', 'Meja 17')
            ->assertJsonPath('customer_name', 'Rani')
            ->assertJsonPath('payment_status', 'Pending')
            ->assertJsonPath('fulfillment_status', 'NotQueued');

        $orderId = $res->json('order_id');
        $guestToken = $res->json('guest_token');

        // Order tracking secured by guest token
        $trackingRes = $this->withHeader('X-Guest-Token', $guestToken)->getJson("/api/v1/guest/orders/{$orderId}");
        $trackingRes->assertOk()
            ->assertJsonPath('order.order_code', $res->json('order_code'))
            ->assertJsonPath('order.total_cents', 68000);

        // Access denied if wrong token passed
        $badTokenRes = $this->withHeader('X-Guest-Token', 'wrong-token')->getJson("/api/v1/guest/orders/{$orderId}");
        $badTokenRes->assertStatus(403);
    }

    /**
     * Q10: Pending payment NEVER enters production queue (fulfillment_status = NotQueued).
     */
    public function test_q10_pending_payment_does_not_enter_production_queue(): void
    {
        $table = Table::first();
        $p = Product::first();

        $res = $this->postJson('/api/v1/guest/checkouts', [
            'table_id' => $table->id,
            'items' => [
                ['product_id' => $p->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::find($res->json('order_id'));
        $this->assertEquals('Pending', $order->payment_status);
        $this->assertEquals('NotQueued', $order->fulfillment_status);

        // Staff orders query for active queue (New, Accepted, Preparing, Ready) must NOT contain this pending order
        $admin = User::first();
        $queueRes = $this->actingAs($admin)->getJson('/api/v1/admin/orders?active_only=true');
        $queueRes->assertOk();

        $orderIds = array_column($queueRes->json('data'), 'id');
        $this->assertNotContains($order->id, $orderIds);
    }

    /**
     * Q13 & Q14: Verified payment creates 1 Sale, decrements stock once, creates 1 New order & notification.
     * Duplicate callback is strictly idempotent and does NOT duplicate effects.
     */
    public function test_q13_and_q14_verified_payment_creates_sale_and_deduplicates(): void
    {
        $table = Table::where('table_code', 'T003')->first();
        $cap = Product::find('p-cappuccino');
        $initialStock = $cap->stock_qty;

        $checkoutRes = $this->postJson('/api/v1/guest/checkouts', [
            'table_id' => $table->id,
            'customer_name' => 'Budi',
            'items' => [
                ['product_id' => $cap->id, 'quantity' => 2],
            ],
        ]);
        $orderId = $checkoutRes->json('order_id');
        $order = Order::find($orderId);

        $initialSalesCount = Sale::count();

        // 1. Send verified webhook payment notification
        $webhookPayload = [
            'order_id' => $orderId,
            'transaction_status' => 'settlement',
            'transaction_id' => 'TRX-PAY-TEST-001',
            'gross_amount' => 50000,
        ];

        $webhookRes1 = $this->postJson('/api/v1/webhooks/payments/qris', $webhookPayload);
        $webhookRes1->assertOk()
            ->assertJsonPath('success', true);

        // Invariant 2 & 3: Order now Paid + New
        $order->refresh();
        $this->assertEquals('Paid', $order->payment_status);
        $this->assertEquals('New', $order->fulfillment_status);

        // Exactly 1 new Sale created
        $this->assertEquals($initialSalesCount + 1, Sale::count());
        $this->assertDatabaseHas('sales', [
            'source_order_id' => $orderId,
            'status' => 'Completed',
        ]);

        // Stock decremented by 2
        $cap->refresh();
        $this->assertEquals($initialStock - 2, $cap->stock_qty);

        // Persistent notification created
        $this->assertDatabaseHas('table_notifications', [
            'order_id' => $orderId,
            'type' => 'payment',
        ]);

        // Q14: Second identical webhook callback must be idempotent
        $webhookRes2 = $this->postJson('/api/v1/webhooks/payments/qris', $webhookPayload);
        $webhookRes2->assertOk();

        // Sale count and stock must NOT change again!
        $this->assertEquals($initialSalesCount + 1, Sale::count());
        $cap->refresh();
        $this->assertEquals($initialStock - 2, $cap->stock_qty);
    }

    /**
     * Q24, Q25, Q30: Staff lifecycle transitions New -> Accepted -> Preparing -> Ready -> Served.
     */
    public function test_q24_order_fulfillment_lifecycle_transitions(): void
    {
        $admin = User::first();
        $order = Order::where('order_code', 'Q-20261011-041')->first();
        $this->assertEquals('New', $order->fulfillment_status);

        // 1. Accept
        $r1 = $this->actingAs($admin)->postJson("/api/v1/admin/orders/{$order->id}/transitions", ['action' => 'accept']);
        $r1->assertOk();
        $this->assertEquals('Accepted', $order->fresh()->fulfillment_status);

        // 2. Start prepare
        $r2 = $this->actingAs($admin)->postJson("/api/v1/admin/orders/{$order->id}/transitions", ['action' => 'start-prepare']);
        $r2->assertOk();
        $this->assertEquals('Preparing', $order->fresh()->fulfillment_status);

        // 3. Mark ready
        $r3 = $this->actingAs($admin)->postJson("/api/v1/admin/orders/{$order->id}/transitions", ['action' => 'mark-ready']);
        $r3->assertOk();
        $this->assertEquals('Ready', $order->fresh()->fulfillment_status);

        // 4. Mark served
        $initialSalesCount = Sale::count();
        $r4 = $this->actingAs($admin)->postJson("/api/v1/admin/orders/{$order->id}/transitions", ['action' => 'mark-served']);
        $r4->assertOk();
        $this->assertEquals('Served', $order->fresh()->fulfillment_status);

        // Q30: Mark Served does NOT create a second sale
        $this->assertEquals($initialSalesCount, Sale::count());
    }

    /**
     * Q31: Refund flow records reason, updates status to Refunded and Cancelled.
     */
    public function test_q31_refund_process_and_approval(): void
    {
        $admin = User::where('role', 'Admin')->first();
        $order = Order::where('order_code', 'Q-20261011-038')->first();

        // Request refund
        $reqRes = $this->actingAs($admin)->postJson("/api/v1/admin/orders/{$order->id}/refund-requests", [
            'reason' => 'Bahan habis',
            'amount_cents' => $order->total_cents,
        ]);
        $reqRes->assertCreated();
        $refundId = $reqRes->json('id');

        // Approve refund
        $appRes = $this->actingAs($admin)->postJson("/api/v1/admin/refunds/{$refundId}/approve");
        $appRes->assertOk();

        $order->refresh();
        $this->assertEquals('Refunded', $order->payment_status);
        $this->assertEquals('Cancelled', $order->fulfillment_status);
    }

    /**
     * User Request: Delivery options (Diantar ke Meja vs Ambil di Kasir) with admin configurable fee.
     */
    public function test_table_delivery_fee_and_counter_pickup_options(): void
    {
        $table17 = Table::where('table_code', 'T017')->first();
        $cap = Product::find('p-cappuccino');

        // Configure table service fee enabled with 3000 cents
        $settings = Setting::instance();
        $settings->update([
            'table_service_fee_enabled' => true,
            'table_service_fee_cents' => 3000,
        ]);

        // 1. Checkout with delivery_type = 'table' -> should include Rp 3.000 fee
        $resTable = $this->postJson('/api/v1/guest/checkouts', [
            'table_id' => $table17->id,
            'customer_name' => 'Mirwan',
            'delivery_type' => 'table',
            'items' => [
                ['product_id' => $cap->id, 'quantity' => 1],
            ],
        ]);

        $resTable->assertCreated()
            ->assertJsonPath('delivery_type', 'table')
            ->assertJsonPath('service_fee_cents', 3000)
            ->assertJsonPath('total_cents', 28000); // 25.000 + 3.000 = 28.000

        // 2. Checkout with delivery_type = 'counter' -> fee should be 0
        $resCounter = $this->postJson('/api/v1/guest/checkouts', [
            'table_id' => $table17->id,
            'customer_name' => 'Mirwan',
            'delivery_type' => 'counter',
            'items' => [
                ['product_id' => $cap->id, 'quantity' => 1],
            ],
        ]);

        $resCounter->assertCreated()
            ->assertJsonPath('delivery_type', 'counter')
            ->assertJsonPath('service_fee_cents', 0)
            ->assertJsonPath('total_cents', 25000); // 25.000 + 0 = 25.000

        // 3. User Request: "Pesanan Saya" tracking list endpoint
        $listRes = $this->getJson("/api/v1/guest/orders?table_id={$table17->id}");
        $listRes->assertOk();
        $this->assertNotEmpty($listRes->json('orders'));
    }

    /**
     * User Request: Customer login via phone number and password, with full account order history retrieval.
     */
    public function test_customer_login_by_phone_and_account_order_history(): void
    {
        $customer = Customer::create([
            'id' => 'CUS-TEST-LOGIN',
            'name' => 'Siti Rahma',
            'phone' => '081298765432',
            'password' => 'secret123',
            'email' => 'siti@example.com',
            'joined_at' => now(),
        ]);

        $table01 = Table::where('table_code', 'T001')->first();
        $table02 = Table::where('table_code', 'T002')->first();
        $cap = Product::find('p-cappuccino');

        // Order at Table 01
        $this->postJson('/api/v1/guest/checkouts', [
            'table_id' => $table01->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'items' => [['product_id' => $cap->id, 'quantity' => 1]],
        ]);

        // Order at Table 02
        $this->postJson('/api/v1/guest/checkouts', [
            'table_id' => $table02->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'items' => [['product_id' => $cap->id, 'quantity' => 2]],
        ]);

        // 1. Login with exact phone and correct password -> 200 OK
        $loginRes1 = $this->postJson('/api/v1/guest/login', [
            'phone' => '081298765432',
            'password' => 'secret123',
        ]);
        $loginRes1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.id', 'CUS-TEST-LOGIN')
            ->assertJsonPath('customer.name', 'Siti Rahma');

        // 2. Login with alternate international prefix format (+6281298765432) and password -> 200 OK
        $loginRes2 = $this->postJson('/api/v1/guest/login', [
            'phone' => '+62 812-9876-5432',
            'password' => 'secret123',
        ]);
        $loginRes2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.id', 'CUS-TEST-LOGIN');

        // 3. Login with WRONG password -> 401 Unauthorized
        $loginResWrongPass = $this->postJson('/api/v1/guest/login', [
            'phone' => '081298765432',
            'password' => 'wrongpass',
        ]);
        $loginResWrongPass->assertStatus(401)
            ->assertJsonPath('success', false);

        // 4. Login with unknown phone -> 404 Not Found
        $loginResFail = $this->postJson('/api/v1/guest/login', [
            'phone' => '089999999999',
            'password' => 'secret123',
        ]);
        $loginResFail->assertNotFound()
            ->assertJsonPath('success', false);

        // 5. Register new customer with password and then login
        $regRes = $this->postJson('/api/v1/guest/register', [
            'name' => 'Dimas Arya',
            'phone' => '081377889900',
            'password' => 'dimaspass2026',
            'email' => 'dimas.arya@example.com',
        ]);
        $regRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.name', 'Dimas Arya');

        $loginDimas = $this->postJson('/api/v1/guest/login', [
            'phone' => '081377889900',
            'password' => 'dimaspass2026',
        ]);
        $loginDimas->assertOk()
            ->assertJsonPath('customer.name', 'Dimas Arya');

        // 6. Retrieve complete account order history across both tables
        $historyRes = $this->getJson("/api/v1/guest/orders?scope=account&customer_id={$customer->id}");
        $historyRes->assertOk();
        $historyOrders = $historyRes->json('orders');
        $this->assertCount(2, $historyOrders);
    }

    /**
     * User Request: Universal Portal (Single QR Code for whole cafe) routes render properly.
     */
    public function test_universal_portal_routes_render_successfully(): void
    {
        $resOrder = $this->get('/order');
        $resOrder->assertOk();
        $resOrder->assertViewIs('pages.guest-order');
        $resOrder->assertViewHas('isUniversalPortal', true);
        $resOrder->assertViewHas('table', null);

        $resPesan = $this->get('/pesan');
        $resPesan->assertOk();
        $resPesan->assertViewIs('pages.guest-order');

        $resM = $this->get('/m');
        $resM->assertOk();
        $resM->assertViewIs('pages.guest-order');
    }

    /**
     * User Request: Manual Table Number input at checkout.
     * Buyer inputs table number manually (e.g. "17") or picks Counter.
     */
    public function test_checkout_with_manual_table_number_input(): void
    {
        $cap = Product::find('p-cappuccino');

        // 1. Customer types manual table number "17" without specifying table_id
        $resManual = $this->postJson('/api/v1/guest/checkouts', [
            'table_number' => '17',
            'delivery_type' => 'table',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081233445566',
            'items' => [
                ['product_id' => $cap->id, 'quantity' => 1],
            ],
        ]);

        $resManual->assertCreated()
            ->assertJsonPath('delivery_type', 'table');

        $orderId = $resManual->json('order_id');
        $order = Order::find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('Meja 17', $order->table_label_snapshot);
        $this->assertEquals('T017', $order->table_id);

        // 2. Customer selects Counter delivery (No table number needed)
        $resCounter = $this->postJson('/api/v1/guest/checkouts', [
            'delivery_type' => 'counter',
            'customer_name' => 'Budi Kasir',
            'items' => [
                ['product_id' => $cap->id, 'quantity' => 1],
            ],
        ]);

        $resCounter->assertCreated()
            ->assertJsonPath('delivery_type', 'counter')
            ->assertJsonPath('service_fee_cents', 0);

        $counterOrder = Order::find($resCounter->json('order_id'));
        $this->assertNotNull($counterOrder);
        $this->assertEquals('Ambil di Kasir', $counterOrder->table_label_snapshot);
    }

    /**
     * Q15: Counter delivery order accurately computes tax per admin settings and records in sales & reports.
     */
    public function test_q15_counter_delivery_includes_tax_per_settings_and_records_in_sales_and_reports(): void
    {
        $settings = Setting::instance();
        $settings->update([
            'tax_enabled' => true,
            'tax_rate_bps' => 1100, // 11% PPN
            'tax_name' => 'PPN',
            'table_service_fee_enabled' => true,
            'table_service_fee_cents' => 2000,
        ]);

        $prod = Product::where('status', 'Active')->first();
        $this->assertNotNull($prod);

        // 1. Verify getMenu returns tax settings including tax_name
        $menuRes = $this->getJson('/api/v1/guest/menu');
        $menuRes->assertOk()
            ->assertJsonPath('settings.tax_enabled', true)
            ->assertJsonPath('settings.tax_rate_bps', 1100)
            ->assertJsonPath('settings.tax_name', 'PPN');

        // 2. Checkout with delivery_type: counter (Ambil di Kasir)
        $subtotal = $prod->price_cents * 2;
        $expectedTax = (int) round(($subtotal * 1100) / 10000);
        $expectedTotal = $subtotal + $expectedTax; // No delivery fee for counter

        $res = $this->postJson('/api/v1/guest/checkouts', [
            'delivery_type' => 'counter',
            'customer_name' => 'Siti Kasir',
            'items' => [
                ['product_id' => $prod->id, 'quantity' => 2],
            ],
        ]);

        $res->assertCreated();
        $orderId = $res->json('order_id');
        $order = Order::find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('counter', $order->delivery_type);
        $this->assertEquals(0, $order->service_fee_cents);
        $this->assertEquals($subtotal, $order->subtotal_cents);
        $this->assertEquals($expectedTax, $order->tax_cents);
        $this->assertEquals($expectedTotal, $order->total_cents);

        // 3. Simulate QRIS payment
        $payRes = $this->postJson("/api/v1/guest/orders/{$orderId}/simulate-pay");
        $payRes->assertOk();

        // 4. Verify Sale is created with correct tax and snapshots
        $sale = Sale::where('source_order_id', $orderId)->first();
        $this->assertNotNull($sale);
        $this->assertEquals($expectedTax, $sale->tax_cents);
        $this->assertEquals($expectedTotal, $sale->total_cents);
        $this->assertEquals('PPN', $sale->tax_name_snapshot);
        $this->assertEquals(1100, $sale->tax_rate_bps_snapshot);

        // 5. Verify Report reflects this tax
        $user = User::where('role', 'Admin')->first();
        $this->assertNotNull($user);
        $reportRes = $this->actingAs($user)->getJson('/api/v1/reports/summary?range=today');
        $reportRes->assertOk();
        $this->assertGreaterThanOrEqual($expectedTax, $reportRes->json('tax_cents'));
    }
}
