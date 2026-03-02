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
            // Create dashboard snapshot before sending report (5 PM snapshot)
            $this->info('📊 Creating dashboard snapshot...');
            try {
                \Artisan::call('dashboard:snapshot');
                $this->info('✅ Dashboard snapshot created successfully');
            } catch (\Exception $e) {
                $this->warn('⚠️  Could not create snapshot: ' . $e->getMessage());
                // Continue with report even if snapshot fails
            }

        $smartAlertService = new \App\Services\SmartStockAlertService();


            try {
    $inventoryData = $smartAlertService->getInventoryValueSummary();
    $this->info('✅ Inventory value data retrieved successfully');
} catch (\Exception $e) {
    $this->warn('⚠️  Could not get inventory data: ' . $e->getMessage());
    $inventoryData = [];
}






            // Use Smart Alert System (same as dashboard) - respects alert_sent status
            $smartAlertService = new \App\Services\SmartStockAlertService();
            
            // Get email addresses from command option or config
            $emails = $this->option('email');
            if (empty($emails)) {
                // Use config with fallback handling for both string and array
                $emailRecipients = config('mail.low_stock_recipients', 'ramesh.koloursyncc@gmail.com,microbelts@gmail.com');
                
                // Handle both string and array formats
                if (is_array($emailRecipients)) {
                    $emails = $emailRecipients;
                } else {
                    $emails = explode(',', $emailRecipients);
                }
            }
            
            // Clean up email addresses (trim whitespace)
            $emails = array_map('trim', $emails);
            
            $this->info('📧 Email recipients: ' . implode(', ', $emails));

            // Sync and get items that actually need alerts (not already sent)
            $smartAlertService->syncStockAlertTracking();
            $itemsNeedingAlerts = $smartAlertService->getItemsNeedingAlerts();
            
            if (!$itemsNeedingAlerts->isEmpty()) {
                // Prepare alert data with inventory summary
                $alertData = $smartAlertService->prepareAlertData($itemsNeedingAlerts);
                
                // Add inventory value summary
                try {
                    $inventoryData = $smartAlertService->getInventoryValueSummary();
                    if (!empty($inventoryData)) {
                        $alertData['inventory_summary'] = $inventoryData;
                    }
                } catch (\Exception $e) {
                    $this->warn('⚠️  Could not add inventory summary: ' . $e->getMessage());
                }
                
                $totalItems = $alertData['total_items'] ?? 0;
                $this->info("Found {$totalItems} items needing alerts (not previously sent). Sending reports...");
                
                foreach ($emails as $email) {
                    // Email 1: Smart Stock Alert Excel (with inventory summary)
                    Mail::to(trim($email))->send(new \App\Mail\SmartStockReportExcel($alertData));
                    $this->info("Smart stock alert report sent to: {$email}");

                    // Email 2: Production Planning Excel
                    Mail::to(trim($email))->send(new \App\Mail\ProductionPlanningExcel($alertData));
                    $this->info("Production planning report sent to: {$email}");
                }
                
                // Mark alerts as sent using Smart Alert Service
                $smartAlertService->markAlertsAsSent($itemsNeedingAlerts);
                
                $this->info('✅ Stock alert reports sent successfully and alerts marked as sent!');
            } else {
                $this->info('ℹ️  No new alerts to send - all low stock items have already been alerted.');
                $this->info('📊 Sending daily inventory summary report...');
                
                // Always send daily inventory summary even when no alerts
                try {
                    $inventoryData = $smartAlertService->getInventoryValueSummary();
                    $dailyInventoryData = [
                        'generated_at' => now()->toDateTimeString(),
                        'total_items' => 0,
                        'total_dies_needed' => 0,
                        'belt_types' => [],
                        'inventory_summary' => $inventoryData,
                        'message' => 'Daily Inventory Summary - No New Stock Alerts'
                    ];
                    
                    foreach ($emails as $email) {
                        Mail::to(trim($email))->send(new \App\Mail\SmartStockReportExcel($dailyInventoryData));
                        $this->info("Daily inventory summary sent to: {$email}");
                    }
                    
                    $this->info('✅ Daily inventory summary sent successfully!');
                } catch (\Exception $e) {
                    $this->warn("Could not send daily inventory summary: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $this->error('Error generating low stock report: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
