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
        Schema::create('user_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Customer & Delivery Info
            $table->string('name');
            $table->string('phone');
            $table->text('delivery_address');
            $table->enum('paid_by', ['cash', 'cod', 'online'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('order_status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();

            // Order Financial & Quantities
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_discount', 10, 2)->default(0.00);
            $table->decimal('total_price', 10, 2)->default(0.00);
            
            // Item snapshots stored as JSON
            $table->json('items');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast lookups
            $table->index('user_id');
            $table->index('phone');
            $table->index('order_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_orders');
    }
};
