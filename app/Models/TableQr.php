<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableQr extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'table_id',
        'public_token',
        'status',
        'version',
        'scan_count',
        'last_printed_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'scan_count' => 'integer',
            'last_printed_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function getUrlAttribute(): string
    {
        return url('/m/' . $this->public_token);
    }
}
