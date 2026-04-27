<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'barcode',
        'sku',
        'image_path',
        'unit',
        'weight',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    /**
     * Get the category that owns the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ProductCategory, Product>
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the brand that owns the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Brand, Product>
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the inventories for the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Inventory, Product>
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the stock movements for the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<StockMovement, Product>
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the cart items for the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<CartItem, Product>
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
