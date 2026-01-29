<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_carbons', function (Blueprint $table) {
            $table->decimal('balance_stock', 10, 3)->default(0)->change(); // Change from integer to decimal(10,3)
            $table->decimal('in_stock', 10, 3)->default(0)->change(); // Also change related stock fields
            $table->decimal('out_stock', 10, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('raw_carbons', function (Blueprint $table) {
            $table->integer('balance_stock')->default(0)->change(); // Revert back to integer
            $table->integer('in_stock')->default(0)->change();
            $table->integer('out_stock')->default(0)->change();
        });
    }
};