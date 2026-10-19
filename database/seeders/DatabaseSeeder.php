<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosReturn;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnitAllocation;
use App\Models\Setting;
use App\Models\User;
use App\Services\MoneyAllocator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Settings
        Setting::instance();

        // 2. Categories
        $categories = [
            ['id' => 'kopi', 'name' => 'Kopi', 'sort_order' => 1],
            ['id' => 'makanan', 'name' => 'Makanan', 'sort_order' => 2],
            ['id' => 'minuman', 'name' => 'Minuman', 'sort_order' => 3],
            ['id' => 'dessert', 'name' => 'Dessert', 'sort_order' => 4],
        ];
        foreach ($categories as $cat) {
            Category::updateOrCreate(['id' => $cat['id']], $cat);
        }

        // 3. Demo Users (6 users, default password 'posymart-demo')
        $users = [
            [
                'id' => 'user-john-doe',
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => Hash::make('posymart-demo'),
                'role' => 'Admin',
                'status' => 'Active',
                'is_active' => true,
                'last_active_at' => null,
            ],
            [
                'id' => 'user-avery-morgan',
                'name' => 'Avery Morgan',
                'email' => 'avery.morgan@example.com',
                'password' => Hash::make('posymart-demo'),
                'role' => 'Manager',
                'status' => 'Active',
                'is_active' => true,
                'last_active_at' => Carbon::create(2026, 10, 10, 9, 42),
            ],
            [
                'id' => 'user-jordan-lee',
                'name' => 'Jordan Lee',
                'email' => 'jordan.lee@example.com',
                'password' => Hash::make('posymart-demo'),
                'role' => 'Cashier',
                'status' => 'Active',
                'is_active' => true,
                'last_active_at' => Carbon::create(2026, 10, 10, 9, 18),
            ],
            [
                'id' => 'user-taylor-reed',
                'name' => 'Taylor Reed',
                'email' => 'taylor.reed@example.com',
                'password' => Hash::make('posymart-demo'),
                'role' => 'Cashier',
                'status' => 'Active',
                'is_active' => true,
                'last_active_at' => Carbon::create(2026, 10, 9, 17, 5),
            ],
            [
                'id' => 'user-casey-brooks',
                'name' => 'Casey Brooks',
                'email' => 'casey.brooks@example.com',
                'password' => Hash::make('posymart-demo'),
                'role' => 'Cashier',
                'status' => 'Invited',
                'is_active' => false,
                'last_active_at' => null,
            ],
            [
                'id' => 'user-riley-parker',
                'name' => 'Riley Parker',
                'email' => 'riley.parker@example.com',
                'password' => Hash::make('posymart-demo'),
                'role' => 'Manager',
                'status' => 'Disabled',
                'is_active' => false,
                'last_active_at' => Carbon::create(2026, 10, 7, 16, 30),
            ],
        ];
        foreach ($users as $u) {
            User::updateOrCreate(['id' => $u['id']], $u);
        }

        // 4. Products (12 items)
        $products = [
            ['id' => 'p-cappuccino', 'sku' => 'POS-001', 'name' => 'Cappuccino', 'category_id' => 'kopi', 'category' => 'Kopi', 'price_cents' => 25000, 'stock_qty' => 42, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Espresso dengan susu hangat dan foam lembut.', 'image_url' => '/images/products/p-cappuccino.jpg'],
            ['id' => 'p-latte', 'sku' => 'POS-002', 'name' => 'Latte', 'category_id' => 'kopi', 'category' => 'Kopi', 'price_cents' => 38000, 'stock_qty' => 38, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Espresso lembut dengan susu hangat.', 'image_url' => '/images/products/p-latte.jpg'],
            ['id' => 'p-americano', 'sku' => 'POS-003', 'name' => 'Americano', 'category_id' => 'kopi', 'category' => 'Kopi', 'price_cents' => 29000, 'stock_qty' => 55, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Espresso dengan tambahan air panas.', 'image_url' => '/images/products/p-americano.jpg'],
            ['id' => 'p-mocha', 'sku' => 'POS-004', 'name' => 'Mocha', 'category_id' => 'kopi', 'category' => 'Kopi', 'price_cents' => 42000, 'stock_qty' => 18, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Espresso, susu, dan cokelat.', 'image_url' => '/images/products/p-mocha.jpg'],
            ['id' => 'p-bagel', 'sku' => 'POS-005', 'name' => 'Bagel', 'category_id' => 'makanan', 'category' => 'Makanan', 'price_cents' => 18000, 'stock_qty' => 24, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Bagel panggang segar dengan kulit keemasan.', 'image_url' => '/images/products/p-bagel.jpg'],
            ['id' => 'p-sandwich', 'sku' => 'POS-006', 'name' => 'Sandwich', 'category_id' => 'makanan', 'category' => 'Makanan', 'price_cents' => 55000, 'stock_qty' => 12, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Sandwich roti gandum dengan sayuran segar.', 'image_url' => '/images/products/p-sandwich.jpg'],
            ['id' => 'p-caesar-salad', 'sku' => 'POS-007', 'name' => 'Caesar Salad', 'category_id' => 'makanan', 'category' => 'Makanan', 'price_cents' => 65000, 'stock_qty' => 8, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Sayuran segar dengan dressing Caesar dan topping renyah.', 'image_url' => '/images/products/p-caesar-salad.jpg'],
            ['id' => 'p-muffin', 'sku' => 'POS-008', 'name' => 'Muffin', 'category_id' => 'dessert', 'category' => 'Dessert', 'price_cents' => 26000, 'stock_qty' => 6, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Muffin lembut dengan potongan cokelat.', 'image_url' => '/images/products/p-muffin.jpg'],
            ['id' => 'p-orange-juice', 'sku' => 'POS-009', 'name' => 'Orange Juice', 'category_id' => 'minuman', 'category' => 'Minuman', 'price_cents' => 29000, 'stock_qty' => 30, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Jus jeruk segar disajikan dingin.', 'image_url' => '/images/products/p-orange-juice.jpg'],
            ['id' => 'p-smoothie', 'sku' => 'POS-010', 'name' => 'Smoothie', 'category_id' => 'minuman', 'category' => 'Minuman', 'price_cents' => 39000, 'stock_qty' => 14, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Smoothie buah creamy disajikan dingin.', 'image_url' => '/images/products/p-smoothie.jpg'],
            ['id' => 'p-chocolate-cake', 'sku' => 'POS-011', 'name' => 'Chocolate Cake', 'category_id' => 'dessert', 'category' => 'Dessert', 'price_cents' => 45000, 'stock_qty' => 5, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Kue lapis cokelat kaya rasa.', 'image_url' => '/images/products/p-chocolate-cake.jpg'],
            ['id' => 'p-cookie', 'sku' => 'POS-012', 'name' => 'Cookie', 'category_id' => 'dessert', 'category' => 'Dessert', 'price_cents' => 18000, 'stock_qty' => 28, 'low_stock_threshold' => 8, 'status' => 'Active', 'description' => 'Cookie klasik dengan chocolate chips.', 'image_url' => '/images/products/p-cookie.jpg'],
        ];
        foreach ($products as $p) {
            Product::updateOrCreate(['id' => $p['id']], $p);
        }

        // 5. Customers (5 items)
        $customers = [
            ['id' => 'CUS-001', 'name' => 'Ava Thompson', 'email' => 'ava.thompson@example.com', 'phone' => '+1 202 555 0101', 'status' => 'Active', 'joined_at' => Carbon::create(2026, 10, 1, 9, 12), 'notes' => 'Lebih suka salinan digital struk demo.'],
            ['id' => 'CUS-002', 'name' => 'Liam Carter', 'email' => 'liam.carter@example.com', 'phone' => '+1 202 555 0102', 'status' => 'Active', 'joined_at' => Carbon::create(2026, 10, 1, 9, 30), 'notes' => 'Biasanya memesan kopi dengan camilan.'],
            ['id' => 'CUS-003', 'name' => 'Olivia Brooks', 'email' => 'olivia.brooks@example.com', 'phone' => '+1 202 555 0103', 'status' => 'Active', 'joined_at' => Carbon::create(2026, 10, 1, 10, 5), 'notes' => 'Pelanggan sesekali pada jam makan siang.'],
            ['id' => 'CUS-004', 'name' => 'Ethan Miller', 'email' => 'ethan.miller@example.com', 'phone' => '+1 202 555 0104', 'status' => 'Active', 'joined_at' => Carbon::create(2026, 10, 1, 10, 48), 'notes' => 'Suka pengambilan cepat.'],
            ['id' => 'CUS-005', 'name' => 'Sofia Reed', 'email' => 'sofia.reed@example.com', 'phone' => '+1 202 555 0105', 'status' => 'Inactive', 'joined_at' => Carbon::create(2026, 10, 1, 11, 2), 'notes' => ''],
        ];
        foreach ($customers as $c) {
            Customer::updateOrCreate(['id' => $c['id']], $c);
        }

        // 6. Discounts (7 items)
        $discounts = [
            [
                'id' => 'd-welcome10',
                'code' => 'WELCOME10',
                'name' => 'Welcome Offer',
                'type' => 'percentage',
                'scope' => 'all',
                'value_bps' => 1000, // 10%
                'min_subtotal_cents' => 100000,
                'usage_limit' => 100,
                'usage_count' => 32,
                'baseline_usage_count' => 32,
                'start_at' => Carbon::create(2026, 10, 1),
                'end_at' => Carbon::create(2026, 11, 1),
                'is_active' => true,
                'published' => true,
            ],
            [
                'id' => 'd-coffee1',
                'code' => 'COFFEE1',
                'name' => 'Coffee Break',
                'type' => 'fixed',
                'scope' => 'categories',
                'value_cents' => 10000,
                'target_categories' => ['kopi', 'Kopi'],
                'min_subtotal_cents' => 0,
                'usage_limit' => null,
                'usage_count' => 18,
                'baseline_usage_count' => 18,
                'start_at' => Carbon::create(2026, 10, 1),
                'end_at' => Carbon::create(2026, 11, 1),
                'is_active' => true,
                'published' => true,
            ],
            [
                'id' => 'd-weekend15',
                'code' => 'WEEKEND15',
                'name' => 'Weekend Treat',
                'type' => 'percentage',
                'scope' => 'categories',
                'value_bps' => 1500, // 15%
                'target_categories' => ['dessert', 'Dessert'],
                'min_subtotal_cents' => 0,
                'usage_limit' => 50,
                'usage_count' => 0,
                'baseline_usage_count' => 0,
                'start_at' => Carbon::create(2026, 10, 17),
                'end_at' => Carbon::create(2026, 10, 19),
                'is_active' => true,
                'published' => true,
            ],
            [
                'id' => 'd-lunch2',
                'code' => 'LUNCH2',
                'name' => 'Lunch Combo',
                'type' => 'fixed',
                'scope' => 'categories',
                'value_cents' => 20000,
                'target_categories' => ['makanan', 'Makanan'],
                'min_subtotal_cents' => 0,
                'usage_limit' => 100,
                'usage_count' => 45,
                'baseline_usage_count' => 45,
                'start_at' => Carbon::create(2026, 9, 1),
                'end_at' => Carbon::create(2026, 10, 1),
                'is_active' => true,
                'published' => true,
            ],
            [
                'id' => 'd-member5',
                'code' => 'MEMBER5',
                'name' => 'Member Reward',
                'type' => 'percentage',
                'scope' => 'all',
                'value_bps' => 500, // 5%
                'target_customers' => ['CUS-003'],
                'min_subtotal_cents' => 0,
                'usage_limit' => null,
                'usage_count' => 20,
                'baseline_usage_count' => 20,
                'start_at' => Carbon::create(2026, 10, 1),
                'end_at' => Carbon::create(2026, 11, 1),
                'is_active' => false,
                'published' => true,
            ],
            [
                'id' => 'd-first25',
                'code' => 'FIRST25',
                'name' => 'First 25 Orders',
                'type' => 'fixed',
                'scope' => 'all',
                'value_cents' => 15000,
                'min_subtotal_cents' => 0,
                'usage_limit' => 25,
                'usage_count' => 25,
                'baseline_usage_count' => 25,
                'start_at' => Carbon::create(2026, 10, 1),
                'end_at' => Carbon::create(2026, 11, 1),
                'is_active' => true,
                'published' => true,
            ],
            [
                'id' => 'd-autumn20',
                'code' => 'AUTUMN20',
                'name' => 'Autumn Special',
                'type' => 'percentage',
                'scope' => 'all',
                'value_bps' => 2000, // 20%
                'min_subtotal_cents' => 0,
                'usage_limit' => null,
                'usage_count' => 0,
                'baseline_usage_count' => 0,
                'start_at' => null,
                'end_at' => null,
                'is_active' => true,
                'published' => false,
            ],
        ];
        foreach ($discounts as $d) {
            Discount::updateOrCreate(['id' => $d['id']], $d);
        }

        // Helper untuk membuat Sale dengan alokasi presisi
        $createSale = function (
            string $id,
            Carbon $createdAt,
            string $paymentMethod,
            string $status,
            array $rawItems,
            ?string $customerId = null,
            ?string $customerName = null,
            int $discountCents = 0,
            ?string $discountId = null,
            ?string $voidReason = null,
            ?Carbon $voidedAt = null
        ) {
            $customerNameSnapshot = $customerName ?? ($customerId ? Customer::find($customerId)?->name : 'Walk-in Customer') ?? 'Walk-in Customer';
            $cashierId = 'user-john-doe';
            $cashierNameSnapshot = 'John Doe';

            $itemsForAlloc = [];
            foreach ($rawItems as $it) {
                $p = Product::find($it['product_id']);
                $unitPrice = $it['unit_price_cents'] ?? $p->price_cents;
                $itemsForAlloc[] = [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'quantity' => $it['quantity'],
                    'unit_price_cents' => $unitPrice,
                ];
            }

            $totals = MoneyAllocator::computeTotals($itemsForAlloc, $discountCents, true, 800);
            $allocs = MoneyAllocator::allocateToItems($itemsForAlloc, $totals['discount_cents'], $totals['tax_cents']);

            $sale = Sale::updateOrCreate(['id' => $id], [
                'tx_code' => $id,
                'customer_id' => $customerId,
                'customer_name_snapshot' => $customerNameSnapshot,
                'cashier_id' => $cashierId,
                'cashier_name_snapshot' => $cashierNameSnapshot,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'subtotal_cents' => $totals['gross_subtotal_cents'],
                'discount_cents' => $totals['discount_cents'],
                'discount_id' => $discountId,
                'taxable_subtotal_cents' => $totals['taxable_subtotal_cents'],
                'tax_cents' => $totals['tax_cents'],
                'tax_rate_bps_snapshot' => 800,
                'tax_name_snapshot' => 'Pajak',
                'tax_enabled_snapshot' => true,
                'total_cents' => $totals['total_cents'],
                'paid_cents' => $totals['total_cents'],
                'change_cents' => 0,
                'void_reason' => $voidReason,
                'voided_at' => $voidedAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Save items and unit allocations
            $sale->items()->delete();
            foreach ($itemsForAlloc as $i => $itemData) {
                $itemAlloc = $allocs[$i];
                $saleItem = SaleItem::create([
                    'id' => "si-{$sale->id}-" . ($i + 1),
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'name' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                    'unit_price_cents' => $itemData['unit_price_cents'],
                    'gross_subtotal_cents' => $itemAlloc['gross_subtotal_cents'],
                    'allocated_discount_cents' => $itemAlloc['allocated_discount_cents'],
                    'net_subtotal_cents' => $itemAlloc['net_subtotal_cents'],
                    'allocated_tax_cents' => $itemAlloc['allocated_tax_cents'],
                    'total_cents' => $itemAlloc['total_cents'],
                    'sort_order' => $i,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $unitAllocations = MoneyAllocator::buildUnitAllocations(
                    $itemAlloc['net_subtotal_cents'],
                    $itemAlloc['allocated_tax_cents'],
                    $itemData['quantity']
                );

                foreach ($unitAllocations as $u) {
                    SaleItemUnitAllocation::create([
                        'sale_item_id' => $saleItem->id,
                        'unit_index' => $u['unit_index'],
                        'net_cents' => $u['net_cents'],
                        'tax_cents' => $u['tax_cents'],
                        'total_cents' => $u['total_cents'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }

            return $sale;
        };

        // 7. Seed Sales: 6 Today + 6 Archive
        $createSale('TX-1001', Carbon::create(2026, 10, 10, 10, 32), 'Cash', 'Completed', [
            ['product_id' => 'p-cappuccino', 'quantity' => 1],
            ['product_id' => 'p-bagel', 'quantity' => 2],
            ['product_id' => 'p-caesar-salad', 'quantity' => 1],
            ['product_id' => 'p-orange-juice', 'quantity' => 1],
        ]);
        $createSale('TX-1002', Carbon::create(2026, 10, 10, 11, 5), 'Card', 'Completed', [
            ['product_id' => 'p-cappuccino', 'quantity' => 1],
            ['product_id' => 'p-sandwich', 'quantity' => 1],
        ], 'CUS-001', 'Ava Thompson');
        $createSale('TX-1003', Carbon::create(2026, 10, 10, 11, 28), 'Card', 'Completed', [
            ['product_id' => 'p-latte', 'quantity' => 2],
            ['product_id' => 'p-cookie', 'quantity' => 2],
        ], 'CUS-002', 'Liam Carter');
        $createSale('TX-1004', Carbon::create(2026, 10, 10, 12, 10), 'Cash', 'Completed', [
            ['product_id' => 'p-mocha', 'quantity' => 1],
            ['product_id' => 'p-muffin', 'quantity' => 1],
        ], 'CUS-003', 'Olivia Brooks');
        $createSale('TX-1005', Carbon::create(2026, 10, 10, 12, 45), 'Card', 'Completed', [
            ['product_id' => 'p-americano', 'quantity' => 2],
            ['product_id' => 'p-caesar-salad', 'quantity' => 1],
        ], 'CUS-004', 'Ethan Miller');
        $createSale('TX-1006', Carbon::create(2026, 10, 10, 13, 15), 'Cash', 'Voided', [
            ['product_id' => 'p-smoothie', 'quantity' => 1],
            ['product_id' => 'p-chocolate-cake', 'quantity' => 1],
        ], 'CUS-005', 'Sofia Reed', 0, null, 'Penjualan demo duplikat', Carbon::create(2026, 10, 10, 13, 22));

        // Archive Sales
        $createSale('TX-0988', Carbon::create(2026, 10, 3, 11, 10), 'Card', 'Completed', [
            ['product_id' => 'p-smoothie', 'quantity' => 1],
            ['product_id' => 'p-chocolate-cake', 'quantity' => 1],
        ]);
        $createSale('TX-0990', Carbon::create(2026, 9, 28, 9, 40), 'Cash', 'Completed', [
            ['product_id' => 'p-cappuccino', 'quantity' => 1],
        ]);
        $createSale('TX-0992', Carbon::create(2026, 10, 1, 10, 15), 'Cash', 'Completed', [
            ['product_id' => 'p-bagel', 'quantity' => 1],
        ]);
        $createSale('TX-0993', Carbon::create(2026, 10, 2, 11, 30), 'Card', 'Completed', [
            ['product_id' => 'p-cookie', 'quantity' => 2],
        ]);
        $createSale('TX-0994', Carbon::create(2026, 10, 3, 9, 50), 'Card', 'Completed', [
            ['product_id' => 'p-latte', 'quantity' => 1],
        ]);
        $createSale('TX-0995', Carbon::create(2026, 10, 3, 10, 5), 'Cash', 'Completed', [
            ['product_id' => 'p-smoothie', 'quantity' => 1],
        ]);

        // 8. 44 Deterministic Historical Sales (04-09 Oct)
        $templates = [
            150000 => fn () => [['product_id' => 'p-cappuccino', 'quantity' => 3, 'unit_price_cents' => 35000], ['product_id' => 'p-chocolate-cake', 'quantity' => 1, 'unit_price_cents' => 45000]],
            200000 => fn () => [['product_id' => 'p-sandwich', 'quantity' => 2, 'unit_price_cents' => 55000], ['product_id' => 'p-chocolate-cake', 'quantity' => 2, 'unit_price_cents' => 45000]],
            250000 => fn () => [['product_id' => 'p-cappuccino', 'quantity' => 2, 'unit_price_cents' => 35000], ['product_id' => 'p-chocolate-cake', 'quantity' => 4, 'unit_price_cents' => 45000]],
            300000 => fn () => [['product_id' => 'p-cappuccino', 'quantity' => 6, 'unit_price_cents' => 35000], ['product_id' => 'p-chocolate-cake', 'quantity' => 2, 'unit_price_cents' => 45000]],
            350000 => fn () => [['product_id' => 'p-cappuccino', 'quantity' => 10, 'unit_price_cents' => 35000]],
            450000 => fn () => [['product_id' => 'p-chocolate-cake', 'quantity' => 10, 'unit_price_cents' => 45000]],
            500000 => fn () => [['product_id' => 'p-cappuccino', 'quantity' => 4, 'unit_price_cents' => 35000], ['product_id' => 'p-chocolate-cake', 'quantity' => 8, 'unit_price_cents' => 45000]],
        ];

        $histDays = [
            ['y' => 2026, 'm' => 10, 'd' => 4, 'rows' => [['Cash', 300000, 2], ['Card', 450000, 2], ['Digital Wallet', 150000, 2]]],
            ['y' => 2026, 'm' => 10, 'd' => 5, 'rows' => [['Cash', 250000, 2], ['Card', 300000, 4], ['Digital Wallet', 150000, 2]]],
            ['y' => 2026, 'm' => 10, 'd' => 6, 'rows' => [['Cash', 250000, 3], ['Card', 250000, 5], ['Digital Wallet', 250000, 1]]],
            ['y' => 2026, 'm' => 10, 'd' => 7, 'rows' => [['Cash', 250000, 2], ['Card', 300000, 4], ['Digital Wallet', 350000, 1]]],
            ['y' => 2026, 'm' => 10, 'd' => 8, 'rows' => [['Cash', 250000, 3], ['Card', 250000, 6], ['Digital Wallet', 250000, 1]]],
            ['y' => 2026, 'm' => 10, 'd' => 9, 'rows' => [['Cash', 150000, 2], ['Card', 500000, 1], ['Digital Wallet', 200000, 1]]],
        ];

        $histId = 1100;
        foreach ($histDays as $hd) {
            $minOffset = 0;
            foreach ($hd['rows'] as [$method, $netVal, $count]) {
                for ($k = 0; $k < $count; $k++) {
                    $hour = 9 + intdiv($minOffset, 60);
                    $minute = $minOffset % 60;
                    $createdAt = Carbon::create($hd['y'], $hd['m'], $hd['d'], $hour, $minute);
                    $itemsGenerator = $templates[$netVal];
                    $createSale("TX-{$histId}", $createdAt, $method, 'Completed', $itemsGenerator());
                    $histId++;
                    $minOffset += 10;
                }
            }
        }

        // 9. Orders (7 fixtures)
        $orders = [
            [
                'id' => 'ORD-2007',
                'order_code' => 'ORD-2007',
                'customer_id' => 'CUS-001',
                'customer_name_snapshot' => 'Ava Thompson',
                'note' => 'Tanpa es, tolong.',
                'status' => 'Pending',
                'created_by' => 'user-john-doe',
                'created_at' => Carbon::create(2026, 10, 10, 9, 43),
                'items' => [
                    ['product_id' => 'p-bagel', 'name' => 'Bagel', 'quantity' => 1, 'unit_price_cents' => 28000],
                    ['product_id' => 'p-orange-juice', 'name' => 'Orange Juice', 'quantity' => 1, 'unit_price_cents' => 29000],
                ],
            ],
            [
                'id' => 'ORD-2001',
                'order_code' => 'ORD-2001',
                'customer_id' => 'CUS-001',
                'customer_name_snapshot' => 'Ava Thompson',
                'note' => 'Bagel dipanggang sebentar.',
                'status' => 'Pending',
                'created_by' => 'user-john-doe',
                'created_at' => Carbon::create(2026, 10, 10, 9, 40),
                'items' => [
                    ['product_id' => 'p-cappuccino', 'name' => 'Cappuccino', 'quantity' => 2, 'unit_price_cents' => 35000],
                    ['product_id' => 'p-bagel', 'name' => 'Bagel', 'quantity' => 2, 'unit_price_cents' => 28000],
                ],
            ],
            [
                'id' => 'ORD-2002',
                'order_code' => 'ORD-2002',
                'customer_id' => 'CUS-002',
                'customer_name_snapshot' => 'Liam Carter',
                'table_number' => '04',
                'status' => 'Preparing',
                'created_by' => 'user-john-doe',
                'created_at' => Carbon::create(2026, 10, 10, 9, 35),
                'items' => [
                    ['product_id' => 'p-latte', 'name' => 'Latte', 'quantity' => 1, 'unit_price_cents' => 38000],
                    ['product_id' => 'p-sandwich', 'name' => 'Sandwich', 'quantity' => 1, 'unit_price_cents' => 55000],
                ],
            ],
            [
                'id' => 'ORD-2003',
                'order_code' => 'ORD-2003',
                'customer_id' => 'CUS-003',
                'customer_name_snapshot' => 'Olivia Brooks',
                'note' => 'Dikemas terpisah.',
                'status' => 'Ready',
                'created_by' => 'user-john-doe',
                'created_at' => Carbon::create(2026, 10, 10, 9, 28),
                'items' => [
                    ['product_id' => 'p-americano', 'name' => 'Americano', 'quantity' => 2, 'unit_price_cents' => 29000],
                    ['product_id' => 'p-muffin', 'name' => 'Muffin', 'quantity' => 2, 'unit_price_cents' => 26000],
                ],
            ],
            [
                'id' => 'ORD-2004',
                'order_code' => 'ORD-2004',
                'customer_id' => null,
                'customer_name_snapshot' => 'Walk-in Customer',
                'status' => 'Draft',
                'created_by' => 'user-john-doe',
                'created_at' => Carbon::create(2026, 10, 10, 9, 20),
                'items' => [
                    ['product_id' => 'p-caesar-salad', 'name' => 'Caesar Salad', 'quantity' => 1, 'unit_price_cents' => 65000],
                    ['product_id' => 'p-orange-juice', 'name' => 'Orange Juice', 'quantity' => 1, 'unit_price_cents' => 29000],
                ],
            ],
            [
                'id' => 'ORD-2006',
                'order_code' => 'ORD-2006',
                'customer_id' => 'CUS-005',
                'customer_name_snapshot' => 'Sofia Reed',
                'cancel_reason' => 'Pelanggan berubah pikiran.',
                'status' => 'Cancelled',
                'cancelled_at' => Carbon::create(2026, 10, 8, 8, 52),
                'created_by' => 'user-john-doe',
                'created_at' => Carbon::create(2026, 10, 8, 8, 40),
                'items' => [
                    ['product_id' => 'p-cappuccino', 'name' => 'Cappuccino', 'quantity' => 1, 'unit_price_cents' => 35000],
                    ['product_id' => 'p-cookie', 'name' => 'Cookie', 'quantity' => 2, 'unit_price_cents' => 18000],
                ],
            ],
            [
                'id' => 'ORD-2005',
                'order_code' => 'ORD-2005',
                'customer_id' => null,
                'customer_name_snapshot' => 'Walk-in Customer',
                'table_number' => '12',
                'status' => 'Completed',
                'converted_sale_id' => 'TX-0988',
                'completed_at' => Carbon::create(2026, 10, 3, 11, 26),
                'created_by' => 'user-john-doe',
                'created_at' => Carbon::create(2026, 10, 3, 11, 10),
                'items' => [
                    ['product_id' => 'p-smoothie', 'name' => 'Smoothie', 'quantity' => 1, 'unit_price_cents' => 39000],
                    ['product_id' => 'p-chocolate-cake', 'name' => 'Chocolate Cake', 'quantity' => 1, 'unit_price_cents' => 45000],
                ],
            ],
        ];

        foreach ($orders as $od) {
            $itemsData = $od['items'];
            unset($od['items']);
            $order = Order::updateOrCreate(['id' => $od['id']], $od);
            $order->items()->delete();
            foreach ($itemsData as $idx => $it) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $it['product_id'],
                    'name' => $it['name'],
                    'quantity' => $it['quantity'],
                    'unit_price_cents' => $it['unit_price_cents'],
                    'sort_order' => $idx,
                ]);
            }
        }

        // 10. Returns (5 fixtures)
        $returns = [
            [
                'id' => 'R-1001',
                'return_code' => 'R-1001',
                'sale_id' => 'TX-0990',
                'status' => 'Completed',
                'processor_id' => 'user-avery-morgan',
                'reason' => 'Barang salah',
                'refund_method' => 'Cash',
                'submitted_at' => Carbon::create(2026, 10, 6, 10, 30),
                'completed_at' => Carbon::create(2026, 10, 6, 10, 36),
                'picks' => [['product_id' => 'p-cappuccino', 'quantity' => 1, 'restock' => true, 'condition' => 'Unopened']],
            ],
            [
                'id' => 'R-1002',
                'return_code' => 'R-1002',
                'sale_id' => 'TX-0992',
                'status' => 'Draft',
                'reason' => 'Masalah kualitas',
                'submitted_at' => Carbon::create(2026, 10, 10, 8, 55),
                'picks' => [['product_id' => 'p-bagel', 'quantity' => 1, 'restock' => false, 'condition' => 'Opened']],
            ],
            [
                'id' => 'R-1003',
                'return_code' => 'R-1003',
                'sale_id' => 'TX-0993',
                'status' => 'Completed',
                'processor_id' => 'user-avery-morgan',
                'reason' => 'Masalah kualitas',
                'refund_method' => 'Card',
                'submitted_at' => Carbon::create(2026, 10, 8, 11, 20),
                'completed_at' => Carbon::create(2026, 10, 8, 11, 28),
                'picks' => [['product_id' => 'p-cookie', 'quantity' => 2, 'restock' => true, 'condition' => 'Unopened']],
            ],
            [
                'id' => 'R-1004',
                'return_code' => 'R-1004',
                'sale_id' => 'TX-0994',
                'status' => 'Pending',
                'reason' => 'Barang salah',
                'submitted_at' => Carbon::create(2026, 10, 10, 9, 5),
                'picks' => [['product_id' => 'p-latte', 'quantity' => 1, 'restock' => true, 'condition' => 'Unopened']],
            ],
            [
                'id' => 'R-1005',
                'return_code' => 'R-1005',
                'sale_id' => 'TX-0995',
                'status' => 'Rejected',
                'processor_id' => 'user-avery-morgan',
                'reason' => 'Berubah pikiran',
                'rejected_at' => Carbon::create(2026, 10, 10, 9, 22),
                'submitted_at' => Carbon::create(2026, 10, 10, 9, 15),
                'picks' => [['product_id' => 'p-smoothie', 'quantity' => 1, 'restock' => false, 'condition' => 'Opened']],
            ],
        ];

        foreach ($returns as $retData) {
            $sale = Sale::with('items.unitAllocations')->find($retData['sale_id']);
            $picks = $retData['picks'];
            unset($retData['picks']);

            $totalRefundSubtotal = 0;
            $totalRefundTax = 0;

            $returnItemsToCreate = [];
            foreach ($picks as $pk) {
                $saleItem = $sale->items->firstWhere('product_id', $pk['product_id']);
                $units = $saleItem->unitAllocations->take($pk['quantity']);

                $selectedUnits = [];
                $itemRefundSub = 0;
                $itemRefundTax = 0;
                foreach ($units as $u) {
                    $selectedUnits[] = [
                        'unitIndex' => $u->unit_index,
                        'netCents' => $u->net_cents,
                        'taxCents' => $u->tax_cents,
                    ];
                    $itemRefundSub += $u->net_cents;
                    $itemRefundTax += $u->tax_cents;

                    if ($retData['status'] === 'Completed') {
                        $u->update(['is_refunded' => true, 'refunded_at' => $retData['completed_at'] ?? now()]);
                    }
                }

                $totalRefundSubtotal += $itemRefundSub;
                $totalRefundTax += $itemRefundTax;

                $returnItemsToCreate[] = [
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $pk['product_id'],
                    'name' => $saleItem->name,
                    'quantity' => $pk['quantity'],
                    'selected_units' => $selectedUnits,
                    'restock' => $pk['restock'],
                    'condition' => $pk['condition'],
                    'refund_subtotal_cents' => $itemRefundSub,
                    'refund_tax_cents' => $itemRefundTax,
                    'refund_total_cents' => $itemRefundSub + $itemRefundTax,
                ];
            }

            $retData['subtotal_refund_cents'] = $totalRefundSubtotal;
            $retData['tax_refund_cents'] = $totalRefundTax;
            $retData['total_refund_cents'] = $totalRefundSubtotal + $totalRefundTax;

            $posReturn = PosReturn::updateOrCreate(['id' => $retData['id']], $retData);
            $posReturn->items()->delete();
            foreach ($returnItemsToCreate as $it) {
                $it['return_id'] = $posReturn->id;
                ReturnItem::create($it);
            }
        }

        // 11. Recalculate customer total_spent_cents & total_orders
        foreach (Customer::all() as $cust) {
            $completedSales = Sale::where('customer_id', $cust->id)->where('status', 'Completed')->get();
            $totalSpent = $completedSales->sum('total_cents');
            $totalOrders = $completedSales->count();
            $cust->update([
                'total_spent_cents' => $totalSpent,
                'total_orders' => $totalOrders,
            ]);
        }

        // 12. PosyMart Table Order (50 Tables & QRs, canonical Meja 17 Rani order, queues)
        $this->call(TableOrderSeeder::class);
    }
}
