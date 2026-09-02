<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'password',
        'image',           // Renamed from 'photo'
        'staff_number',    // Removed 'guardian_number'
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

    /**
     * Get all attendance logs for this staff member.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class, 'staff_id');
    }
}