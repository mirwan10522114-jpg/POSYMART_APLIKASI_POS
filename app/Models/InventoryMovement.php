<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'movement_id',
        'product_id',
        'type', // SaleDecrement, VoidRestore, ReturnRestock, ManualAdjust
        'delta_qty',
        'sale_id',
        'return_id',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'delta_qty' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class, 'return_id');
    }
}
