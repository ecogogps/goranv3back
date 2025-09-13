<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'woocommerce_id',
        'name',
        'slug',
        'parent_id',
        'description',
        'display',
        'image',
        'count',
        'last_synced_at',
    ];

    protected $casts = [
        'count' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relación con categoría padre
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * Relación con subcategorías hijas
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    /**
     * Relación muchos a muchos con productos
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_category', 'product_category_id', 'product_id');
    }

    /**
     * Scope para categorías padre (sin parent_id)
     */
    public function scopeParent($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope para subcategorías (con parent_id)
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }
}
