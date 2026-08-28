<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Admin\Inventory;
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'quantity',
        'buying_price',
        'selling_price',
        'location',
        'notes',
    ];

    // Cast fields to appropriate native PHP data types
    protected $casts = [
        'quantity' => 'integer',
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'images' => 'array',
    ];

    // Relationship: A product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
  public function inventories() {
        return $this->hasMany(Inventory::class);
    }
}
