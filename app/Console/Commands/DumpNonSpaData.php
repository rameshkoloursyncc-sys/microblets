<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DumpNonSpaData extends Command
{
    protected $signature = 'inventory:dump-non-spa {--backup : Create backup before deletion}';
    protected $description = 'Dump all product data except SPA section data';

    public function handle()
    {
        $this->info('Starting data dump process (excluding SPA section)...');
        
        // Get SPA products count before deletion
        $spaCount = Product::where('name', 'LIKE', '%SPA%')
                          ->orWhere('sku', 'LIKE', '%SPA%')
                          ->count();
        
        $totalCount = Product::count();
        $toDeleteCount = $totalCount - $spaCount;
        
        $this->info("Total products: {$totalCount}");
        $this->info("SPA products (will be preserved): {$spaCount}");
        $this->info("Products to be deleted: {$toDeleteCount}");
        
        if ($this->option('backup')) {
            $this->createBackup();
        }
        
        if ($this->confirm('Are you sure you want to delete all non-SPA products?')) {
            $this->deleteNonSpaProducts();
        } else {
            $this->info('Operation cancelled.');
        }
    }
    
    private function createBackup()
    {
        $this->info('Creating backup of all data...');
        
        $timestamp = now()->format('Y_m_d_H_i_s');
        $backupFile = storage_path("app/backups/products_backup_{$timestamp}.sql");
        
        // Create backup directory if it doesn't exist
        if (!file_exists(dirname($backupFile))) {
            mkdir(dirname($backupFile), 0755, true);
        }
        
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPassword = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        
        $command = "mysqldump -h {$dbHost} -u {$dbUser}";
        if ($dbPassword) {
            $command .= " -p{$dbPassword}";
        }
        $command .= " {$dbName} products > {$backupFile}";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->info("Backup created: {$backupFile}");
        } else {
            $this->error("Backup failed!");
            return false;
        }
        
        return true;
    }
    
    private function deleteNonSpaProducts()
    {
        $this->info('Deleting non-SPA products...');
        
        try {
            DB::beginTransaction();
            
            // Delete all products that are NOT SPA section
            // Check both the section field and name/sku for backward compatibility
            $deletedCount = Product::where(function($query) {
                $query->where('section', '!=', 'SPA')
                      ->orWhereNull('section');
            })
            ->where('name', 'NOT LIKE', '%SPA%')
            ->where('sku', 'NOT LIKE', '%SPA%')
            ->delete();
            
            DB::commit();
            
            $this->info("Successfully deleted {$deletedCount} non-SPA products.");
            
            $remainingCount = Product::count();
            $this->info("Remaining products (SPA section): {$remainingCount}");
            
            // Show remaining SPA products
            $this->showRemainingSpaProducts();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error occurred: " . $e->getMessage());
        }
    }
    
    private function showRemainingSpaProducts()
    {
        $spaProducts = Product::where('name', 'LIKE', '%SPA%')
                             ->orWhere('sku', 'LIKE', '%SPA%')
                             ->get(['id', 'name', 'sku', 'stock']);
        
        if ($spaProducts->count() > 0) {
            $this->info("\nRemaining SPA products:");
            $this->table(
                ['ID', 'Name', 'SKU', 'Stock'],
                $spaProducts->map(function ($product) {
                    return [
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->stock ?? 0
                    ];
                })->toArray()
            );
        } else {
            $this->warn("No SPA products found to preserve!");
        }
    }
}