<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentAttempt extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'attempt_number',
        'provider',
        'provider_reference',
        'payment_method',
        'amount_cents',
        'status',
        'qr_string',
        'payment_url',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'amount_cents' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class, 'payment_attempt_id');
    }
}
