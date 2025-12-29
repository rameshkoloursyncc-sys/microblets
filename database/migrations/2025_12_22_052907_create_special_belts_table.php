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
        Schema::create('special_belts', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20); // Conical C, Harvester, RAX, RBX, R3VX, R5VX, 8M PK, 8M PL
            $table->string('size', 20);
            $table->string('type', 30)->default('Special'); // Special, Banded Cogged, Hybrid, Coating
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
            $table->index('type');
            $table->index('balance_stock');
            $table->unique(['section', 'size', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_belts');
    }
};
