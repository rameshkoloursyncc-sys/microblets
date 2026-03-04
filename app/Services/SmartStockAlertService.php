<?php

namespace App\Services;

use App\Models\StockAlertTracking;
use App\Mail\SmartStockReport;
use App\Mail\SmartStockReportExcel;
use Illuminate\Support\Facades\Mail;

class SmartStockAlertService
{
    /**
     * Get items that need alerts (not yet sent)
     */
    public function getItemsNeedingAlerts()
    {
        return StockAlertTracking::needsAlert()
            ->get()
            ->groupBy(['belt_type', 'section']);
    }

    /**
     * Send smart stock alerts
     */
    public function sendSmartAlerts(?array $emails = null)
    {
        // First, sync current stock data to tracking table
        $this->syncStockAlertTracking();
        
        $itemsNeedingAlerts = $this->getItemsNeedingAlerts();

        if ($itemsNeedingAlerts->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No new alerts to send',
                'alerts_sent' => 0
            ];
        }

        // Prepare alert data
        $alertData = $this->prepareAlertData($itemsNeedingAlerts);

        // Add inventory value summary to alert data
        try {
            $inventoryData = $this->getInventoryValueSummary();
            if (!empty($inventoryData)) {
                $alertData['inventory_summary'] = $inventoryData;
            }
        } catch (\Exception $e) {
            \Log::warning('Could not add inventory summary to smart alerts: ' . $e->getMessage());
        }

        // Send emails
        $emails = $emails ?? explode(',', config('mail.low_stock_recipients', 'ramesh.koloursyncc@gmail.com,microbelts@gmail.com'));
        if (is_string($emails)) {
            $emails = explode(',', $emails);
        }
        
        // Clean up email addresses (trim whitespace)
        $emails = array_map('trim', $emails);

        foreach ($emails as $email) {
            // Email 1: Smart Stock Alert Excel (existing)
            Mail::to(trim($email))->send(new SmartStockReportExcel($alertData));
            
            // Email 2: Production Planning Excel (new)
            Mail::to(trim($email))->send(new \App\Mail\ProductionPlanningExcel($alertData));
        }

        // Mark alerts as sent
        $this->markAlertsAsSent($itemsNeedingAlerts);

        return [
            'success' => true,
            'message' => 'Smart stock alerts sent successfully',
            'alerts_sent' => $itemsNeedingAlerts->flatten()->count(),
            'recipients' => $emails
        ];
    }

    /**
     * Sync current stock data to tracking table
     * NOW HANDLES: Incremental alerts, IN/OUT transactions, stock improvements
     */
    public function syncStockAlertTracking()
    {
        $beltTypes = [
            'vee_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'vee'],
            'cogged_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'cogged'],
            'poly_belts' => ['stock_column' => 'ribs', 'size_column' => 'size', 'name' => 'poly'],
            'tpu_belts' => ['stock_column' => 'meter', 'size_column' => 'width', 'name' => 'tpu'],
            'timing_belts' => ['stock_column' => 'total_mm', 'size_column' => 'size', 'name' => 'timing'],
            'special_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'special'],
            'raw_carbons' => ['stock_column' => 'balance_stock', 'size_column' => 'packing', 'name' => 'rawcarbon']
        ];

        foreach ($beltTypes as $table => $config) {
            try {
                // Check if table exists and has required columns
                $columns = \DB::getSchemaBuilder()->getColumnListing($table);
                if (!in_array($config['stock_column'], $columns)) {
                    $config['stock_column'] = 'balance_stock'; // fallback
                }
                if (!in_array($config['size_column'], $columns)) {
                    $config['size_column'] = 'size'; // fallback
                }

                // Get items that need tracking (have reorder_level >= 1 and are below reorder level)
                $lowStockItems = \DB::table($table)
                    ->select([
                        'id',
                        'section',
                        $config['size_column'] . ' as size',
                        $config['stock_column'] . ' as current_stock',
                        'reorder_level'
                    ])
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} < reorder_level")
                    ->get();

                foreach ($lowStockItems as $item) {
                    // Get dynamic stock per die configuration
                    $stockPerDie = \App\Models\DieConfiguration::getStockPerDie($config['name'], $item->section);
                    
                    // Create SKU
                    $sku = $item->section . '-' . $item->size;

                    // Check if tracking record exists
                    $tracking = StockAlertTracking::where('belt_type', $config['name'])
                        ->where('section', $item->section)
                        ->where('product_id', $item->id)
                        ->first();

                    if ($tracking) {
                        // ===== EXISTING RECORD - INCREMENTAL LOGIC =====
                        
                        $previousStock = $tracking->current_stock; // Stock before this sync
                        $newStock = $item->current_stock; // Current stock from database
                        
                        // Case 1: Stock improved (IN transaction)
                        if ($newStock > $previousStock) {
                            // If stock is back above minimum, reset everything
                            if ($newStock >= $item->reorder_level) {
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $newStock,
                                    'reorder_level' => $item->reorder_level,
                                    'stock_per_die' => $stockPerDie,
                                    'dies_needed' => 0,
                                    'is_active' => true,
                                    'alert_sent' => false,
                                    'last_alerted_stock' => null
                                ]);
                                continue;
                            }
                            
                            // Stock improved but still below minimum - just update stock, keep alert status
                            $tracking->update([
                                'current_stock' => $newStock,
                                'previous_stock' => $newStock, // Update for next comparison
                                'reorder_level' => $item->reorder_level,
                                'stock_per_die' => $stockPerDie,
                                'is_active' => true
                                // Keep alert_sent and last_alerted_stock unchanged
                            ]);
                            continue;
                        }
                        // Case 2: Stock dropped (OUT transaction)
                        else if ($newStock < $previousStock) {
                            // Determine if we need a new alert
                            if ($tracking->alert_sent && $tracking->last_alerted_stock !== null) {
                                // Already alerted before - only alert if dropped below last alerted level
                                if ($newStock < $tracking->last_alerted_stock) {
                                    // Stock dropped below last alerted level - NEW ALERT NEEDED
                                    // Calculate deficit from last_alerted_stock (not previous_stock)
                                    $deficit = $tracking->last_alerted_stock - $newStock;
                                    $diesNeeded = ceil($deficit / $stockPerDie);
                                    
                                    $tracking->update([
                                        'current_stock' => $newStock,
                                        'previous_stock' => $previousStock,
                                        'reorder_level' => $item->reorder_level,
                                        'stock_per_die' => $stockPerDie,
                                        'dies_needed' => $diesNeeded,
                                        'is_active' => true,
                                        'alert_sent' => false // Trigger new alert
                                    ]);
                                } else {
                                    // Stock dropped but still above last alerted level - no new alert
                                    $tracking->update([
                                        'current_stock' => $newStock,
                                        'previous_stock' => $previousStock,
                                        'reorder_level' => $item->reorder_level,
                                        'stock_per_die' => $stockPerDie,
                                        'is_active' => true
                                        // Keep alert_sent = true, no new alert
                                    ]);
                                }
                            } else {
                                // First time below minimum OR alert not sent yet
                                $firstTimeDeficit = $item->reorder_level - $newStock;
                                $firstTimeDies = ceil($firstTimeDeficit / $stockPerDie);
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'reorder_level' => $item->reorder_level,
                                    'stock_per_die' => $stockPerDie,
                                    'dies_needed' => $firstTimeDies,
                                    'is_active' => true,
                                    'alert_sent' => false
                                ]);
                            }
                        }
                        // Case 3: Stock unchanged
                        else {
                            // Just update metadata, keep everything else
                            $tracking->update([
                                'reorder_level' => $item->reorder_level,
                                'stock_per_die' => $stockPerDie,
                                'is_active' => true
                            ]);
                        }
                        
                    } else {
                        // ===== NEW RECORD - FIRST TIME LOW STOCK =====
                        
                        // Calculate initial deficit and dies needed
                        $deficit = max(0, $item->reorder_level - $item->current_stock);
                        $diesNeeded = ceil($deficit / $stockPerDie);

                        // Create new tracking record
                        StockAlertTracking::create([
                            'belt_type' => $config['name'],
                            'section' => $item->section,
                            'product_id' => $item->id,
                            'product_sku' => $sku,
                            'current_stock' => $item->current_stock,
                            'reorder_level' => $item->reorder_level,
                            'stock_per_die' => $stockPerDie,
                            'dies_needed' => $diesNeeded,
                            'alert_sent' => false,
                            'is_active' => true,
                            'previous_stock' => $item->current_stock, // Initialize with current
                            'last_alerted_stock' => null // Will be set when alert is sent
                        ]);
                    }
                }

                // Deactivate tracking for items that are no longer low stock
                // Also reset their tracking fields for clean slate
                StockAlertTracking::where('belt_type', $config['name'])
                    ->where('is_active', true)
                    ->whereNotIn('product_id', $lowStockItems->pluck('id'))
                    ->update([
                        'is_active' => false,
                        'alert_sent' => false,
                        'last_alerted_stock' => null,
                        'previous_stock' => null
                    ]);

            } catch (\Exception $e) {
                \Log::warning("Error syncing stock tracking for {$table}: " . $e->getMessage());
            }
        }
    }

    /**
     * Prepare alert data with die calculations (public method)
     */
    public function prepareAlertData($itemsNeedingAlerts)
    {
        $alertData = [
            'generated_at' => now()->toDateTimeString(),
            'total_items' => $itemsNeedingAlerts->flatten()->count(),
            'total_dies_needed' => 0,
            'belt_types' => []
        ];

        foreach ($itemsNeedingAlerts as $beltType => $sections) {
            $beltData = [
                'name' => ucfirst($beltType) . ' Belts',
                'sections' => [],
                'total_items' => 0,
                'total_dies' => 0
            ];

            foreach ($sections as $section => $items) {
                $sectionDies = $items->sum('dies_needed');
                $beltData['sections'][$section] = [
                    'items' => $items,
                    'count' => $items->count(),
                    'dies_needed' => $sectionDies
                ];
                $beltData['total_items'] += $items->count();
                $beltData['total_dies'] += $sectionDies;
            }

            $alertData['belt_types'][$beltType] = $beltData;
            $alertData['total_dies_needed'] += $beltData['total_dies'];
        }

        return $alertData;
    }

    /**
     * Mark alerts as sent (public method for cron job)
     */
    public function markAlertsAsSent($itemsNeedingAlerts)
    {
        foreach ($itemsNeedingAlerts->flatten() as $tracking) {
            $tracking->markAlertSent();
        }
    }

    /**
     * Get die requirements summary (all low stock items)
     */
    public function getDieRequirementsSummary()
    {
        // First sync the data
        $this->syncStockAlertTracking();
        
        // Return all active low stock items (regardless of alert status)
        return StockAlertTracking::where('is_active', true)
            ->whereRaw('current_stock < reorder_level')
            ->selectRaw('belt_type, section, SUM(dies_needed) as total_dies, COUNT(*) as items_count')
            ->groupBy(['belt_type', 'section'])
            ->get()
            ->groupBy('belt_type');
    }

    /**
     * Get die requirements summary (only unalerted items)
     */
    public function getDieRequirementsUnalerted()
    {
        // First sync the data
        $this->syncStockAlertTracking();
        
        return StockAlertTracking::needsAlert()
            ->selectRaw('belt_type, section, SUM(dies_needed) as total_dies, COUNT(*) as items_count')
            ->groupBy(['belt_type', 'section'])
            ->get()
            ->groupBy('belt_type');
    }

    /**
     * Send smart stock alerts with force option
     */
    public function sendSmartAlertsForced(?array $emails = null)
    {
        // First, sync current stock data to tracking table
        $this->syncStockAlertTracking();
        
        // Reset all alert_sent flags to force sending
        StockAlertTracking::where('is_active', true)
            ->where('alert_sent', true)
            ->update(['alert_sent' => false]);
        
        return $this->sendSmartAlerts($emails);
    }

    /**
    /**
     * Reset alerts for replenished items
     */
    public function resetReplenishedAlerts()
    {
        $replenishedItems = StockAlertTracking::where('alert_sent', true)
            ->whereRaw('current_stock >= reorder_level')
            ->get();

        foreach ($replenishedItems as $item) {
            $item->resetAlert();
        }

        return $replenishedItems->count();
    }

    /**
     * Get inventory value summary for daily report
     */
    public function getInventoryValueSummary()
    {
        try {
            // Use the existing DashboardController logic
            $controller = new \App\Http\Controllers\Api\DashboardController();
            $response = $controller->getInventoryStats();
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                return $data['data'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            \Log::warning('Error getting inventory value summary: ' . $e->getMessage());
            return [];
        }
    }
}