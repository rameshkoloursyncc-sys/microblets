<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_carbons', function (Blueprint $table) {
            $table->decimal('reorder_level', 10, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('raw_carbons', function (Blueprint $table) {
            $table->decimal('reorder_level', 10, 3)->nullable(false)->change();
        });
    }
};