<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_name',
        'tagline',
        'shop_location',
        'website_logo',
        'slider1_hero_text',
        'slider1_headline',
        'slider1_paragraph',
        'slider1_image',
        'slider1_button',
        'slider2_hero_text',
        'slider2_headline',
        'slider2_paragraph',
        'slider2_image',
        'slider2_button',
        'card1_headline',
        'card1_text',
        'card2_headline',
        'card2_text',
        'card3_headline',
        'card3_text',
        'card4_headline',
        'card4_text',
    ];
}
