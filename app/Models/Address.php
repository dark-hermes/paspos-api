<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'notes',
        'receiver_name',
        'receiver_phone',
        'is_default',
        'user_id',
    ];

    /**
     * Get the user that owns the address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Address>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
