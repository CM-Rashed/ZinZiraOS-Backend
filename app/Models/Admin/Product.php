<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'quantity',
        'alert_quantity',
        'unit',
        'buying_price',
        'selling_price',
        'location',
        'is_active',
        'notes',
        'image',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'category_id'    => 'integer',
        'quantity'       => 'integer',
        'alert_quantity' => 'integer',
        'buying_price'   => 'decimal:2',
        'selling_price'  => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    /**
     * Appended accessors.
     */
    protected $appends = [
        'image_url',
        'is_low_stock',
    ];

    /**
     * Get the full URL for the product image.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    /**
     * Accessor to check if the product is running low on stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity <= $this->alert_quantity;
    }

    /**
     * Product category relation.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope: Filter active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter low-stock products.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'alert_quantity');
    }
}