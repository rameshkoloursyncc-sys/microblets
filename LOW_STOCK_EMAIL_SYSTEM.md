# Low Stock Email System Implementation

## Overview
The low stock email system provides automated daily reports for inventory items that are running low on stock. The system uses individual product-level tracking rather than section-wide tracking.

## Key Features

### 1. Selective Tracking
- **Default Behavior**: `reorder_level = null` means the item is NOT tracked for low stock alerts
- **Tracking Enabled**: Set `reorder_level > 1` to enable tracking for specific items
- **Individual Control**: Each product can be independently tracked or ignored

### 2. Low Stock Logic
Items are considered "low stock" when:
- `reorder_level IS NOT NULL`
- `reorder_level > 1` 
- `current_stock > 0` (not out of stock)
- `current_stock <= reorder_level`

### 3. Daily Email Reports
- **Schedule**: Daily at 8:00 AM (Asia/Kolkata timezone)
- **Content**: Grouped by belt type and section
- **Recipients**: Configurable via environment variables
- **Conditional Sending**: Only sends when low stock items exist (with optional weekly "all good" reports)

## Implementation Details

### Database Changes
- Migration: `2025_12_30_063641_change_reorder_level_default_to_null_all_tables.php`
- Changed `reorder_level` default from `5` to `null` across all belt tables
- Updated existing records to set `reorder_level = null` for selective tracking

### Backend Components

#### 1. Dashboard Controller (`app/Http/Controllers/Api/DashboardController.php`)
- Updated low stock calculations to use new logic
- Added `getLowStockItems()` API endpoint
- Handles different stock column names per belt type

#### 2. Mail Class (`app/Mail/LowStockReport.php`)
- Handles email composition and sending
- Passes low stock data to email template

#### 3. Console Command (`app/Console/Commands/SendDailyLowStockReport.php`)
- Command: `php artisan report:low-stock`
- Options: `--email=user@example.com` (can specify multiple)
- Handles data collection and email sending
- Includes error handling and logging

#### 4. Email Template (`resources/views/emails/low-stock-report.blade.php`)
- Responsive HTML email template
- Groups items by belt type and section
- Shows "No low stock items" message when appropriate
- Includes summary statistics

### Frontend Updates

#### Updated Components
All belt table components updated to handle null `reorder_level`:
- `VeeBeltTable.vue`
- `CoggedBeltTable.vue` 
- `PolyBeltTable.vue`
- `TpuBeltTable.vue`
- `TimingBeltTable.vue`
- `SpecialBeltTable.vue`

#### Changes Made
- Low stock filters only include items with `reorder_level > 1`
- Display shows "Not tracked" for null reorder_level
- Create forms default to `reorder_level = null`
- Updated form labels to explain tracking behavior

### Configuration

#### Environment Variables
Add to `.env` file:
```bash
# Low Stock Email Reports
LOW_STOCK_EMAIL_RECIPIENTS="admin@example.com,manager@example.com"

# Mail configuration (example for Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="Belt Inventory System"
```

#### Scheduled Task
Added to `routes/console.php`:
```php
Schedule::command('report:low-stock')
    ->dailyAt('08:00')
    ->timezone('Asia/Kolkata')
    ->description('Send daily low stock report via email');
```

## Usage Instructions

### 1. Enable Tracking for Specific Items
1. Go to any belt inventory table
2. Click on the "Minimum Inventory Level" cell for an item
3. Enter a number >= 1 to enable tracking (1 or higher)
4. Leave empty (null) to disable tracking

### 2. Manual Report Generation

#### Via Dashboard Button
1. Go to Dashboard in the web interface
2. Scroll to the "Stock Alerts" section
3. Click "Send Stock Alert" button
4. Report will be sent to configured email addresses

#### Via Command Line
```bash
# Send to default recipients
php artisan report:low-stock

# Send to specific email(s)
php artisan report:low-stock --email=manager@company.com --email=admin@company.com
```

### 3. Test Stock Alert Detection
```bash
# Check current stock alert items via API (requires authentication)
curl -H "Cookie: laravel_session=your-session" http://localhost:8000/api/dashboard/low-stock-items
```

### 4. Cron Job Setup (Production)
Add to server crontab:
```bash
# Run Laravel scheduler every minute
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## Belt Type Configurations

Each belt type uses different column names for stock tracking:

| Belt Type | Stock Column | Size Column | Notes |
|-----------|-------------|-------------|-------|
| Vee Belts | balance_stock | size | Standard inventory |
| Cogged Belts | balance_stock | size | Standard inventory |
| Poly Belts | ribs | size | Uses ribs for stock |
| TPU Belts | meter | width | Uses meter & width |
| Timing Belts | total_mm | size | Uses total_mm for stock |
| Special Belts | balance_stock | size | Standard inventory |

## API Endpoints

### Get Stock Alert Items
```
GET /api/dashboard/low-stock-items
```

Response:
```json
{
  "success": true,
  "data": {
    "low_stock_items": {
      "vee_belts": {
        "name": "Vee Belts",
        "items": [...],
        "count": 5
      }
    },
    "out_of_stock_items": {
      "vee_belts": {
        "name": "Vee Belts", 
        "items": [...],
        "count": 3
      }
    },
    "total_low_stock_count": 5,
    "total_out_of_stock_count": 3,
    "total_alert_count": 8,
    "generated_at": "2025-12-30 08:00:00"
  }
}
```

### Send Stock Alert Email
```
POST /api/dashboard/send-stock-alert
```

Request Body:
```json
{
  "emails": ["custom@example.com"], // Optional: override default recipients
  "force": true // Optional: send even if no alerts found
}
```

Response:
```json
{
  "success": true,
  "message": "Stock alert report sent successfully to 2 recipient(s)",
  "data": {
    "total_low_stock": 5,
    "total_out_of_stock": 3,
    "total_alerts": 8,
    "recipients": ["admin@example.com", "manager@example.com"],
    "sent_at": "2025-12-30 08:00:00"
  }
}
```

## Troubleshooting

### Common Issues

1. **No emails being sent**
   - Check mail configuration in `.env`
   - Verify `LOW_STOCK_EMAIL_RECIPIENTS` is set
   - Test with: `php artisan report:low-stock --email=test@example.com`

2. **Scheduler not running**
   - Ensure cron job is set up: `* * * * * cd /path/to/project && php artisan schedule:run`
   - Check Laravel logs for scheduler errors

3. **Items not showing as low stock**
   - Verify `reorder_level > 1` is set for the item
   - Check that `current_stock <= reorder_level`
   - Ensure item has stock > 0 (not out of stock)

### Debug Commands

```bash
# Check scheduled tasks
php artisan schedule:list

# Test email sending (logs to storage/logs/laravel.log if using log driver)
php artisan report:low-stock --email=test@example.com

# Check low stock items via API
php artisan tinker
>>> app('App\Http\Controllers\Api\DashboardController')->getLowStockItems()
```

## Future Enhancements

Potential improvements:
1. **Email Frequency Options**: Weekly, bi-weekly reports
2. **Threshold Alerts**: Multiple warning levels (yellow at 50%, red at 10%)
3. **Email Templates**: Different templates for different recipient types
4. **Dashboard Integration**: Low stock widget on main dashboard
5. **Mobile Notifications**: Push notifications for critical low stock
6. **Supplier Integration**: Auto-generate purchase orders for low stock items

## Security Considerations

- Email recipients are configured via environment variables
- No sensitive data exposed in email templates
- Command requires proper Laravel authentication context
- API endpoints protected by session middleware

## Performance Notes

- Low stock queries use indexed columns (reorder_level, stock columns)
- Email generation is lightweight (no heavy computations)
- Scheduled task runs quickly (typically < 5 seconds)
- Database queries are optimized with proper WHERE clauses