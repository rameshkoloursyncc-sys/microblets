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

        // Send emails
        $emails = $emails ?? explode(',', env('LOW_STOCK_EMAIL_RECIPIENTS', 'ramesh.koloursyncc@gmail.com'));
        if (is_string($emails)) {
            $emails = explode(',', $emails);
        }
        
        // Clean up email addresses (trim whitespace)
        $emails = array_map('trim', $emails);

        foreach ($emails as $email) {
            Mail::to(trim($email))->send(new SmartStockReportExcel($alertData));
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
     */
    public function syncStockAlertTracking()
    {
        $beltTypes = [
            'vee_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'vee'],
            'cogged_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'cogged'],
            'poly_belts' => ['stock_column' => 'ribs', 'size_column' => 'size', 'name' => 'poly'],
            'tpu_belts' => ['stock_column' => 'meter', 'size_column' => 'width', 'name' => 'tpu'],
            'timing_belts' => ['stock_column' => 'total_mm', 'size_column' => 'size', 'name' => 'timing'],
            'special_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'special']
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
                    
                    // Calculate dies needed
                    $deficit = max(0, $item->reorder_level - $item->current_stock);
                    $diesNeeded = ceil($deficit / $stockPerDie);

                    // Create SKU
                    $sku = $item->section . '-' . $item->size;

                    // Check if tracking record exists
                    $tracking = StockAlertTracking::where('belt_type', $config['name'])
                        ->where('section', $item->section)
                        ->where('product_id', $item->id)
                        ->first();

                    if ($tracking) {
                        // Update existing record
                        $tracking->update([
                            'current_stock' => $item->current_stock,
                            'min-inventory' => $item->reorder_level,
                            'stock_per_die' => $stockPerDie,
                            'dies_needed' => $diesNeeded,
                            'is_active' => true,
                            // Reset alert if stock was replenished
                            'alert_sent' => $item->current_stock >= $item->reorder_level ? false : $tracking->alert_sent
                        ]);
                    } else {
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
                            'is_active' => true
                        ]);
                    }
                }

                // Deactivate tracking for items that are no longer low stock
                StockAlertTracking::where('belt_type', $config['name'])
                    ->where('is_active', true)
                    ->whereNotIn('product_id', $lowStockItems->pluck('id'))
                    ->update(['is_active' => false]);

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
     * Mark alerts as sent
     */
    private function markAlertsAsSent($itemsNeedingAlerts)
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
}