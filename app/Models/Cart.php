<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'guest_session_id',
        'table_id',
        'version',
        'items_data',
        'discount_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'items_data' => 'array',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }
}
