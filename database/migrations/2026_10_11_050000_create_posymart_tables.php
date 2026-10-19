<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->string('id')->primary(); // slug or uuid e.g. 'kopi', 'makanan'
            $table->string('name')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Update/ensure users table has the needed PosyMart fields
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role')) {
                    $table->string('role', 20)->default('Cashier');
                }
                if (!Schema::hasColumn('users', 'status')) {
                    $table->string('status', 20)->default('Active');
                }
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('users', 'last_active_at')) {
                    $table->timestamp('last_active_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'note')) {
                    $table->text('note')->nullable();
                }
                if (!Schema::hasColumn('users', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable();
                }
            });
        }

        // 2. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'CUS-001' or UUID
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('Active');
            $table->bigInteger('total_spent_cents')->default(0);
            $table->integer('total_orders')->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'name']);
            $table->index('phone');
            $table->index('email');
        });

        // 3. Products
        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'p-cappuccino' or UUID
            $table->string('sku')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category_id')->nullable();
            $table->string('category')->nullable();
            $table->bigInteger('price_cents')->default(0);
            $table->bigInteger('cost_cents')->nullable();
            $table->integer('tax_rate_bps')->default(800);
            $table->string('status', 20)->default('Active');
            $table->integer('stock_qty')->default(0);
            $table->boolean('track_stock')->default(true);
            $table->integer('low_stock_threshold')->default(8);
            $table->text('image_url')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'name']);
            $table->index('category_id');
        });

        // 4. Discounts
        Schema::create('discounts', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'd-welcome10'
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 30); // percentage, fixed, category_percentage
            $table->string('scope', 30)->default('all'); // all, categories, products, customers
            $table->integer('value_bps')->nullable(); // e.g. 10 for 10%
            $table->bigInteger('value_cents')->nullable(); // for fixed amount e.g. 10000
            $table->json('target_categories')->nullable();
            $table->json('target_products')->nullable();
            $table->json('target_customers')->nullable();
            $table->bigInteger('min_subtotal_cents')->default(0);
            $table->bigInteger('max_discount_cents')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('baseline_usage_count')->default(0);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('published')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'start_at', 'end_at']);
        });

        // 5. Sales
        Schema::create('sales', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'TX-1001' or UUID
            $table->string('tx_code')->unique();
            $table->string('customer_id')->nullable(); // NULL = Walk-in Customer!
            $table->string('customer_name_snapshot');
            $table->string('cashier_id');
            $table->string('cashier_name_snapshot');
            $table->string('status', 20)->default('Completed'); // Completed, Voided
            $table->string('payment_method', 30)->default('Cash'); // Cash, Card, Digital Wallet
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->string('discount_id')->nullable();
            $table->bigInteger('taxable_subtotal_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->integer('tax_rate_bps_snapshot')->default(800);
            $table->string('tax_name_snapshot')->default('Pajak');
            $table->boolean('tax_enabled_snapshot')->default(true);
            $table->bigInteger('total_cents')->default(0);
            $table->bigInteger('paid_cents')->nullable();
            $table->bigInteger('change_cents')->default(0);
            $table->text('note')->nullable();
            $table->string('source_order_id')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
            $table->index('customer_id');
            $table->index('cashier_id');
        });

        // 6. Sale Items
        Schema::create('sale_items', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'si-1' or UUID
            $table->string('sale_id');
            $table->string('product_id')->nullable();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->bigInteger('unit_price_cents')->default(0);
            $table->bigInteger('gross_subtotal_cents')->default(0);
            $table->bigInteger('allocated_discount_cents')->default(0);
            $table->bigInteger('net_subtotal_cents')->default(0);
            $table->bigInteger('allocated_tax_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->index('sale_id');
            $table->index('product_id');
        });

        // 7. Sale Item Unit Allocations
        Schema::create('sale_item_unit_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('sale_item_id');
            $table->integer('unit_index')->default(0);
            $table->bigInteger('net_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->boolean('is_refunded')->default(false);
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->foreign('sale_item_id')->references('id')->on('sale_items')->onDelete('cascade');
            $table->unique(['sale_item_id', 'unit_index']);
            $table->index(['sale_item_id', 'is_refunded']);
        });

        // 8. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'ORD-2001'
            $table->string('order_code')->unique();
            $table->string('status', 20)->default('Draft'); // Draft, Pending, Preparing, Ready, Completed, Cancelled
            $table->string('customer_id')->nullable(); // NULL = Walk-in
            $table->string('customer_name_snapshot')->default('Walk-in Customer');
            $table->text('note')->nullable();
            $table->string('table_number')->nullable();
            $table->string('converted_sale_id')->nullable();
            $table->string('created_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('customer_id');
        });

        // 9. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('product_id')->nullable();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->bigInteger('unit_price_cents')->default(0);
            $table->text('note')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index('order_id');
        });

        // 10. Returns
        Schema::create('returns', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'RET-3001'
            $table->string('return_code')->unique();
            $table->string('sale_id');
            $table->string('status', 20)->default('Draft'); // Draft, Pending, Completed, Rejected
            $table->string('processor_id')->nullable();
            $table->text('reason')->nullable();
            $table->bigInteger('subtotal_refund_cents')->default(0);
            $table->bigInteger('tax_refund_cents')->default(0);
            $table->bigInteger('total_refund_cents')->default(0);
            $table->string('refund_method', 30)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
            $table->index(['status', 'created_at']);
            $table->index('sale_id');
        });

        // 11. Return Items
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->string('return_id');
            $table->string('sale_item_id');
            $table->string('product_id')->nullable();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->json('selected_units'); // [{unitIndex, netCents, taxCents}]
            $table->boolean('restock')->default(false);
            $table->string('condition', 20)->default('Unopened'); // Unopened, Opened, Damaged
            $table->bigInteger('refund_subtotal_cents')->default(0);
            $table->bigInteger('refund_tax_cents')->default(0);
            $table->bigInteger('refund_total_cents')->default(0);
            $table->timestamps();

            $table->foreign('return_id')->references('id')->on('returns')->onDelete('cascade');
            $table->index('return_id');
            $table->index('sale_item_id');
        });

        // 12. Discount Usages
        Schema::create('discount_usages', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
            $table->string('sale_id');
            $table->bigInteger('applied_cents')->default(0);
            $table->timestamps();

            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('restrict');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->unique('sale_id');
        });

        // 13. Inventory Movements
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_id')->unique(); // UUID for idempotency
            $table->string('product_id');
            $table->string('type', 30); // SaleDecrement, VoidRestore, ReturnRestock, ManualAdjust
            $table->integer('delta_qty');
            $table->string('sale_id')->nullable();
            $table->string('return_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('sale_id');
            $table->index('return_id');
        });

        // 14. Settings (singleton)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('PosyMart');
            $table->text('store_address')->nullable();
            $table->string('store_phone')->nullable();
            $table->string('store_email')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->boolean('receipt_show_cashier')->default(true);
            $table->boolean('receipt_show_customer')->default(true);
            $table->boolean('tax_enabled')->default(true);
            $table->integer('tax_rate_bps')->default(800);
            $table->string('tax_name')->default('Pajak');
            $table->boolean('track_inventory')->default(true);
            $table->integer('low_stock_threshold')->default(8);
            $table->string('currency_code', 10)->default('IDR');
            $table->string('currency_symbol', 10)->default('Rp');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('theme_mode', 20)->default('Light');
            $table->string('accent_color', 20)->default('teal');
            $table->integer('version')->default(1);
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        // 15. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('action', 50);
            $table->string('target_type', 30);
            $table->string('target_id')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('trace_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });

        // 16. Idempotency Keys
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('user_id')->nullable();
            $table->string('request_hash');
            $table->integer('response_status');
            $table->json('response_body')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });

        // 17. Sale Drafts (persistent cart per user)
        Schema::create('sale_drafts', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->json('draft_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_drafts');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('discount_usages');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('sale_item_unit_allocations');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('categories');
    }
};
