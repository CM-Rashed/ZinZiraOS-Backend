<?php

namespace App\Models\Staff;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'password',
        'photo',
        'guardian_number',
        'staff_number',
        'salary',
        'age',
        'type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed', // Automatically handles Hash::make()
        'salary'   => 'decimal:2',
        'age'      => 'integer',
    ];
}