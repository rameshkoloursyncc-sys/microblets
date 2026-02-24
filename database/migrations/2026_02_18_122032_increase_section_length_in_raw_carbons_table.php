<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_carbons', function (Blueprint $table) {
            $table->string('section', 100)->change(); // Increase from 50 to 100 characters
        });
    }

    public function down(): void
    {
        Schema::table('raw_carbons', function (Blueprint $table) {
            $table->string('section', 50)->change(); // Revert back to 50 characters
        });
    }
};
