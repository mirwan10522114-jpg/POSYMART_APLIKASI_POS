<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnitAllocation;
use App\Models\Table;
use App\Models\TableNotification;
use App\Models\TableQr;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TableOrderSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Cappuccino and Bagel prices match PRD canonical specs (Rp 25.000 & Rp 18.000)
        Product::where('id', 'p-cappuccino')->update([
            'price_cents' => 25000,
            'description' => 'Espresso dengan susu hangat dan foam lembut.',
            'image_url' => '/images/products/p-cappuccino.jpg',
        ]);
        Product::where('id', 'p-bagel')->update([
            'price_cents' => 18000,
            'description' => 'Bagel panggang segar dengan kulit keemasan.',
            'image_url' => '/images/products/p-bagel.jpg',
        ]);
        foreach (Product::all() as $prod) {
            if (!$prod->image_url) {
                $prod->update(['image_url' => "/images/products/{$prod->id}.jpg"]);
            }
        }

        // 2. Seed exactly 50 Tables (T001 - T050) & 50 Table QRs (QR-T001 - QR-T050)
        $areas = [
            [1, 20, 'Indoor Utama'],
            [21, 35, 'Outdoor Garden'],
            [36, 50, 'Lantai 2 Mezzanine'],
        ];

        for ($i = 1; $i <= 50; $i++) {
            $numPad = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $numPad3 = str_pad((string)$i, 3, '0', STR_PAD_LEFT);

            $tableCode = "T{$numPad3}";
            $tableLabel = "Meja {$numPad}";
            $qrId = "QR-T{$numPad3}";
            $publicToken = "qr-meja-{$numPad}";

            $areaName = 'Indoor Utama';
            foreach ($areas as $range) {
                if ($i >= $range[0] && $i <= $range[1]) {
                    $areaName = $range[2];
                    break;
                }
            }

            $table = Table::updateOrCreate(
                ['id' => $tableCode],
                [
                    'table_code' => $tableCode,
                    'label' => $tableLabel,
                    'area' => $areaName,
                    'is_active' => true,
                ]
            );

            TableQr::updateOrCreate(
                ['id' => $qrId],
                [
                    'table_id' => $table->id,
                    'public_token' => $publicToken,
                    'version' => 1,
                    'status' => 'Active',
                    'scan_count' => rand(5, 45),
                    'last_printed_at' => Carbon::create(2026, 10, 11, 8, 0, 0),
                ]
            );
        }

        // 3. Seed Canonical Meja 17 Order (Rani - Q-20261011-041)
        // Spec: Meja 17, Rani, 2 Cappuccino (@25.000) + 1 Bagel (@18.000) = Rp 68.000
        $table17 = Table::where('table_code', 'T017')->first();
        $pCap = Product::find('p-cappuccino');
        $pBagel = Product::find('p-bagel');

        $now = Carbon::create(2026, 10, 11, 10, 15, 0);

        $orderRani = Order::updateOrCreate(
            ['order_code' => 'Q-20261011-041'],
            [
                'id' => 'ord-q-20261011-041',
                'order_code' => 'Q-20261011-041',
                'table_id' => $table17->id,
                'table_number' => '17',
                'table_code_snapshot' => 'T017',
                'table_label_snapshot' => 'Meja 17',
                'customer_name' => 'Rani',
                'customer_name_snapshot' => 'Rani',
                'guest_token' => 'guest-tok-rani-meja-17',
                'customer_id' => null,
                'created_by' => 'guest',
                'status' => 'Pending',
                'payment_status' => 'Paid',
                'fulfillment_status' => 'New',
                'subtotal_cents' => 68000,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 68000,
                'note' => 'Tolong sedotan kertas',
                'created_at' => $now->copy()->subMinutes(2),
                'updated_at' => $now,
            ]
        );

        // Order Items
        $orderRani->items()->delete();
        $orderRani->items()->create([
            'order_id' => $orderRani->id,
            'product_id' => $pCap->id,
            'name' => 'Cappuccino',
            'quantity' => 2,
            'unit_price_cents' => 25000,
            'note' => 'Less sugar',
            'sort_order' => 1,
        ]);
        $orderRani->items()->create([
            'order_id' => $orderRani->id,
            'product_id' => $pBagel->id,
            'name' => 'Bagel',
            'quantity' => 1,
            'unit_price_cents' => 18000,
            'note' => 'Tolong dipanaskan',
            'sort_order' => 2,
        ]);

        // Payment Attempt
        $attemptRani = PaymentAttempt::updateOrCreate(
            ['provider_reference' => 'QRIS-REF-20261011-041'],
            [
                'id' => 'att-q-20261011-041-01',
                'order_id' => $orderRani->id,
                'attempt_number' => 1,
                'provider' => 'qris_simulated',
                'provider_reference' => 'QRIS-REF-20261011-041',
                'payment_method' => 'QRIS',
                'amount_cents' => 68000,
                'status' => 'Paid',
                'qr_string' => '00020101021226600016ID.CO.QRIS.WWW011893600999000001041021000000000000303UME51440014ID.LINKAJA.WWW02159360099900000105204581253033605802ID5913POSYMART CAFE6013JAKARTA TIMUR61051341062070703A016304ABCD',
                'expires_at' => $now->copy()->addMinutes(15),
                'verified_at' => $now,
            ]
        );

        // Payment Receipt
        PaymentReceipt::updateOrCreate(
            ['provider_transaction_id' => 'TRX-QRIS-20261011-041'],
            [
                'id' => 'rcpt-q-20261011-041',
                'order_id' => $orderRani->id,
                'payment_attempt_id' => $attemptRani->id,
                'amount_cents' => 68000,
                'payment_method' => 'QRIS',
                'verified_at' => $now,
                'raw_payload' => [
                    'provider' => 'QRIS',
                    'issuer' => 'BCA QRIS',
                    'rrn' => '20261011987654',
                    'merchant_id' => 'POSYMART-M01',
                ],
            ]
        );

        // Sale corresponding to Rani order
        $saleRani = Sale::updateOrCreate(
            ['tx_code' => 'SALE-Q-20261011-041'],
            [
                'id' => 'sale-q-20261011-041',
                'source_order_id' => $orderRani->id,
                'customer_id' => null,
                'customer_name_snapshot' => 'Rani (Meja 17)',
                'cashier_id' => 'user-system',
                'cashier_name_snapshot' => 'Sistem QR Meja',
                'status' => 'Completed',
                'payment_method' => 'QRIS',
                'subtotal_cents' => 68000,
                'discount_cents' => 0,
                'taxable_subtotal_cents' => 68000,
                'tax_cents' => 0,
                'total_cents' => 68000,
                'paid_cents' => 68000,
                'change_cents' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $orderRani->update(['converted_sale_id' => $saleRani->id]);

        $saleRani->items()->delete();
        $saleItemCap = $saleRani->items()->create([
            'id' => 'si-rani-cap',
            'sale_id' => $saleRani->id,
            'product_id' => $pCap->id,
            'name' => 'Cappuccino',
            'quantity' => 2,
            'unit_price_cents' => 25000,
            'gross_subtotal_cents' => 50000,
            'allocated_discount_cents' => 0,
            'net_subtotal_cents' => 50000,
            'allocated_tax_cents' => 0,
            'total_cents' => 50000,
            'sort_order' => 1,
        ]);
        $saleItemBagel = $saleRani->items()->create([
            'id' => 'si-rani-bagel',
            'sale_id' => $saleRani->id,
            'product_id' => $pBagel->id,
            'name' => 'Bagel',
            'quantity' => 1,
            'unit_price_cents' => 18000,
            'gross_subtotal_cents' => 18000,
            'allocated_discount_cents' => 0,
            'net_subtotal_cents' => 18000,
            'allocated_tax_cents' => 0,
            'total_cents' => 18000,
            'sort_order' => 2,
        ]);

        SaleItemUnitAllocation::whereIn('sale_item_id', [$saleItemCap->id, $saleItemBagel->id])->delete();
        SaleItemUnitAllocation::create([
            'sale_item_id' => $saleItemCap->id,
            'unit_index' => 1,
            'net_cents' => 25000,
            'tax_cents' => 0,
            'total_cents' => 25000,
            'is_refunded' => false,
        ]);
        SaleItemUnitAllocation::create([
            'sale_item_id' => $saleItemCap->id,
            'unit_index' => 2,
            'net_cents' => 25000,
            'tax_cents' => 0,
            'total_cents' => 25000,
            'is_refunded' => false,
        ]);
        SaleItemUnitAllocation::create([
            'sale_item_id' => $saleItemBagel->id,
            'unit_index' => 1,
            'net_cents' => 18000,
            'tax_cents' => 0,
            'total_cents' => 18000,
            'is_refunded' => false,
        ]);

        // Notification for Rani order
        TableNotification::updateOrCreate(
            ['event_id' => 'ev-order-paid-q-20261011-041'],
            [
                'id' => 'notif-q-20261011-041',
                'type' => 'OrderPaid',
                'order_id' => $orderRani->id,
                'title' => 'Pesanan Baru Meja 17 (Rani)',
                'message' => 'Pesanan Q-20261011-041 telah dibayar via QRIS Rp 68.000.',
                'data' => [
                    'order_code' => 'Q-20261011-041',
                    'table_label' => 'Meja 17',
                    'customer_name' => 'Rani',
                    'items_count' => 3,
                    'total_cents' => 68000,
                ],
                'is_read' => false,
                'created_at' => $now,
            ]
        );

        // 4. Seed Additional Realistic Orders Across Statuses for Live Queue Testing
        $extraOrders = [
            [
                'order_code' => 'Q-20261011-038',
                'table_code' => 'T003',
                'table_label' => 'Meja 03',
                'customer_name' => 'Budi',
                'payment_status' => 'Paid',
                'fulfillment_status' => 'Accepted',
                'total_cents' => 76000,
                'accepted_at' => $now->copy()->subMinutes(12),
                'items' => [
                    ['product_id' => 'p-latte', 'name' => 'Latte', 'qty' => 2, 'price' => 38000],
                ],
            ],
            [
                'order_code' => 'Q-20261011-039',
                'table_code' => 'T008',
                'table_label' => 'Meja 08',
                'customer_name' => 'Dewi',
                'payment_status' => 'Paid',
                'fulfillment_status' => 'Preparing',
                'total_cents' => 84000,
                'accepted_at' => $now->copy()->subMinutes(20),
                'preparing_at' => $now->copy()->subMinutes(8),
                'items' => [
                    ['product_id' => 'p-mocha', 'name' => 'Mocha', 'qty' => 2, 'price' => 42000],
                ],
            ],
            [
                'order_code' => 'Q-20261011-040',
                'table_code' => 'T012',
                'table_label' => 'Meja 12',
                'customer_name' => 'Andi',
                'payment_status' => 'Paid',
                'fulfillment_status' => 'Ready',
                'total_cents' => 55000,
                'accepted_at' => $now->copy()->subMinutes(25),
                'preparing_at' => $now->copy()->subMinutes(15),
                'ready_at' => $now->copy()->subMinutes(2),
                'items' => [
                    ['product_id' => 'p-sandwich', 'name' => 'Sandwich', 'qty' => 1, 'price' => 55000],
                ],
            ],
            [
                'order_code' => 'Q-20261011-035',
                'table_code' => 'T005',
                'table_label' => 'Meja 05',
                'customer_name' => 'Siti',
                'payment_status' => 'Paid',
                'fulfillment_status' => 'Served',
                'total_cents' => 58000,
                'accepted_at' => $now->copy()->subMinutes(45),
                'preparing_at' => $now->copy()->subMinutes(35),
                'ready_at' => $now->copy()->subMinutes(20),
                'served_at' => $now->copy()->subMinutes(10),
                'items' => [
                    ['product_id' => 'p-americano', 'name' => 'Americano', 'qty' => 2, 'price' => 29000],
                ],
            ],
            [
                'order_code' => 'Q-20261011-042',
                'table_code' => 'T022',
                'table_label' => 'Meja 22',
                'customer_name' => 'Fajar',
                'payment_status' => 'Paid',
                'fulfillment_status' => 'ReviewRequired',
                'total_cents' => 65000,
                'items' => [
                    ['product_id' => 'p-caesar-salad', 'name' => 'Caesar Salad', 'qty' => 1, 'price' => 65000],
                ],
            ],
            // Invariant 1: Pending payment NEVER enters production queue (NotQueued)
            [
                'order_code' => 'Q-20261011-043',
                'table_code' => 'T025',
                'table_label' => 'Meja 25',
                'customer_name' => 'Tamu 25',
                'payment_status' => 'Pending',
                'fulfillment_status' => 'NotQueued',
                'total_cents' => 58000,
                'items' => [
                    ['product_id' => 'p-orange-juice', 'name' => 'Orange Juice', 'qty' => 2, 'price' => 29000],
                ],
            ],
        ];

        foreach ($extraOrders as $eo) {
            $tbl = Table::where('table_code', $eo['table_code'])->first();
            $ord = Order::updateOrCreate(
                ['order_code' => $eo['order_code']],
                [
                    'id' => 'ord-' . strtolower($eo['order_code']),
                    'order_code' => $eo['order_code'],
                    'table_id' => $tbl?->id,
                    'table_number' => str_replace('Meja ', '', $eo['table_label']),
                    'table_code_snapshot' => $eo['table_code'],
                    'table_label_snapshot' => $eo['table_label'],
                    'customer_name' => $eo['customer_name'],
                    'customer_name_snapshot' => $eo['customer_name'],
                    'guest_token' => 'guest-tok-' . strtolower($eo['order_code']),
                    'created_by' => 'guest',
                    'status' => in_array($eo['fulfillment_status'], ['Served', 'Ready']) ? 'Completed' : 'Pending',
                    'payment_status' => $eo['payment_status'],
                    'fulfillment_status' => $eo['fulfillment_status'],
                    'subtotal_cents' => $eo['total_cents'],
                    'discount_cents' => 0,
                    'tax_cents' => 0,
                    'total_cents' => $eo['total_cents'],
                    'accepted_at' => $eo['accepted_at'] ?? null,
                    'preparing_at' => $eo['preparing_at'] ?? null,
                    'ready_at' => $eo['ready_at'] ?? null,
                    'served_at' => $eo['served_at'] ?? null,
                    'created_at' => $now->copy()->subMinutes(rand(10, 60)),
                    'updated_at' => $now,
                ]
            );

            $ord->items()->delete();
            $sortIdx = 1;
            foreach ($eo['items'] as $item) {
                $ord->items()->create([
                    'order_id' => $ord->id,
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'quantity' => $item['qty'],
                    'unit_price_cents' => $item['price'],
                    'sort_order' => $sortIdx++,
                ]);
            }

            if ($eo['payment_status'] === 'Paid') {
                $att = PaymentAttempt::updateOrCreate(
                    ['provider_reference' => 'QRIS-' . $eo['order_code']],
                    [
                        'id' => 'att-' . strtolower($eo['order_code']),
                        'order_id' => $ord->id,
                        'attempt_number' => 1,
                        'provider' => 'qris_simulated',
                        'provider_reference' => 'QRIS-' . $eo['order_code'],
                        'payment_method' => 'QRIS',
                        'amount_cents' => $eo['total_cents'],
                        'status' => 'Paid',
                        'expires_at' => $now->copy()->addMinutes(15),
                        'verified_at' => $now,
                    ]
                );

                PaymentReceipt::updateOrCreate(
                    ['provider_transaction_id' => 'TRX-' . $eo['order_code']],
                    [
                        'id' => 'rcpt-' . strtolower($eo['order_code']),
                        'order_id' => $ord->id,
                        'payment_attempt_id' => $att->id,
                        'amount_cents' => $eo['total_cents'],
                        'payment_method' => 'QRIS',
                        'verified_at' => $now,
                        'raw_payload' => ['provider' => 'QRIS'],
                    ]
                );
            }
        }
    }
}
