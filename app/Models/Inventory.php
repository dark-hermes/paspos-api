<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'product_id',
        'stock',
        'purchase_price',
        'selling_price',
        'discount_percentage',
        'min_stock',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'discount_percentage' => 'integer',
            'min_stock' => 'decimal:2',
        ];
    }

    /**
     * Get the store that owns the inventory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Store, Inventory>
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the product that owns the inventory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, Inventory>
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
