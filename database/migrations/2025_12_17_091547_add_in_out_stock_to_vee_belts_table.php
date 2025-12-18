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
        Schema::table('vee_belts', function (Blueprint $table) {
            $table->integer('in_stock')->default(0)->after('balance_stock');
            $table->integer('out_stock')->default(0)->after('in_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vee_belts', function (Blueprint $table) {
            $table->dropColumn(['in_stock', 'out_stock']);
        });
    }
};
