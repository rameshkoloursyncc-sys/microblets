#!/bin/bash

# Fix Poly Belts Production Issues
# This script fixes the poly belt size field, formulas, and rate calculations

echo "🔧 Fixing Poly Belts Production Issues..."

# 1. Run the migration to change size field to decimal
echo "📊 Running migration to fix size field..."
php artisan migrate --force

# 2. Update rate formulas to use 'size' instead of 'ribs'
echo "📝 Updating rate formulas..."
php artisan tinker --execute="
DB::table('rate_formulas')
    ->where('category', 'poly_belts')
    ->update([
        'formula' => DB::raw(\"REPLACE(formula, 'ribs/', 'size/')\")
    ]);
echo 'Rate formulas updated successfully' . PHP_EOL;
"

# 3. Recalculate all poly belt rates based on new formulas
echo "🔄 Recalculating all poly belt rates..."
php artisan tinker --execute="
\$updated = 0;
\$polyBelts = App\Models\PolyBelt::all();
foreach (\$polyBelts as \$belt) {
    \$oldRate = \$belt->rate_per_rib;
    \$belt->rate_per_rib = \$belt->calculateRatePerRib();
    \$belt->value = \$belt->ribs * \$belt->rate_per_rib;
    \$belt->save();
    \$updated++;
}
echo 'Recalculated rates for ' . \$updated . ' poly belts' . PHP_EOL;
"

# 4. Clear application cache
echo "🧹 Clearing cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✅ Poly belts production fix completed!"
echo ""
echo "📋 Summary of changes:"
echo "   - Size field changed from string to decimal(10,2)"
echo "   - Rate formulas updated to use 'size' instead of 'ribs'"
echo "   - All existing poly belt rates recalculated"
echo "   - Application cache cleared"
echo ""
echo "🎯 Expected behavior:"
echo "   - Size changes will automatically recalculate rates"
echo "   - No more 'size must be string' validation errors"
echo "   - No more NaN values in frontend"
echo "   - Rate formula: rate_per_rib = (size ÷ 25.4) × multiplier"