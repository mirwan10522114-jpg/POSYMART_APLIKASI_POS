<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosReturn extends Model
{
    protected $table = 'returns';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'return_code',
        'sale_id',
        'status', // Draft, Pending, Completed, Rejected
        'processor_id',
        'reason',
        'subtotal_refund_cents',
        'tax_refund_cents',
        'total_refund_cents',
        'refund_method',
        'submitted_at',
        'completed_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_refund_cents' => 'integer',
            'tax_refund_cents' => 'integer',
            'total_refund_cents' => 'integer',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
