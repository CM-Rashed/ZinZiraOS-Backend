<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable(); // Optional field
            $table->string('guardian_number');
            $table->string('staff_number')->unique();
            $table->decimal('salary', 10, 2);
            $table->integer('age');
            $table->enum('type', ['full_time', 'part_time']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};