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
        Schema::table('timing_belts', function (Blueprint $table) {
            if (Schema::hasColumn('timing_belts', 'balance_stock')) {
                $table->dropColumn('balance_stock');
            }

            if (Schema::hasColumn('timing_belts', 'in_stock')) {
                $table->dropColumn('in_stock');
            }

            if (Schema::hasColumn('timing_belts', 'out_stock')) {
                $table->dropColumn('out_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timing_belts', function (Blueprint $table) {
            // Optional: add columns back if needed
            // $table->integer('balance_stock')->nullable();
            // $table->integer('in_stock')->nullable();
            // $table->integer('out_stock')->nullable();
        });
    }
};
