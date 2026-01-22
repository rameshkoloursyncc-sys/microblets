# Stock Alert Color Implementation Guide

This guide shows how to implement the yellow color indicator for products where stock alerts have been sent, plus the "Send Report" button functionality and daily cron job setup.

## What We Implemented for CoggedBelt

### 1. Backend Changes

#### Model Relationship (app/Models/CoggedBelt.php)
```php
public function stockAlert()
{
    return $this->hasOne(StockAlertTracking::class, 'product_id')
        ->where('belt_type', 'cogged')  // Note: 'cogged' not 'cogged_belts'
        ->where('is_active', true);
}
```

#### Controller Changes (app/Http/Controllers/Api/CoggedBeltController.php)
```php
// In index() method
$query->with('stockAlert');

// In bySection() method  
return CoggedBelt::bySection($section)
    ->with('stockAlert')
    ->orderByRaw('CAST(size AS UNSIGNED) ASC')
    ->get();
```

#### Dashboard Controller - Mark Alerts as Sent (app/Http/Controllers/Api/DashboardController.php)
```php
// Modified sendStockAlert method to mark alerts as sent
public function sendStockAlert(Request $request)
{
    // ... existing code ...
    
    if ($totalAlerts > 0 || $request->input('force', false)) {
        foreach ($emails as $email) {
            \Mail::to(trim($email))->send(new \App\Mail\LowStockReport($lowStockData));
        }
        
        // Mark alerts as sent in StockAlertTracking table
        $this->markStockAlertsAsSent($lowStockData);
        
        // ... rest of response ...
    }
}

// New method to mark alerts as sent
private function markStockAlertsAsSent($lowStockData)
{
    // Sync tracking data and mark alerts as sent
    // ... implementation details ...
}
```

### 2. Frontend Changes

#### TypeScript Interface (resources/js/composables/useCoggedBelts.ts)
```typescript
export interface StockAlert {
  id: number
  belt_type: string
  section: string
  product_id: number
  product_sku: string
  current_stock: number
  reorder_level: number
  stock_per_die: number
  dies_needed: number
  alert_sent: boolean
  alert_sent_at: string | null
  is_active: boolean
  alert_history: any[]
}

export interface CoggedBelt {
  // ... existing fields
  stock_alert?: StockAlert | null
}
```

#### Component Logic (resources/js/components/inventory/CoggedBeltTable.vue)
```typescript
const getStockClass = (p: CoggedBelt) => { 
  if (p.balance_stock <= 0) return 'text-red-600 font-bold'
  if (p.reorder_level !== null && p.reorder_level >= 1 && p.balance_stock <= p.reorder_level) {
    // Check if alert has been sent
    if (p.stock_alert?.alert_sent) {
      return 'text-yellow-600 font-bold' // Yellow if alert sent
    }
    return 'text-red-600 font-bold' // Red if low stock but no alert sent
  }
  return 'text-green-600 font-bold'
}
```

### 3. Console Command Updates

#### Modified Daily Report Command (app/Console/Commands/SendDailyLowStockReport.php)
```php
// Updated to mark alerts as sent after sending emails
public function handle()
{
    // ... existing code ...
    
    if ($totalAlerts > 0) {
        foreach ($emails as $email) {
            Mail::to(trim($email))->send(new LowStockReport($lowStockData));
        }
        
        // Mark alerts as sent in StockAlertTracking table
        $this->markStockAlertsAsSent($lowStockData);
        
        $this->info('✅ Stock alert report sent successfully and alerts marked as sent!');
    }
}
```

### 4. Cron Job Setup

#### Quick 5 PM Setup Script (setup-5pm-stock-alert.sh)
```bash
#!/bin/bash
# Quick setup for 5 PM daily stock alert cron job
CRON_SCHEDULE="0 17 * * *"  # 5:00 PM daily
CRON_COMMAND="$CRON_SCHEDULE cd $PROJECT_PATH && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/microbelts/stock-alerts.log 2>&1"
```

#### Usage:
```bash
# Quick setup for 5 PM daily alerts
./setup-5pm-stock-alert.sh

# Or use the full setup script with more options
./setup-stock-alert-cron.sh
```

## How the "Send Report" Button Works

1. **Manual Send**: When user clicks "Send Stock Alert" or "Send Smart Alert" in the UI
2. **Email Sent**: System sends email to configured recipients
3. **Mark as Sent**: System automatically marks all alerted items as `alert_sent = true` in `StockAlertTracking` table
4. **Color Change**: Frontend immediately shows yellow color for items where `stock_alert.alert_sent = true`
5. **Reset on Restock**: When stock goes above reorder level, `alert_sent` is reset to `false`

## Daily Cron Job (5 PM)

1. **Automatic Execution**: Cron runs daily at 5:00 PM IST
2. **Check Stock**: System checks all belt types for low stock items
3. **Send Email**: If low stock items found, sends email report
4. **Mark as Sent**: Automatically marks alerts as sent
5. **Logging**: All activity logged to `/var/log/microbelts/stock-alerts.log`

## Color Meaning:
- **Green**: Stock is above reorder level
- **Red**: Stock is at or below reorder level, no alert sent yet
- **Yellow**: Stock is at or below reorder level, alert has been sent

## How to Apply to Other Belt Types

### For VeeBelt (example):

1. **Add relationship to VeeBelt model:**
```php
public function stockAlert()
{
    return $this->hasOne(StockAlertTracking::class, 'product_id')
        ->where('belt_type', 'vee')  // Change belt_type to match SmartStockAlertService
        ->where('is_active', true);
}
```

2. **Update VeeBeltController:**
```php
// Add ->with('stockAlert') to queries
$query->with('stockAlert');
```

3. **Update VeeBelt interface in useVeeBelts.ts:**
```typescript
export interface VeeBelt {
  // ... existing fields
  stock_alert?: StockAlert | null
}
```

4. **Update getStockClass in VeeBeltTable.vue:**
```typescript
const getStockClass = (p: VeeBelt) => { 
  if (p.balance_stock <= 0) return 'text-red-600 font-bold'
  if (p.reorder_level !== null && p.reorder_level >= 1 && p.balance_stock <= p.reorder_level) {
    if (p.stock_alert?.alert_sent) {
      return 'text-yellow-600 font-bold'
    }
    return 'text-red-600 font-bold'
  }
  return 'text-green-600 font-bold'
}
```

## Belt Types to Update:
- VeeBelt (belt_type: 'vee')
- PolyBelt (belt_type: 'poly') 
- TpuBelt (belt_type: 'tpu')
- TimingBelt (belt_type: 'timing')
- SpecialBelt (belt_type: 'special')

Each follows the same pattern, just change the belt_type in the relationship and update the corresponding files.

## ✅ COMPLETE SOLUTION SUMMARY

### 🎯 What We Fixed:

1. **"Send Report" Button Marks Alerts as Sent** ✅
   - Modified `DashboardController::sendStockAlert()` to call `markStockAlertsAsSent()`
   - Updated `SendDailyLowStockReport` command to mark alerts as sent
   - Both manual and cron alerts now mark items as sent

2. **Yellow Color Shows When Alert Sent** ✅
   - Added `StockAlert` relationship to `CoggedBelt` model
   - Modified controller to include `->with('stockAlert')`
   - Updated frontend `getStockClass()` to show yellow when `alert_sent = true`
   - Added refresh mechanism to update colors after sending alerts

3. **Auto-Reset When Stock Replenished** ✅
   - Added logic in `inOut()` method to reset `alert_sent = false` when stock >= reorder_level
   - Added same logic in `update()` method for direct stock edits
   - Uses `StockAlertTracking::resetAlert()` method

4. **Daily 5 PM Cron Job** ✅
   - Updated `setup-stock-alert-cron.sh` with 5 PM as recommended option
   - Created `setup-5pm-stock-alert.sh` for quick setup
   - Cron automatically marks alerts as sent after sending

### 🔄 Complete Flow:

1. **Stock Goes Low** → Shows **RED** (alert_sent = false)
2. **Click "Send Report" OR 5 PM Cron** → Email sent + `alert_sent = true`
3. **Frontend Refreshes** → Items turn **YELLOW** (alert has been sent)
4. **Stock Replenished (IN operation)** → If stock >= reorder_level, `alert_sent = false`
5. **Color Updates** → Back to **GREEN** (above reorder) or **RED** (still low, ready for new alert)

### 🎨 Color System:
- **🟢 Green**: Stock above reorder level (safe)
- **🔴 Red**: Stock at/below reorder level, no alert sent yet (needs attention)
- **🟡 Yellow**: Stock at/below reorder level, alert has been sent (acknowledged)

### 🚀 Setup Instructions:

#### 1. Set up 5 PM daily cron job:
```bash
./setup-5pm-stock-alert.sh
```

#### 2. Test the system:
```bash
./test-stock-alert-system.sh
```

#### 3. Verify in browser:
- Go to any Cogged Belt section
- Items with low stock should show RED initially
- Click "Send Stock Alert" button
- Items should turn YELLOW after refresh
- Do an IN operation to bring stock above reorder level
- Items should turn GREEN

#### 4. Monitor the system:
```bash
# View cron jobs
crontab -l

# Monitor logs
tail -f /var/log/microbelts/stock-alerts.log

# Check database
php artisan tinker --execute="echo \App\Models\StockAlertTracking::where('alert_sent', true)->count() . ' alerts sent'"
```

### 🔧 Technical Implementation:

#### Backend Changes:
- `CoggedBelt` model: Added `stockAlert()` relationship
- `CoggedBeltController`: Added `->with('stockAlert')` and reset logic in IN/OUT operations
- `DashboardController`: Added `markStockAlertsAsSent()` method
- `SendDailyLowStockReport`: Added alert marking after email sent
- `SmartStockAlertService`: Made `syncStockAlertTracking()` public

#### Frontend Changes:
- `CoggedBeltTable.vue`: Updated `getStockClass()` with yellow logic and refresh mechanism
- `InventoryApp.vue`: Added `refreshTables()` function to trigger data refresh after alerts
- `useCoggedBelts.ts`: Added `StockAlert` interface

#### Cron Setup:
- `setup-5pm-stock-alert.sh`: Quick 5 PM setup
- `setup-stock-alert-cron.sh`: Updated with 5 PM as recommended option

### 🧪 Testing:
- `test-stock-alert-system.sh`: Comprehensive test script
- Tests manual alerts, tracking sync, and die requirements

The system is now fully functional with visual feedback and automatic alert management!