<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemUnitAllocation extends Model
{
    protected $fillable = [
        'sale_item_id',
        'unit_index',
        'net_cents',
        'tax_cents',
        'total_cents',
        'is_refunded',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_index' => 'integer',
            'net_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'is_refunded' => 'boolean',
            'refunded_at' => 'datetime',
        ];
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
