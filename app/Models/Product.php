<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sku',
        'name',
        'description',
        'category_id',
        'category',
        'price_cents',
        'cost_cents',
        'tax_rate_bps',
        'status',
        'stock_qty',
        'track_stock',
        'low_stock_threshold',
        'image_url',
        'archived_at',
    ];

    protected $appends = ['price', 'stock'];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'cost_cents' => 'integer',
            'tax_rate_bps' => 'integer',
            'stock_qty' => 'integer',
            'track_stock' => 'boolean',
            'low_stock_threshold' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function getPriceAttribute(): int
    {
        return $this->price_cents ?? 0;
    }

    public function getStockAttribute(): int
    {
        if (!$this->track_stock) {
            return 999;
        }
        return $this->stock_qty ?? 0;
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
