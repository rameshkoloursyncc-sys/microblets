# Email Template Customization Guide

## File Location
`resources/views/emails/low-stock-report.blade.php`

## Key Customization Areas

### 1. Company Branding
```html
<!-- Add your company logo -->
<div class="header">
    <img src="https://your-domain.com/logo.png" alt="Company Logo" style="max-height: 60px; margin-bottom: 10px;">
    <h1>Daily Stock Alert Report</h1>
    <p><strong>Report Date:</strong> {{ $reportDate }}</p>
</div>
```

### 2. Color Scheme
```css
/* Update colors in the <style> section */
.header {
    background-color: #your-brand-color; /* Change header color */
}

.summary {
    background-color: #your-secondary-color; /* Change summary background */
}

.belt-header {
    background-color: #your-accent-color; /* Change section headers */
}
```

### 3. Custom Styling Examples

#### Modern Dark Theme
```css
body {
    background-color: #1a1a1a;
    color: #ffffff;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.belt-section {
    background-color: #2d2d2d;
    border: 1px solid #444;
}
```

#### Corporate Professional
```css
body {
    font-family: 'Times New Roman', serif;
    background-color: #f5f5f5;
}

.header {
    background-color: #003366;
    color: white;
    border-bottom: 4px solid #gold;
}

.summary {
    background-color: #e8f4f8;
    border-left: 4px solid #003366;
}
```

### 4. Add Custom Sections
```html
<!-- Add after summary section -->
<div class="custom-section">
    <h3>📞 Need Help?</h3>
    <p>Contact our inventory team:</p>
    <ul>
        <li>📧 Email: inventory@yourcompany.com</li>
        <li>📱 Phone: +91-XXXX-XXXX</li>
        <li>🌐 Portal: https://inventory.yourcompany.com</li>
    </ul>
</div>
```

### 5. Responsive Design
```css
/* Mobile-friendly styles */
@media (max-width: 600px) {
    body {
        padding: 10px;
    }
    
    table {
        font-size: 12px;
    }
    
    .belt-header {
        font-size: 16px;
        padding: 10px;
    }
}
```

### 6. Data Customization

#### Add Custom Fields
In your controller (`app/Http/Controllers/Api/DashboardController.php`), add more data:
```php
return [
    'low_stock_items' => $lowStockItems,
    'out_of_stock_items' => $outOfStockItems,
    'company_name' => 'Your Company Name',
    'report_period' => 'Daily',
    'next_report_date' => now()->addDay()->format('Y-m-d'),
    // ... existing data
];
```

Then use in template:
```html
<p><strong>Company:</strong> {{ $lowStockData['company_name'] ?? 'Microbelts IMA' }}</p>
<p><strong>Next Report:</strong> {{ $lowStockData['next_report_date'] ?? 'Tomorrow' }}</p>
```

### 7. Multiple Templates

Create different templates for different purposes:

```bash
# Create new templates
cp resources/views/emails/low-stock-report.blade.php resources/views/emails/critical-stock-alert.blade.php
cp resources/views/emails/low-stock-report.blade.php resources/views/emails/weekly-stock-summary.blade.php
```

Update Mail class to use different templates:
```php
// In app/Mail/LowStockReport.php
public function content(): Content
{
    $template = $this->alertLevel === 'critical' 
        ? 'emails.critical-stock-alert' 
        : 'emails.low-stock-report';
        
    return new Content(view: $template);
}
```

## Testing Your Changes

After making changes, test with:
```bash
# Test email template
php artisan test:email your-email@gmail.com

# Test stock alert
php artisan report:low-stock --email=your-email@gmail.com
```

## Advanced Customizations

### 1. Add Charts/Graphs
Use Chart.js or similar libraries to add visual data representation.

### 2. PDF Attachments
Generate PDF reports and attach to emails:
```php
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('reports.stock-summary', $data);
$message->attach($pdf->output(), 'stock-report.pdf', [
    'mime' => 'application/pdf',
]);
```

### 3. Dynamic Content
Add conditional content based on stock levels:
```html
@if($lowStockData['total_alert_count'] > 50)
    <div class="critical-alert">
        🚨 CRITICAL: Over 50 items need attention!
    </div>
@endif
```

## Best Practices

1. **Keep it Simple**: Email clients have limited CSS support
2. **Test Across Clients**: Gmail, Outlook, Apple Mail render differently
3. **Mobile First**: Most emails are read on mobile devices
4. **Use Inline CSS**: Some email clients strip `<style>` tags
5. **Alt Text**: Always include alt text for images
6. **Fallback Colors**: Use web-safe colors as fallbacks