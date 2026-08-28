<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_inventories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('added_quantity'); // Positive for addition, negative for reduction
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inventories');
    }
};