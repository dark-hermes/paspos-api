<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'type',
    ];

    /**
     * Get the users that belong to the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the inventories for the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Inventory, Store>
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the cart items for the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<CartItem, Store>
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
