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
        Schema::table('stock_alert_tracking', function (Blueprint $table) {
            // Add columns to track stock changes for incremental alerts
            $table->decimal('last_alerted_stock', 10, 2)->nullable()->after('alert_sent_at')
                ->comment('Stock level when last alert was sent');
            $table->decimal('previous_stock', 10, 2)->nullable()->after('last_alerted_stock')
                ->comment('Stock level before current sync (for calculating incremental deficit)');
        });
        
        // Backfill existing records with data from alert_history
        \DB::table('stock_alert_tracking')
            ->where('alert_sent', true)
            ->whereNotNull('alert_history')
            ->get()
            ->each(function ($record) {
                $history = json_decode($record->alert_history, true);
                if (!empty($history)) {
                    $lastAlert = end($history);
                    \DB::table('stock_alert_tracking')
                        ->where('id', $record->id)
                        ->update([
                            'last_alerted_stock' => $lastAlert['stock_at_time'] ?? null,
                            'previous_stock' => $record->current_stock
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_alert_tracking', function (Blueprint $table) {
            $table->dropColumn(['last_alerted_stock', 'previous_stock']);
        });
    }
};
