<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_us', function (Blueprint $table) {
            $table->id();

            // Sliders
            $table->string('slider_hero_text')->default('Your About Us Hero Text');
            $table->string('slider_headline')->default('Your About Us Headline');
            $table->string('slider_paragraph')->default('Your About Us Paragraph'); // Changed to string
            $table->string('slider_button')->default('Your About Us Button');

            // Why Choose Us
            $table->string('why_choose_hero_text')->default('Why Choose Us Hero Text');
            $table->string('why_choose_headline')->default('Why Choose Us Headline');
            $table->string('why_choose_paragraph')->default('Why Choose Us Paragraph'); // Changed to string

            // 3 Cards (Icon, Header, Title)
            for ($i = 1; $i <= 3; $i++) {
                $table->string("card{$i}_icon")->nullable()->default("default_card{$i}_icon.png");
                $table->string("card{$i}_header")->default("Card {$i} Header");
                $table->string("card{$i}_title")->default("Card {$i} Title");
            }

            // Physical Outlets
            $table->string('physical_outlets_header')->default('Our Outlets Header');
            $table->string('physical_outlets_title')->default('Our Outlets Title');

            // Store Info & Contact
            $table->string('stores_location')->default('123 Main Street, City, Country'); // Changed to string
            $table->string('stores_google_map_location')->default('https://maps.google.com/?q=loc:0,0'); // Changed to string
            $table->string('working_hours')->default('Mon - Sat: 9:00 AM - 8:00 PM');
            $table->string('hotline_number_1')->default('+1234567890');
            $table->string('hotline_number_2')->default('+0987654321');
            $table->string('hotmail')->default('support@example.com');

            // Our Vision
            $table->string('our_vision_header')->default('Our Vision Header');
            $table->string('our_vision_paragraph')->default('Our Vision Paragraph'); // Changed to string
            $table->string('our_vision_image')->nullable()->default('default_vision.png');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_us');
    }
};