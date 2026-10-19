<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'table_service_fee_enabled')) {
                    $table->boolean('table_service_fee_enabled')->default(true)->after('tax_name');
                }
                if (!Schema::hasColumn('settings', 'table_service_fee_cents')) {
                    $table->bigInteger('table_service_fee_cents')->default(2000)->after('table_service_fee_enabled');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'delivery_type')) {
                    $table->string('delivery_type', 20)->default('table')->after('fulfillment_status');
                }
                if (!Schema::hasColumn('orders', 'service_fee_cents')) {
                    $table->bigInteger('service_fee_cents')->default(0)->after('discount_cents');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (Schema::hasColumn('settings', 'table_service_fee_cents')) {
                    $table->dropColumn('table_service_fee_cents');
                }
                if (Schema::hasColumn('settings', 'table_service_fee_enabled')) {
                    $table->dropColumn('table_service_fee_enabled');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'service_fee_cents')) {
                    $table->dropColumn('service_fee_cents');
                }
                if (Schema::hasColumn('orders', 'delivery_type')) {
                    $table->dropColumn('delivery_type');
                }
            });
        }
    }
};
