<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'type',
        'category',
        'amount',
        'reference_type',
        'reference_id',
        'description',
    ];
}
