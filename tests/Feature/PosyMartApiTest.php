<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PosReturn;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosyMartApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminToken;
    protected string $cashierToken;
    protected User $adminUser;
    protected User $cashierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::where('role', 'Admin')->where('is_active', true)->firstOrFail();
        $this->cashierUser = User::where('role', 'Cashier')->where('is_active', true)->firstOrFail();

        $this->adminToken = $this->adminUser->createToken('admin-test')->plainTextToken;
        $this->cashierToken = $this->cashierUser->createToken('cashier-test')->plainTextToken;
    }

    public function test_auth_login_and_me(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->adminUser->email,
            'password' => 'posymart-demo',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'role'],
            ]);

        $token = $response->json('token');

        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('user.id', $this->adminUser->id);
    }

    public function test_products_and_categories_listing(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'sku', 'name', 'price_cents', 'stock_qty']
                ]
            ]);

        $catResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/v1/categories');

        $catResponse->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_walk_in_checkout_with_idempotency_and_unit_allocations(): void
    {
        $product = Product::where('stock_qty', '>', 5)->firstOrFail();
        $initialStock = $product->stock_qty;

        // 1. Put item in draft
        $draftResp = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/sales/draft/items', [
                'productId' => $product->id,
                'quantity' => 2,
            ]);

        $draftResp->assertStatus(200);

        // 2. Checkout as Walk-in (customerId is null)
        $idempotencyKey = 'idem-test-' . uniqid();
        $headers = [
            'Authorization' => 'Bearer ' . $this->cashierToken,
            'Idempotency-Key' => $idempotencyKey,
        ];

        $payPayload = [
            'customerId' => null, // Walk-in
            'paymentMethod' => 'cash',
            'amountTendered' => ($product->price_cents * 2) + 50000,
            'notes' => 'Test Walk-in Sale',
        ];

        $payResp = $this->withHeaders($headers)->postJson('/api/v1/sales/draft/pay', $payPayload);
        $payResp->assertStatus(201)
            ->assertJsonPath('sale.customerId', null)
            ->assertJsonPath('sale.payment_method', 'Cash');

        $saleId = $payResp->json('sale.id');

        // Verify product stock decremented
        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->stock_qty);

        // Verify sale unit allocations in DB
        $sale = Sale::with(['items.unitAllocations'])->findOrFail($saleId);
        $this->assertCount(1, $sale->items);
        $this->assertCount(2, $sale->items->first()->unitAllocations);

        // Invariant: sum of unit net = item net
        $item = $sale->items->first();
        $sumUnitNet = $item->unitAllocations->sum('net_cents');
        $this->assertEquals($item->net_subtotal_cents, $sumUnitNet);

        // 3. Test Idempotency replay with same key
        $replayResp = $this->withHeaders($headers)->postJson('/api/v1/sales/draft/pay', $payPayload);
        $replayResp->assertStatus(201);
        $this->assertEquals($saleId, $replayResp->json('sale.id'));
    }

    public function test_sale_void_restores_inventory(): void
    {
        $product = Product::where('stock_qty', '>', 5)->firstOrFail();
        $initialStock = $product->stock_qty;

        // Create a sale directly
        $sale = Sale::create([
            'id' => 'TX-TEST-VOID',
            'tx_code' => 'TX-TEST-VOID',
            'customer_name_snapshot' => 'Walk-in Customer',
            'cashier_id' => $this->adminUser->id,
            'cashier_name_snapshot' => $this->adminUser->name,
            'status' => 'Completed',
            'payment_method' => 'Cash',
            'subtotal_cents' => $product->price_cents * 2,
            'discount_cents' => 0,
            'taxable_subtotal_cents' => $product->price_cents * 2,
            'tax_cents' => 0,
            'total_cents' => $product->price_cents * 2,
            'paid_cents' => $product->price_cents * 2,
            'change_cents' => 0,
        ]);

        $sale->items()->create([
            'id' => 'si-TX-TEST-VOID-1',
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 2,
            'unit_price_cents' => $product->price_cents,
            'gross_subtotal_cents' => $product->price_cents * 2,
            'allocated_discount_cents' => 0,
            'net_subtotal_cents' => $product->price_cents * 2,
            'allocated_tax_cents' => 0,
            'total_cents' => $product->price_cents * 2,
            'sort_order' => 0,
        ]);

        $product->decrement('stock_qty', 2);
        $this->assertEquals($initialStock - 2, $product->fresh()->stock_qty);

        // Admin voids the sale
        $voidResp = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/api/v1/sales/{$sale->id}/void", [
                'reason' => 'Customer changed mind',
            ]);

        $voidResp->assertStatus(200)
            ->assertJsonPath('sale.status', 'Voided');

        // Stock restored
        $this->assertEquals($initialStock, $product->fresh()->stock_qty);
    }

    public function test_orders_creation_and_transition(): void
    {
        $product = Product::firstOrFail();

        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/orders', [
                'customerName' => 'Budi Tester',
                'customerPhone' => '081299998888',
                'deliveryType' => 'takeaway',
                'paymentMethod' => 'qris',
                'items' => [
                    [
                        'productId' => $product->id,
                        'quantity' => 1,
                    ]
                ],
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('order.status', 'Pending');

        $orderId = $createResp->json('order.id');

        // Transition to preparing
        $transResp = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson("/api/v1/orders/{$orderId}/transition", [
                'status' => 'preparing',
                'note' => 'Cooking in progress',
            ]);

        $transResp->assertStatus(200)
            ->assertJsonPath('order.status', 'Preparing');

        // Transition to completed
        $transResp2 = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson("/api/v1/orders/{$orderId}/transition", [
                'status' => 'completed',
                'note' => 'Customer picked up',
            ]);

        $transResp2->assertStatus(200)
            ->assertJsonPath('order.status', 'Completed');
    }

    public function test_reports_summary_returns_correct_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/v1/reports/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'totalSales',
                'transactionCount',
                'netSales',
                'grossProfit',
                'averageBasket',
                'activeCashiers',
            ]);
    }

    public function test_rbac_cashier_cannot_manage_users_or_settings(): void
    {
        // Cashier attempts to update settings
        $settingsResp = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->putJson('/api/v1/settings', [
                'store_name' => 'Hacked Store',
            ]);

        $settingsResp->assertStatus(403);

        // Cashier attempts to create user
        $userCreateResp = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'secret123',
                'role' => 'Cashier',
            ]);

        $userCreateResp->assertStatus(403);
    }
}
