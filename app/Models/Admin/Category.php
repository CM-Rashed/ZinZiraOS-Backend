<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned via create() or update()
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    // Example Relationship: A category has many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}