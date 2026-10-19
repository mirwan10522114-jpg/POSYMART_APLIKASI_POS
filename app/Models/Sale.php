<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tx_code',
        'customer_id',
        'customer_name_snapshot',
        'cashier_id',
        'cashier_name_snapshot',
        'status',
        'payment_method',
        'subtotal_cents',
        'discount_cents',
        'discount_id',
        'taxable_subtotal_cents',
        'tax_cents',
        'tax_rate_bps_snapshot',
        'tax_name_snapshot',
        'tax_enabled_snapshot',
        'total_cents',
        'paid_cents',
        'change_cents',
        'note',
        'source_order_id',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $appends = [
        'customerId',
        'cashierName',
        'subtotal',
        'total',
        'discount',
        'tax',
        'paymentMethod',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'taxable_subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'tax_rate_bps_snapshot' => 'integer',
            'tax_enabled_snapshot' => 'boolean',
            'total_cents' => 'integer',
            'paid_cents' => 'integer',
            'change_cents' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    public function getCustomerIdAttribute(): ?string
    {
        return $this->attributes['customer_id'] ?? null;
    }

    public function getCashierNameAttribute(): string
    {
        return $this->attributes['cashier_name_snapshot'] ?? '';
    }

    public function getSubtotalAttribute(): int
    {
        return $this->attributes['subtotal_cents'] ?? 0;
    }

    public function getTotalAttribute(): int
    {
        return $this->attributes['total_cents'] ?? 0;
    }

    public function getDiscountAttribute(): int
    {
        return $this->attributes['discount_cents'] ?? 0;
    }

    public function getTaxAttribute(): int
    {
        return $this->attributes['tax_cents'] ?? 0;
    }

    public function getPaymentMethodAttribute(): string
    {
        return $this->attributes['payment_method'] ?? 'cash';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function discountUsage(): HasOne
    {
        return $this->hasOne(DiscountUsage::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PosReturn::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
