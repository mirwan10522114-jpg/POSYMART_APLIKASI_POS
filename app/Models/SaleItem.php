<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'name',
        'quantity',
        'unit_price_cents',
        'gross_subtotal_cents',
        'allocated_discount_cents',
        'net_subtotal_cents',
        'allocated_tax_cents',
        'total_cents',
        'sort_order',
    ];

    protected $appends = [
        'unitPrice',
        'subtotal',
        'total',
        'netAmount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'gross_subtotal_cents' => 'integer',
            'allocated_discount_cents' => 'integer',
            'net_subtotal_cents' => 'integer',
            'allocated_tax_cents' => 'integer',
            'total_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function getUnitPriceAttribute(): int
    {
        return $this->attributes['unit_price_cents'] ?? 0;
    }

    public function getSubtotalAttribute(): int
    {
        return $this->attributes['gross_subtotal_cents'] ?? 0;
    }

    public function getTotalAttribute(): int
    {
        return $this->attributes['total_cents'] ?? 0;
    }

    public function getNetAmountAttribute(): int
    {
        return $this->attributes['net_subtotal_cents'] ?? 0;
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unitAllocations(): HasMany
    {
        return $this->hasMany(SaleItemUnitAllocation::class);
    }
}
