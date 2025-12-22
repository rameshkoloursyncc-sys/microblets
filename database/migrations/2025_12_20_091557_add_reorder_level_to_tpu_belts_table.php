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
        Schema::table('tpu_belts', function (Blueprint $table) {
            $table->decimal('reorder_level', 10, 2)->default(5.00)->after('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tpu_belts', function (Blueprint $table) {
            $table->dropColumn('reorder_level');
        });
    }
};