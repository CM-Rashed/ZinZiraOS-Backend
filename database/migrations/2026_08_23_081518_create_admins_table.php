<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            
            // Admin Credentials & Personal Details
            $table->string('admin_name');
            $table->string('admin_number');
            $table->string('email')->nullable()->unique(); // Optional email as requested
            $table->string('password');

            // Shop Details
            $table->string('shop_name');
            $table->string('shop_location');
            $table->integer('staff_numbers')->default(0);
            $table->enum('shop_type', ['grocery', 'supermarket', 'library', 'telecom']);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};