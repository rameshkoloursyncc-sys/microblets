<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cogged_belts', function (Blueprint $table) {
            $table->id();
            $table->string('section', 10); // AX, BX, CX, XPA, XPB, XPC, XPZ, 3VX, 5VX, 8VX
            $table->string('size', 20);
            $table->integer('balance_stock')->default(0);
            $table->integer('in_stock')->default(0);
            $table->integer('out_stock')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('value', 10, 2)->default(0);
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            $table->index('section');
            $table->index('balance_stock');
            $table->unique(['section', 'size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cogged_belts');
    }
};
