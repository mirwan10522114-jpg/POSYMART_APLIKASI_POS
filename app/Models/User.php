<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'is_active',
        'last_active_at',
        'note',
        'archived_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === 'Owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['Owner', 'Admin']);
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['Owner', 'Admin', 'Manager']);
    }

    public function isCashier(): bool
    {
        return in_array($this->role, ['Owner', 'Admin', 'Manager', 'Cashier']);
    }
}
