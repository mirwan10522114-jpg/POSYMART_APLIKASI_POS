<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tables (Titik Layanan Meja - Min 50 meja T001..T050)
        Schema::create('tables', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. T001
            $table->string('table_code')->unique(); // e.g. T001
            $table->string('label'); // e.g. Meja 01
            $table->string('area')->nullable()->default('Indoor'); // e.g. Indoor, Outdoor, VIP
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Table QRs (QR-T001..QR-T050)
        Schema::create('table_qrs', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. QR-T001
            $table->string('table_id');
            $table->string('public_token')->unique(); // Random secure token e.g. tok_...
            $table->enum('status', ['Draft', 'Active', 'Inactive', 'Revoked'])->default('Active');
            $table->integer('version')->default(1);
            $table->integer('scan_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamps();

            $table->foreign('table_id')->references('id')->on('tables')->cascadeOnDelete();
        });

        // 3. Guest Sessions (Sesi Tamu per scan meja)
        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. sess_...
            $table->string('table_id');
            $table->string('table_qr_id');
            $table->string('customer_name')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('table_id')->references('id')->on('tables');
            $table->foreign('table_qr_id')->references('id')->on('table_qrs');
        });

        // 4. Guest Carts
        Schema::create('carts', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. cart_...
            $table->string('guest_session_id')->nullable();
            $table->string('table_id');
            $table->integer('version')->default(1);
            $table->json('items_data')->nullable();
            $table->string('discount_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Update/create table order fields in orders table
        // We'll create payment_attempts table
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. att_...
            $table->string('order_id');
            $table->integer('attempt_number')->default(1);
            $table->string('provider')->default('qris_simulated'); // midtrans / qris_simulated
            $table->string('provider_reference')->unique(); // unique reference for provider
            $table->string('payment_method')->default('QRIS');
            $table->bigInteger('amount_cents');
            $table->enum('status', ['Pending', 'Verifying', 'Paid', 'Failed', 'Expired', 'Cancelled'])->default('Pending');
            $table->text('qr_string')->nullable(); // QRIS payload string
            $table->string('payment_url')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // 6. Payment Receipts (Penerimaan sah dari provider)
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. rcpt_...
            $table->string('order_id');
            $table->string('payment_attempt_id');
            $table->string('provider_transaction_id')->unique();
            $table->bigInteger('amount_cents');
            $table->string('payment_method')->default('QRIS');
            $table->timestamp('verified_at');
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        // 7. Table Refunds
        Schema::create('table_refunds', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. ref_...
            $table->string('order_id');
            $table->string('payment_receipt_id')->nullable();
            $table->bigInteger('amount_cents');
            $table->text('reason');
            $table->enum('status', ['Requested', 'Approved', 'Processing', 'Succeeded', 'Failed', 'Rejected'])->default('Requested');
            $table->string('requested_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // 8. Notifications (Notifikasi Admin & Kasir)
        Schema::create('table_notifications', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. notif_...
            $table->string('event_id')->unique();
            $table->string('type'); // OrderPaid, ReviewRequired, EscalationWarning, RefundFailed
            $table->string('order_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // 9. Add columns to orders table if not exist
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'table_id')) {
                $table->string('table_id')->nullable()->after('customer_name_snapshot');
            }
            if (!Schema::hasColumn('orders', 'table_code_snapshot')) {
                $table->string('table_code_snapshot')->nullable()->after('table_id');
            }
            if (!Schema::hasColumn('orders', 'table_label_snapshot')) {
                $table->string('table_label_snapshot')->nullable()->after('table_code_snapshot');
            }
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('table_label_snapshot');
            }
            if (!Schema::hasColumn('orders', 'guest_token')) {
                $table->string('guest_token')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('Unpaid')->after('status');
            }
            if (!Schema::hasColumn('orders', 'fulfillment_status')) {
                $table->string('fulfillment_status')->default('NotQueued')->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'accepted_by')) {
                $table->string('accepted_by')->nullable()->after('fulfillment_status');
            }
            if (!Schema::hasColumn('orders', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('accepted_by');
            }
            if (!Schema::hasColumn('orders', 'preparing_at')) {
                $table->timestamp('preparing_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('orders', 'ready_at')) {
                $table->timestamp('ready_at')->nullable()->after('preparing_at');
            }
            if (!Schema::hasColumn('orders', 'served_at')) {
                $table->timestamp('served_at')->nullable()->after('ready_at');
            }
            if (!Schema::hasColumn('orders', 'subtotal_cents')) {
                $table->bigInteger('subtotal_cents')->default(0)->after('served_at');
            }
            if (!Schema::hasColumn('orders', 'discount_cents')) {
                $table->bigInteger('discount_cents')->default(0)->after('subtotal_cents');
            }
            if (!Schema::hasColumn('orders', 'tax_cents')) {
                $table->bigInteger('tax_cents')->default(0)->after('discount_cents');
            }
            if (!Schema::hasColumn('orders', 'total_cents')) {
                $table->bigInteger('total_cents')->default(0)->after('tax_cents');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_notifications');
        Schema::dropIfExists('table_refunds');
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('guest_sessions');
        Schema::dropIfExists('table_qrs');
        Schema::dropIfExists('tables');
    }
};
