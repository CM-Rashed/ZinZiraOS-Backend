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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            
            // Authentication Credentials
            $table->string('email')->unique();
            $table->string('password');

            // Staff Details
            $table->string('name');
            $table->integer('age');
            $table->string('mobile');
            $table->decimal('salary', 10, 2);
            $table->string('photo')->nullable(); // Optional photo path/URL

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};