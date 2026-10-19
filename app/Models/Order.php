<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_code',
        'status',
        'customer_id',
        'customer_name_snapshot',
        'note',
        'table_number',
        'table_id',
        'table_code_snapshot',
        'table_label_snapshot',
        'customer_name',
        'guest_token',
        'payment_status',
        'fulfillment_status',
        'delivery_type',
        'service_fee_cents',
        'accepted_by',
        'accepted_at',
        'preparing_at',
        'ready_at',
        'served_at',
        'subtotal_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
        'converted_sale_id',
        'created_by',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $appends = [
        'tableLabel',
        'customerName',
        'paymentStatus',
        'fulfillmentStatus',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'service_fee_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'accepted_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'served_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getTableLabelAttribute(): string
    {
        return $this->attributes['table_label_snapshot'] ?? $this->attributes['table_number'] ?? 'Meja';
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->attributes['customer_name'] ?? $this->attributes['customer_name_snapshot'] ?? 'Pelanggan';
    }

    public function getPaymentStatusAttribute(): string
    {
        return $this->attributes['payment_status'] ?? 'Unpaid';
    }

    public function getFulfillmentStatusAttribute(): string
    {
        return $this->attributes['fulfillment_status'] ?? $this->attributes['status'] ?? 'NotQueued';
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'order_id');
    }

    public function latestAttempt(): HasOne
    {
        return $this->hasOne(PaymentAttempt::class, 'order_id')->latestOfMany();
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class, 'order_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(TableRefund::class, 'order_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(TableNotification::class, 'order_id');
    }
}
