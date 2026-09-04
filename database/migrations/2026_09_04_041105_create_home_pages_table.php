<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_pages', function (Blueprint $table) {
            $table->id();

            // General Shop Info
            $table->string('website_name')->default('Your Website Name');
            $table->string('tagline')->default('Your Website Tagline');
            $table->string('shop_location')->default('Your Shop Location');
            $table->string('website_logo')->nullable()->default('default_logo.png');

            // Slider 1
            $table->string('slider1_hero_text')->default('Your Website Hero Text 1');
            $table->string('slider1_headline')->default('Your Website Headline 1');
            $table->string('slider1_paragraph')->default('Your Website Paragraph 1'); // Changed to string
            $table->string('slider1_image')->nullable()->default('default_slider1.png');
            $table->string('slider1_button')->default('Your Website Button 1');

            // Slider 2
            $table->string('slider2_hero_text')->default('Your Website Hero Text 2');
            $table->string('slider2_headline')->default('Your Website Headline 2');
            $table->string('slider2_paragraph')->default('Your Website Paragraph 2'); // Changed to string
            $table->string('slider2_image')->nullable()->default('default_slider2.png');
            $table->string('slider2_button')->default('Your Website Button 2');

            // 4 Cards
            for ($i = 1; $i <= 4; $i++) {
                $table->string("card{$i}_headline")->default("Your Website Card {$i} Headline");
                $table->string("card{$i}_text")->default("Your Website Card {$i} Text"); // Changed to string
            }

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_pages');
    }
};