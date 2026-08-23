<?php

namespace App\Models\Staff;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasApiTokens;

    // Specifies the table name if Laravel defaults to 'staffs'
    protected $table = 'staff';

    protected $fillable = [
        'name',
        'email',
        'password',
        'age',
        'mobile',
        'salary',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'age' => 'integer',
    ];
}

