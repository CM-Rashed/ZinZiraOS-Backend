<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class UserOrder extends Model
{
   use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'name',
        'phone',
        'delivery_address',
        'paid_by',
        'payment_status',
        'order_status',
        'notes',
        'total_quantity',
        'total_discount',
        'total_price',
        'items',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array', // Ensures items is automatically serialized/deserialized to JSON
        'total_price' => 'decimal:2',
        'total_discount' => 'decimal:2',
    ];
}
