<?php

namespace App\Models\Admin;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'admin_name',
        'admin_number',
        'email',
        'password',
        'shop_name',
        'shop_location',
        'staff_numbers',
        'shop_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}