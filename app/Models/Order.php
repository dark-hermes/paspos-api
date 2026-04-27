<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
    ];

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function cashier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
