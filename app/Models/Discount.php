<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
        'type', // percentage, fixed, category_percentage
        'scope', // all, categories, products, customers
        'value_bps',
        'value_cents',
        'target_categories',
        'target_products',
        'target_customers',
        'min_subtotal_cents',
        'max_discount_cents',
        'usage_limit',
        'usage_count',
        'baseline_usage_count',
        'start_at',
        'end_at',
        'is_active',
        'published',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'value_bps' => 'integer',
            'value_cents' => 'integer',
            'target_categories' => 'array',
            'target_products' => 'array',
            'target_customers' => 'array',
            'min_subtotal_cents' => 'integer',
            'max_discount_cents' => 'integer',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'baseline_usage_count' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
            'published' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }
}
