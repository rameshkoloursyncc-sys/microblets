# Complete Stock Alert System - Learning Guide

## 🎯 Project Overview

This guide covers the complete implementation of a **Stock Alert Email System** for a Laravel-based inventory management application. The system automatically sends daily email reports for low stock and out-of-stock items.

## 📚 Table of Contents

1. [System Architecture](#system-architecture)
2. [Database Design](#database-design)
3. [Backend Implementation](#backend-implementation)
4. [Frontend Implementation](#frontend-implementation)
5. [Email System](#email-system)
6. [Scheduling & Automation](#scheduling--automation)
7. [Key Learning Points](#key-learning-points)
8. [Best Practices](#best-practices)
9. [Advanced Concepts](#advanced-concepts)

---

## 🏗️ System Architecture

### High-Level Flow
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Database      │    │   Laravel App    │    │   Email System  │
│                 │    │                  │    │                 │
│ • Belt Tables   │───▶│ • Controllers    │───▶│ • SMTP Config   │
│ • reorder_level │    │ • Commands       │    │ • Templates     │
│ • stock columns │    │ • Schedulers     │    │ • Recipients    │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         ▲                        │                       │
         │                        ▼                       ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend      │    │   API Routes     │    │   Cron Jobs     │
│                 │    │                  │    │                 │
│ • Vue.js        │    │ • Authentication │    │ • Daily @ 8 AM  │
│ • Dashboard     │    │ • JSON Response  │    │ • Laravel       │
│ • Manual Button │    │ • Error Handling │    │   Scheduler     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

### Key Components
1. **Database Layer**: Inventory tables with selective tracking
2. **Business Logic**: Controllers and commands for data processing
3. **Presentation Layer**: Vue.js frontend with manual controls
4. **Communication Layer**: Email templates and SMTP configuration
5. **Automation Layer**: Laravel scheduler with cron jobs

---

## 🗄️ Database Design

### Core Concept: Selective Tracking
```sql
-- Each belt table has a reorder_level column
ALTER TABLE vee_belts ADD COLUMN reorder_level INT NULL DEFAULT NULL;
ALTER TABLE cogged_belts ADD COLUMN reorder_level INT NULL DEFAULT NULL;
-- ... for all belt types
```

### Tracking Logic
```php
// Not tracked (excluded from reports)
reorder_level = NULL OR reorder_level = 0

// Tracked items (included in reports)
reorder_level >= 1

// Low Stock: stock > 0 AND stock <= reorder_level
// Out of Stock: stock = 0 AND reorder_level >= 1
```

### Migration Strategy
```php
// database/migrations/2025_12_30_063641_change_reorder_level_default_to_null_all_tables.php
public function up(): void
{
    $tables = ['vee_belts', 'cogged_belts', 'poly_belts', 'tpu_belts', 'timing_belts', 'special_belts'];
    
    foreach ($tables as $table) {
        if (Schema::hasTable($table) && Schema::hasColumn($table, 'reorder_level')) {
            // Change default to NULL for selective tracking
            Schema::table($table, function (Blueprint $table) {
                $table->integer('reorder_level')->nullable()->default(null)->change();
            });
            
            // Update existing records
            DB::table($table)->where('reorder_level', 5)->update(['reorder_level' => null]);
        }
    }
}
```

**Learning Point**: Database migrations should be reversible and handle existing data gracefully.

---

## ⚙️ Backend Implementation

### 1. Dashboard Controller
**File**: `app/Http/Controllers/Api/DashboardController.php`

```php
public function getLowStockItems()
{
    $lowStockItems = [];
    $outOfStockItems = [];
    
    $beltTypes = [
        'vee_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Vee Belts'],
        // ... other belt types with their specific column mappings
    ];
    
    foreach ($beltTypes as $table => $config) {
        // Dynamic column checking for flexibility
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        
        // Low stock query
        $lowStockQuery = DB::table($table)
            ->select($selectColumns)
            ->whereNotNull('reorder_level')
            ->where('reorder_level', '>=', 1)
            ->whereRaw("{$config['stock_column']} > 0")
            ->whereRaw("{$config['stock_column']} <= reorder_level")
            ->get();
            
        // Out of stock query
        $outOfStockQuery = DB::table($table)
            ->select($selectColumns)
            ->whereNotNull('reorder_level')
            ->where('reorder_level', '>=', 1)
            ->whereRaw("{$config['stock_column']} = 0")
            ->get();
    }
    
    return response()->json([
        'low_stock_items' => $lowStockItems,
        'out_of_stock_items' => $outOfStockItems,
        'total_alert_count' => $totalLowStock + $totalOutOfStock
    ]);
}
```

**Learning Points**:
- **Dynamic Schema Checking**: Use `getColumnListing()` to handle different table structures
- **Raw SQL for Complex Conditions**: `whereRaw()` for custom logic
- **Separation of Concerns**: Separate methods for different alert types
- **Consistent API Response**: Standardized JSON structure

### 2. Console Command
**File**: `app/Console/Commands/SendDailyLowStockReport.php`

```php
class SendDailyLowStockReport extends Command
{
    protected $signature = 'report:low-stock {--email=* : Email addresses to send the report to}';
    
    public function handle()
    {
        $lowStockData = $this->getLowStockData();
        $emails = $this->option('email') ?: config('mail.low_stock_recipients');
        
        $totalAlerts = $lowStockData['total_alert_count'] ?? 0;
        
        if ($totalAlerts > 0) {
            foreach ($emails as $email) {
                Mail::to($email)->send(new LowStockReport($lowStockData));
            }
            $this->info('✅ Stock alert report sent successfully!');
        } else {
            $this->info('ℹ️  No stock alerts found.');
        }
    }
}
```

**Learning Points**:
- **Command Options**: Use `{--email=*}` for multiple email inputs
- **Configuration Integration**: Fallback to config values
- **User Feedback**: Informative console output with emojis
- **Error Handling**: Try-catch blocks with meaningful messages

### 3. Mail Class
**File**: `app/Mail/LowStockReport.php`

```php
class LowStockReport extends Mailable
{
    public $lowStockData;
    public $reportDate;

    public function __construct($lowStockData)
    {
        $this->lowStockData = $lowStockData;
        $this->reportDate = now()->format('Y-m-d');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Stock Alert Report - ' . $this->reportDate,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-report',
        );
    }
}
```

**Learning Points**:
- **Data Passing**: Pass complex data structures to email templates
- **Dynamic Subjects**: Include dates and context in email subjects
- **Laravel 11 Structure**: New envelope/content methods

---

## 🎨 Frontend Implementation

### Vue.js Dashboard Integration
**File**: `resources/js/components/inventory/InventoryApp.vue`

```vue
<template>
  <!-- Stock Alert Section -->
  <div class="mt-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Stock Alerts</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">Send low stock and out of stock report via email</p>
        </div>
        <button 
          @click="sendStockAlert"
          :disabled="sendingAlert"
          class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
        >
          <svg v-if="sendingAlert" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white">
            <!-- Loading spinner SVG -->
          </svg>
          {{ sendingAlert ? 'Sending...' : 'Send Stock Alert' }}
        </button>
      </div>
      
      <!-- Status Messages -->
      <div v-if="alertMessage" class="mt-4 p-3 rounded-lg" :class="alertMessage.type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
        {{ alertMessage.text }}
      </div>
    </div>
  </div>
</template>

<script setup>
const sendingAlert = ref(false)
const alertMessage = ref(null)

const sendStockAlert = async () => {
  sendingAlert.value = true
  alertMessage.value = null
  
  try {
    const response = await axios.post('/api/dashboard/send-stock-alert', {
      force: true
    })
    
    alertMessage.value = {
      type: 'success',
      text: response.data.message
    }
  } catch (error) {
    alertMessage.value = {
      type: 'error',
      text: error.response?.data?.message || 'Failed to send stock alert'
    }
  } finally {
    sendingAlert.value = false
    setTimeout(() => alertMessage.value = null, 5000)
  }
}
</script>
```

**Learning Points**:
- **Reactive State Management**: Use `ref()` for component state
- **Loading States**: Disable button and show spinner during API calls
- **Error Handling**: Display user-friendly error messages
- **Auto-dismiss**: Clear messages after timeout
- **Responsive Design**: Mobile-friendly button and layout

### Belt Table Updates
**Files**: All belt table components (`*BeltTable.vue`)

```vue
<!-- Updated reorder_level display -->
<div v-else @click="startEdit(p, 'reorder_level')" class="cursor-pointer">
  {{ p.reorder_level ?? 'Not tracked' }}
</div>

<!-- Updated low stock logic -->
<script>
const lowStockCount = computed(() => {
  return visibleProducts.value.filter(p => 
    p.reorder_level !== null && 
    p.reorder_level >= 1 && 
    p.balance_stock <= p.reorder_level && 
    p.balance_stock > 0
  ).length
})

const getStockClass = (p) => { 
  if (p.balance_stock <= 0) return 'text-red-600 font-semibold'
  if (p.reorder_level !== null && p.reorder_level >= 1 && p.balance_stock <= p.reorder_level) 
    return 'text-yellow-600 font-semibold'
  return 'text-green-600 font-semibold'
}
</script>
```

**Learning Points**:
- **Null Handling**: Use nullish coalescing (`??`) for clean defaults
- **Computed Properties**: Reactive calculations for UI state
- **Conditional Styling**: Dynamic CSS classes based on data
- **User Experience**: Clear visual indicators for different states

---

## 📧 Email System

### SMTP Configuration
**File**: `.env`

```bash
# Gmail SMTP Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password  # No dashes!
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="Your Company Name"

# Recipients
LOW_STOCK_EMAIL_RECIPIENTS="admin@company.com,manager@company.com"
```

**Learning Points**:
- **App Passwords**: Gmail requires app-specific passwords, not regular passwords
- **No Dashes**: App passwords should not contain dashes in .env
- **TLS Encryption**: Use TLS for Gmail (port 587)
- **Environment Variables**: Store sensitive data in .env, not code

### Email Template Design
**File**: `resources/views/emails/low-stock-report.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Stock Alert Report</title>
    <style>
        /* Inline CSS for email compatibility */
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .belt-section { border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; }
        .belt-header { background-color: #2196f3; color: white; padding: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Daily Stock Alert Report</h1>
        <p><strong>Report Date:</strong> {{ $reportDate }}</p>
    </div>

    <!-- Summary Section -->
    <div class="summary">
        <h2>Summary</h2>
        <p><strong>Total Low Stock Items:</strong> {{ $lowStockData['total_low_stock_count'] ?? 0 }}</p>
        <p><strong>Total Out of Stock Items:</strong> {{ $lowStockData['total_out_of_stock_count'] ?? 0 }}</p>
    </div>

    <!-- Low Stock Items -->
    @if(!empty($lowStockData['low_stock_items']))
        <h2 style="color: #ff9800;">⚠️ Low Stock Items</h2>
        @foreach($lowStockData['low_stock_items'] as $beltType => $data)
            <div class="belt-section">
                <div class="belt-header" style="background-color: #ff9800;">
                    {{ $data['name'] }} ({{ $data['count'] }} items)
                </div>
                <div class="belt-content">
                    <table>
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Size</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Value (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['items'] as $item)
                                <tr>
                                    <td><strong>{{ $item->section }}</strong></td>
                                    <td>{{ $item->size }}</td>
                                    <td class="low-stock">{{ number_format($item->current_stock, 2) }}</td>
                                    <td>{{ $item->reorder_level }}</td>
                                    <td>₹{{ number_format($item->value ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif

    <!-- Out of Stock Items -->
    @if(!empty($lowStockData['out_of_stock_items']))
        <h2 style="color: #d32f2f;">🚨 Out of Stock Items</h2>
        @foreach($lowStockData['out_of_stock_items'] as $beltType => $data)
            <!-- Similar structure with red styling -->
        @endforeach
    @endif
</body>
</html>
```

**Learning Points**:
- **Email-Safe HTML**: Use tables for layout, inline CSS for styling
- **Blade Templating**: Use `@if`, `@foreach`, `{{ }}` for dynamic content
- **Responsive Design**: Mobile-friendly with viewport meta tag
- **Visual Hierarchy**: Different colors for different alert levels
- **Data Safety**: Use `?? 0` for null-safe operations

---

## ⏰ Scheduling & Automation

### Laravel Scheduler
**File**: `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;

// Schedule daily stock alert report
Schedule::command('report:low-stock')
    ->dailyAt('08:00')
    ->timezone('Asia/Kolkata')
    ->description('Send daily stock alert report (low stock + out of stock) via email');
```

**Learning Points**:
- **Single Entry Point**: Laravel scheduler manages all scheduled tasks
- **Timezone Awareness**: Specify timezone for consistent execution
- **Descriptive Names**: Clear descriptions for maintenance
- **Centralized Configuration**: All schedules in one place

### Production Cron Setup
```bash
# Single cron entry for all Laravel scheduled tasks
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**Learning Points**:
- **Every Minute**: Laravel scheduler checks every minute for due tasks
- **Path Specification**: Always `cd` to project directory first
- **Output Redirection**: `>> /dev/null 2>&1` prevents email spam
- **Full PHP Path**: Use `/usr/bin/php` if `php` command not found

---

## 🧠 Key Learning Points

### 1. Database Design Patterns

**Selective Tracking Pattern**:
```php
// Instead of tracking everything (expensive)
WHERE stock <= 5

// Track only what matters (efficient)
WHERE reorder_level IS NOT NULL 
  AND reorder_level >= 1 
  AND stock <= reorder_level
```

**Benefits**:
- Reduces noise in reports
- Allows per-item customization
- Improves query performance
- Provides business flexibility

### 2. API Design Principles

**Consistent Response Structure**:
```php
return response()->json([
    'success' => true,
    'data' => $actualData,
    'message' => 'Human readable message',
    'meta' => ['timestamp', 'counts', etc.]
]);
```

**Error Handling**:
```php
try {
    // Business logic
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'message' => 'User-friendly error message',
        'error' => app()->environment('local') ? $e->getMessage() : null
    ], 500);
}
```

### 3. Frontend State Management

**Reactive Data Flow**:
```javascript
// State
const sendingAlert = ref(false)
const alertMessage = ref(null)

// Action
const sendStockAlert = async () => {
  sendingAlert.value = true  // Update UI immediately
  try {
    await apiCall()
    alertMessage.value = { type: 'success', text: 'Success!' }
  } catch (error) {
    alertMessage.value = { type: 'error', text: error.message }
  } finally {
    sendingAlert.value = false  // Always reset loading state
  }
}
```

### 4. Email System Architecture

**Separation of Concerns**:
- **Data Collection**: Controllers/Commands gather data
- **Email Composition**: Mail classes handle structure
- **Template Rendering**: Blade views handle presentation
- **Delivery**: SMTP configuration handles transport

### 5. Configuration Management

**Environment-Based Configuration**:
```php
// config/mail.php
'low_stock_recipients' => env('LOW_STOCK_EMAIL_RECIPIENTS') ? 
    explode(',', env('LOW_STOCK_EMAIL_RECIPIENTS')) : 
    ['admin@example.com'],
```

**Benefits**:
- Different settings per environment
- Sensitive data in .env files
- Easy deployment configuration
- No code changes for config updates

---

## 🏆 Best Practices Demonstrated

### 1. Code Organization
- **Single Responsibility**: Each class has one clear purpose
- **Dependency Injection**: Use Laravel's service container
- **Configuration Over Code**: Use config files and environment variables
- **Consistent Naming**: Clear, descriptive names throughout

### 2. Error Handling
- **Graceful Degradation**: System continues working if one part fails
- **User-Friendly Messages**: Clear feedback for users
- **Logging**: Comprehensive error logging for debugging
- **Fallback Values**: Default values for missing data

### 3. Security
- **Input Validation**: Validate all user inputs
- **SQL Injection Prevention**: Use query builder/Eloquent
- **Environment Variables**: Sensitive data in .env files
- **Authentication**: Protect API endpoints

### 4. Performance
- **Efficient Queries**: Only fetch needed data
- **Caching**: Cache configuration in production
- **Batch Operations**: Process multiple items together
- **Background Jobs**: Use queues for heavy operations (optional)

### 5. Maintainability
- **Documentation**: Comprehensive guides and comments
- **Testing**: Test commands for verification
- **Version Control**: Proper git workflow
- **Deployment**: Automated deployment processes

---

## 🚀 Advanced Concepts

### 1. Queue Integration
```php
// For high-volume emails, use queues
Mail::to($email)->queue(new LowStockReport($data));

// Configure queue in .env
QUEUE_CONNECTION=redis
```

### 2. Multiple Email Templates
```php
// Different templates for different alert levels
public function content(): Content
{
    $template = $this->alertLevel === 'critical' 
        ? 'emails.critical-stock-alert' 
        : 'emails.low-stock-report';
        
    return new Content(view: $template);
}
```

### 3. Webhook Integration
```php
// Send alerts to Slack, Discord, etc.
public function sendWebhookAlert($data)
{
    Http::post('https://hooks.slack.com/webhook-url', [
        'text' => "Stock Alert: {$data['total_alert_count']} items need attention"
    ]);
}
```

### 4. Advanced Scheduling
```php
// Different schedules for different environments
if (app()->environment('production')) {
    Schedule::command('report:low-stock')->dailyAt('08:00');
} else {
    Schedule::command('report:low-stock')->everyMinute(); // For testing
}

// Conditional scheduling
Schedule::command('report:low-stock')
    ->dailyAt('08:00')
    ->when(function () {
        return now()->isWeekday(); // Only on weekdays
    });
```

### 5. Monitoring & Alerting
```php
// Health check endpoint
Route::get('/health/scheduler', function () {
    $lastRun = Cache::get('last_scheduler_run');
    $isHealthy = $lastRun && $lastRun->diffInMinutes(now()) < 5;
    
    return response()->json([
        'status' => $isHealthy ? 'healthy' : 'unhealthy',
        'last_run' => $lastRun
    ]);
});
```

---

## 🎓 What You've Learned

By studying this implementation, you've learned:

1. **Full-Stack Development**: Backend APIs, frontend integration, database design
2. **Laravel Advanced Features**: Commands, scheduling, mail system, migrations
3. **Vue.js Integration**: Reactive state, API calls, user experience
4. **Email Systems**: SMTP configuration, template design, delivery
5. **Production Deployment**: Cron jobs, environment configuration, monitoring
6. **Best Practices**: Error handling, security, performance, maintainability

### Next Steps for Learning:
1. **Add Tests**: Write unit and feature tests
2. **Add Queues**: Implement background job processing
3. **Add Monitoring**: Set up application monitoring
4. **Add Webhooks**: Integrate with external services
5. **Add Analytics**: Track email open rates, click rates
6. **Add Internationalization**: Multi-language support

This system demonstrates professional-level Laravel development with real-world considerations for scalability, maintainability, and user experience.

---

## 📖 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Guide](https://vuejs.org/guide/)
- [Laravel Scheduling](https://laravel.com/docs/scheduling)
- [Laravel Mail](https://laravel.com/docs/mail)
- [Email Template Best Practices](https://www.campaignmonitor.com/dev-resources/)
- [Cron Job Tutorial](https://crontab.guru/)

---

*This guide represents a complete, production-ready implementation that you can use as a reference for similar projects.*