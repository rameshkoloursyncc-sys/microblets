<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowStockReport;
use App\Mail\LowStockReportExcel;

class SendDailyLowStockReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:low-stock {--email=* : Email addresses to send the report to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily low stock and out of stock report via email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating daily stock alert report (low stock + out of stock)...');

        try {
            // Get low stock data
            $lowStockData = $this->getLowStockData();
            
            // Get email addresses from command option or config
            $emails = $this->option('email');
            if (empty($emails)) {
                $emails = explode(',', env('LOW_STOCK_EMAIL_RECIPIENTS', 'admin@example.com'));
            }

            // Send email if there are low stock or out of stock items
            $totalLowStock = $lowStockData['total_low_stock_count'] ?? 0;
            $totalOutOfStock = $lowStockData['total_out_of_stock_count'] ?? 0;
            $totalAlerts = $lowStockData['total_alert_count'] ?? 0;
            
            if ($totalAlerts > 0) {
                $this->info("Found {$totalLowStock} low stock and {$totalOutOfStock} out of stock items. Sending report...");
                
                foreach ($emails as $email) {
                    Mail::to(trim($email))->send(new LowStockReportExcel($lowStockData));
                    $this->info("Excel report sent to: {$email}");
                }
                
                // Mark alerts as sent in StockAlertTracking table
                $this->markStockAlertsAsSent($lowStockData);
                
                $this->info('✅ Stock alert report sent successfully and alerts marked as sent!');
            } else {
                $this->info('ℹ️  No low stock or out of stock items found. No email sent.');
                
                // Optionally send a "all good" report on specific days (e.g., Monday)
                if (now()->dayOfWeek === 1) { // Monday
                    foreach ($emails as $email) {
                        Mail::to(trim($email))->send(new LowStockReportExcel($lowStockData));
                        $this->info("Weekly 'all good' Excel report sent to: {$email}");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('Error generating low stock report: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getLowStockData()
    {
        $lowStockItems = [];
        $outOfStockItems = [];

        // Get low stock and out of stock items from all belt types
        $beltTypes = [
            'vee_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Vee Belts'],
            'cogged_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Cogged Belts'],
            'poly_belts' => ['stock_column' => 'ribs', 'size_column' => 'size', 'name' => 'Poly Belts'],
            'tpu_belts' => ['stock_column' => 'meter', 'size_column' => 'width', 'name' => 'TPU Belts'],
            'timing_belts' => ['stock_column' => 'total_mm', 'size_column' => 'size', 'name' => 'Timing Belts'],
            'special_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Special Belts']
        ];

        foreach ($beltTypes as $table => $config) {
            try {
                // Check if table exists and has required columns
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                if (!in_array($config['stock_column'], $columns)) {
                    $config['stock_column'] = 'balance_stock'; // fallback
                }
                if (!in_array($config['size_column'], $columns)) {
                    $config['size_column'] = 'size'; // fallback
                }

                // Build select array based on available columns
                $selectColumns = [
                    'id',
                    'section',
                    $config['size_column'] . ' as size',
                    $config['stock_column'] . ' as current_stock',
                    'reorder_level'
                ];

                // Add optional columns if they exist
                if (in_array('sku', $columns)) {
                    $selectColumns[] = 'sku';
                }
                if (in_array('value', $columns)) {
                    $selectColumns[] = 'value';
                }

                // Get LOW STOCK items (reorder_level >= 1 AND current_stock > 0 AND current_stock <= reorder_level)
                $lowStockQuery = DB::table($table)
                    ->select($selectColumns)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} > 0")
                    ->whereRaw("{$config['stock_column']} <= reorder_level")
                    ->orderBy('section')
                    ->orderBy($config['size_column'])
                    ->get();

                // Get OUT OF STOCK items (reorder_level >= 1 AND current_stock = 0)
                $outOfStockQuery = DB::table($table)
                    ->select($selectColumns)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} = 0")
                    ->orderBy('section')
                    ->orderBy($config['size_column'])
                    ->get();

                if ($lowStockQuery->count() > 0) {
                    $lowStockItems[$table] = [
                        'name' => $config['name'],
                        'items' => $lowStockQuery->toArray(),
                        'count' => $lowStockQuery->count()
                    ];
                }

                if ($outOfStockQuery->count() > 0) {
                    $outOfStockItems[$table] = [
                        'name' => $config['name'],
                        'items' => $outOfStockQuery->toArray(),
                        'count' => $outOfStockQuery->count()
                    ];
                }
            } catch (\Exception $e) {
                $this->warn("Error processing {$table}: " . $e->getMessage());
            }
        }

        return [
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'total_low_stock_count' => array_sum(array_column($lowStockItems, 'count')),
            'total_out_of_stock_count' => array_sum(array_column($outOfStockItems, 'count')),
            'total_alert_count' => array_sum(array_column($lowStockItems, 'count')) + array_sum(array_column($outOfStockItems, 'count')),
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Mark stock alerts as sent in StockAlertTracking table
     */
    private function markStockAlertsAsSent($lowStockData)
    {
        try {
            // First sync the stock alert tracking data
            $smartAlertService = new \App\Services\SmartStockAlertService();
            $smartAlertService->syncStockAlertTracking();
            
            // Get all low stock and out of stock items from the data
            $allItems = array_merge(
                $lowStockData['low_stock_items'] ?? [],
                $lowStockData['out_of_stock_items'] ?? []
            );
            
            // Map table names to belt types
            $tableToType = [
                'vee_belts' => 'vee',
                'cogged_belts' => 'cogged',
                'poly_belts' => 'poly',
                'tpu_belts' => 'tpu',
                'timing_belts' => 'timing',
                'special_belts' => 'special'
            ];
            
            $markedCount = 0;
            foreach ($allItems as $table => $data) {
                $beltType = $tableToType[$table] ?? null;
                if (!$beltType || !isset($data['items'])) {
                    continue;
                }
                
                foreach ($data['items'] as $item) {
                    // Find the corresponding tracking record and mark as sent
                    $tracking = \App\Models\StockAlertTracking::where('belt_type', $beltType)
                        ->where('product_id', $item->id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($tracking) {
                        $tracking->markAlertSent();
                        $markedCount++;
                    }
                }
            }
            
            $this->info("Marked {$markedCount} alerts as sent in tracking system.");
            
        } catch (\Exception $e) {
            $this->warn("Error marking stock alerts as sent: " . $e->getMessage());
        }
    }
}
