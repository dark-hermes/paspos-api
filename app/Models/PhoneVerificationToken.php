<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneVerificationToken extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'phone',
        'purpose',
        'token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
