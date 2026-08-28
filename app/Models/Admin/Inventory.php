<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model {
    protected $fillable = [
        'product_id',
        'added_quantity',
        'previous_quantity',
        'new_quantity',
        'notes',
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
