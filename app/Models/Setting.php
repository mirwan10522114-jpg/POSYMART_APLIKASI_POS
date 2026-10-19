<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'store_name',
        'store_address',
        'store_phone',
        'store_email',
        'receipt_footer',
        'receipt_show_cashier',
        'receipt_show_customer',
        'tax_enabled',
        'tax_rate_bps',
        'tax_name',
        'table_service_fee_enabled',
        'table_service_fee_cents',
        'track_inventory',
        'low_stock_threshold',
        'currency_code',
        'currency_symbol',
        'timezone',
        'theme_mode',
        'accent_color',
        'version',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'receipt_show_cashier' => 'boolean',
            'receipt_show_customer' => 'boolean',
            'tax_enabled' => 'boolean',
            'tax_rate_bps' => 'integer',
            'table_service_fee_enabled' => 'boolean',
            'table_service_fee_cents' => 'integer',
            'track_inventory' => 'boolean',
            'low_stock_threshold' => 'integer',
            'version' => 'integer',
        ];
    }

    public static function instance(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'store_name' => 'PosyMart',
            'store_address' => 'Jl. Kopi Harum No. 10, Jakarta Selatan',
            'store_phone' => '+62 21 555 0199',
            'store_email' => 'halo@posymart.id',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
            'receipt_show_cashier' => true,
            'receipt_show_customer' => true,
            'tax_enabled' => true,
            'tax_rate_bps' => 800,
            'tax_name' => 'Pajak',
            'track_inventory' => true,
            'low_stock_threshold' => 8,
            'currency_code' => 'IDR',
            'currency_symbol' => 'Rp',
            'timezone' => 'Asia/Jakarta',
            'theme_mode' => 'Light',
            'accent_color' => 'teal',
            'version' => 1,
        ]);
    }
}
