<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountUsage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'discount_id',
        'sale_id',
        'applied_cents',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
