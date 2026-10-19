<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\PosReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnitAllocation;
use App\Models\TableNotification;
use App\Models\TableQr;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClearDummyDataCommand extends Command
{
    protected $signature = 'app:clear-dummy-data';
    protected $description = 'Bersihkan seluruh data transaksi dummy dengan tetap mempertahankan data Master & Admin';

    public function handle(): int
    {
        $this->info('Membersihkan data transaksi dummy...');

        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::transaction(function () {
            // 1. Bersihkan transaksi retur (merujuk ke sales)
            DB::table('return_items')->delete();
            DB::table('returns')->delete();

            // 2. Bersihkan order & meja
            DB::table('order_items')->delete();
            DB::table('payment_receipts')->delete();
            DB::table('payment_attempts')->delete();
            DB::table('table_refunds')->delete();
            DB::table('table_notifications')->delete();
            DB::table('guest_sessions')->delete();
            DB::table('carts')->delete();
            DB::table('orders')->delete();

            // 3. Bersihkan transaksi penjualan POS
            DB::table('sale_item_unit_allocations')->delete();
            DB::table('sale_items')->delete();
            DB::table('sales')->delete();
            DB::table('sale_drafts')->delete();
            DB::table('inventory_movements')->delete();
            DB::table('discount_usages')->delete();
            DB::table('audit_logs')->delete();
            DB::table('idempotency_keys')->delete();

            // 4. Reset scan count pada 50 Meja QR
            DB::table('table_qrs')->update(['scan_count' => 0]);

            // 4. Pastikan Akun Admin tetap utuh dan aktif
            User::updateOrCreate(
                ['id' => 'user-admin'],
                [
                    'name' => 'Administrator',
                    'email' => 'admin@posymart.com',
                    'password' => Hash::make('admin123'),
                    'role' => 'Admin',
                    'status' => 'Active',
                    'is_active' => true,
                ]
            );

            User::updateOrCreate(
                ['id' => 'user-john-doe'],
                [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'password' => Hash::make('posymart-demo'),
                    'role' => 'Admin',
                    'status' => 'Active',
                    'is_active' => true,
                ]
            );
        });

        DB::statement('PRAGMA foreign_keys = ON;');

        $this->info('✓ Seluruh data transaksi dummy berhasil dibersihkan.');
        $this->info('✓ Data 50 Meja & QR tetap aman.');
        $this->info('✓ Data Katalog Produk & Kategori tetap aman.');
        $this->info('✓ Data Akun Admin (admin@posymart.com & john.doe@example.com) aktif dan siap digunakan.');

        return Command::SUCCESS;
    }
}
