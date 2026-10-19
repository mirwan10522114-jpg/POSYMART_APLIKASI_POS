<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'sale_item_id',
        'product_id',
        'name',
        'quantity',
        'selected_units',
        'restock',
        'condition',
        'refund_subtotal_cents',
        'refund_tax_cents',
        'refund_total_cents',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'selected_units' => 'array',
            'restock' => 'boolean',
            'refund_subtotal_cents' => 'integer',
            'refund_tax_cents' => 'integer',
            'refund_total_cents' => 'integer',
        ];
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class, 'return_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
