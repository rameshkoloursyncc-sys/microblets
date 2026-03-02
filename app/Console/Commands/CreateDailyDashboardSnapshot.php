<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DashboardSnapshot;
use App\Models\VeeBelt;
use App\Models\CoggedBelt;
use App\Models\PolyBelt;
use App\Models\TpuBelt;
use App\Models\TimingBelt;
use App\Models\SpecialBelt;
use App\Models\RawCarbon;
use Carbon\Carbon;

class CreateDailyDashboardSnapshot extends Command
{
    protected $signature = 'dashboard:snapshot {date?}';
    protected $description = 'Create a daily dashboard snapshot for historical data';

    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::today();
        
        $this->info("Creating dashboard snapshot for {$date->toDateString()}...");
        
        // Calculate finished goods stats
        $finishedStats = $this->calculateFinishedGoodsStats();
        
        // Calculate raw materials stats
        $rawStats = $this->calculateRawMaterialsStats();
        
        // Calculate die requirements
        $dieRequirements = $this->calculateDieRequirements();
        
        // Create or update snapshot
        $snapshot = DashboardSnapshot::updateOrCreate(
            ['snapshot_date' => $date],
            array_merge($finishedStats, $rawStats, [
                'die_requirements' => $dieRequirements,
                'total_alerts' => $finishedStats['finished_low_stock'] + $rawStats['raw_low_stock'],
            ])
        );
        
        $this->info("✅ Snapshot created successfully for {$date->toDateString()}");
        $this->info("   Finished Goods Value: ₹" . number_format($snapshot->finished_total_value, 2));
        $this->info("   Raw Materials Value: ₹" . number_format($snapshot->raw_total_value, 2));
        
        return 0;
    }
    
    private function calculateFinishedGoodsStats()
    {
        $veeStats = $this->getBeltStats(VeeBelt::class);
        $coggedStats = $this->getBeltStats(CoggedBelt::class);
        $polyStats = $this->getBeltStats(PolyBelt::class);
        $tpuStats = $this->getBeltStats(TpuBelt::class);
        $timingStats = $this->getBeltStats(TimingBelt::class);
        $specialStats = $this->getBeltStats(SpecialBelt::class);
        
        return [
            'finished_total_products' => $veeStats['total'] + $coggedStats['total'] + $polyStats['total'] + 
                                        $tpuStats['total'] + $timingStats['total'] + $specialStats['total'],
            'finished_in_stock' => $veeStats['in_stock'] + $coggedStats['in_stock'] + $polyStats['in_stock'] + 
                                  $tpuStats['in_stock'] + $timingStats['in_stock'] + $specialStats['in_stock'],
            'finished_low_stock' => $veeStats['low_stock'] + $coggedStats['low_stock'] + $polyStats['low_stock'] + 
                                   $tpuStats['low_stock'] + $timingStats['low_stock'] + $specialStats['low_stock'],
            'finished_out_of_stock' => $veeStats['out_of_stock'] + $coggedStats['out_of_stock'] + $polyStats['out_of_stock'] + 
                                      $tpuStats['out_of_stock'] + $timingStats['out_of_stock'] + $specialStats['out_of_stock'],
            'finished_total_value' => $veeStats['value'] + $coggedStats['value'] + $polyStats['value'] + 
                                     $tpuStats['value'] + $timingStats['value'] + $specialStats['value'],
            'vee_belts_value' => $veeStats['value'],
            'cogged_belts_value' => $coggedStats['value'],
            'poly_belts_value' => $polyStats['value'],
            'tpu_belts_value' => $tpuStats['value'],
            'timing_belts_value' => $timingStats['value'],
            'special_belts_value' => $specialStats['value'],
        ];
    }
    
    private function getBeltStats($model)
    {
        $total = $model::count();
        
        // Different models use different stock field names
        $stockField = $this->getStockFieldForModel($model);
        
        $inStock = $model::where($stockField, '>', 0)->count();
        $lowStock = $model::whereColumn($stockField, '<=', 'reorder_level')
                          ->where($stockField, '>', 0)
                          ->count();
        $outOfStock = $model::where($stockField, 0)->count();
        $value = $model::sum('value');
        
        return [
            'total' => $total,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'value' => $value,
        ];
    }
    
    private function getStockFieldForModel($model)
    {
        // Different belt types use different stock field names
        switch ($model) {
            case PolyBelt::class:
                return 'ribs';
            case TpuBelt::class:
                return 'meter';
            case TimingBelt::class:
                return 'total_mm';
            default:
                return 'balance_stock';
        }
    }
    
    private function calculateRawMaterialsStats()
    {
        $total = RawCarbon::count();
        $available = RawCarbon::where('balance_stock', '>', 0)->count();
        $lowStock = RawCarbon::whereNotNull('reorder_level')
                             ->where('reorder_level', '>=', 1)
                             ->whereColumn('balance_stock', '<=', 'reorder_level')
                             ->where('balance_stock', '>', 0)
                             ->count();
        $outOfStock = RawCarbon::where('balance_stock', 0)->count();
        
        // Calculate category values
        $categories = [
            'Carbon',
            'Chemical',
            'Oil',
            'Others',
            'Resin',
            'Rubber',
            'TPU',
            'Fibre Glass Cord',
            'Steel Wire',
            'Packing',
            'Open',
        ];
        
        $categoryValues = [];
        $totalValue = 0;
        
        foreach ($categories as $category) {
            $value = RawCarbon::where('category', $category)->sum('value');
            $categoryValues[$category] = $value;
            $totalValue += $value;
        }
        
        // Calculate combined Cord value (all cord subsections)
        $cordValue = RawCarbon::where('category', 'LIKE', 'Cord -%')->sum('value');
        $categoryValues['Cord (All)'] = $cordValue;
        $totalValue += $cordValue;
        
        // Calculate combined Fabric value (all fabric subsections)
        $fabricValue = RawCarbon::where('category', 'LIKE', 'Fabric -%')->sum('value');
        $categoryValues['Fabric (All)'] = $fabricValue;
        $totalValue += $fabricValue;
        
        return [
            'raw_total_materials' => $total,
            'raw_available' => $available,
            'raw_low_stock' => $lowStock,
            'raw_out_of_stock' => $outOfStock,
            'raw_total_value' => $totalValue,
            'raw_carbon_value' => $categoryValues['Carbon'] ?? 0,
            'raw_chemical_value' => $categoryValues['Chemical'] ?? 0,
            'raw_cord_all_value' => $cordValue,
            'raw_fabric_all_value' => $fabricValue,
            'raw_oil_value' => $categoryValues['Oil'] ?? 0,
            'raw_others_value' => $categoryValues['Others'] ?? 0,
            'raw_resin_value' => $categoryValues['Resin'] ?? 0,
            'raw_rubber_value' => $categoryValues['Rubber'] ?? 0,
            'raw_tpu_value' => $categoryValues['TPU'] ?? 0,
            'raw_fibre_glass_cord_value' => $categoryValues['Fibre Glass Cord'] ?? 0,
            'raw_steel_wire_value' => $categoryValues['Steel Wire'] ?? 0,
            'raw_packing_value' => $categoryValues['Packing'] ?? 0,
            'raw_open_value' => $categoryValues['Open'] ?? 0,
            'raw_category_values' => $categoryValues,
        ];
    }
    
    private function calculateDieRequirements()
    {
        // Get all low stock items with die requirements
        $dieRequirements = [];
        
        $models = [
            'vee_belts' => VeeBelt::class,
            'cogged_belts' => CoggedBelt::class,
            'timing_belts' => TimingBelt::class,
        ];
        
        foreach ($models as $type => $model) {
            $lowStockItems = $model::with('stockAlert')
                ->whereHas('stockAlert', function($q) {
                    $q->where('is_active', true);
                })
                ->get();
            
            foreach ($lowStockItems as $item) {
                if ($item->stockAlert && $item->stockAlert->dies_needed > 0) {
                    $section = $item->section ?? 'Unknown';
                    if (!isset($dieRequirements[$section])) {
                        $dieRequirements[$section] = [
                            'dies' => 0,
                            'items' => 0,
                        ];
                    }
                    $dieRequirements[$section]['dies'] += $item->stockAlert->dies_needed;
                    $dieRequirements[$section]['items']++;
                }
            }
        }
        
        return $dieRequirements;
    }
}
