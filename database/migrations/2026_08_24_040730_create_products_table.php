<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            
            // Core Inventory Tracking
            $table->unsignedInteger('quantity')->default(0); // Prevents negative inventory
            $table->unsignedInteger('alert_quantity')->default(5); // Low stock threshold
            $table->string('unit')->default('pcs'); // pcs, box, kg, etc.
            $table->string('location')->nullable(); // Warehouse / Shelf / Bin
            
            // Pricing & Valuation
            $table->decimal('buying_price', 10, 2)->default(0.00);
            $table->decimal('selling_price', 10, 2)->default(0.00);
            
            // Metadata & Flags
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->string('image')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Preserves inventory history

            // Performance Indexes
            $table->index('category_id');
            $table->index(['quantity', 'alert_quantity']);
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};