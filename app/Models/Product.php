<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'woocommerce_id',
        'name',
        'slug',
        'type',
        'status',
        'featured',
        'catalog_visibility',
        'price',
        'regular_price',
        'sale_price',
        'on_sale',
        'stock_status',
        'stock_quantity',
        'manage_stock',
        'sku',
        'weight',
        'dimensions',
        'short_description',
        'description',
        'images',
        'attributes',
        'total_sales',
        'tax_status',
        'tax_class',
        'last_synced_at',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'on_sale' => 'boolean',
        'manage_stock' => 'boolean',
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'dimensions' => 'array',
        'images' => 'array',
        'attributes' => 'array',
        'total_sales' => 'integer',
        'last_synced_at' => 'datetime',
    ];
    
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'product_product_category', 'product_id', 'product_category_id');
    }

    /**
     * The statuses that belong to the product.
     */
    public function statuses(): BelongsToMany
    {
        return $this->belongsToMany(ProductStatus::class, 'product_product_status')
                    ->withPivot([
                        'stock_quantity',
                        'is_own_product',
                        'sample_available',
                        'preventa_start_date',
                        'preventa_end_date'
                    ])
                    ->withTimestamps();
    }

    /**
     * Scope para productos publicados
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    /**
     * Scope para productos destacados
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope para productos en stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'instock');
    }
}
