#!/bin/bash

echo "📐 Updating Timing Belt Formulas on Production"
echo "============================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

echo "📦 Creating backup of current formulas..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    \$data = DB::table('rate_formulas')->where('category', 'timing_belts')->get();
    file_put_contents('timing_belt_formulas_backup_' . date('Y_m_d_H_i_s') . '.json', json_encode(\$data, JSON_PRETTY_PRINT));
    echo 'Backup created: timing_belt_formulas_backup_' . date('Y_m_d_H_i_s') . '.json' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Backup failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

echo ""
echo "🔧 Running formula update..."
php update_timing_belt_formulas_production.php

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Timing belt formulas updated successfully!"
    echo ""
    echo "📊 Formula Details:"
    echo "  📐 Formula: value = (size × type × 450 × multiplier) + (size × total_mm × multiplier)"
    echo "  🔢 L sections (L, NEOPRENE-L): 0.0045"
    echo "  🔢 All other sections: 0.0094"
    echo "  🔢 Fixed constant: 450"
    echo ""
    echo "✅ You can now test the Settings page formula updates!"
else
    echo "❌ Formula update failed!"
    exit 1
fi