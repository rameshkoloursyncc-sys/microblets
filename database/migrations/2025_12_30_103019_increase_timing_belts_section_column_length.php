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
            // Increase section column length from varchar(10) to varchar(20) to accommodate NEOPRENE-XL, NEOPRENE-XH, etc.
            $table->string('section', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timing_belts', function (Blueprint $table) {
            // Revert back to varchar(10)
            $table->string('section', 10)->change();
        });
    }
};