<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'src_store_id',
        'dest_store_id',
        'product_id',
        'quantity',
        'type',
        'title',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    /**
     * Get the source store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Store, StockMovement>
     */
    public function sourceStore()
    {
        return $this->belongsTo(Store::class, 'src_store_id');
    }

    /**
     * Get the destination store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Store, StockMovement>
     */
    public function destinationStore()
    {
        return $this->belongsTo(Store::class, 'dest_store_id');
    }

    /**
     * Get the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, StockMovement>
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
