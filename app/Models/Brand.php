<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'logo_path',
    ];

    /**
     * Get the products for the brand.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Product, Brand>
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
