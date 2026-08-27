<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'total_quantity',
        'total_discount',
        'total_price',
        'items',
    ];

    /**
     * Automatic attribute casting.
     */
    protected $casts = [
        'items' => 'array',
        'total_quantity' => 'integer',
        'total_discount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];
}
