<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    use HasFactory;

    protected $fillable = [
        'slider_hero_text',
        'slider_headline',
        'slider_paragraph',
        'slider_button',

        'why_choose_hero_text',
        'why_choose_headline',
        'why_choose_paragraph',

        'card1_icon',
        'card1_header',
        'card1_title',
        'card2_icon',
        'card2_header',
        'card2_title',
        'card3_icon',
        'card3_header',
        'card3_title',

        'physical_outlets_header',
        'physical_outlets_title',

        'stores_location',
        'stores_google_map_location',
        'working_hours',
        'hotline_number_1',
        'hotline_number_2',
        'hotmail',

        'our_vision_header',
        'our_vision_paragraph',
        'our_vision_image',
    ];
}