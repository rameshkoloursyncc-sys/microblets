<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50); // vee_belts, tpu_belts, etc.
            $table->unsignedBigInteger('product_id'); // ID from respective table
            $table->enum('type', ['IN', 'OUT', 'EDIT']);
            $table->integer('quantity')->nullable(); // For IN/OUT
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->decimal('rate', 10, 2);
            $table->text('description');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index(['category', 'product_id']);
            $table->index('user_id');
            $table->index('created_at');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
